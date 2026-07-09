<?php
/**
 * MetCPT Uninstall Handler
 *
 * Runs only when the plugin is DELETED via WordPress Admin → Plugins.
 * Deactivation alone never triggers this file.
 *
 * Default behaviour: preserve all data (safe uninstall).
 * To enable full cleanup, uncomment the block below.
 *
 * @package MetCPT
 * @version 1.1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
    exit;
}

// ── FULL CLEANUP (disabled by default — uncomment to enable) ──────────────────
/*
global $wpdb;

// Delete all MetCPT CPT posts
foreach ( array( 'metcpt_event', 'metcpt_tender', 'metcpt_career', 'metcpt_company' ) as $post_type ) {
    $ids = get_posts( array(
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    foreach ( $ids as $id ) {
        wp_delete_post( $id, true );
    }
}

// Delete post meta
$meta_keys = array(
    'event_date', 'event_time', 'event_venue', 'event_organizer',
    'event_registration_url', 'event_whatsapp_group', 'event_vips',
    'event_itinerary', 'event_faqs', 'event_dress_code',
    'event_attendance_method', 'event_guidelines',
    'event_contact_name', 'event_contact_phone', 'event_contact_email',
    'tender_ref', 'tender_issuer', 'tender_location', 'tender_category',
    'tender_close_date', 'tender_close_time', 'tender_validity', 'tender_fee',
    'tender_submission_method', 'tender_submission_address',
    'tender_document_url', 'tender_contact_name', 'tender_contact_email',
    'tender_contact_phone', 'metcpt_closing_notified',
    'career_company_id', 'career_department', 'career_location',
    'career_type', 'career_close_date', 'career_salary', 'career_apply_url',
    'career_contact_name', 'career_contact_email', 'career_contact_phone',
    'company_full_name', 'company_short_name', 'company_website',
    'company_address', 'company_description',
);
foreach ( $meta_keys as $key ) {
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $key
    ) );
}

// Delete plugin options
$options = array(
    'metcpt_events_page_url', 'metcpt_tenders_page_url',
    'metcpt_careers_page_url', 'metcpt_closing_soon_days',
    'metcpt_notify_email', 'metcpt_tenders_template',
    'metcpt_tenders_b_label', 'metcpt_tenders_b_headline',
    'metcpt_tenders_b_headline_italic', 'metcpt_tenders_b_all_text',
    'metcpt_tenders_b_view_all_text', 'metcpt_career_departments',
    'metcpt_news_label', 'metcpt_news_headline',
    'metcpt_news_headline_italic', 'metcpt_news_view_all_text',
    'metcpt_news_view_all_url', 'metcpt_news_category',
    'metcpt_accent_color',
);
foreach ( $options as $option ) {
    delete_option( $option );
}

// Clear transients and cron
delete_transient( 'metcpt_dashboard_counts' );
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_metcpt_%'
     OR option_name LIKE '_transient_timeout_metcpt_%'"
);
$timestamp = wp_next_scheduled( 'metcpt_daily_tender_check' );
if ( $timestamp ) {
    wp_unschedule_event( $timestamp, 'metcpt_daily_tender_check' );
}
$timestamp = wp_next_scheduled( 'metcpt_purge_error_log' );
if ( $timestamp ) {
    wp_unschedule_event( $timestamp, 'metcpt_purge_error_log' );
}

// Drop error log table
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}metcpt_error_log" );
delete_option( 'metcpt_error_log_db_version' );
*/