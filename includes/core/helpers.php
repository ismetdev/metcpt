<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared, standalone helpers used across the plugin.
 */
function metcpt_page_has_shortcode( $shortcode ) {
    global $post;
    return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode );
}
