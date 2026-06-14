<?php
/**
 * Plugin Name:       Haraka
 * Plugin URI:        https://v2.iiumholdings.com.my
 * Description:       Corporate Content Hub for WordPress. Manage Events, Tenders, and Careers with beautiful structured listings and full single-page templates. Built for IIUM Holdings.
 * Version:           1.0.0
 * Author:            Ismet Fitri
 * Author URI:        https://v2.iiumholdings.com.my
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
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

// Core — always needed
require_once HARAKA_PLUGIN_DIR . 'includes/post-types.php';
require_once HARAKA_PLUGIN_DIR . 'includes/admin/cron.php';

// Admin only
if ( is_admin() ) {
    require_once HARAKA_PLUGIN_DIR . 'includes/admin/settings-page.php';
    require_once HARAKA_PLUGIN_DIR . 'includes/admin/settings-fields.php';
    require_once HARAKA_PLUGIN_DIR . 'includes/admin/dashboard-widget.php';
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

// ── Enqueue frontend styles ───────────────────────────────────────────────────
function haraka_enqueue_styles() {

    // General styles always load
    wp_enqueue_style(
        'haraka-general',
        HARAKA_PLUGIN_URL . 'assets/style-general.css',
        array(),
        HARAKA_VERSION
    );

    // Events
    if ( is_singular( 'hrk_event' ) || is_post_type_archive( 'hrk_event' ) || haraka_page_has_shortcode( 'events_list' ) ) {
        wp_enqueue_style(
            'haraka-events',
            HARAKA_PLUGIN_URL . 'assets/style-events.css',
            array(),
            HARAKA_VERSION
        );
    }

    // Tenders
    if ( is_singular( 'hrk_tender' ) || is_post_type_archive( 'hrk_tender' ) || haraka_page_has_shortcode( 'tenders_list' ) || haraka_page_has_shortcode( 'tenders_preview' ) ) {
        wp_enqueue_style(
            'haraka-tenders',
            HARAKA_PLUGIN_URL . 'assets/style-tenders.css',
            array(),
            HARAKA_VERSION
        );
    }

    // Careers
    if ( is_singular( 'hrk_career' ) || is_post_type_archive( 'hrk_career' ) || haraka_page_has_shortcode( 'careers_list' ) || haraka_page_has_shortcode( 'careers_preview' ) ) {
        wp_enqueue_style(
            'haraka-careers',
            HARAKA_PLUGIN_URL . 'assets/style-careers.css',
            array(),
            HARAKA_VERSION
        );
    }

    // Posts
    if ( haraka_page_has_shortcode( 'news_grid' ) || haraka_page_has_shortcode( 'category_posts' ) ) {
        wp_enqueue_style(
            'haraka-posts',
            HARAKA_PLUGIN_URL . 'assets/style-posts.css',
            array(),
            HARAKA_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'haraka_enqueue_styles' );

// ── Helper: check shortcode in page ───────────────────────────────────────────
function haraka_page_has_shortcode( $shortcode ) {
    global $post;
    return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode );
}

// ── Enqueue admin styles ──────────────────────────────────────────────────────
function haraka_enqueue_admin_styles( $hook ) {
    $allowed_hooks = array(
        'post.php',
        'post-new.php',
        'index.php',
        'settings_page_haraka-settings',
    );
    if ( ! in_array( $hook, $allowed_hooks ) ) {
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

// ── Activation hook ───────────────────────────────────────────────────────────
function haraka_activate() {
    haraka_register_post_types();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'haraka_activate' );

// ── Deactivation hook ─────────────────────────────────────────────────────────
function haraka_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'haraka_deactivate' );