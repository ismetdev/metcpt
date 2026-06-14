<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function haraka_register_post_types() {

    // ── Events CPT ────────────────────────────────────────────────────────────
    register_post_type( 'hrk_event', array(
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
        'has_archive'         => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'           => 'dashicons-calendar-alt',
        'rewrite'             => array( 'slug' => 'events' ),
        'menu_position'       => 5,
    ) );

    // ── Tenders CPT ───────────────────────────────────────────────────────────
    register_post_type( 'hrk_tender', array(
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
        'has_archive'         => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'           => 'dashicons-media-document',
        'rewrite'             => array( 'slug' => 'tenders' ),
        'menu_position'       => 6,
    ) );

    // ── Companies CPT ─────────────────────────────────────────────────────────
    register_post_type( 'hrk_company', array(
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
    register_post_type( 'hrk_career', array(
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
        'has_archive'         => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'editor', 'excerpt' ),
        'menu_icon'           => 'dashicons-id-alt',
        'rewrite'             => array( 'slug' => 'careers' ),
        'menu_position'       => 7,
    ) );
}
add_action( 'init', 'haraka_register_post_types' );