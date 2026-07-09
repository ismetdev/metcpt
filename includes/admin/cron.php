<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Schedule daily cron on plugin activation ───────────────────────────────
function metcpt_schedule_cron() {
    if ( ! wp_next_scheduled( 'metcpt_daily_tender_check' ) ) {
        wp_schedule_event( time(), 'daily', 'metcpt_daily_tender_check' );
    }
}
add_action( 'wp', 'metcpt_schedule_cron' );


// ── Clear cron on plugin deactivation ──────────────────────────────────────
function metcpt_clear_cron() {
    $timestamp = wp_next_scheduled( 'metcpt_daily_tender_check' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'metcpt_daily_tender_check' );
    }
}
register_deactivation_hook(
    plugin_dir_path( __DIR__ ) . '../../metcpt.php',
    'metcpt_clear_cron'
);


// ── Main daily task ────────────────────────────────────────────────────────
function metcpt_daily_tender_check() {
    $today     = date( 'Y-m-d' );
    $threshold = (int) get_option( 'metcpt_closing_soon_days', 7 );

    // Query all published tenders
    $query = new WP_Query( array(
        'post_type'      => 'metcpt_tender',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'tender_close_date',
                'compare' => 'EXISTS',
            ),
        ),
    ) );

    if ( ! $query->have_posts() ) {
        return;
    }

    $notify_tenders = array();

    while ( $query->have_posts() ) {
        $query->the_post();
        $post_id    = get_the_ID();
        $close_date = get_post_meta( $post_id, 'tender_close_date', true );

        if ( empty( $close_date ) ) {
            continue;
        }

        $date_obj = date_create( $close_date );
        if ( ! $date_obj ) {
            continue;
        }

        $today_obj  = new DateTime( 'today' );
        $days_left  = (int) $today_obj->diff( $date_obj )->days;
        $is_past    = $date_obj < $today_obj;

        // ── Flag tender as notified to avoid duplicate emails ──────────────
        $already_notified = get_post_meta( $post_id, 'metcpt_closing_notified', true );

        // Send email if within threshold and not already notified
        if ( ! $is_past && $days_left <= $threshold && ! $already_notified ) {
            $notify_tenders[] = array(
                'id'         => $post_id,
                'title'      => get_the_title(),
                'ref'        => get_post_meta( $post_id, 'tender_ref',  true ),
                'close_date' => $date_obj->format( 'd M Y' ),
                'days_left'  => $days_left,
                'url'        => get_permalink(),
            );
            // Mark as notified so we don't email again
            update_post_meta( $post_id, 'metcpt_closing_notified', '1' );
        }

        // ── Reset notification flag if tender is re-opened ─────────────────
        // (e.g. close date extended — allow new notification)
        if ( $is_past ) {
            delete_post_meta( $post_id, 'metcpt_closing_notified' );
        }
    }

    wp_reset_postdata();

    // ── Send email if there are tenders to notify ──────────────────────────
    if ( ! empty( $notify_tenders ) ) {
        metcpt_send_closing_notification( $notify_tenders );
    }
}
add_action( 'metcpt_daily_tender_check', 'metcpt_daily_tender_check' );


// ── Send closing notification email ───────────────────────────────────────
function metcpt_send_closing_notification( $tenders ) {

    $recipient  = get_option( 'metcpt_notify_email', get_option( 'admin_email' ) );
    $site_name  = get_bloginfo( 'name' );
    $threshold  = (int) get_option( 'metcpt_closing_soon_days', 7 );

    if ( empty( $recipient ) ) {
        return;
    }

    $count   = count( $tenders );
    $subject = '[' . $site_name . '] ' . $count . ' Tender' . ( $count > 1 ? 's' : '' ) . ' Closing Soon';

    // ── Build email body ───────────────────────────────────────────────────
    $body  = 'Dear Administrator,' . "\n\n";
    $body .= 'This is an automated reminder from MetCPT on ' . $site_name . '.' . "\n\n";
    $body .= 'The following ' . ( $count > 1 ? 'tenders are' : 'tender is' ) . ' closing within ' . $threshold . ' days:' . "\n\n";
    $body .= str_repeat( '-', 60 ) . "\n\n";

    foreach ( $tenders as $tender ) {
        $body .= 'Title      : ' . $tender['title'] . "\n";
        $body .= 'Reference  : ' . ( $tender['ref'] ? $tender['ref'] : 'N/A' ) . "\n";
        $body .= 'Closing On : ' . $tender['close_date'] . "\n";
        $body .= 'Days Left  : ' . $tender['days_left'] . ' day' . ( $tender['days_left'] !== 1 ? 's' : '' ) . "\n";
        $body .= 'View       : ' . $tender['url'] . "\n\n";
        $body .= str_repeat( '-', 60 ) . "\n\n";
    }

    $body .= 'Please ensure these tenders are handled before their closing dates.' . "\n\n";
    $body .= 'This email was sent automatically by MetCPT — Corporate Content Hub.' . "\n";
    $body .= 'To manage notification settings, visit: ' . admin_url( 'admin.php?page=metcpt-settings&tab=tenders' );

    $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

    wp_mail( $recipient, $subject, $body, $headers );
}


// ── Manual trigger — for testing from admin ────────────────────────────────
function metcpt_manual_cron_trigger() {
    if ( ! isset( $_GET['metcpt_run_cron'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'metcpt_run_cron' ) ) {
        return;
    }
    metcpt_daily_tender_check();
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>MetCPT:</strong> Cron task ran successfully. Check your email if any tenders are closing soon.</p>';
        echo '</div>';
    } );
}
add_action( 'admin_init', 'metcpt_manual_cron_trigger' );