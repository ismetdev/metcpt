<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class — bootstraps all modules and hooks.
 *
 * @package MetCPT
 */
class MetCPT {

    /**
     * Single instance of this class.
     *
     * @var MetCPT
     */
    private static $instance = null;

    /**
     * Returns the single instance. Creates it on first call.
     *
     * @return MetCPT
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
        require_once METCPT_PATH . 'includes/core/post-types.php';
        require_once METCPT_PATH . 'includes/admin/cron.php';
        require_once METCPT_PATH . 'includes/admin/error-log.php';
        require_once METCPT_PATH . 'includes/admin/dummy-data.php';
        require_once METCPT_PATH . 'includes/admin/docs-page.php';        

        // Admin only
        if ( is_admin() ) {
            require_once METCPT_PATH . 'includes/admin/settings-page.php';
            require_once METCPT_PATH . 'includes/admin/settings-fields.php';
            require_once METCPT_PATH . 'includes/admin/dashboard-widget.php';
            require_once METCPT_PATH . 'includes/admin/error-log-page.php';
            require_once METCPT_PATH . 'includes/careers/meta-boxes-company.php';
            require_once METCPT_PATH . 'includes/careers/meta-boxes-career.php';
            require_once METCPT_PATH . 'includes/events/meta-boxes.php';
            require_once METCPT_PATH . 'includes/tenders/meta-boxes.php';
        }

        // Frontend only
        if ( ! is_admin() ) {
            require_once METCPT_PATH . 'includes/posts/shortcode-general.php';
            require_once METCPT_PATH . 'includes/posts/shortcode-news-grid.php';
            require_once METCPT_PATH . 'includes/events/shortcode-list.php';
            require_once METCPT_PATH . 'includes/events/templates.php';
            require_once METCPT_PATH . 'includes/tenders/shortcode-list.php';
            require_once METCPT_PATH . 'includes/tenders/shortcode-preview.php';
            require_once METCPT_PATH . 'includes/tenders/shortcode-template-b.php';
            require_once METCPT_PATH . 'includes/tenders/templates.php';
            require_once METCPT_PATH . 'includes/careers/shortcode-list.php';
            require_once METCPT_PATH . 'includes/careers/shortcode-preview.php';
            require_once METCPT_PATH . 'includes/careers/templates.php';
        }
    }

    /**
     * Register all plugin-level hooks.
     */
    private function register_hooks() {
        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_frontend_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        register_activation_hook( METCPT_FILE,   array( $this, 'activate' ) );
        register_deactivation_hook( METCPT_FILE, array( $this, 'deactivate' ) );
    }

    /**
     * Cache-busting version string for an asset.
     *
     * Returns the file's last-modified time so that any change to the file
     * automatically produces a new version query string. This forces browsers
     * and caching layers (e.g. LiteSpeed) to fetch the updated file instead of
     * serving a stale copy. Falls back to METCPT_VERSION if the file is missing.
     *
     * @param string $relative_path Path relative to the plugin root, e.g. 'assets/css/style-general.css'.
     * @return string|int Version string for wp_enqueue_style().
     */
    private function asset_version( $relative_path ) {
        $full_path = METCPT_PATH . $relative_path;
        return file_exists( $full_path ) ? filemtime( $full_path ) : METCPT_VERSION;
    }

    /**
     * Enqueue frontend CSS, one module at a time, only where it is used.
     *
     * Every sheet is registered first, then enqueued by condition. A sheet loads
     * when the view is that module's single post, its CPT archive, or a page that
     * carries the module's shortcode. The tokens sheet is a dependency of the
     * tenders and posts sheets, so it loads with them and nowhere else.
     */
    public function enqueue_frontend_styles() {

        // Register every sheet. Registering does not output anything; only the
        // enqueue calls below do. Dependencies (tokens) are declared here.
        $sheets = array(
            'metcpt-tokens'  => array( 'assets/css/style-tokens.css',  array() ),
            'metcpt-general' => array( 'assets/css/style-general.css', array() ),
            'metcpt-events'  => array( 'assets/css/style-events.css',  array() ),
            'metcpt-tenders' => array( 'assets/css/style-tenders.css', array( 'metcpt-tokens' ) ),
            'metcpt-careers' => array( 'assets/css/style-careers.css', array() ),
            'metcpt-posts'   => array( 'assets/css/style-posts.css',   array( 'metcpt-tokens' ) ),
        );
        foreach ( $sheets as $handle => $sheet ) {
            wp_register_style(
                $handle,
                METCPT_URL . $sheet[0],
                $sheet[1],
                $this->asset_version( $sheet[0] )
            );
        }

        // Events: single event, event archive, or the [events_list] shortcode.
        if ( is_singular( 'metcpt_event' )
            || is_post_type_archive( 'metcpt_event' )
            || metcpt_page_has_shortcode( 'events_list' ) ) {
            wp_enqueue_style( 'metcpt-events' );
        }

        // Tenders: single, archive, or a tenders shortcode. Pulls tokens via dep.
        if ( is_singular( 'metcpt_tender' )
            || is_post_type_archive( 'metcpt_tender' )
            || metcpt_page_has_shortcode( 'tenders_list' )
            || metcpt_page_has_shortcode( 'tenders_preview' ) ) {
            wp_enqueue_style( 'metcpt-tenders' );
        }

        // Careers: single, archive, or a careers shortcode.
        if ( is_singular( 'metcpt_career' )
            || is_post_type_archive( 'metcpt_career' )
            || metcpt_page_has_shortcode( 'careers_list' )
            || metcpt_page_has_shortcode( 'careers_preview' ) ) {
            wp_enqueue_style( 'metcpt-careers' );
        }

        // News grid: the [news_grid] shortcode. Pulls tokens via dep.
        if ( metcpt_page_has_shortcode( 'news_grid' ) ) {
            wp_enqueue_style( 'metcpt-posts' );
        }

        // General post list: the [category_posts] shortcode.
        if ( metcpt_page_has_shortcode( 'category_posts' ) ) {
            wp_enqueue_style( 'metcpt-general' );
        }
    }

    /**
     * Enqueue admin CSS on relevant pages only.
     */
    public function enqueue_admin_styles( $hook ) {
        $allowed_hooks = array(
            'post.php',
            'post-new.php',
            'index.php',
            'settings_page_metcpt-settings',
            'toplevel_page_metcpt-settings',
        );

        // Also load on any MetCPT settings page regardless of hook
        $is_metcpt_page = isset( $_GET['page'] ) && $_GET['page'] === 'metcpt-settings';

        if ( ! $is_metcpt_page && ! in_array( $hook, $allowed_hooks ) ) {
            return;
        }

        wp_enqueue_style(
            'metcpt-admin',
            METCPT_URL . 'assets/css/style-admin.css',
            array(),
            $this->asset_version( 'assets/css/style-admin.css' )
        );

        // Settings-page-only styles (editorial paper + gold theme).
        if ( $is_metcpt_page ) {
            wp_enqueue_style(
                'metcpt-settings',
                METCPT_URL . 'assets/css/style-settings.css',
                array(),
                $this->asset_version( 'assets/css/style-settings.css' )
            );

            // How-To tab only. Loaded here instead of a raw <link> echo so it
            // gets the same filemtime cache-busting as every other sheet.
            if ( isset( $_GET['tab'] ) && $_GET['tab'] === 'how-to' ) {
                wp_enqueue_style(
                    'metcpt-docs',
                    METCPT_URL . 'assets/css/style-docs.css',
                    array(),
                    $this->asset_version( 'assets/css/style-docs.css' )
                );
            }
        }
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Migrate any legacy Haraka data before registering the new post types,
        // so existing content is already on the metcpt_* slugs when they register.
        if ( function_exists( 'metcpt_maybe_migrate_from_haraka' ) ) {
            metcpt_maybe_migrate_from_haraka();
        }
        metcpt_register_post_types();
        metcpt_error_log_create_table();
        flush_rewrite_rules();
        // Stamp the rewrite version so the admin_init auto-flush (see
        // post-types.php) does not redundantly flush again on first admin load.
        update_option( 'metcpt_rewrite_version', METCPT_VERSION );
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}