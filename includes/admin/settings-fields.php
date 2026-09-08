<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── General Settings ──────────────────────────────────────────────────────────
function metcpt_render_general_settings() {
    ?>

    <?php
    // ── Developer Tools ───────────────────────────────────────────────────────
    ?>
    <div class="metcpt-section-title">Developer Tools</div>

    <div class="metcpt-field-row">
        <div class="metcpt-field-label">
            Enable Dummy Data Button
            <span class="metcpt-field-hint">
                Shows seed and clear buttons for local testing only.
                Never enable on a live production site.
            </span>
        </div>
        <div class="metcpt-toggle-wrap">
            <label class="metcpt-toggle">
                <input type="checkbox"
                       name="metcpt_enable_dummy_data"
                       value="1"
                       <?php checked( 1, get_option( 'metcpt_enable_dummy_data', 0 ) ); ?> />
                <span class="metcpt-toggle-slider"></span>
            </label>
            <span class="metcpt-toggle-label">
                <?php echo get_option( 'metcpt_enable_dummy_data', 0 ) ? 'Enabled' : 'Disabled'; ?>
            </span>
        </div>
    </div>

    <?php if ( get_option( 'metcpt_enable_dummy_data', 0 ) ) : ?>
    <div class="metcpt-field-row">
        <div class="metcpt-field-label">
            Dummy Data Actions
            <span class="metcpt-field-hint">
                Seeds 10 events, 10 tenders, and 10 careers.<br>
                Clear removes only dummy posts — real content is never affected.
            </span>
        </div>
        <div class="mcpt-dummy-actions">
            <button type="button"
                    class="button button-primary mcpt-seed-btn"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'metcpt_dummy_data' ) ); ?>">
                Seed Dummy Data
            </button>
            <button type="button"
                    class="button mcpt-clear-btn"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'metcpt_dummy_data' ) ); ?>">
                Clear Dummy Data
            </button>
            <button type="button"
                    class="button mcpt-seed-errors-btn"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'metcpt_dummy_data' ) ); ?>">
                Seed Dummy Errors
            </button>
            <span class="mcpt-dummy-status" id="mcpt-dummy-status"></span>
        </div>
    </div>

    <script>
    (function() {
        var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
        var status  = document.getElementById('mcpt-dummy-status');

        function runAction(action, nonce, btn, msg) {
            btn.disabled    = true;
            btn.textContent = 'Please wait…';
            status.textContent = '';
            status.className   = 'mcpt-dummy-status';

            var body = new FormData();
            body.append('action', action);
            body.append('nonce',  nonce);

            fetch(ajaxUrl, { method: 'POST', body: body })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled    = false;
                    btn.textContent = msg;
                    if (data.success) {
                        status.textContent = '✓ ' + data.data.message;
                        status.className   = 'mcpt-dummy-status mcpt-dummy-ok';
                    } else {
                        status.textContent = '✗ ' + (data.data ? data.data.message : 'Error.');
                        status.className   = 'mcpt-dummy-status mcpt-dummy-err';
                    }
                })
                .catch(function() {
                    btn.disabled    = false;
                    btn.textContent = msg;
                    status.textContent = '✗ Network error.';
                    status.className   = 'mcpt-dummy-status mcpt-dummy-err';
                });
        }

        var seedBtn = document.querySelector('.mcpt-seed-btn');
        if (seedBtn) {
            seedBtn.addEventListener('click', function() {
                if (!confirm('Seed 10 events, 10 tenders and 10 careers?')) return;
                runAction('metcpt_seed_dummy_data', seedBtn.getAttribute('data-nonce'), seedBtn, 'Seed Dummy Data');
            });
        }

        var clearBtn = document.querySelector('.mcpt-clear-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (!confirm('Clear all dummy data? This cannot be undone.')) return;
                runAction('metcpt_clear_dummy_data', clearBtn.getAttribute('data-nonce'), clearBtn, 'Clear Dummy Data');
            });
        }

        var seedErrBtn = document.querySelector('.mcpt-seed-errors-btn');
        if (seedErrBtn) {
            seedErrBtn.addEventListener('click', function() {
                if (!confirm('Seed 12 dummy error log entries?')) return;
                runAction('metcpt_seed_dummy_errors', seedErrBtn.getAttribute('data-nonce'), seedErrBtn, 'Seed Dummy Errors');
            });
        }
    })();
    </script>
    <?php endif; ?>

    <?php
}


// ── Events Settings ───────────────────────────────────────────────────────────
function metcpt_render_events_settings() {
    $archive_url      = get_option( 'metcpt_events_archive_url',   '/events' );
    $vip_roles_raw    = get_option( 'metcpt_vip_roles',            "Guest of Honour\nTazkirah\nNotable Attendee\nSpeaker\nMC" );
    $summary_position = get_option( 'metcpt_event_summary_position_default', 'top' );
    ?>

    <div class="metcpt-section-title">Display</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_events_archive_url">
            Events Archive URL
            <span class="metcpt-field-hint">Used in the Back to Events link on single event pages</span>
        </label>
        <input type="text" id="metcpt_events_archive_url"
               name="metcpt_events_archive_url"
               value="<?php echo esc_attr( $archive_url ); ?>"
               placeholder="e.g. /events or https://yoursite.com/events" />
    </div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_event_summary_position_default">
            Event Summary Position
            <span class="metcpt-field-hint">
                Where the date, time, venue and organiser summary appears on an
                event post by default. Any single post can override this in its
                own Event Details box.
            </span>
        </label>
        <select id="metcpt_event_summary_position_default" name="metcpt_event_summary_position_default">
            <option value="top"    <?php selected( $summary_position, 'top' ); ?>>Top of the post</option>
            <option value="bottom" <?php selected( $summary_position, 'bottom' ); ?>>Bottom of the post</option>
            <option value="both"   <?php selected( $summary_position, 'both' ); ?>>Both top and bottom</option>
            <option value="none"   <?php selected( $summary_position, 'none' ); ?>>Do not show</option>
        </select>
    </div>

    <div class="metcpt-section-title">VIP Roles</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_vip_roles">
            VIP Role Options
            <span class="metcpt-field-hint">One role per line. These appear in the VIP role dropdown when editing an event.</span>
        </label>
        <textarea id="metcpt_vip_roles"
                  name="metcpt_vip_roles"
                  rows="7"
                  placeholder="Guest of Honour&#10;Tazkirah&#10;Notable Attendee&#10;Speaker&#10;MC"><?php echo esc_textarea( $vip_roles_raw ); ?></textarea>
    </div>

    <?php metcpt_render_events_migration_panel(); ?>

    <?php
}


// ── Events → Posts migration panel ────────────────────────────────────────────
function metcpt_render_events_migration_panel() {
    $stats = metcpt_events_migration_stats();
    ?>

    <div class="metcpt-section-title">Migrate Legacy Events to Posts</div>

    <div class="metcpt-field-row">
        <div class="metcpt-field-label">
            Remaining metcpt_event entries
            <span class="metcpt-field-hint">
                Converts each into a native post with the same content, meta,
                featured image, comments and publish date. Old
                <code>/event/&lt;slug&gt;/</code> URLs redirect to the new post
                afterwards. This does not touch tenders, careers or companies.
            </span>
        </div>
        <div class="mcpt-dummy-actions">
            <strong id="mcpt-migrate-remaining"><?php echo (int) $stats['remaining']; ?></strong>
            <span>event<?php echo 1 === (int) $stats['remaining'] ? '' : 's'; ?> not yet migrated</span>
        </div>
    </div>

    <?php if ( $stats['remaining'] > 0 ) : ?>
    <div class="metcpt-field-row">
        <div class="metcpt-field-label">
            Run Migration
            <span class="metcpt-field-hint">
                Always preview first. Take a database backup before running this
                on a live site.
            </span>
        </div>
        <div class="mcpt-dummy-actions">
            <button type="button"
                    class="button mcpt-migrate-preview-btn"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'metcpt_migrate_events' ) ); ?>">
                Preview (Dry Run)
            </button>
            <button type="button"
                    class="button button-primary mcpt-migrate-run-btn"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'metcpt_migrate_events' ) ); ?>">
                Run Migration
            </button>
            <span class="mcpt-dummy-status" id="mcpt-migrate-status"></span>
        </div>
    </div>

    <div class="metcpt-field-row" id="mcpt-migrate-preview-row" style="display:none;">
        <div class="metcpt-field-label">Preview</div>
        <div id="mcpt-migrate-preview-list" style="font-size:12px; max-height:260px; overflow-y:auto;"></div>
    </div>
    <?php endif; ?>

    <div class="metcpt-field-row">
        <div class="metcpt-field-label">
            Hide Legacy Events Menu
            <span class="metcpt-field-hint">
                Once every event is migrated, hide the old Events menu item in
                wp-admin. The post type stays registered, so old links still
                redirect, and you can re-enable this later if needed.
            </span>
        </div>
        <div class="metcpt-toggle-wrap">
            <label class="metcpt-toggle">
                <input type="checkbox"
                       name="metcpt_hide_events_cpt_menu"
                       value="1"
                       <?php checked( 1, get_option( 'metcpt_hide_events_cpt_menu', 0 ) ); ?> />
                <span class="metcpt-toggle-slider"></span>
            </label>
            <span class="metcpt-toggle-label">
                <?php echo get_option( 'metcpt_hide_events_cpt_menu', 0 ) ? 'Hidden' : 'Visible'; ?>
            </span>
        </div>
    </div>

    <script>
    (function() {
        var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
        var status  = document.getElementById( 'mcpt-migrate-status' );

        function runAction( action, nonce, extra ) {
            var body = new FormData();
            body.append( 'action', action );
            body.append( 'nonce', nonce );
            return fetch( ajaxUrl, { method: 'POST', body: body } ).then( function( r ) { return r.json(); } );
        }

        function escapeHtml( str ) {
            var div = document.createElement( 'div' );
            div.textContent = str;
            return div.innerHTML;
        }

        var previewBtn = document.querySelector( '.mcpt-migrate-preview-btn' );
        if ( previewBtn ) {
            previewBtn.addEventListener( 'click', function() {
                previewBtn.disabled = true;
                if ( status ) { status.textContent = 'Loading preview…'; status.className = 'mcpt-dummy-status'; }

                runAction( 'metcpt_migrate_events_preview', previewBtn.getAttribute( 'data-nonce' ) )
                    .then( function( data ) {
                        previewBtn.disabled = false;
                        var row  = document.getElementById( 'mcpt-migrate-preview-row' );
                        var list = document.getElementById( 'mcpt-migrate-preview-list' );
                        if ( ! data.success ) {
                            if ( status ) {
                                status.textContent = '✗ ' + ( data.data ? data.data.message : 'Error.' );
                                status.className   = 'mcpt-dummy-status mcpt-dummy-err';
                            }
                            return;
                        }
                        if ( status ) { status.textContent = ''; }
                        if ( row && list ) {
                            row.style.display = 'block';
                            if ( ! data.data.items.length ) {
                                list.innerHTML = '<p>Nothing to migrate.</p>';
                            } else {
                                var html = '<ul style="margin:0; padding-left:18px;">';
                                data.data.items.forEach( function( item ) {
                                    html += '<li>#' + item.id + ' — ' + escapeHtml( item.title ) + ' (' + item.status + ')</li>';
                                } );
                                html += '</ul>';
                                list.innerHTML = html;
                            }
                        }
                    } )
                    .catch( function() {
                        previewBtn.disabled = false;
                        if ( status ) {
                            status.textContent = '✗ Network error.';
                            status.className   = 'mcpt-dummy-status mcpt-dummy-err';
                        }
                    } );
            } );
        }

        var runBtn = document.querySelector( '.mcpt-migrate-run-btn' );
        if ( runBtn ) {
            runBtn.addEventListener( 'click', function() {
                if ( ! confirm( 'Migrate every remaining metcpt_event entry into a native post? Preview first if you have not already. Take a database backup before running this on a live site.' ) ) {
                    return;
                }
                runBtn.disabled = true;
                if ( status ) { status.textContent = 'Migrating…'; status.className = 'mcpt-dummy-status'; }

                runAction( 'metcpt_migrate_events_run', runBtn.getAttribute( 'data-nonce' ) )
                    .then( function( data ) {
                        runBtn.disabled = false;
                        if ( ! data.success ) {
                            if ( status ) {
                                status.textContent = '✗ ' + ( data.data ? data.data.message : 'Error.' );
                                status.className   = 'mcpt-dummy-status mcpt-dummy-err';
                            }
                            return;
                        }
                        if ( status ) {
                            status.textContent = '✓ ' + data.data.message;
                            status.className   = 'mcpt-dummy-status mcpt-dummy-ok';
                        }
                        var remaining = document.getElementById( 'mcpt-migrate-remaining' );
                        if ( remaining ) {
                            remaining.textContent = Math.max( 0, parseInt( remaining.textContent, 10 ) - data.data.migrated );
                        }
                    } )
                    .catch( function() {
                        runBtn.disabled = false;
                        if ( status ) {
                            status.textContent = '✗ Network error.';
                            status.className   = 'mcpt-dummy-status mcpt-dummy-err';
                        }
                    } );
            } );
        }
    })();
    </script>

    <?php
}


// ── Tenders Settings ──────────────────────────────────────────────────────────
function metcpt_render_tenders_settings() {
    $tenders_url         = get_option( 'metcpt_tenders_page_url',           '/tenders' );
    $closing_soon_days   = get_option( 'metcpt_closing_soon_days',           7 );
    $categories_raw      = get_option( 'metcpt_tender_categories',           "Goods\nServices\nConstruction\nConsultancy\nOthers" );
    ?>

    <div class="metcpt-section-title">Display</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_tenders_page_url">
            Tenders Page URL
            <span class="metcpt-field-hint">Used in the View All Tenders link in the preview strip</span>
        </label>
        <input type="text" id="metcpt_tenders_page_url"
               name="metcpt_tenders_page_url"
               value="<?php echo esc_attr( $tenders_url ); ?>"
               placeholder="e.g. /tender or https://yoursite.com/tender" />
    </div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_closing_soon_days">
            Closing Soon Threshold
            <span class="metcpt-field-hint">How many days before close date the Closing Soon badge appears</span>
        </label>
        <div>
            <input type="number" id="metcpt_closing_soon_days"
                   name="metcpt_closing_soon_days"
                   value="<?php echo esc_attr( $closing_soon_days ); ?>"
                   min="1" max="90"
                   class="metcpt-field-narrow" />
            <p class="metcpt-field-note">days before closing date</p>
        </div>
    </div>

    <div class="metcpt-section-title">Tender Categories</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_tender_categories">
            Category Options
            <span class="metcpt-field-hint">One category per line. These appear in the Category dropdown when editing a tender.</span>
        </label>
        <textarea id="metcpt_tender_categories"
                  name="metcpt_tender_categories"
                  rows="7"
                  placeholder="Goods&#10;Services&#10;Construction&#10;Consultancy&#10;Others"><?php echo esc_textarea( $categories_raw ); ?></textarea>
    </div>

    <div class="metcpt-section-title">Email Notifications</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_notify_email">
            Notification Recipient
            <span class="metcpt-field-hint">
                Who receives the closing soon email alert.
                Defaults to your WordPress admin email.
            </span>
        </label>
        <input type="text"
               id="metcpt_notify_email"
               name="metcpt_notify_email"
               value="<?php echo esc_attr( get_option( 'metcpt_notify_email', get_option( 'admin_email' ) ) ); ?>"
               placeholder="e.g. tender@iiumholdings.com.my" />
    </div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label">
            Test Email Notification
            <span class="metcpt-field-hint">
                Manually trigger the cron task right now to test your email setup.
                Only tenders within the closing threshold and not yet notified will trigger an email.
            </span>
        </label>
        <div>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=metcpt-settings&tab=tenders&metcpt_run_cron=1' ), 'metcpt_run_cron' ) ); ?>"
               class="button button-secondary">
                Run Cron Now
            </a>
            <p class="metcpt-field-note">
                This simulates what happens automatically every day at midnight.
            </p>
        </div>
    </div>

    <div class="metcpt-section-title">Display Template</div>

    <?php
    $selected_template    = get_option( 'metcpt_tenders_template',         'a' );
    $tb_label             = get_option( 'metcpt_tenders_b_label',          'Tender Opportunities' );
    $tb_headline          = get_option( 'metcpt_tenders_b_headline',       'Open procurement' );
    $tb_headline_italic   = get_option( 'metcpt_tenders_b_headline_italic','across the group.' );
    $tb_all_text          = get_option( 'metcpt_tenders_b_all_text',       'All tenders' );
    $tb_view_all_text     = get_option( 'metcpt_tenders_b_view_all_text',  'View all' );
    ?>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label">
            Active Template
            <span class="metcpt-field-hint">
                Applies globally to both [tenders_list] and [tenders_preview]
            </span>
        </label>
        <div class="metcpt-radio-stack">

            <label class="metcpt-radio-card">
                <input type="radio"
                       name="metcpt_tenders_template"
                       value="a"
                       <?php checked( $selected_template, 'a' ); ?>
                       onchange="document.getElementById('mcpt-tb-fields').style.display='none';" />
                <div>
                    <div class="metcpt-radio-title">Template A — Table Layout</div>
                    <div class="metcpt-radio-desc">
                        Searchable table with filter tabs, status badges, and document download button.
                        Best for dedicated tender listing pages.
                    </div>
                    <div class="metcpt-mock metcpt-mock-a">
                        <div class="metcpt-mock-a-head">
                            <div class="metcpt-mock-bar metcpt-mock-bar-gold"></div>
                            <div class="metcpt-mock-bar metcpt-mock-bar-a"></div>
                            <div class="metcpt-mock-bar metcpt-mock-bar-b"></div>
                        </div>
                        <div class="metcpt-mock-a-rows">
                            <?php for ( $i = 0; $i < 8; $i++ ) : ?>
                            <div class="metcpt-mock-cell <?php echo $i < 4 ? '' : 'metcpt-mock-cell-strong'; ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </label>

            <label class="metcpt-radio-card">
                <input type="radio"
                       name="metcpt_tenders_template"
                       value="b"
                       <?php checked( $selected_template, 'b' ); ?>
                       onchange="document.getElementById('mcpt-tb-fields').style.display='block';" />
                <div style="flex:1;">
                    <div class="metcpt-radio-title">Template B — Editorial Layout</div>
                    <div class="metcpt-radio-desc">
                        Cream background with large editorial headline, tabbed Open/Closed filter,
                        and clean row listing. Best for landing pages and homepages.
                    </div>
                    <div class="metcpt-mock metcpt-mock-b">
                        <div class="metcpt-mock-b-label">— Tender Opportunities</div>
                        <div class="metcpt-mock-b-head">Open procurement <em>across the group.</em></div>
                        <div class="metcpt-mock-b-panel">
                            <div class="metcpt-mock-b-tabs">
                                <div class="metcpt-mock-b-tab-on">Open Tenders</div>
                                <div class="metcpt-mock-b-tab-off">Closed</div>
                            </div>
                            <div class="metcpt-mock-b-row">
                                <span>Tender title example...</span>
                                <span class="metcpt-mock-b-badge">OPEN</span>
                            </div>
                        </div>
                    </div>
                </div>
            </label>

        </div>
    </div>

    <?php /* ── Template B header fields ── */ ?>
    <div id="mcpt-tb-fields" style="display:<?php echo $selected_template === 'b' ? 'block' : 'none'; ?>;">

        <div class="metcpt-section-title">Template B — Header Text</div>

        <div class="metcpt-field-row">
            <label class="metcpt-field-label" for="metcpt_tenders_b_label">
                Section Label
                <span class="metcpt-field-hint">Small uppercase text above headline. e.g. Tender Opportunities</span>
            </label>
            <input type="text"
                   id="metcpt_tenders_b_label"
                   name="metcpt_tenders_b_label"
                   value="<?php echo esc_attr( $tb_label ); ?>"
                   placeholder="e.g. Tender Opportunities" />
        </div>

        <div class="metcpt-field-row">
            <label class="metcpt-field-label" for="metcpt_tenders_b_headline">
                Headline — Regular Portion
                <span class="metcpt-field-hint">e.g. Open procurement</span>
            </label>
            <input type="text"
                   id="metcpt_tenders_b_headline"
                   name="metcpt_tenders_b_headline"
                   value="<?php echo esc_attr( $tb_headline ); ?>"
                   placeholder="e.g. Open procurement" />
        </div>

        <div class="metcpt-field-row">
            <label class="metcpt-field-label" for="metcpt_tenders_b_headline_italic">
                Headline — Italic Portion
                <span class="metcpt-field-hint">e.g. across the group.</span>
            </label>
            <input type="text"
                   id="metcpt_tenders_b_headline_italic"
                   name="metcpt_tenders_b_headline_italic"
                   value="<?php echo esc_attr( $tb_headline_italic ); ?>"
                   placeholder="e.g. across the group." />
        </div>

        <div class="metcpt-field-row">
            <label class="metcpt-field-label" for="metcpt_tenders_b_all_text">
                Top-Right Link Text
                <span class="metcpt-field-hint">Text for the link top-right of the header. e.g. All tenders</span>
            </label>
            <input type="text"
                   id="metcpt_tenders_b_all_text"
                   name="metcpt_tenders_b_all_text"
                   value="<?php echo esc_attr( $tb_all_text ); ?>"
                   placeholder="e.g. All tenders" />
        </div>

        <div class="metcpt-field-row">
            <label class="metcpt-field-label" for="metcpt_tenders_b_view_all_text">
                Tab Bar Link Text
                <span class="metcpt-field-hint">Text for the View all link inside the tab bar. e.g. View all</span>
            </label>
            <input type="text"
                   id="metcpt_tenders_b_view_all_text"
                   name="metcpt_tenders_b_view_all_text"
                   value="<?php echo esc_attr( $tb_view_all_text ); ?>"
                   placeholder="e.g. View all" />
        </div>

        <div class="metcpt-section-title">Template B — Live Preview</div>

        <div class="metcpt-preview-box">
            <p class="metcpt-preview-label">
                — <span id="mcpt-tb-preview-label"><?php echo esc_html( $tb_label ); ?></span>
            </p>
            <p class="metcpt-preview-headline">
                <span id="mcpt-tb-preview-headline"><?php echo esc_html( $tb_headline ); ?></span>
                <em id="mcpt-tb-preview-italic"><?php echo esc_html( $tb_headline_italic ); ?></em>
            </p>
            <p class="metcpt-preview-links">
                <span id="mcpt-tb-preview-all"><?php echo esc_html( $tb_all_text ); ?></span> &rarr;
                &nbsp;&nbsp;
                <span id="mcpt-tb-preview-viewall" class="muted"><?php echo esc_html( $tb_view_all_text ); ?> &rarr;</span>
            </p>
        </div>

    </div>

    <script>
    (function() {
        var tbFields = {
            'metcpt_tenders_b_label':          'mcpt-tb-preview-label',
            'metcpt_tenders_b_headline':       'mcpt-tb-preview-headline',
            'metcpt_tenders_b_headline_italic':'mcpt-tb-preview-italic',
            'metcpt_tenders_b_all_text':       'mcpt-tb-preview-all',
            'metcpt_tenders_b_view_all_text':  'mcpt-tb-preview-viewall',
        };
        Object.keys(tbFields).forEach(function(fieldId) {
            var input   = document.getElementById(fieldId);
            var preview = document.getElementById(tbFields[fieldId]);
            if (input && preview) {
                input.addEventListener('input', function() {
                    preview.textContent = this.value;
                });
            }
        });
    })();
    </script>

    <?php
}

// ── Careers Settings ──────────────────────────────────────────────────────────
function metcpt_render_careers_settings() {
    $departments  = get_option( 'metcpt_career_departments', "Finance\nHuman Resource\nICT\nOperations\nProcurement\nLegal\nMarketing\nAdministration" );
    $careers_url  = get_option( 'metcpt_careers_page_url',   '/careers' );
    ?>

    <div class="metcpt-section-title">Display</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_careers_page_url">
            Careers Page URL
            <span class="metcpt-field-hint">
                Used in the View All Positions link in the careers preview strip
            </span>
        </label>
        <input type="text"
               id="metcpt_careers_page_url"
               name="metcpt_careers_page_url"
               value="<?php echo esc_attr( $careers_url ); ?>"
               placeholder="e.g. /careers or https://yoursite.com/careers" />
    </div>

    <div class="metcpt-section-title">Department Options</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_career_departments">
            Department List
            <span class="metcpt-field-hint">
                One department per line. These appear in the Department dropdown
                when editing a job vacancy post.
            </span>
        </label>
        <textarea id="metcpt_career_departments"
                  name="metcpt_career_departments"
                  rows="10"
                  placeholder="Finance&#10;Human Resource&#10;ICT&#10;Operations&#10;Procurement"><?php echo esc_textarea( $departments ); ?></textarea>
    </div>

    <?php
}
// ── Posts Templates Settings ──────────────────────────────────────────────────
function metcpt_render_posts_settings() {
    $label          = get_option( 'metcpt_news_label',          'Impact & Activities' );
    $headline       = get_option( 'metcpt_news_headline',       'News, milestones, and' );
    $headline_italic= get_option( 'metcpt_news_headline_italic','community work.' );
    $view_all_text  = get_option( 'metcpt_news_view_all_text',  'View newsroom' );
    $view_all_url   = get_option( 'metcpt_news_view_all_url',   '/newsroom' );
    $category       = get_option( 'metcpt_news_category',       '' );
    ?>

    <div class="metcpt-section-title">News Grid — Section Header</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_news_label">
            Section Label
            <span class="metcpt-field-hint">
                Small uppercase text above the headline.
                e.g. Impact &amp; Activities
            </span>
        </label>
        <input type="text"
               id="metcpt_news_label"
               name="metcpt_news_label"
               value="<?php echo esc_attr( $label ); ?>"
               placeholder="e.g. Impact &amp; Activities" />
    </div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_news_headline">
            Headline — Regular Portion
            <span class="metcpt-field-hint">
                The normal-weight part of the headline.
                e.g. News, milestones, and
            </span>
        </label>
        <input type="text"
               id="metcpt_news_headline"
               name="metcpt_news_headline"
               value="<?php echo esc_attr( $headline ); ?>"
               placeholder="e.g. News, milestones, and" />
    </div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_news_headline_italic">
            Headline — Italic Portion
            <span class="metcpt-field-hint">
                The italic part of the headline — appears after the regular portion.
                e.g. community work.
            </span>
        </label>
        <input type="text"
               id="metcpt_news_headline_italic"
               name="metcpt_news_headline_italic"
               value="<?php echo esc_attr( $headline_italic ); ?>"
               placeholder="e.g. community work." />
    </div>

    <div class="metcpt-section-title">News Grid — Link</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_news_view_all_text">
            View All Link Text
            <span class="metcpt-field-hint">
                Text shown on the top-right link. e.g. View newsroom
            </span>
        </label>
        <input type="text"
               id="metcpt_news_view_all_text"
               name="metcpt_news_view_all_text"
               value="<?php echo esc_attr( $view_all_text ); ?>"
               placeholder="e.g. View newsroom" />
    </div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_news_view_all_url">
            View All Link URL
            <span class="metcpt-field-hint">
                Where the link points to. e.g. /newsroom
            </span>
        </label>
        <input type="text"
               id="metcpt_news_view_all_url"
               name="metcpt_news_view_all_url"
               value="<?php echo esc_attr( $view_all_url ); ?>"
               placeholder="e.g. /newsroom or https://yoursite.com/newsroom" />
    </div>

    <div class="metcpt-section-title">News Grid — Default Content</div>

    <div class="metcpt-field-row">
        <label class="metcpt-field-label" for="metcpt_news_category">
            Default Category Slug
            <span class="metcpt-field-hint">
                Optional. If set, the [news_grid] shortcode will filter by this
                category by default. Leave blank to show all recent posts.
                e.g. csr or news
            </span>
        </label>
        <input type="text"
               id="metcpt_news_category"
               name="metcpt_news_category"
               value="<?php echo esc_attr( $category ); ?>"
               placeholder="e.g. csr or leave blank for all posts" />
    </div>

    <div class="metcpt-section-title">Available Shortcodes — Reference</div>

    <div class="metcpt-table-scroll">
        <table class="metcpt-shortcode-table">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid]</td>
                    <td class="metcpt-shortcode-desc">Shows 4 most recent posts using settings defaults</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid category="csr"]</td>
                    <td class="metcpt-shortcode-desc">Filter by category slug — replace <em>csr</em> with your slug</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid posts_per_page="6"]</td>
                    <td class="metcpt-shortcode-desc">Show more posts — minimum 4, featured is always first</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid show_excerpt="no"]</td>
                    <td class="metcpt-shortcode-desc">Hide the excerpt on the featured post</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid order="ASC"]</td>
                    <td class="metcpt-shortcode-desc">Oldest posts first — default is DESC (newest first)</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid orderby="title"]</td>
                    <td class="metcpt-shortcode-desc">Sort by title — also accepts: date, modified, rand</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid label="Our Stories"]</td>
                    <td class="metcpt-shortcode-desc">Override the section label for this instance only</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid headline="Latest from" headline_italic="our team."]</td>
                    <td class="metcpt-shortcode-desc">Override headline text for this instance only</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid view_all_text="Read all" view_all_url="/news"]</td>
                    <td class="metcpt-shortcode-desc">Override the view all link text and URL</td>
                </tr>
                <tr>
                    <td class="metcpt-shortcode-code">[news_grid category="csr" show_excerpt="no" order="DESC"]</td>
                    <td class="metcpt-shortcode-desc">Combine multiple parameters in one shortcode</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="metcpt-tip">
        <p>
            <strong>Tip:</strong> Parameters set in the shortcode always override the defaults above.
            Use MetCPT Settings to set your site-wide defaults, and use shortcode parameters
            when you need a one-off variation on a specific page.
        </p>
    </div>

    <?php
}