<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── General Settings ──────────────────────────────────────────────────────────
function haraka_render_general_settings() {
    $accent_colour     = get_option( 'haraka_accent_colour',     '#0056b3' );
    $organisation_name = get_option( 'haraka_organisation_name', 'IIUM Holdings Sdn Bhd' );
    ?>

    <div class="haraka-section-title">Branding</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label">
            Accent Colour
            <span class="haraka-field-hint">Used for borders, links and badges across all listings</span>
        </label>
        <div>
            <input type="color" id="haraka_accent_colour"
                   name="haraka_accent_colour"
                   value="<?php echo esc_attr( $accent_colour ); ?>" />
            <div class="haraka-colour-preview">
                <div class="haraka-colour-swatch" id="haraka-colour-swatch"
                     style="background: <?php echo esc_attr( $accent_colour ); ?>"></div>
                <span class="haraka-colour-value" id="haraka-colour-value">
                    <?php echo esc_html( $accent_colour ); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_organisation_name">
            Organisation Name
            <span class="haraka-field-hint">Used as the default organiser in Events</span>
        </label>
        <input type="text" id="haraka_organisation_name"
               name="haraka_organisation_name"
               value="<?php echo esc_attr( $organisation_name ); ?>"
               placeholder="e.g. IIUM Holdings Sdn Bhd" />
    </div>

    <?php

    // ── Developer Tools ───────────────────────────────────────────────────────
    ?>
    <div class="haraka-section-title">Developer Tools</div>

    <div class="haraka-field-row">
        <div class="haraka-field-label">
            Enable Dummy Data Button
            <span class="haraka-field-hint">
                Shows seed and clear buttons for local testing only.
                Never enable on a live production site.
            </span>
        </div>
        <div class="haraka-toggle-wrap">
            <label class="haraka-toggle">
                <input type="checkbox"
                       name="haraka_enable_dummy_data"
                       value="1"
                       <?php checked( 1, get_option( 'haraka_enable_dummy_data', 0 ) ); ?> />
                <span class="haraka-toggle-slider"></span>
            </label>
            <span class="haraka-toggle-label">
                <?php echo get_option( 'haraka_enable_dummy_data', 0 ) ? 'Enabled' : 'Disabled'; ?>
            </span>
        </div>
    </div>

    <?php if ( get_option( 'haraka_enable_dummy_data', 0 ) ) : ?>
    <div class="haraka-field-row">
        <div class="haraka-field-label">
            Dummy Data Actions
            <span class="haraka-field-hint">
                Seeds 10 events, 10 tenders, and 10 careers.<br>
                Clear removes only dummy posts — real content is never affected.
            </span>
        </div>
        <div class="hrk-dummy-actions">
            <button type="button"
                    class="button button-primary hrk-seed-btn"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'haraka_dummy_data' ) ); ?>">
                Seed Dummy Data
            </button>
            <button type="button"
                    class="button hrk-clear-btn"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'haraka_dummy_data' ) ); ?>">
                Clear Dummy Data
            </button>
            <span class="hrk-dummy-status" id="hrk-dummy-status"></span>
        </div>
    </div>

    <script>
    (function() {
        var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
        var status  = document.getElementById('hrk-dummy-status');

        function runAction(action, nonce, btn, msg) {
            btn.disabled    = true;
            btn.textContent = 'Please wait…';
            status.textContent = '';
            status.className   = 'hrk-dummy-status';

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
                        status.className   = 'hrk-dummy-status hrk-dummy-ok';
                    } else {
                        status.textContent = '✗ ' + (data.data ? data.data.message : 'Error.');
                        status.className   = 'hrk-dummy-status hrk-dummy-err';
                    }
                })
                .catch(function() {
                    btn.disabled    = false;
                    btn.textContent = msg;
                    status.textContent = '✗ Network error.';
                    status.className   = 'hrk-dummy-status hrk-dummy-err';
                });
        }

        var seedBtn = document.querySelector('.hrk-seed-btn');
        if (seedBtn) {
            seedBtn.addEventListener('click', function() {
                if (!confirm('Seed 10 events, 10 tenders and 10 careers?')) return;
                runAction('haraka_seed_dummy_data', seedBtn.getAttribute('data-nonce'), seedBtn, 'Seed Dummy Data');
            });
        }

        var clearBtn = document.querySelector('.hrk-clear-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (!confirm('Clear all dummy data? This cannot be undone.')) return;
                runAction('haraka_clear_dummy_data', clearBtn.getAttribute('data-nonce'), clearBtn, 'Clear Dummy Data');
            });
        }
    })();
    </script>
    <?php endif; ?>

    <?php
}


// ── Events Settings ───────────────────────────────────────────────────────────
function haraka_render_events_settings() {
    $archive_url    = get_option( 'haraka_events_archive_url',   '/events' );
    $default_order  = get_option( 'haraka_events_default_order', 'ASC' );
    $show_excerpt   = get_option( 'haraka_events_show_excerpt',  'yes' );
    $vip_roles_raw  = get_option( 'haraka_vip_roles',            "Guest of Honour\nTazkirah\nNotable Attendee\nSpeaker\nMC" );
    ?>

    <div class="haraka-section-title">Display</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_events_archive_url">
            Events Archive URL
            <span class="haraka-field-hint">Used in the Back to Events link on single event pages</span>
        </label>
        <input type="text" id="haraka_events_archive_url"
               name="haraka_events_archive_url"
               value="<?php echo esc_attr( $archive_url ); ?>"
               placeholder="e.g. /events or https://yoursite.com/events" />
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_events_default_order">
            Default Event Order
            <span class="haraka-field-hint">How events are sorted in the listing</span>
        </label>
        <select id="haraka_events_default_order" name="haraka_events_default_order">
            <option value="ASC"  <?php selected( $default_order, 'ASC' ); ?>>
                ASC — Soonest first
            </option>
            <option value="DESC" <?php selected( $default_order, 'DESC' ); ?>>
                DESC — Latest first
            </option>
        </select>
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label">
            Show Excerpt by Default
            <span class="haraka-field-hint">Show post excerpt in the events listing</span>
        </label>
        <div class="haraka-toggle-wrap">
            <label class="haraka-toggle">
                <input type="checkbox" name="haraka_events_show_excerpt"
                       value="yes" <?php checked( $show_excerpt, 'yes' ); ?> />
                <span class="haraka-toggle-slider"></span>
            </label>
            <span class="haraka-toggle-label">
                <?php echo $show_excerpt === 'yes' ? 'Enabled' : 'Disabled'; ?>
            </span>
        </div>
    </div>

    <div class="haraka-section-title">VIP Roles</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_vip_roles">
            VIP Role Options
            <span class="haraka-field-hint">One role per line. These appear in the VIP role dropdown when editing an event.</span>
        </label>
        <textarea id="haraka_vip_roles"
                  name="haraka_vip_roles"
                  rows="7"
                  placeholder="Guest of Honour&#10;Tazkirah&#10;Notable Attendee&#10;Speaker&#10;MC"><?php echo esc_textarea( $vip_roles_raw ); ?></textarea>
    </div>

    <?php
}


// ── Tenders Settings ──────────────────────────────────────────────────────────
function haraka_render_tenders_settings() {
    $tenders_url         = get_option( 'haraka_tenders_page_url',           '/tenders' );
    $closing_soon_days   = get_option( 'haraka_closing_soon_days',           7 );
    $default_address     = get_option( 'haraka_default_submission_address',  '' );
    $categories_raw      = get_option( 'haraka_tender_categories',           "Goods\nServices\nConstruction\nConsultancy\nOthers" );
    $default_fee         = get_option( 'haraka_default_tender_fee',          '' );
    ?>

    <div class="haraka-section-title">Display</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_tenders_page_url">
            Tenders Page URL
            <span class="haraka-field-hint">Used in the View All Tenders link in the preview strip</span>
        </label>
        <input type="text" id="haraka_tenders_page_url"
               name="haraka_tenders_page_url"
               value="<?php echo esc_attr( $tenders_url ); ?>"
               placeholder="e.g. /tender or https://yoursite.com/tender" />
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_closing_soon_days">
            Closing Soon Threshold
            <span class="haraka-field-hint">How many days before close date the Closing Soon badge appears</span>
        </label>
        <input type="number" id="haraka_closing_soon_days"
               name="haraka_closing_soon_days"
               value="<?php echo esc_attr( $closing_soon_days ); ?>"
               min="1" max="90"
               style="max-width: 100px;" />
        <p style="font-size:12px;color:#94a3b8;margin:4px 0 0;">days before closing date</p>
    </div>

    <div class="haraka-section-title">Tender Categories</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_tender_categories">
            Category Options
            <span class="haraka-field-hint">One category per line. These appear in the Category dropdown when editing a tender.</span>
        </label>
        <textarea id="haraka_tender_categories"
                  name="haraka_tender_categories"
                  rows="7"
                  placeholder="Goods&#10;Services&#10;Construction&#10;Consultancy&#10;Others"><?php echo esc_textarea( $categories_raw ); ?></textarea>
    </div>

    <div class="haraka-section-title">Defaults</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_default_tender_fee">
            Default Tender Fee
            <span class="haraka-field-hint">Pre-filled when creating a new tender. Leave blank for no default.</span>
        </label>
        <input type="text" id="haraka_default_tender_fee"
               name="haraka_default_tender_fee"
               value="<?php echo esc_attr( $default_fee ); ?>"
               placeholder="e.g. RM 50" />
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_default_submission_address">
            Default Submission Address
            <span class="haraka-field-hint">Pre-filled in the Submission Address field for every new tender</span>
        </label>
        <textarea id="haraka_default_submission_address"
                  name="haraka_default_submission_address"
                  rows="5"
                  placeholder="e.g. Procurement Unit, IIUM Holdings Sdn Bhd&#10;Level 3, Muhammad Abdul Rauf Building&#10;Jalan Gombak, 53100 Kuala Lumpur"><?php echo esc_textarea( $default_address ); ?></textarea>
    </div>

    <div class="haraka-section-title">Email Notifications</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_notify_email">
            Notification Recipient
            <span class="haraka-field-hint">
                Who receives the closing soon email alert.
                Defaults to your WordPress admin email.
            </span>
        </label>
        <input type="text"
               id="haraka_notify_email"
               name="haraka_notify_email"
               value="<?php echo esc_attr( get_option( 'haraka_notify_email', get_option( 'admin_email' ) ) ); ?>"
               placeholder="e.g. tender@iiumholdings.com.my" />
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label">
            Test Email Notification
            <span class="haraka-field-hint">
                Manually trigger the cron task right now to test your email setup.
                Only tenders within the closing threshold and not yet notified will trigger an email.
            </span>
        </label>
        <div>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=haraka-settings&tab=tenders&haraka_run_cron=1' ), 'haraka_run_cron' ) ); ?>"
               class="button button-secondary">
                Run Cron Now
            </a>
            <p style="font-size:11px;color:#94a3b8;margin:6px 0 0;">
                This simulates what happens automatically every day at midnight.
            </p>
        </div>
    </div>

    <div class="haraka-section-title">Display Template</div>

    <?php
    $selected_template    = get_option( 'haraka_tenders_template',         'a' );
    $tb_label             = get_option( 'haraka_tenders_b_label',          'Tender Opportunities' );
    $tb_headline          = get_option( 'haraka_tenders_b_headline',       'Open procurement' );
    $tb_headline_italic   = get_option( 'haraka_tenders_b_headline_italic','across the group.' );
    $tb_all_text          = get_option( 'haraka_tenders_b_all_text',       'All tenders' );
    $tb_view_all_text     = get_option( 'haraka_tenders_b_view_all_text',  'View all' );
    ?>

    <div class="haraka-field-row">
        <label class="haraka-field-label">
            Active Template
            <span class="haraka-field-hint">
                Applies globally to both [tenders_list] and [tenders_preview]
            </span>
        </label>
        <div style="display:flex;flex-direction:column;gap:16px;padding-top:4px;">

            <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;">
                <input type="radio"
                       name="haraka_tenders_template"
                       value="a"
                       <?php checked( $selected_template, 'a' ); ?>
                       style="margin-top:3px;width:auto;max-width:none;flex-shrink:0;"
                       onchange="document.getElementById('hrk-tb-fields').style.display='none';" />
                <div>
                    <div style="font-size:13px;font-weight:600;color:#1d2327;margin-bottom:4px;">
                        Template A — Table Layout
                    </div>
                    <div style="font-size:11px;color:#646970;line-height:1.5;">
                        Searchable table with filter tabs, status badges, and document download button.
                        Best for dedicated tender listing pages.
                    </div>
                    <div style="margin-top:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px 14px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <div style="height:6px;background:#0056b3;border-radius:2px;flex:1;"></div>
                            <div style="height:6px;background:#e2e8f0;border-radius:2px;width:40px;"></div>
                            <div style="height:6px;background:#e2e8f0;border-radius:2px;width:60px;"></div>
                        </div>
                        <div style="display:grid;grid-template-columns:80px 1fr 50px 50px;gap:8px;align-items:center;">
                            <?php for($i=0;$i<8;$i++): ?>
                            <div style="height:5px;background:<?php echo $i<4?'#f1f5f9':'#e2e8f0'; ?>;border-radius:2px;"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </label>

            <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;">
                <input type="radio"
                       name="haraka_tenders_template"
                       value="b"
                       <?php checked( $selected_template, 'b' ); ?>
                       style="margin-top:3px;width:auto;max-width:none;flex-shrink:0;"
                       onchange="document.getElementById('hrk-tb-fields').style.display='block';" />
                <div style="flex:1;">
                    <div style="font-size:13px;font-weight:600;color:#1d2327;margin-bottom:4px;">
                        Template B — Editorial Layout
                    </div>
                    <div style="font-size:11px;color:#646970;line-height:1.5;">
                        Cream background with large editorial headline, tabbed Open/Closed filter,
                        and clean row listing. Best for landing pages and homepages.
                    </div>
                    <div style="margin-top:10px;background:#f5f0e8;border:1px solid #e2d9c8;border-radius:6px;padding:12px 14px;">
                        <div style="font-size:9px;color:#7a6f60;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:4px;">— Tender Opportunities</div>
                        <div style="font-size:13px;font-weight:700;color:#1a1410;margin-bottom:8px;">Open procurement <em style="font-style:italic;font-weight:400;">across the group.</em></div>
                        <div style="background:#fff;border-radius:3px;padding:8px 10px;">
                            <div style="display:flex;gap:12px;border-bottom:1px solid #f0ebe2;padding-bottom:6px;margin-bottom:6px;">
                                <div style="font-size:9px;font-weight:600;color:#1a1410;border-bottom:1.5px solid #1a1410;padding-bottom:3px;">Open Tenders</div>
                                <div style="font-size:9px;color:#8a7d6a;">Closed</div>
                            </div>
                            <div style="font-size:9px;color:#4a3f30;display:flex;justify-content:space-between;padding:3px 0;">
                                <span>Tender title example...</span>
                                <span style="background:#dcfce7;color:#15803d;padding:1px 5px;border-radius:8px;font-weight:600;">OPEN</span>
                            </div>
                        </div>
                    </div>
                </div>
            </label>

        </div>
    </div>

    <?php /* ── Template B header fields ── */ ?>
    <div id="hrk-tb-fields" style="display:<?php echo $selected_template === 'b' ? 'block' : 'none'; ?>;">

        <div class="haraka-section-title">Template B — Header Text</div>

        <div class="haraka-field-row">
            <label class="haraka-field-label" for="haraka_tenders_b_label">
                Section Label
                <span class="haraka-field-hint">Small uppercase text above headline. e.g. Tender Opportunities</span>
            </label>
            <input type="text"
                   id="haraka_tenders_b_label"
                   name="haraka_tenders_b_label"
                   value="<?php echo esc_attr( $tb_label ); ?>"
                   placeholder="e.g. Tender Opportunities" />
        </div>

        <div class="haraka-field-row">
            <label class="haraka-field-label" for="haraka_tenders_b_headline">
                Headline — Regular Portion
                <span class="haraka-field-hint">e.g. Open procurement</span>
            </label>
            <input type="text"
                   id="haraka_tenders_b_headline"
                   name="haraka_tenders_b_headline"
                   value="<?php echo esc_attr( $tb_headline ); ?>"
                   placeholder="e.g. Open procurement" />
        </div>

        <div class="haraka-field-row">
            <label class="haraka-field-label" for="haraka_tenders_b_headline_italic">
                Headline — Italic Portion
                <span class="haraka-field-hint">e.g. across the group.</span>
            </label>
            <input type="text"
                   id="haraka_tenders_b_headline_italic"
                   name="haraka_tenders_b_headline_italic"
                   value="<?php echo esc_attr( $tb_headline_italic ); ?>"
                   placeholder="e.g. across the group." />
        </div>

        <div class="haraka-field-row">
            <label class="haraka-field-label" for="haraka_tenders_b_all_text">
                Top-Right Link Text
                <span class="haraka-field-hint">Text for the link top-right of the header. e.g. All tenders</span>
            </label>
            <input type="text"
                   id="haraka_tenders_b_all_text"
                   name="haraka_tenders_b_all_text"
                   value="<?php echo esc_attr( $tb_all_text ); ?>"
                   placeholder="e.g. All tenders" />
        </div>

        <div class="haraka-field-row">
            <label class="haraka-field-label" for="haraka_tenders_b_view_all_text">
                Tab Bar Link Text
                <span class="haraka-field-hint">Text for the View all link inside the tab bar. e.g. View all</span>
            </label>
            <input type="text"
                   id="haraka_tenders_b_view_all_text"
                   name="haraka_tenders_b_view_all_text"
                   value="<?php echo esc_attr( $tb_view_all_text ); ?>"
                   placeholder="e.g. View all" />
        </div>

        <div class="haraka-section-title">Template B — Live Preview</div>

        <div style="background:#f5f0e8;border-radius:8px;padding:20px 24px;border:1px solid #e2d9c8;">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:#7a6f60;margin:0 0 6px;">
                — <span id="hrk-tb-preview-label"><?php echo esc_html( $tb_label ); ?></span>
            </p>
            <p style="font-size:22px;font-weight:700;color:#1a1410;margin:0 0 8px;line-height:1.3;">
                <span id="hrk-tb-preview-headline"><?php echo esc_html( $tb_headline ); ?></span>
                <em id="hrk-tb-preview-italic" style="font-style:italic;font-weight:400;">
                    <?php echo esc_html( $tb_headline_italic ); ?>
                </em>
            </p>
            <p style="font-size:12px;color:#4a3f30;margin:0;">
                <span id="hrk-tb-preview-all"><?php echo esc_html( $tb_all_text ); ?></span> &rarr;
                &nbsp;&nbsp;
                <span id="hrk-tb-preview-viewall" style="font-size:11px;color:#8a7d6a;"><?php echo esc_html( $tb_view_all_text ); ?> &rarr;</span>
            </p>
        </div>

    </div>

    <script>
    (function() {
        var tbFields = {
            'haraka_tenders_b_label':          'hrk-tb-preview-label',
            'haraka_tenders_b_headline':       'hrk-tb-preview-headline',
            'haraka_tenders_b_headline_italic':'hrk-tb-preview-italic',
            'haraka_tenders_b_all_text':       'hrk-tb-preview-all',
            'haraka_tenders_b_view_all_text':  'hrk-tb-preview-viewall',
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
function haraka_render_careers_settings() {
    $departments  = get_option( 'haraka_career_departments', "Finance\nHuman Resource\nICT\nOperations\nProcurement\nLegal\nMarketing\nAdministration" );
    $careers_url  = get_option( 'haraka_careers_page_url',   '/careers' );
    ?>

    <div class="haraka-section-title">Display</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_careers_page_url">
            Careers Page URL
            <span class="haraka-field-hint">
                Used in the View All Positions link in the careers preview strip
            </span>
        </label>
        <input type="text"
               id="haraka_careers_page_url"
               name="haraka_careers_page_url"
               value="<?php echo esc_attr( $careers_url ); ?>"
               placeholder="e.g. /careers or https://yoursite.com/careers" />
    </div>

    <div class="haraka-section-title">Department Options</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_career_departments">
            Department List
            <span class="haraka-field-hint">
                One department per line. These appear in the Department dropdown
                when editing a job vacancy post.
            </span>
        </label>
        <textarea id="haraka_career_departments"
                  name="haraka_career_departments"
                  rows="10"
                  placeholder="Finance&#10;Human Resource&#10;ICT&#10;Operations&#10;Procurement"><?php echo esc_textarea( $departments ); ?></textarea>
    </div>

    <?php
}
// ── Posts Templates Settings ──────────────────────────────────────────────────
function haraka_render_posts_settings() {
    $label          = get_option( 'haraka_news_label',          'Impact & Activities' );
    $headline       = get_option( 'haraka_news_headline',       'News, milestones, and' );
    $headline_italic= get_option( 'haraka_news_headline_italic','community work.' );
    $view_all_text  = get_option( 'haraka_news_view_all_text',  'View newsroom' );
    $view_all_url   = get_option( 'haraka_news_view_all_url',   '/newsroom' );
    $category       = get_option( 'haraka_news_category',       '' );
    ?>

    <div class="haraka-section-title">News Grid — Section Header</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_news_label">
            Section Label
            <span class="haraka-field-hint">
                Small uppercase text above the headline.
                e.g. Impact &amp; Activities
            </span>
        </label>
        <input type="text"
               id="haraka_news_label"
               name="haraka_news_label"
               value="<?php echo esc_attr( $label ); ?>"
               placeholder="e.g. Impact &amp; Activities" />
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_news_headline">
            Headline — Regular Portion
            <span class="haraka-field-hint">
                The normal-weight part of the headline.
                e.g. News, milestones, and
            </span>
        </label>
        <input type="text"
               id="haraka_news_headline"
               name="haraka_news_headline"
               value="<?php echo esc_attr( $headline ); ?>"
               placeholder="e.g. News, milestones, and" />
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_news_headline_italic">
            Headline — Italic Portion
            <span class="haraka-field-hint">
                The italic part of the headline — appears after the regular portion.
                e.g. community work.
            </span>
        </label>
        <input type="text"
               id="haraka_news_headline_italic"
               name="haraka_news_headline_italic"
               value="<?php echo esc_attr( $headline_italic ); ?>"
               placeholder="e.g. community work." />
    </div>

    <div class="haraka-section-title">News Grid — Link</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_news_view_all_text">
            View All Link Text
            <span class="haraka-field-hint">
                Text shown on the top-right link. e.g. View newsroom
            </span>
        </label>
        <input type="text"
               id="haraka_news_view_all_text"
               name="haraka_news_view_all_text"
               value="<?php echo esc_attr( $view_all_text ); ?>"
               placeholder="e.g. View newsroom" />
    </div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_news_view_all_url">
            View All Link URL
            <span class="haraka-field-hint">
                Where the link points to. e.g. /newsroom
            </span>
        </label>
        <input type="text"
               id="haraka_news_view_all_url"
               name="haraka_news_view_all_url"
               value="<?php echo esc_attr( $view_all_url ); ?>"
               placeholder="e.g. /newsroom or https://yoursite.com/newsroom" />
    </div>

    <div class="haraka-section-title">News Grid — Default Content</div>

    <div class="haraka-field-row">
        <label class="haraka-field-label" for="haraka_news_category">
            Default Category Slug
            <span class="haraka-field-hint">
                Optional. If set, the [news_grid] shortcode will filter by this
                category by default. Leave blank to show all recent posts.
                e.g. csr or news
            </span>
        </label>
        <input type="text"
               id="haraka_news_category"
               name="haraka_news_category"
               value="<?php echo esc_attr( $category ); ?>"
               placeholder="e.g. csr or leave blank for all posts" />
    </div>

    <div class="haraka-section-title">Preview</div>

    <div style="background: #f5f0e8; border-radius: 8px; padding: 20px 24px; border: 1px solid #e2d9c8;">
        <p style="font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #7a6f60; margin: 0 0 6px;">
            — <span id="hrk-preview-label"><?php echo esc_html( $label ); ?></span>
        </p>
        <p style="font-size: 20px; font-weight: 700; color: #1a1410; margin: 0 0 8px; line-height: 1.3;">
            <span id="hrk-preview-headline"><?php echo esc_html( $headline ); ?></span>
            <em id="hrk-preview-italic" style="font-style: italic; font-weight: 400;">
                <?php echo esc_html( $headline_italic ); ?>
            </em>
        </p>
        <p style="font-size: 12px; color: #4a3f30; margin: 0;">
            <span id="hrk-preview-link"><?php echo esc_html( $view_all_text ); ?></span> &rarr;
        </p>
    </div>

    <script>
    (function() {
        var fields = {
            'haraka_news_label':          'hrk-preview-label',
            'haraka_news_headline':       'hrk-preview-headline',
            'haraka_news_headline_italic':'hrk-preview-italic',
            'haraka_news_view_all_text':  'hrk-preview-link',
        };
        Object.keys(fields).forEach(function(fieldId) {
            var input   = document.getElementById(fieldId);
            var preview = document.getElementById(fields[fieldId]);
            if ( input && preview ) {
                input.addEventListener('input', function() {
                    preview.textContent = this.value;
                });
            }
        });
    })();
    </script>

    <div class="haraka-section-title">Available Shortcodes — Reference</div>

    <div style="overflow-x: auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;font-family:monospace;">
            <thead>
                <tr style="background:#0f172a;color:#fff;">
                    <th style="padding:8px 12px;text-align:left;font-weight:600;white-space:nowrap;">Shortcode</th>
                    <th style="padding:8px 12px;text-align:left;font-weight:600;">Description</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:#f8fafc;">
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Shows 4 most recent posts using settings defaults</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid category="csr"]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Filter by category slug — replace <em>csr</em> with your slug</td>
                </tr>
                <tr style="background:#f8fafc;">
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid posts_per_page="6"]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Show more posts — minimum 4, featured is always first</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid show_excerpt="no"]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Hide the excerpt on the featured post</td>
                </tr>
                <tr style="background:#f8fafc;">
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid order="ASC"]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Oldest posts first — default is DESC (newest first)</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid orderby="title"]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Sort by title — also accepts: date, modified, rand</td>
                </tr>
                <tr style="background:#f8fafc;">
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid label="Our Stories"]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Override the section label for this instance only</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid headline="Latest from" headline_italic="our team."]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Override headline text for this instance only</td>
                </tr>
                <tr style="background:#f8fafc;">
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;color:#0056b3;">[news_grid view_all_text="Read all" view_all_url="/news"]</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-family:sans-serif;color:#334155;">Override the view all link text and URL</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;white-space:nowrap;color:#0056b3;">[news_grid category="csr" show_excerpt="no" order="DESC"]</td>
                    <td style="padding:8px 12px;font-family:sans-serif;color:#334155;">Combine multiple parameters in one shortcode</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;padding:12px 16px;background:#f0fdf4;border-left:3px solid #16a34a;border-radius:0 4px 4px 0;">
        <p style="font-size:12px;color:#166534;margin:0;font-family:sans-serif;">
            <strong>Tip:</strong> Parameters set in the shortcode always override the defaults above.
            Use Haraka Settings to set your site-wide defaults, and use shortcode parameters
            when you need a one-off variation on a specific page.
        </p>
    </div>

    <?php
}