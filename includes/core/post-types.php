<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function metcpt_register_post_types() {

    // ── Events CPT ────────────────────────────────────────────────────────────
    register_post_type( 'metcpt_event', array(
        'labels' => array(
            'name'               => 'Events',
            'singular_name'      => 'Event',
            'add_new'            => 'Add New Event',
            'add_new_item'       => 'Add New Event',
            'edit_item'          => 'Edit Event',
            'new_item'           => 'New Event',
            'view_item'          => 'View Event',
            'search_items'       => 'Search Events',
            'not_found'          => 'No events found',
            'not_found_in_trash' => 'No events found in Trash',
            'menu_name'          => 'Events',
        ),
        'public'              => true,
        'has_archive'         => false,
        // Hidden from the admin menu once events are migrated to native posts
        // (see includes/admin/migrate-events-to-posts.php), but the post type
        // stays registered so migrated posts' old /event/<slug>/ URLs still
        // resolve to a query var for the redirect handler, and so the CPT can
        // be shown again if ever needed.
        'show_in_menu'        => ! get_option( 'metcpt_hide_events_cpt_menu', 0 ),
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'           => 'dashicons-calendar-alt',
        'rewrite'             => array( 'slug' => 'event' ),
        'menu_position'       => 5,
    ) );

    // ── Tenders CPT ───────────────────────────────────────────────────────────
    register_post_type( 'metcpt_tender', array(
        'labels' => array(
            'name'               => 'Tenders',
            'singular_name'      => 'Tender',
            'add_new'            => 'Add New Tender',
            'add_new_item'       => 'Add New Tender',
            'edit_item'          => 'Edit Tender',
            'new_item'           => 'New Tender',
            'view_item'          => 'View Tender',
            'search_items'       => 'Search Tenders',
            'not_found'          => 'No tenders found',
            'not_found_in_trash' => 'No tenders found in Trash',
            'menu_name'          => 'Tenders',
        ),
        'public'              => true,
        'has_archive'         => false,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'           => 'dashicons-media-document',
        'rewrite'             => array( 'slug' => 'tender' ),
        'menu_position'       => 6,
    ) );

    // ── Companies CPT ─────────────────────────────────────────────────────────
    register_post_type( 'metcpt_company', array(
        'labels' => array(
            'name'               => 'Companies',
            'singular_name'      => 'Company',
            'add_new'            => 'Add New Company',
            'add_new_item'       => 'Add New Company',
            'edit_item'          => 'Edit Company',
            'new_item'           => 'New Company',
            'view_item'          => 'View Company',
            'search_items'       => 'Search Companies',
            'not_found'          => 'No companies found',
            'not_found_in_trash' => 'No companies found in Trash',
            'menu_name'          => 'Companies',
        ),
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'thumbnail' ),
        'menu_icon'           => 'dashicons-building',
        'menu_position'       => 8,
        'has_archive'         => false,
        'rewrite'             => false,
    ) );

    // ── Careers CPT ───────────────────────────────────────────────────────────
    register_post_type( 'metcpt_career', array(
        'labels' => array(
            'name'               => 'Careers',
            'singular_name'      => 'Career',
            'add_new'            => 'Add New Position',
            'add_new_item'       => 'Add New Position',
            'edit_item'          => 'Edit Position',
            'new_item'           => 'New Position',
            'view_item'          => 'View Position',
            'search_items'       => 'Search Positions',
            'not_found'          => 'No positions found',
            'not_found_in_trash' => 'No positions found in Trash',
            'menu_name'          => 'Careers',
        ),
        'public'              => true,
        'has_archive'         => false,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'editor', 'excerpt' ),
        'menu_icon'           => 'dashicons-id-alt',
        'rewrite'             => array( 'slug' => 'career' ),
        'menu_position'       => 7,
    ) );
}
add_action( 'init', 'metcpt_register_post_types' );


/**
 * Flush rewrite rules once after a plugin update.
 *
 * WordPress runs the activation hook (which flushes) only on activation, never
 * on update — and MetCPT updates arrive via the update screen. Without this, a
 * release that changes CPT/rewrite registration would leave stale rewrite rules
 * until an admin manually visited Settings → Permalinks → Save.
 *
 * Version-gated so the expensive flush_rewrite_rules() runs at most once per
 * version. Post types are already registered here (init has fired). Admin-only:
 * updates are admin-initiated, so this fires in the same session as the update.
 * Mirrors the version-gate pattern used for metcpt_error_log_db_version.
 */
function metcpt_maybe_flush_rewrite_rules() {
    if ( get_option( 'metcpt_rewrite_version' ) === METCPT_VERSION ) {
        return;
    }
    flush_rewrite_rules();
    update_option( 'metcpt_rewrite_version', METCPT_VERSION );
}
add_action( 'admin_init', 'metcpt_maybe_flush_rewrite_rules' );