<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Button-triggered migration: metcpt_event CPT posts → native posts.
 *
 * Converts every metcpt_event post into an ordinary post with the MetCPT event
 * meta already set, so it appears in the Add Post screen and the [events_list]
 * listing exactly like any newly created event. Runs only when an admin clicks
 * the button on Settings → Events — never automatically on plugin update. See
 * PLAN/PRD-events-as-posts.md, decision 8.
 *
 * Idempotent without a separate guard option: a migrated post's post_type is
 * no longer metcpt_event, so it is not matched again on a repeat run.
 *
 * Old /event/<slug>/ URLs keep working via metcpt_events_legacy_redirect()
 * below, which 301s them to the new post permalink using the slug stored in
 * metcpt_legacy_event_slug at migration time.
 *
 * @package MetCPT
 * @subpackage Admin
 */

// ── How many metcpt_event posts are left to migrate ────────────────────────────
function metcpt_events_migration_stats() {
    $query = new WP_Query( array(
        'post_type'      => 'metcpt_event',
        'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ) );

    return array(
        'remaining' => $query->found_posts,
    );
}


// ── AJAX: dry run — list what would change, nothing is written ─────────────────
function metcpt_ajax_migrate_events_preview() {
    check_ajax_referer( 'metcpt_migrate_events', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    $query = new WP_Query( array(
        'post_type'      => 'metcpt_event',
        'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ) );

    $items = array();
    foreach ( $query->posts as $post ) {
        $items[] = array(
            'id'     => $post->ID,
            'title'  => get_the_title( $post ) ? get_the_title( $post ) : '(no title)',
            'status' => $post->post_status,
            'slug'   => $post->post_name,
        );
    }

    wp_send_json_success( array(
        'count' => count( $items ),
        'items' => $items,
    ) );
}
add_action( 'wp_ajax_metcpt_migrate_events_preview', 'metcpt_ajax_migrate_events_preview' );


// ── AJAX: run the migration ─────────────────────────────────────────────────────
function metcpt_ajax_migrate_events_run() {
    check_ajax_referer( 'metcpt_migrate_events', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    $query = new WP_Query( array(
        'post_type'      => 'metcpt_event',
        'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ) );

    $migrated = 0;
    $failed   = array();

    foreach ( $query->posts as $post ) {
        $result = wp_update_post(
            array(
                'ID'        => $post->ID,
                'post_type' => 'post',
            ),
            true
        );

        if ( is_wp_error( $result ) ) {
            $failed[] = array(
                'id'    => $post->ID,
                'title' => get_the_title( $post ),
                'error' => $result->get_error_message(),
            );
            continue;
        }

        update_post_meta( $post->ID, 'metcpt_is_event', '1' );
        update_post_meta( $post->ID, 'metcpt_legacy_event_slug', $post->post_name );

        if ( function_exists( 'metcpt_assign_events_category' ) ) {
            metcpt_assign_events_category( $post->ID );
        }

        $migrated++;
    }

    wp_send_json_success( array(
        'migrated' => $migrated,
        'failed'   => $failed,
        'message'  => sprintf(
            '%d event%s migrated.%s',
            $migrated,
            1 === $migrated ? '' : 's',
            empty( $failed ) ? '' : ' ' . count( $failed ) . ' failed, see console.'
        ),
    ) );
}
add_action( 'wp_ajax_metcpt_migrate_events_run', 'metcpt_ajax_migrate_events_run' );


// ── Front end: redirect old /event/<slug>/ URLs to the migrated post ──────────
//
// The metcpt_event rewrite slug ('event') stays registered (see
// includes/core/post-types.php) so this query var still populates on
// /event/<slug>/ requests. Once a post is migrated away from the metcpt_event
// post type, that URL 404s — is_404() here catches exactly that case, so an
// event not yet migrated (rollback, or migration not yet run) keeps resolving
// to its normal CPT single page and is left alone.
function metcpt_events_legacy_redirect() {
    if ( ! is_404() ) {
        return;
    }

    $legacy_slug = get_query_var( 'metcpt_event' );
    if ( empty( $legacy_slug ) ) {
        return;
    }

    $matches = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'meta_key'       => 'metcpt_legacy_event_slug',
        'meta_value'     => $legacy_slug,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ) );

    if ( empty( $matches ) ) {
        return;
    }

    wp_safe_redirect( get_permalink( $matches[0] ), 301 );
    exit;
}
add_action( 'template_redirect', 'metcpt_events_legacy_redirect' );
