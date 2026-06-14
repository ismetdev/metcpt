<?php
/**
 * Plugin Name:       Haraka
 * Plugin URI:        https://v2.iiumholdings.com.my
 * Description:       Corporate Content Hub for WordPress. Manage Events, Tenders, and Careers with beautiful structured listings and full single-page templates. Built for IIUM Holdings.
 * Version:           1.0.0
 * Author:            Ismet Fitri
 * Author URI:        https://v2.iiumholdings.com.my
 * License:           GPL2
 * Text Domain:       haraka
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'HARAKA_VERSION',    '1.0.0' );
define( 'HARAKA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HARAKA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ── Load all modules ──────────────────────────────────────────────────────────
require_once HARAKA_PLUGIN_DIR . 'includes/post-types.php';
require_once HARAKA_PLUGIN_DIR . 'includes/shortcode-general.php';
require_once HARAKA_PLUGIN_DIR . 'includes/admin/settings-page.php';
require_once HARAKA_PLUGIN_DIR . 'includes/admin/settings-fields.php';
require_once HARAKA_PLUGIN_DIR . 'includes/admin/cron.php';
require_once HARAKA_PLUGIN_DIR . 'includes/admin/dashboard-widget.php';
require_once HARAKA_PLUGIN_DIR . 'includes/careers/meta-boxes-company.php';
require_once HARAKA_PLUGIN_DIR . 'includes/careers/meta-boxes-career.php';
require_once HARAKA_PLUGIN_DIR . 'includes/careers/shortcode-list.php';
require_once HARAKA_PLUGIN_DIR . 'includes/careers/shortcode-preview.php';
require_once HARAKA_PLUGIN_DIR . 'includes/careers/template-single.php';
require_once HARAKA_PLUGIN_DIR . 'includes/events/meta-boxes.php';
require_once HARAKA_PLUGIN_DIR . 'includes/events/shortcode-list.php';
require_once HARAKA_PLUGIN_DIR . 'includes/events/template-single.php';
require_once HARAKA_PLUGIN_DIR . 'includes/tenders/meta-boxes.php';
require_once HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-list.php';
require_once HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-preview.php';
require_once HARAKA_PLUGIN_DIR . 'includes/tenders/template-single.php';
require_once HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-template-b.php';
require_once HARAKA_PLUGIN_DIR . 'includes/posts/shortcode-news-grid.php';

// ── Enqueue frontend styles ───────────────────────────────────────────────────
function haraka_enqueue_styles() {
    wp_enqueue_style(
        'haraka-general',
        HARAKA_PLUGIN_URL . 'assets/style-general.css',
        array(),
        HARAKA_VERSION
    );
    wp_enqueue_style(
        'haraka-events',
        HARAKA_PLUGIN_URL . 'assets/style-events.css',
        array(),
        HARAKA_VERSION
    );
    wp_enqueue_style(
        'haraka-tenders',
        HARAKA_PLUGIN_URL . 'assets/style-tenders.css',
        array(),
        HARAKA_VERSION
    );
    wp_enqueue_style(
        'haraka-careers',
        HARAKA_PLUGIN_URL . 'assets/style-careers.css',
        array(),
        HARAKA_VERSION
    );
    wp_enqueue_style(
        'haraka-posts',
        HARAKA_PLUGIN_URL . 'assets/style-posts.css',
        array(),
        HARAKA_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'haraka_enqueue_styles' );

// ── Enqueue admin styles ──────────────────────────────────────────────────────
function haraka_enqueue_admin_styles( $hook ) {
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
        return;
    }
    wp_enqueue_style(
        'haraka-admin',
        HARAKA_PLUGIN_URL . 'assets/style-admin.css',
        array(),
        HARAKA_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'haraka_enqueue_admin_styles' );

// ── Flush rewrite rules on activation ────────────────────────────────────────
function haraka_activate() {
    haraka_register_post_types();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'haraka_activate' );

// ── Flush rewrite rules on deactivation ──────────────────────────────────────
function haraka_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'haraka_deactivate' );
