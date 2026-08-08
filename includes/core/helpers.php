<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared, standalone helpers used across the plugin.
 */

/**
 * Whether the current page uses a given shortcode.
 *
 * Checks classic post_content, and also the raw Elementor data. The listing
 * Pages (/events/, /tenders/, /careers/) are Elementor built and hold the
 * shortcode inside a widget, not in post_content, so a post_content-only check
 * would miss them and load no CSS.
 *
 * @param string $shortcode Shortcode tag, without brackets.
 * @return bool
 */
function metcpt_page_has_shortcode( $shortcode ) {
    global $post;

    if ( ! is_a( $post, 'WP_Post' ) ) {
        return false;
    }

    if ( has_shortcode( $post->post_content, $shortcode ) ) {
        return true;
    }

    // Elementor stores the shortcode string in the _elementor_data meta JSON,
    // e.g. "[events_list]". Match the opening bracket plus the tag.
    $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
    if ( is_string( $elementor_data ) && $elementor_data !== '' ) {
        return strpos( $elementor_data, '[' . $shortcode ) !== false;
    }

    return false;
}
