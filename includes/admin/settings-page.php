<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register settings menu ────────────────────────────────────────────────────
function haraka_admin_menu() {
    add_menu_page(
        'Haraka Settings',
        'Haraka Settings',
        'manage_options',
        'haraka-settings',
        'haraka_settings_page_html',
        'dashicons-screenoptions',
        8
    );
}
add_action( 'admin_menu', 'haraka_admin_menu' );


// ── Register all settings ─────────────────────────────────────────────────────
function haraka_register_settings() {

    // ── General ───────────────────────────────────────────────────────────────
    register_setting( 'haraka_general', 'haraka_accent_colour',       array( 'sanitize_callback' => 'sanitize_hex_color',      'default' => '#0056b3' ) );
    register_setting( 'haraka_general', 'haraka_organisation_name',   array( 'sanitize_callback' => 'sanitize_text_field',      'default' => 'IIUM Holdings Sdn Bhd' ) );
    register_setting( 'haraka_general', 'haraka_enable_dummy_data',   array( 'sanitize_callback' => 'absint',                   'default' => 0 ) );
    // ── Events ────────────────────────────────────────────────────────────────
    register_setting( 'haraka_events', 'haraka_events_archive_url',   array( 'sanitize_callback' => 'sanitize_text_field',      'default' => '/events' ) );
    register_setting( 'haraka_events', 'haraka_events_default_order', array( 'sanitize_callback' => 'sanitize_text_field',      'default' => 'ASC' ) );
    register_setting( 'haraka_events', 'haraka_events_show_excerpt',  array( 'sanitize_callback' => 'sanitize_text_field',      'default' => 'yes' ) );
    register_setting( 'haraka_events', 'haraka_vip_roles',            array( 'sanitize_callback' => 'sanitize_textarea_field',  'default' => "Guest of Honour\nTazkirah\nNotable Attendee\nSpeaker\nMC" ) );

    // ── Tenders ───────────────────────────────────────────────────────────────
    register_setting( 'haraka_tenders', 'haraka_tenders_page_url',             array( 'sanitize_callback' => 'sanitize_text_field',     'default' => '/tenders' ) );
    register_setting( 'haraka_tenders', 'haraka_closing_soon_days',            array( 'sanitize_callback' => 'absint',                   'default' => 7 ) );
    register_setting( 'haraka_tenders', 'haraka_default_submission_address',   array( 'sanitize_callback' => 'sanitize_textarea_field',  'default' => '' ) );
    register_setting( 'haraka_tenders', 'haraka_tender_categories',            array( 'sanitize_callback' => 'sanitize_textarea_field',  'default' => "Goods\nServices\nConstruction\nConsultancy\nOthers" ) );
    register_setting( 'haraka_tenders', 'haraka_default_tender_fee',           array( 'sanitize_callback' => 'sanitize_text_field',      'default' => '' ) );
    register_setting( 'haraka_tenders', 'haraka_notify_email', array( 'sanitize_callback' => 'sanitize_email', 'default' => get_option( 'admin_email' ) ) );
    register_setting( 'haraka_tenders', 'haraka_tenders_template',        array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'a' ) );
    register_setting( 'haraka_tenders', 'haraka_tenders_b_label',         array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'Tender Opportunities' ) );
    register_setting( 'haraka_tenders', 'haraka_tenders_b_headline',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'Open procurement' ) );
    register_setting( 'haraka_tenders', 'haraka_tenders_b_headline_italic',array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'across the group.' ) );
    register_setting( 'haraka_tenders', 'haraka_tenders_b_all_text',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'All tenders' ) );
    register_setting( 'haraka_tenders', 'haraka_tenders_b_view_all_text', array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'View all' ) );

    // ── Careers ───────────────────────────────────────────────────────────────
    register_setting( 'haraka_careers', 'haraka_career_departments', array( 'sanitize_callback' => 'sanitize_textarea_field', 'default' => "Finance\nHuman Resource\nICT\nOperations\nProcurement\nLegal\nMarketing\nAdministration" ) );
    register_setting( 'haraka_careers', 'haraka_careers_page_url',   array( 'sanitize_callback' => 'sanitize_text_field',     'default' => '/careers' ) );

    // ── Posts Templates ───────────────────────────────────────────────────────
    register_setting( 'haraka_posts', 'haraka_news_label',         array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'Impact & Activities' ) );
    register_setting( 'haraka_posts', 'haraka_news_headline',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'News, milestones, and' ) );
    register_setting( 'haraka_posts', 'haraka_news_headline_italic',array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'community work.' ) );
    register_setting( 'haraka_posts', 'haraka_news_view_all_text', array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'View newsroom' ) );
    register_setting( 'haraka_posts', 'haraka_news_view_all_url',  array( 'sanitize_callback' => 'sanitize_text_field',  'default' => '/newsroom' ) );
    register_setting( 'haraka_posts', 'haraka_news_category',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => '' ) );
}
add_action( 'admin_init', 'haraka_register_settings' );


// ── Settings page HTML ────────────────────────────────────────────────────────
function haraka_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Which tab is active
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

    // Show success notice manually after save
    if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Haraka Settings saved successfully.</strong></p></div>';
    }
    ?>

    <div class="wrap haraka-settings-wrap">

        <div class="haraka-settings-header">
            <div class="haraka-settings-header-inner">
                <h1 class="haraka-settings-title">
                    <span class="haraka-logo">H</span>
                    Haraka Settings
                </h1>
                <p class="haraka-settings-subtitle">
                    Corporate Content Hub — v<?php echo esc_html( HARAKA_VERSION ); ?>
                </p>
            </div>
        </div>

        <div class="haraka-settings-body">

            <nav class="haraka-settings-nav">
                <a href="?page=haraka-settings&tab=general"
                   class="haraka-nav-item <?php echo $active_tab === 'general'  ? 'active' : ''; ?>">
                    General
                </a>
                <a href="?page=haraka-settings&tab=events"
                   class="haraka-nav-item <?php echo $active_tab === 'events'   ? 'active' : ''; ?>">
                    Events
                </a>
                <a href="?page=haraka-settings&tab=tenders"
                   class="haraka-nav-item <?php echo $active_tab === 'tenders'  ? 'active' : ''; ?>">
                    Tenders
                </a>
                <a href="?page=haraka-settings&tab=careers"
                   class="haraka-nav-item <?php echo $active_tab === 'careers'  ? 'active' : ''; ?>">
                    Careers
                </a>
                <a href="?page=haraka-settings&tab=posts"
                   class="haraka-nav-item <?php echo $active_tab === 'posts'  ? 'active' : ''; ?>">
                    Posts
                </a>
                <a href="?page=haraka-settings&tab=error-log"
                   class="haraka-nav-item hrk-nav-error-log <?php echo $active_tab === 'error-log' ? 'active' : ''; ?>">
                    Error Log
                </a>
            </nav>

            <div class="haraka-settings-content <?php echo $active_tab === 'error-log' ? 'haraka-settings-content-wide' : ''; ?>">
                <form method="post" action="options.php">
                    <?php
                    if ( $active_tab === 'general' ) {
                        settings_fields( 'haraka_general' );
                        haraka_render_general_settings();
                    } elseif ( $active_tab === 'events' ) {
                        settings_fields( 'haraka_events' );
                        haraka_render_events_settings();
                    } elseif ( $active_tab === 'tenders' ) {
                        settings_fields( 'haraka_tenders' );
                        haraka_render_tenders_settings();
                    } elseif ( $active_tab === 'careers' ) {
                        settings_fields( 'haraka_careers' );
                        haraka_render_careers_settings();
                    } elseif ( $active_tab === 'posts' ) {
                        settings_fields( 'haraka_posts' );
                        haraka_render_posts_settings();
                    } elseif ( $active_tab === 'error-log' ) {
                        haraka_render_error_log_tab();
                    }
                    if ( $active_tab !== 'error-log' ) {
                        submit_button( 'Save Settings', 'primary', 'submit', true, array( 'class' => 'haraka-save-btn' ) );
                    }                    ?>
                </form>
            </div>

        </div>

    </div>

    <style>
        .haraka-settings-wrap {
            font-family: -apple-system, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }
        .haraka-settings-header {
            background: #0f172a;
            padding: 24px 32px;
            margin-left: -20px;
            margin-top: -10px;
        }
        .haraka-settings-header-inner {
            max-width: 900px;
        }
        .haraka-settings-title {
            color: #ffffff !important;
            font-size: 20px !important;
            font-weight: 700 !important;
            margin: 0 0 4px !important;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .haraka-logo {
            width: 32px;
            height: 32px;
            background: #0056b3;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
        }
        .haraka-settings-subtitle {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
        }
        .haraka-settings-body {
            display: flex;
            gap: 0;
            max-width: 960px;
            margin-top: 24px;
        }
        .haraka-settings-nav {
            width: 180px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding-right: 20px;
        }
        .haraka-nav-item {
            display: block;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.15s;
        }
        .haraka-nav-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .haraka-nav-item.active {
            background: #0056b3;
            color: #ffffff;
        }
        .haraka-settings-content {
            flex: 1;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
        }
        .haraka-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin: 24px 0 20px;
        }
        .haraka-section-title:first-child {
            margin-top: 0;
        }
        .haraka-field-row {
            display: grid;
            grid-template-columns: 220px 1fr;
            align-items: start;
            margin-bottom: 20px;
            gap: 16px;
        }
        .haraka-field-label {
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            padding-top: 6px;
            line-height: 1.4;
        }
        .haraka-field-hint {
            font-size: 11px;
            font-weight: 400;
            color: #94a3b8;
            display: block;
            margin-top: 3px;
        }
        .haraka-field-row input[type="text"],
        .haraka-field-row input[type="number"],
        .haraka-field-row input[type="url"],
        .haraka-field-row input[type="color"],
        .haraka-field-row select,
        .haraka-field-row textarea {
            width: 100%;
            max-width: 480px;
            font-size: 13px;
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #1d2327;
            background: #fff;
            box-sizing: border-box;
        }
        .haraka-field-row input[type="color"] {
            width: 60px;
            height: 38px;
            padding: 2px 4px;
            cursor: pointer;
        }
        .haraka-field-row textarea {
            height: 100px;
            resize: vertical;
        }
        .haraka-field-row input:focus,
        .haraka-field-row select:focus,
        .haraka-field-row textarea:focus {
            border-color: #0056b3;
            outline: 2px solid rgba( 0, 86, 179, 0.2 );
            outline-offset: 0;
        }
        .haraka-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 4px;
        }
        .haraka-toggle {
            position: relative;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .haraka-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .haraka-toggle-slider {
            position: absolute;
            inset: 0;
            background: #d1d5db;
            border-radius: 24px;
            cursor: pointer;
            transition: 0.2s;
        }
        .haraka-toggle-slider:before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: 0.2s;
        }
        .haraka-toggle input:checked + .haraka-toggle-slider {
            background: #0056b3;
        }
        .haraka-toggle input:checked + .haraka-toggle-slider:before {
            transform: translateX( 20px );
        }
        .haraka-toggle-label {
            font-size: 13px;
            color: #475569;
        }
        .haraka-colour-preview {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        .haraka-colour-swatch {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .haraka-colour-value {
            font-size: 12px;
            font-family: monospace;
            color: #64748b;
        }
        .haraka-save-btn {
            margin-top: 8px !important;
            background: #0f172a !important;
            border-color: #0f172a !important;
            padding: 8px 24px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            height: auto !important;
        }
        .haraka-save-btn:hover {
            background: #1e293b !important;
            border-color: #1e293b !important;
        }
        .updated.notice {
            border-left-color: #0056b3 !important;
        }

        .haraka-settings-content-wide {
            padding: 20px;
            background: #f1f5f9;
            border: none;
            border-radius: 0;
        }
    </style>

    <script>
    (function() {
        var picker = document.getElementById('haraka_accent_colour');
        var swatch = document.getElementById('haraka-colour-swatch');
        var value  = document.getElementById('haraka-colour-value');
        if ( picker && swatch && value ) {
            function update() {
                swatch.style.background = picker.value;
                value.textContent       = picker.value;
            }
            picker.addEventListener('input', update);
            update();
        }
    })();
    </script>

    <?php
}