<?php
/**
 * Plugin Name:       MetCPT WP
 * Plugin URI:        https://github.com/ismetdev/metcpt
 * Description:       Corporate Content Hub for WordPress. Manage Events, Tenders, and Careers with beautiful structured listings and full single-page templates. Built for IIUM Holdings.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ismetdev
 * Author URI:        https://github.com/ismetdev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       metcpt
 *
 * @package MetCPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'METCPT_VERSION',  '1.2.0' );
define( 'METCPT_FILE',     __FILE__ );
define( 'METCPT_PATH',     plugin_dir_path( __FILE__ ) );
define( 'METCPT_URL',      plugin_dir_url( __FILE__ ) );
define( 'METCPT_BASENAME', plugin_basename( __FILE__ ) );

// ── Helpers ───────────────────────────────────────────────────────────────────
function metcpt_page_has_shortcode( $shortcode ) {
    global $post;
    return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode );
}

// ── Auto-updater — checks GitHub releases for updates ─────────────────────────
require_once METCPT_PATH . 'libs/plugin-update-checker/plugin-update-checker.php';
$metcpt_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/ismetdev/metcpt/',
    METCPT_FILE,
    'metcpt'
);
$metcpt_update_checker->setBranch( 'main' );

// Authenticate with GitHub when a token is provided (required for private repos).
// The token is defined in wp-config.php as METCPT_GITHUB_TOKEN and is never
// committed to the repository. If it is absent, the checker runs unauthenticated,
// which still works while the repository is public.
if ( defined( 'METCPT_GITHUB_TOKEN' ) && METCPT_GITHUB_TOKEN ) {
    $metcpt_update_checker->setAuthentication( METCPT_GITHUB_TOKEN );
}

$metcpt_update_checker->getVcsApi()->enableReleaseAssets();

// ── One-time rebrand migration (Haraka → MetCPT) ──────────────────────────────
// Moves legacy Haraka data (post types, options, meta, table, cron) onto the new
// MetCPT names. Runs once, guarded by an option, then never touches the DB again.
// Must run BEFORE the error-log table is auto-created below, so the legacy table
// can be renamed instead of a fresh empty one taking its place.
require_once METCPT_PATH . 'includes/core/migrate-from-haraka.php';
metcpt_maybe_migrate_from_haraka();

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once METCPT_PATH . 'includes/core/class-metcpt.php';
require_once METCPT_PATH . 'includes/admin/error-log.php';
MetCPT::instance();

// ── Ensure error log table exists — runs immediately on load ──────────────────
metcpt_error_log_create_table();
