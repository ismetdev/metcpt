<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bulk Posts Importer — admin screen.
 *
 * A CSV of ready-written posts becomes native WordPress posts in one run:
 * title, body, excerpt, slug, author, category (created if missing), publish
 * date, comment status, featured image with alt text, and the three Yoast
 * SEO fields. Built for the News module's backlog of subsidiary-sourced
 * content, but works on any batch of standard posts. See
 * PLAN/PLAN-bulk-posts-importer.md and PLAN/PRD-bulk-wordpress-posts-importer.md
 * (not shipped in the release zip).
 *
 * Own submenu page rather than a Settings tab: every Settings tab renders
 * inside one <form action="options.php">, and a file upload control does not
 * belong in that form.
 *
 * @package MetCPT
 * @subpackage Admin
 */

// ── Register submenu ─────────────────────────────────────────────────────────
function metcpt_bulk_import_menu() {
    add_submenu_page(
        'metcpt-settings',
        'Bulk Posts Importer',
        'Bulk Posts Importer',
        'manage_options',
        'metcpt-bulk-import',
        'metcpt_bulk_import_page_html'
    );
}
add_action( 'admin_menu', 'metcpt_bulk_import_menu' );

/**
 * Whether the current admin screen is this page. Used by class-metcpt.php to
 * gate the CSS and JS enqueue, so both load only where they are needed.
 *
 * @return bool
 */
function metcpt_is_bulk_import_page() {
    return isset( $_GET['page'] ) && 'metcpt-bulk-import' === $_GET['page'];
}

/**
 * The CSV columns the importer reads, in the order shown on screen. Kept in
 * one place so the on-screen reference table, the blank template, and the
 * ChatGPT prompt cannot drift apart from each other or from
 * metcpt_bulk_import_columns() in bulk-import-csv.php.
 *
 * @return array<string,array{label:string,required:bool,note:string}>
 */
function metcpt_bulk_import_column_reference() {
    return array(
        'title'             => array( 'label' => 'Title',            'required' => true,  'note' => '' ),
        'featured_image'    => array( 'label' => 'Featured image',   'required' => false, 'note' => 'Filename of an image already in the Media Library' ),
        'image_alt'         => array( 'label' => 'Image alt text',   'required' => false, 'note' => 'Written to the image, not the post' ),
        'date'              => array( 'label' => 'Publish date',     'required' => true,  'note' => 'YYYY-MM-DD HH:MM' ),
        'excerpt'           => array( 'label' => 'Excerpt',          'required' => false, 'note' => '' ),
        'content'           => array( 'label' => 'Content',          'required' => true,  'note' => 'HTML, e.g. <p> and <a href="...">' ),
        'focus_keyphrase'   => array( 'label' => 'Focus keyphrase',  'required' => false, 'note' => 'Yoast' ),
        'seo_title'         => array( 'label' => 'SEO title',        'required' => false, 'note' => 'Yoast' ),
        'meta_description'  => array( 'label' => 'Meta description', 'required' => false, 'note' => 'Yoast' ),
        'slug'              => array( 'label' => 'Slug',             'required' => false, 'note' => 'Derived from the title if left blank' ),
        'author'            => array( 'label' => 'Author',           'required' => false, 'note' => 'Falls back to the batch default below' ),
        'category'          => array( 'label' => 'Category',         'required' => false, 'note' => 'Falls back to the batch default. Created if it does not exist' ),
        'comments'          => array( 'label' => 'Comments',         'required' => false, 'note' => '"open" or "closed", falls back to the batch default' ),
    );
}

/**
 * The prompt handed to the operator's own ChatGPT session, in Malay to match
 * how the operator already works. Kept here, not duplicated in JS, so the
 * Copy Prompt button always matches the column order above.
 *
 * @return string
 */
function metcpt_bulk_import_chatgpt_prompt() {
    return <<<PROMPT
bertindak sebagai specialist seo writer untuk website korporat.
saya akan bagi anda input berita yang perlu ditulis. kemudian sila tulis
semula ke dalam bahasa inggeris.

rules penulisan
- mestilah mematuhi standard yoast seo plugin
- mesti sediakan focus key phrase, seo title, slug, excerpt dan meta
  description (not more than 150 characters)
- no em hyphen, not verbose, clear, comprehensive
- no bombastic words at all
- simple corporate english
- menggunakan news writeup

untuk setiap berita saya akan bagi tarikh asal dan nama fail gambar,
contoh:
tarikh: 2026-04-12 09:00
gambar: met-facility.jpg

kita akan buat satu demi satu. tulis setiap berita dalam bentuk biasa
dahulu. bila saya kata "keluarkan CSV", barulah gabungkan semua berita
yang sudah ditulis ke dalam satu fail CSV.

rules output CSV
- fail CSV, encoding UTF-8, sedia untuk saya download
- baris pertama mesti header ini, ikut susunan ini, jangan ubah nama kolum:
  no,title,featured_image,image_alt,date,excerpt,content,focus_keyphrase,seo_title,meta_description,slug
- satu berita satu baris
- setiap nilai mesti dibalut dengan tanda petik dua ("). kalau ada tanda
  petik dua di dalam teks, tulis dua kali ("")
- kolum content mesti dalam HTML. guna <p> untuk perenggan, <h2> untuk
  subtajuk. jangan guna markdown
- kalau saya bagi link, letak sebagai <a href="...">. kalau saya tak bagi
  link, jangan reka link
- kolum excerpt, image_alt, focus_keyphrase, seo_title, meta_description
  dan slug mesti diisi, jangan tinggal kosong
- image_alt ambil daripada focus key phrase, tulis secara natural
- date guna format YYYY-MM-DD HH:MM
- jangan tambah kolum lain, jangan tambah ayat penerangan sebelum atau
  selepas CSV
PROMPT;
}

/**
 * The page itself. Steps 1-4 are one DOM tree; bulk-import.js shows and
 * hides them as the operator progresses. No page reload between steps.
 */
function metcpt_bulk_import_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $columns       = metcpt_bulk_import_column_reference();
    $users         = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) );
    $categories    = get_categories( array( 'hide_empty' => false ) );
    $default_category = get_option( 'metcpt_news_category', '' );
    $yoast_active  = defined( 'WPSEO_VERSION' );
    ?>
    <div class="wrap metcpt-settings-wrap metcpt-bulk-import-wrap">

        <header class="metcpt-settings-header">
            <div class="metcpt-settings-header-inner">
                <div class="metcpt-brand">
                    <span class="metcpt-brand-mark dashicons dashicons-upload"></span>
                    <div class="metcpt-brand-text">
                        <h1 class="metcpt-brand-title">Bulk Posts Importer</h1>
                        <p class="metcpt-brand-sub">Upload a CSV of ready posts. Review, then import.</p>
                    </div>
                </div>
                <div class="metcpt-header-meta">
                    <span class="metcpt-version">v<?php echo esc_html( METCPT_VERSION ); ?></span>
                </div>
            </div>
        </header>

        <div class="metcpt-settings-body metcpt-settings-body-full">
            <div class="metcpt-settings-content metcpt-settings-content-wide mcpt-bulk-import">

                <?php if ( ! $yoast_active ) : ?>
                    <div class="notice notice-warning inline">
                        <p>Yoast SEO is not active on this site. Focus keyphrase, SEO title, and meta
                        description will still be saved, but nothing will read them until Yoast is
                        installed.</p>
                    </div>
                <?php endif; ?>

                <div class="mcpt-bi-steps">
                    <span class="mcpt-bi-step is-active" data-step="1">1. Upload</span>
                    <span class="mcpt-bi-step" data-step="2">2. Preview</span>
                    <span class="mcpt-bi-step" data-step="3">3. Import</span>
                    <span class="mcpt-bi-step" data-step="4">4. Done</span>
                </div>

                <!-- ── Step 1: Upload ─────────────────────────────────────────────── -->
                <section class="mcpt-bi-panel" id="mcpt-bi-step-1">

                    <div class="metcpt-section-title">Get the CSV from ChatGPT</div>
                    <div class="metcpt-field-row">
                        <div class="metcpt-field-label">
                            Writing prompt
                            <span class="metcpt-field-hint">
                                Paste this into a ChatGPT conversation once. Feed it each post as you
                                gather it, then ask it to produce the CSV when the batch is ready.
                            </span>
                        </div>
                        <div class="mcpt-dummy-actions">
                            <button type="button" class="button" id="mcpt-bi-copy-prompt">Copy prompt</button>
                            <button type="button" class="button" id="mcpt-bi-download-template">Download blank template</button>
                            <span class="mcpt-dummy-status" id="mcpt-bi-copy-status"></span>
                        </div>
                        <textarea id="mcpt-bi-prompt-source" hidden readonly><?php echo esc_textarea( metcpt_bulk_import_chatgpt_prompt() ); ?></textarea>
                    </div>

                    <details class="mcpt-bi-columns">
                        <summary>CSV column reference</summary>
                        <table class="widefat striped">
                            <thead>
                                <tr><th>Column</th><th>Field</th><th>Required</th><th>Note</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ( $columns as $key => $col ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $key ); ?></code></td>
                                    <td><?php echo esc_html( $col['label'] ); ?></td>
                                    <td><?php echo $col['required'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo esc_html( $col['note'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="metcpt-field-hint">
                            <code>author</code>, <code>category</code> and <code>comments</code> are
                            optional per row because the batch defaults below normally cover all of
                            them.
                        </p>
                    </details>

                    <div class="metcpt-section-title">Featured images</div>
                    <div class="metcpt-field-row">
                        <div class="metcpt-field-label">Before you upload the CSV</div>
                        <div class="mcpt-bi-hint-box">
                            Upload every image for this batch into
                            <a href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>" target="_blank" rel="noopener">Media &gt; Add New</a>
                            first, in one go. The <code>featured_image</code> column must name the
                            exact file you uploaded, e.g. <code>met-facility.jpg</code>. The Preview
                            step tells you before import if a filename does not match anything in the
                            Media Library.
                        </div>
                    </div>

                    <div class="metcpt-section-title">Batch defaults</div>
                    <div class="metcpt-field-row">
                        <label class="metcpt-field-label" for="mcpt-bi-default-author">
                            Author
                            <span class="metcpt-field-hint">Used for any row that leaves its author column blank</span>
                        </label>
                        <select id="mcpt-bi-default-author">
                            <option value="">— choose —</option>
                            <?php foreach ( $users as $user ) : ?>
                                <option value="<?php echo esc_attr( $user->display_name ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="metcpt-field-row">
                        <label class="metcpt-field-label" for="mcpt-bi-default-category">
                            Category
                            <span class="metcpt-field-hint">Created automatically if it does not exist yet</span>
                        </label>
                        <input type="text" id="mcpt-bi-default-category"
                               value="<?php echo esc_attr( $default_category ); ?>"
                               list="mcpt-bi-category-list" placeholder="e.g. Activity" />
                        <datalist id="mcpt-bi-category-list">
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->name ); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="metcpt-field-row">
                        <label class="metcpt-field-label" for="mcpt-bi-default-comments">Comments</label>
                        <select id="mcpt-bi-default-comments">
                            <option value="closed">Closed</option>
                            <option value="open">Open</option>
                        </select>
                    </div>
                    <div class="metcpt-field-row">
                        <label class="metcpt-field-label" for="mcpt-bi-default-status">
                            Post status
                            <span class="metcpt-field-hint">Applies to every post in this batch</span>
                        </label>
                        <select id="mcpt-bi-default-status">
                            <option value="draft">Save as Draft</option>
                            <option value="publish">Publish now</option>
                        </select>
                    </div>

                    <div class="metcpt-section-title">CSV</div>
                    <div class="metcpt-field-row">
                        <label class="metcpt-field-label" for="mcpt-bi-file">Upload a file</label>
                        <input type="file" id="mcpt-bi-file" accept=".csv,text/csv" />
                    </div>
                    <div class="metcpt-field-row">
                        <label class="metcpt-field-label" for="mcpt-bi-paste">Or paste the CSV text</label>
                        <textarea id="mcpt-bi-paste" rows="6" placeholder="no,title,featured_image,..."></textarea>
                    </div>

                    <p class="mcpt-bi-actions">
                        <button type="button" class="button button-primary button-hero" id="mcpt-bi-parse-btn">Preview</button>
                        <span class="mcpt-dummy-status" id="mcpt-bi-parse-status"></span>
                    </p>
                </section>

                <!-- ── Step 2: Preview ────────────────────────────────────────────── -->
                <section class="mcpt-bi-panel" id="mcpt-bi-step-2" hidden>
                    <div class="metcpt-section-title">Preview</div>
                    <div id="mcpt-bi-preview-summary" class="mcpt-bi-hint-box"></div>
                    <div class="mcpt-bi-table-wrap">
                        <table class="widefat striped" id="mcpt-bi-preview-table">
                            <thead>
                                <tr><th>#</th><th>Title</th><th>Status</th><th>Details</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <p class="mcpt-bi-actions">
                        <button type="button" class="button" id="mcpt-bi-back-btn">Back</button>
                        <button type="button" class="button button-primary button-hero" id="mcpt-bi-import-btn">Import</button>
                    </p>
                </section>

                <!-- ── Steps 3-4: Import, then Done ─────────────────────────────────
                     One panel, not two. The per-row report (with each post's Edit
                     and View links) is the point of this whole screen — it must
                     stay on screen once the run finishes, not get swapped away for
                     a bare summary. The step chips still advance to "4. Done" for
                     orientation; only the summary box and action buttons at the
                     bottom appear, added below the same table. ─────────────────── -->
                <section class="mcpt-bi-panel" id="mcpt-bi-step-3" hidden>
                    <div class="metcpt-section-title" id="mcpt-bi-run-title">Importing</div>
                    <div class="mcpt-bi-progress-wrap">
                        <div class="mcpt-bi-progress-bar"><div class="mcpt-bi-progress-fill" id="mcpt-bi-progress-fill"></div></div>
                        <span id="mcpt-bi-progress-text">0 / 0</span>
                    </div>
                    <div class="mcpt-bi-table-wrap">
                        <table class="widefat striped" id="mcpt-bi-run-table">
                            <thead>
                                <tr><th>#</th><th>Title</th><th>Result</th><th>Links</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="mcpt-bi-done-summary" class="mcpt-bi-hint-box" hidden></div>
                    <p class="mcpt-bi-actions" id="mcpt-bi-done-actions" hidden>
                        <button type="button" class="button button-primary" id="mcpt-bi-restart-btn">Import another batch</button>
                        <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="button">Go to Posts</a>
                    </p>
                </section>

            </div>
        </div>
    </div>
    <?php
}
