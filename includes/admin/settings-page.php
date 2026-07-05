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

    $active_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
    $is_full_page = in_array( $active_tab, array( 'error-log', 'how-to' ), true );

    if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Haraka Settings saved successfully.</strong></p></div>';
    }

    if ( $active_tab === 'how-to' ) {
        echo '<link rel="stylesheet" href="' . esc_url( HARAKA_PLUGIN_URL . 'assets/style-docs.css?v=' . HARAKA_VERSION ) . '">';
    }
    ?>

    <?php
    $haraka_tabs = array(
        'general'   => 'General',
        'events'    => 'Events',
        'tenders'   => 'Tenders',
        'careers'   => 'Careers',
        'posts'     => 'Posts',
        'error-log' => 'Error Log',
        'how-to'    => 'How To',
    );
    ?>

    <div class="wrap haraka-settings-wrap">

        <div class="haraka-settings-header">
            <div class="haraka-settings-header-inner">
                <h1 class="haraka-settings-title">
                    <span class="haraka-logo">H</span>
                    Haraka
                </h1>
                <p class="haraka-settings-subtitle">
                    Corporate Content Hub — v<?php echo esc_html( HARAKA_VERSION ); ?>
                </p>
            </div>
        </div>

        <nav class="haraka-tabbar">
            <?php foreach ( $haraka_tabs as $tab_key => $tab_label ) : ?>
                <a href="?page=haraka-settings&tab=<?php echo esc_attr( $tab_key ); ?>"
                   class="haraka-tab <?php echo $active_tab === $tab_key ? 'active' : ''; ?>">
                    <?php echo esc_html( $tab_label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="haraka-settings-body <?php echo $is_full_page ? 'haraka-settings-body-full' : ''; ?>">

            <div class="haraka-settings-content <?php echo $is_full_page ? 'haraka-settings-content-wide' : ''; ?>">
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
                    } elseif ( $active_tab === 'how-to' ) {
                        haraka_render_docs_tab();
                    }
                    if ( ! $is_full_page ) {
                        submit_button( 'Save Settings', 'primary', 'submit', true, array( 'class' => 'haraka-save-btn' ) );
                    }
                    ?>
                </form>
            </div>

        </div>

    </div>

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