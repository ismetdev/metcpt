<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register settings menu ────────────────────────────────────────────────────
function metcpt_admin_menu() {
    add_menu_page(
        'MetCPT Settings',
        'MetCPT Settings',
        'manage_options',
        'metcpt-settings',
        'metcpt_settings_page_html',
        'dashicons-screenoptions',
        8
    );
}
add_action( 'admin_menu', 'metcpt_admin_menu' );


// ── Register all settings ─────────────────────────────────────────────────────
function metcpt_register_settings() {

    // ── General ───────────────────────────────────────────────────────────────
    register_setting( 'metcpt_general', 'metcpt_enable_dummy_data',   array( 'sanitize_callback' => 'absint',                   'default' => 0 ) );

    // ── Events ────────────────────────────────────────────────────────────────
    register_setting( 'metcpt_events', 'metcpt_events_archive_url',   array( 'sanitize_callback' => 'sanitize_text_field',      'default' => '/events' ) );
    register_setting( 'metcpt_events', 'metcpt_vip_roles',            array( 'sanitize_callback' => 'sanitize_textarea_field',  'default' => "Guest of Honour\nTazkirah\nNotable Attendee\nSpeaker\nMC" ) );

    // ── Tenders ───────────────────────────────────────────────────────────────
    register_setting( 'metcpt_tenders', 'metcpt_tenders_page_url',             array( 'sanitize_callback' => 'sanitize_text_field',     'default' => '/tenders' ) );
    register_setting( 'metcpt_tenders', 'metcpt_closing_soon_days',            array( 'sanitize_callback' => 'absint',                   'default' => 7 ) );
    register_setting( 'metcpt_tenders', 'metcpt_tender_categories',            array( 'sanitize_callback' => 'sanitize_textarea_field',  'default' => "Goods\nServices\nConstruction\nConsultancy\nOthers" ) );
    register_setting( 'metcpt_tenders', 'metcpt_notify_email', array( 'sanitize_callback' => 'sanitize_email', 'default' => get_option( 'admin_email' ) ) );
    register_setting( 'metcpt_tenders', 'metcpt_tenders_template',        array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'a' ) );
    register_setting( 'metcpt_tenders', 'metcpt_tenders_b_label',         array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'Tender Opportunities' ) );
    register_setting( 'metcpt_tenders', 'metcpt_tenders_b_headline',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'Open procurement' ) );
    register_setting( 'metcpt_tenders', 'metcpt_tenders_b_headline_italic',array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'across the group.' ) );
    register_setting( 'metcpt_tenders', 'metcpt_tenders_b_all_text',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'All tenders' ) );
    register_setting( 'metcpt_tenders', 'metcpt_tenders_b_view_all_text', array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'View all' ) );

    // ── Careers ───────────────────────────────────────────────────────────────
    register_setting( 'metcpt_careers', 'metcpt_career_departments', array( 'sanitize_callback' => 'sanitize_textarea_field', 'default' => "Finance\nHuman Resource\nICT\nOperations\nProcurement\nLegal\nMarketing\nAdministration" ) );
    register_setting( 'metcpt_careers', 'metcpt_careers_page_url',   array( 'sanitize_callback' => 'sanitize_text_field',     'default' => '/careers' ) );

    // ── Posts Templates ───────────────────────────────────────────────────────
    register_setting( 'metcpt_posts', 'metcpt_news_label',         array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'Impact & Activities' ) );
    register_setting( 'metcpt_posts', 'metcpt_news_headline',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'News, milestones, and' ) );
    register_setting( 'metcpt_posts', 'metcpt_news_headline_italic',array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'community work.' ) );
    register_setting( 'metcpt_posts', 'metcpt_news_view_all_text', array( 'sanitize_callback' => 'sanitize_text_field',  'default' => 'View newsroom' ) );
    register_setting( 'metcpt_posts', 'metcpt_news_view_all_url',  array( 'sanitize_callback' => 'sanitize_text_field',  'default' => '/newsroom' ) );
    register_setting( 'metcpt_posts', 'metcpt_news_category',      array( 'sanitize_callback' => 'sanitize_text_field',  'default' => '' ) );
}
add_action( 'admin_init', 'metcpt_register_settings' );


// ── Settings page HTML ────────────────────────────────────────────────────────
function metcpt_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $active_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
    $is_full_page = in_array( $active_tab, array( 'error-log', 'how-to' ), true );

    if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>MetCPT Settings saved successfully.</strong></p></div>';
    }

    if ( $active_tab === 'how-to' ) {
        echo '<link rel="stylesheet" href="' . esc_url( METCPT_URL . 'assets/style-docs.css?v=' . METCPT_VERSION ) . '">';
    }
    ?>

    <?php
    $metcpt_tabs = array(
        'general'   => 'General',
        'events'    => 'Events',
        'tenders'   => 'Tenders',
        'careers'   => 'Careers',
        'posts'     => 'Posts',
        'error-log' => 'Error Log',
        'how-to'    => 'How To',
    );
    ?>

    <div class="wrap metcpt-settings-wrap">

        <div class="metcpt-settings-header">
            <div class="metcpt-settings-header-inner">
                <h1 class="metcpt-settings-title">
                    <span class="metcpt-logo">H</span>
                    MetCPT
                </h1>
                <p class="metcpt-settings-subtitle">
                    Corporate Content Hub — v<?php echo esc_html( METCPT_VERSION ); ?>
                </p>
            </div>
        </div>

        <nav class="metcpt-tabbar">
            <?php foreach ( $metcpt_tabs as $tab_key => $tab_label ) : ?>
                <a href="?page=metcpt-settings&tab=<?php echo esc_attr( $tab_key ); ?>"
                   class="metcpt-tab <?php echo $active_tab === $tab_key ? 'active' : ''; ?>">
                    <?php echo esc_html( $tab_label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="metcpt-settings-body <?php echo $is_full_page ? 'metcpt-settings-body-full' : ''; ?>">

            <div class="metcpt-settings-content <?php echo $is_full_page ? 'metcpt-settings-content-wide' : ''; ?>">
                <form method="post" action="options.php">
                    <?php
                    if ( $active_tab === 'general' ) {
                        settings_fields( 'metcpt_general' );
                        metcpt_render_general_settings();
                    } elseif ( $active_tab === 'events' ) {
                        settings_fields( 'metcpt_events' );
                        metcpt_render_events_settings();
                    } elseif ( $active_tab === 'tenders' ) {
                        settings_fields( 'metcpt_tenders' );
                        metcpt_render_tenders_settings();
                    } elseif ( $active_tab === 'careers' ) {
                        settings_fields( 'metcpt_careers' );
                        metcpt_render_careers_settings();
                    } elseif ( $active_tab === 'posts' ) {
                        settings_fields( 'metcpt_posts' );
                        metcpt_render_posts_settings();
                    } elseif ( $active_tab === 'error-log' ) {
                        metcpt_render_error_log_tab();
                    } elseif ( $active_tab === 'how-to' ) {
                        metcpt_render_docs_tab();
                    }
                    if ( ! $is_full_page ) {
                        submit_button( 'Save Settings', 'primary', 'submit', true, array( 'class' => 'metcpt-save-btn' ) );
                    }
                    ?>
                </form>
            </div>

        </div>

    </div>

    <?php
}