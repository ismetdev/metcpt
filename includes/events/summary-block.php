<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Front-end summary block for event posts.
 *
 * Injects a short block of event facts (date, time, venue, organiser) into
 * ordinary WordPress posts that have the MetCPT event tick box on. Uses the
 * the_content filter only — never a single_template filter — so the theme's
 * own single post template keeps rendering everything else unchanged. See
 * PLAN/PRD-events-as-posts.md, decision 6.
 */

// ── Is this post an event? ──────────────────────────────────────────────────────
function metcpt_post_is_event( $post_id ) {
    return (bool) get_post_meta( $post_id, 'metcpt_is_event', true );
}


// ── Where should the summary render on this post? ──────────────────────────────
function metcpt_event_summary_position( $post_id ) {
    $position = get_post_meta( $post_id, 'metcpt_event_summary_position', true );

    if ( empty( $position ) ) {
        $position = get_option( 'metcpt_event_summary_position_default', 'top' );
    }

    $allowed = array( 'top', 'bottom', 'both', 'none' );
    return in_array( $position, $allowed, true ) ? $position : 'top';
}


// ── Render the summary markup ───────────────────────────────────────────────────
//
// Date, time, venue, organiser only — decision 3 in the PRD. Empty fields are
// skipped. Returns '' when the post is not an event.
function metcpt_render_event_summary( $post_id ) {

    if ( ! metcpt_post_is_event( $post_id ) ) {
        return '';
    }

    $date      = get_post_meta( $post_id, 'event_date',      true );
    $time      = get_post_meta( $post_id, 'event_time',      true );
    $venue     = get_post_meta( $post_id, 'event_venue',     true );
    $organiser = get_post_meta( $post_id, 'event_organiser', true );

    $date_label = '';
    if ( ! empty( $date ) ) {
        $date_obj   = date_create( $date );
        $date_label = $date_obj ? $date_obj->format( 'j F Y' ) : '';
    }

    $rows = array();
    if ( ! empty( $date_label ) ) {
        $rows['date'] = array( 'label' => 'Date', 'value' => $date_label );
    }
    if ( ! empty( $time ) ) {
        $rows['time'] = array( 'label' => 'Time', 'value' => $time );
    }
    if ( ! empty( $venue ) ) {
        $rows['venue'] = array( 'label' => 'Venue', 'value' => $venue );
    }
    if ( ! empty( $organiser ) ) {
        $rows['organiser'] = array( 'label' => 'Organiser', 'value' => $organiser );
    }

    if ( empty( $rows ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="mcpt-event-summary">
        <dl class="mcpt-event-summary-list">
            <?php foreach ( $rows as $key => $row ) : ?>
                <div class="mcpt-event-summary-row mcpt-event-summary-<?php echo esc_attr( $key ); ?>">
                    <dt><?php echo esc_html( $row['label'] ); ?></dt>
                    <dd><?php echo esc_html( $row['value'] ); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
    <?php
    return ob_get_clean();
}


// ── Inject the summary into the_content ─────────────────────────────────────────
function metcpt_event_summary_content( $content ) {

    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $post_id = get_the_ID();

    if ( ! metcpt_post_is_event( $post_id ) ) {
        return $content;
    }

    $position = metcpt_event_summary_position( $post_id );

    if ( 'none' === $position ) {
        return $content;
    }

    $summary = metcpt_render_event_summary( $post_id );

    if ( empty( $summary ) ) {
        return $content;
    }

    if ( 'bottom' === $position ) {
        return $content . $summary;
    }

    if ( 'both' === $position ) {
        return $summary . $content . $summary;
    }

    // Default: top.
    return $summary . $content;
}
add_filter( 'the_content', 'metcpt_event_summary_content' );


// ── Event JSON-LD for search engines ─────────────────────────────────────────────
//
// Only Yoast's own post types get Event schema; plain posts do not, so this
// fills that gap for event posts. Emits nothing without a usable event date,
// since startDate is required for the schema to validate.
function metcpt_event_schema_markup() {

    if ( ! is_singular( 'post' ) ) {
        return;
    }

    $post_id = get_the_ID();

    if ( ! metcpt_post_is_event( $post_id ) ) {
        return;
    }

    $date = get_post_meta( $post_id, 'event_date', true );
    if ( empty( $date ) ) {
        return;
    }

    $date_obj = date_create( $date );
    if ( ! $date_obj ) {
        return;
    }

    $time = get_post_meta( $post_id, 'event_time', true );
    // event_time is free text (e.g. "9:00 AM - 5:00 PM"), not a parseable
    // start time, so it is not merged into the ISO 8601 startDate. The date
    // alone is still a valid Event schema.
    unset( $time );

    $venue     = get_post_meta( $post_id, 'event_venue',     true );
    $organiser = get_post_meta( $post_id, 'event_organiser', true );

    $schema = array(
        '@context'  => 'https://schema.org',
        '@type'     => 'Event',
        'name'      => get_the_title( $post_id ),
        'startDate' => $date_obj->format( 'Y-m-d' ),
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'eventStatus'         => 'https://schema.org/EventScheduled',
        'url'       => get_permalink( $post_id ),
    );

    if ( ! empty( $venue ) ) {
        $schema['location'] = array(
            '@type' => 'Place',
            'name'  => $venue,
        );
    } else {
        // Event schema requires a location; without a venue, fall back to the
        // site itself rather than emit an invalid block with no location.
        $schema['location'] = array(
            '@type' => 'VirtualLocation',
            'url'   => get_permalink( $post_id ),
        );
    }

    if ( ! empty( $organiser ) ) {
        $schema['organizer'] = array(
            '@type' => 'Organization',
            'name'  => $organiser,
        );
    }

    if ( has_post_thumbnail( $post_id ) ) {
        $schema['image'] = array( get_the_post_thumbnail_url( $post_id, 'large' ) );
    }

    $excerpt = get_the_excerpt( $post_id );
    if ( ! empty( $excerpt ) ) {
        $schema['description'] = wp_strip_all_tags( $excerpt );
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
}
add_action( 'wp_head', 'metcpt_event_schema_markup' );


// ── Point the theme's back link at the events listing ────────────────────────────
//
// met_hello_child_back_link_url() defaults to the Newsroom archive and is
// filterable for exactly this — but single.php only calls it when the post has
// no category. Every event post carries the auto-assigned Events category (see
// metcpt_assign_events_category()), so that branch never runs; the theme takes
// its own get_term_link( $primary_term ) branch instead, landing on
// /category/events/. Kept below as a harmless fallback for the rare case an
// event post ends up with no category at all.
function metcpt_event_back_link_url( $url ) {

    if ( ! is_singular( 'post' ) ) {
        return $url;
    }

    if ( ! metcpt_post_is_event( get_the_ID() ) ) {
        return $url;
    }

    return get_option( 'metcpt_events_archive_url', '/events' );
}
add_filter( 'met_hello_child_back_link_url', 'metcpt_event_back_link_url' );


// ── The real fix: intercept the Events category term link itself ────────────────
//
// Scoped to the exact call site in the theme's single.php: inside the main
// loop, on a singular post, only for the 'events' category term. No other
// page, widget, or listing on the site resolves that term's link during this
// window, so this does not affect the Events category archive anywhere else —
// only this one back-link href on this one page.
function metcpt_event_back_link_term_url( $url, $term, $taxonomy ) {

    if ( 'category' !== $taxonomy || ! isset( $term->slug ) || 'events' !== $term->slug ) {
        return $url;
    }

    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $url;
    }

    if ( ! metcpt_post_is_event( get_the_ID() ) ) {
        return $url;
    }

    return get_option( 'metcpt_events_archive_url', '/events' );
}
add_filter( 'term_link', 'metcpt_event_back_link_term_url', 10, 3 );
