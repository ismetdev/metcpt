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
define( 'HARAKA_VERSION',     '1.0.0' );
define( 'HARAKA_PLUGIN_FILE', __FILE__ );
define( 'HARAKA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'HARAKA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// ── Helpers ───────────────────────────────────────────────────────────────────
function haraka_page_has_shortcode( $shortcode ) {
    global $post;
    return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode );
}

// ── Auto-updater — checks GitHub releases for updates ─────────────────────────
require_once HARAKA_PLUGIN_DIR . 'libs/plugin-update-checker/plugin-update-checker.php';
$haraka_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/ismetdev/haraka/',
    HARAKA_PLUGIN_FILE,
    'haraka'
);
$haraka_update_checker->setBranch( 'main' );
$haraka_update_checker->getVcsApi()->enableReleaseAssets();

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once HARAKA_PLUGIN_DIR . 'includes/core/class-haraka.php';
require_once HARAKA_PLUGIN_DIR . 'includes/admin/error-log.php';
Haraka::instance();

// ── Ensure error log table exists — runs immediately on load ──────────────────
haraka_error_log_create_table();