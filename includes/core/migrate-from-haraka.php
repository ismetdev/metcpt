<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * One-time data migration from the legacy "Haraka" plugin to "MetCPT".
 *
 * The rebrand renamed the four post types (hrk_* → metcpt_*), every option
 * (haraka_* → metcpt_*), one post-meta key, the error-log table and two cron
 * hooks. That data already lives in the database from when the plugin was called
 * Haraka, so this routine moves it onto the new names exactly once and records a
 * guard option so it never runs again.
 *
 * It is safe on a brand-new install: with no legacy data present every step is a
 * no-op and only the guard is written.
 *
 * @package MetCPT
 * @return void
 */
function metcpt_maybe_migrate_from_haraka() {

    // Guard — run at most once per site.
    if ( get_option( 'metcpt_migrated_from_haraka' ) ) {
        return;
    }

    global $wpdb;

    // ── 1. Post types: hrk_* → metcpt_* ───────────────────────────────────────
    // Existing posts are stored with the old slug in wp_posts.post_type; without
    // this they would still exist but be invisible under the new post types.
    $post_type_map = array(
        'hrk_event'   => 'metcpt_event',
        'hrk_tender'  => 'metcpt_tender',
        'hrk_company' => 'metcpt_company',
        'hrk_career'  => 'metcpt_career',
    );
    foreach ( $post_type_map as $old => $new ) {
        $wpdb->update(
            $wpdb->posts,
            array( 'post_type' => $new ),
            array( 'post_type' => $old ),
            array( '%s' ),
            array( '%s' )
        );
    }

    // ── 2. Post-meta key: haraka_closing_notified → metcpt_closing_notified ────
    $wpdb->update(
        $wpdb->postmeta,
        array( 'meta_key' => 'metcpt_closing_notified' ),
        array( 'meta_key' => 'haraka_closing_notified' ),
        array( '%s' ),
        array( '%s' )
    );

    // ── 3. Options: haraka_* → metcpt_* ───────────────────────────────────────
    // Rename each legacy option row in place (preserving its value and autoload
    // flag). Never overwrite a metcpt_* option that already exists this request
    // (e.g. metcpt_error_log_db_version); drop the stale legacy row instead.
    $legacy_options = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'haraka\\_%'"
    );
    foreach ( $legacy_options as $old_name ) {
        $new_name = 'metcpt_' . substr( $old_name, strlen( 'haraka_' ) );

        $already = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
            $new_name
        ) );

        if ( $already ) {
            $wpdb->delete( $wpdb->options, array( 'option_name' => $old_name ), array( '%s' ) );
            continue;
        }

        $wpdb->update(
            $wpdb->options,
            array( 'option_name' => $new_name ),
            array( 'option_name' => $old_name ),
            array( '%s' ),
            array( '%s' )
        );
    }

    // ── 4. Transients: rename leftover haraka transient rows ──────────────────
    // These are option rows prefixed with _transient_ / _transient_timeout_, so
    // step 3's 'haraka\_%' pattern does not catch them.
    $wpdb->query(
        "UPDATE {$wpdb->options}
         SET option_name = REPLACE( option_name, 'haraka_', 'metcpt_' )
         WHERE option_name LIKE '\\_transient\\_haraka\\_%'
            OR option_name LIKE '\\_transient\\_timeout\\_haraka\\_%'"
    );

    // ── 5. Error-log table: {prefix}haraka_error_log → {prefix}metcpt_error_log ─
    $old_table = $wpdb->prefix . 'haraka_error_log';
    $new_table = $wpdb->prefix . 'metcpt_error_log';

    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) ) ) {
        // A fresh empty metcpt_error_log may have been created earlier this load.
        // Drop it so the populated legacy table can take the name.
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) ) ) {
            $wpdb->query( "DROP TABLE IF EXISTS `{$new_table}`" );
        }
        $wpdb->query( "RENAME TABLE `{$old_table}` TO `{$new_table}`" );
    }

    // ── 6. Cron: clear the two legacy hooks ───────────────────────────────────
    // The new metcpt_* hooks reschedule themselves (see cron.php / error-log.php),
    // so we only need to unschedule the old ones here.
    foreach ( array( 'haraka_daily_tender_check', 'haraka_purge_error_log' ) as $old_hook ) {
        $timestamp = wp_next_scheduled( $old_hook );
        while ( $timestamp ) {
            wp_unschedule_event( $timestamp, $old_hook );
            $timestamp = wp_next_scheduled( $old_hook );
        }
    }

    // ── Done ──────────────────────────────────────────────────────────────────
    // Flush rewrite rules once after the new post types register this request.
    add_action( 'init', 'flush_rewrite_rules', 99 );

    update_option( 'metcpt_migrated_from_haraka', METCPT_VERSION );
}
