<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class — bootstraps all modules and hooks.
 *
 * @package Haraka
 * @version 1.0.4
 */
class Haraka {

    /**
     * Single instance of this class.
     *
     * @var Haraka
     */
    private static $instance = null;

    /**
     * Returns the single instance. Creates it on first call.
     *
     * @return Haraka
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor — loads modules and registers hooks.
     */
    private function __construct() {
        $this->load_modules();
        $this->register_hooks();
    }

    /**
     * Load all module files conditionally.
     */
    private function load_modules() {

        // Core — always needed on every request
        require_once HARAKA_PLUGIN_DIR . 'includes/core/post-types.php';
        require_once HARAKA_PLUGIN_DIR . 'includes/admin/cron.php';
        require_once HARAKA_PLUGIN_DIR . 'includes/admin/error-log.php';
        require_once HARAKA_PLUGIN_DIR . 'includes/admin/dummy-data.php';
        require_once HARAKA_PLUGIN_DIR . 'includes/admin/docs-page.php';        

        // Admin only
        if ( is_admin() ) {
            require_once HARAKA_PLUGIN_DIR . 'includes/admin/settings-page.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/admin/settings-fields.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/admin/dashboard-widget.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/admin/error-log-page.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/careers/meta-boxes-company.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/careers/meta-boxes-career.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/events/meta-boxes.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/tenders/meta-boxes.php';
        }

        // Frontend only
        if ( ! is_admin() ) {
            require_once HARAKA_PLUGIN_DIR . 'includes/posts/shortcode-general.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/posts/shortcode-news-grid.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/events/shortcode-list.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/events/template-single.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/events/template-archive.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-list.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-preview.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-template-b.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/tenders/template-single.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/tenders/template-archive.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/careers/shortcode-list.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/careers/shortcode-preview.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/careers/template-single.php';
            require_once HARAKA_PLUGIN_DIR . 'includes/careers/template-archive.php';
        }
    }

    /**
     * Register all plugin-level hooks.
     */
    private function register_hooks() {
        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_frontend_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        register_activation_hook( HARAKA_PLUGIN_FILE,   array( $this, 'activate' ) );
        register_deactivation_hook( HARAKA_PLUGIN_FILE, array( $this, 'deactivate' ) );
    }

    /**
     * Cache-busting version string for an asset.
     *
     * Returns the file's last-modified time so that any change to the file
     * automatically produces a new version query string. This forces browsers
     * and caching layers (e.g. LiteSpeed) to fetch the updated file instead of
     * serving a stale copy. Falls back to HARAKA_VERSION if the file is missing.
     *
     * @param string $relative_path Path relative to the plugin root, e.g. 'assets/style-general.css'.
     * @return string|int Version string for wp_enqueue_style().
     */
    private function asset_version( $relative_path ) {
        $full_path = HARAKA_PLUGIN_DIR . $relative_path;
        return file_exists( $full_path ) ? filemtime( $full_path ) : HARAKA_VERSION;
    }

    /**
     * Enqueue frontend CSS conditionally per module.
     */
    public function enqueue_frontend_styles() {

        wp_enqueue_style(
            'haraka-tokens',
            HARAKA_PLUGIN_URL . 'assets/style-tokens.css',
            array(),
            $this->asset_version( 'assets/style-tokens.css' )
        );

        wp_enqueue_style(
            'haraka-general',
            HARAKA_PLUGIN_URL . 'assets/style-general.css',
            array(),
            $this->asset_version( 'assets/style-general.css' )
        );

        wp_enqueue_style(
            'haraka-events',
            HARAKA_PLUGIN_URL . 'assets/style-events.css',
            array(),
            $this->asset_version( 'assets/style-events.css' )
        );

        wp_enqueue_style(
            'haraka-tenders',
            HARAKA_PLUGIN_URL . 'assets/style-tenders.css',
            array( 'haraka-tokens' ),
            $this->asset_version( 'assets/style-tenders.css' )
        );

        wp_enqueue_style(
            'haraka-careers',
            HARAKA_PLUGIN_URL . 'assets/style-careers.css',
            array(),
            $this->asset_version( 'assets/style-careers.css' )
        );

        wp_enqueue_style(
            'haraka-posts',
            HARAKA_PLUGIN_URL . 'assets/style-posts.css',
            array( 'haraka-tokens' ),
            $this->asset_version( 'assets/style-posts.css' )
        );
    }

    /**
     * Enqueue admin CSS on relevant pages only.
     */
    public function enqueue_admin_styles( $hook ) {
        $allowed_hooks = array(
            'post.php',
            'post-new.php',
            'index.php',
            'settings_page_haraka-settings',
            'toplevel_page_haraka-settings',
        );

        // Also load on any Haraka settings page regardless of hook
        $is_haraka_page = isset( $_GET['page'] ) && $_GET['page'] === 'haraka-settings';

        if ( ! $is_haraka_page && ! in_array( $hook, $allowed_hooks ) ) {
            return;
        }

        wp_enqueue_style(
            'haraka-admin',
            HARAKA_PLUGIN_URL . 'assets/style-admin.css',
            array(),
            $this->asset_version( 'assets/style-admin.css' )
        );

        // Settings-page-only styles (editorial paper + gold theme).
        if ( $is_haraka_page ) {
            wp_enqueue_style(
                'haraka-settings',
                HARAKA_PLUGIN_URL . 'assets/style-settings.css',
                array(),
                $this->asset_version( 'assets/style-settings.css' )
            );
        }
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        haraka_register_post_types();
        haraka_error_log_create_table();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}