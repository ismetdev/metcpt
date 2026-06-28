<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ──────────────────────────────────────────────────────────────────────────
 * Helper: Category Label
 * ────────────────────────────────────────────────────────────────────────── */
function hrk_get_post_cat_label( $post_id ) {

    $cats = get_the_category( $post_id );

    if ( empty( $cats ) ) {
        return '';
    }

    // Filter out Uncategorized
    $filtered = array_filter( $cats, function( $cat ) {
        return strtolower( $cat->slug ) !== 'uncategorized';
    } );

    if ( ! empty( $filtered ) ) {
        return esc_html( reset( $filtered )->name );
    }

    return esc_html( $cats[0]->name );
}

/* ──────────────────────────────────────────────────────────────────────────
 * Helper: Full Date
 * ────────────────────────────────────────────────────────────────────────── */
function hrk_get_post_date( $post_id, $format = 'd M Y' ) {
    return get_the_date( $format, $post_id );
}

/* ──────────────────────────────────────────────────────────────────────────
 * Helper: Short Date
 * ────────────────────────────────────────────────────────────────────────── */
function hrk_get_post_short_date( $post_id ) {
    return get_the_date( 'd M', $post_id );
}


/* ──────────────────────────────────────────────────────────────────────────
 * News Grid Shortcode
 * ────────────────────────────────────────────────────────────────────────── */
function haraka_news_grid_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'label'           => get_option( 'haraka_news_label', 'Impact & Activities' ),
            'headline'        => get_option( 'haraka_news_headline', 'News, milestones, and' ),
            'headline_italic' => get_option( 'haraka_news_headline_italic', 'community work.' ),
            'view_all_text'   => get_option( 'haraka_news_view_all_text', 'View newsroom' ),
            'view_all_url'    => get_option( 'haraka_news_view_all_url', '/newsroom' ),
            'category'        => get_option( 'haraka_news_category', '' ),
            'posts_per_page'  => 4,
            'show_excerpt'    => 'yes',
            'order'           => 'DESC',
            'orderby'         => 'date',
        ),
        $atts,
        'news_grid'
    );

    // ── Build query args ──────────────────────────────────────────────────
    $query_args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => max( 4, intval( $atts['posts_per_page'] ) ),
        'order'          => sanitize_text_field( $atts['order'] ),
        'orderby'        => sanitize_text_field( $atts['orderby'] ),
    );

    // ── Category filter ───────────────────────────────────────────────────
    if ( ! empty( $atts['category'] ) ) {

        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ),
        );
    }

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return '<p class="hrk-ng-empty">No posts found.</p>';
    }

    $posts = $query->posts;

    wp_reset_postdata();

    // ── Separate featured post from sidebar posts ─────────────────────────
    $featured = array_shift( $posts );
    $sidebar  = array_slice( $posts, 0, 3 );

    // ── Featured post data ────────────────────────────────────────────────
    $f_id      = $featured->ID;
    $f_title   = get_the_title( $f_id );
    $f_url     = get_permalink( $f_id );
    $f_excerpt = get_the_excerpt( $f_id );
    $f_cat     = hrk_get_post_cat_label( $f_id );
    $f_date    = hrk_get_post_date( $f_id, 'd F Y' );

    $f_thumb = has_post_thumbnail( $f_id )
        ? get_the_post_thumbnail_url( $f_id, 'large' )
        : '';

    ob_start();
    ?>

    <div class="hrk-ng-wrap">

        <!-- Header -->
        <div class="hrk-ng-header">

            <div class="hrk-ng-header-left">

                <?php if ( ! empty( $atts['label'] ) ) : ?>
                    <div class="hrk-ng-label">
                        <span class="hrk-ng-label-dash">&mdash;</span>
                        <?php echo esc_html( $atts['label'] ); ?>
                    </div>
                <?php endif; ?>

                <h2 class="hrk-ng-headline">

                    <?php echo esc_html( $atts['headline'] ); ?>

                    <?php if ( ! empty( $atts['headline_italic'] ) ) : ?>
                        <em><?php echo esc_html( $atts['headline_italic'] ); ?></em>
                    <?php endif; ?>

                </h2>

            </div>

            <?php if ( ! empty( $atts['view_all_url'] ) ) : ?>

                <a class="hrk-ng-view-link"
                   href="<?php echo esc_url( $atts['view_all_url'] ); ?>">

                    <?php echo esc_html( $atts['view_all_text'] ); ?>
                    &nbsp;&rarr;

                </a>

            <?php endif; ?>

        </div>

        <!-- Main Grid -->
        <div class="hrk-ng-grid">

            <!-- Featured Post -->
            <article class="hrk-ng-featured">

                <a href="<?php echo esc_url( $f_url ); ?>"
                   class="hrk-ng-featured-img-link">

                    <?php if ( ! empty( $f_thumb ) ) : ?>

                        <img class="hrk-ng-featured-img"
                             src="<?php echo esc_url( $f_thumb ); ?>"
                             alt="<?php echo esc_attr( $f_title ); ?>" />

                    <?php else : ?>

                        <div class="hrk-ng-featured-img hrk-ng-featured-img-placeholder"></div>

                    <?php endif; ?>

                </a>

                <div class="hrk-ng-featured-body">

                    <div class="hrk-ng-meta">

                        <?php if ( ! empty( $f_cat ) ) : ?>
                            <span class="hrk-ng-cat">
                                <?php echo esc_html( $f_cat ); ?>
                            </span>
                        <?php endif; ?>

                        <span class="hrk-ng-date">
                            <?php echo esc_html( $f_date ); ?>
                        </span>

                    </div>

                    <h3 class="hrk-ng-featured-title">

                        <a href="<?php echo esc_url( $f_url ); ?>">
                            <?php echo esc_html( $f_title ); ?>
                        </a>

                    </h3>

                    <?php if ( $atts['show_excerpt'] === 'yes' && ! empty( $f_excerpt ) ) : ?>

                        <p class="hrk-ng-featured-excerpt">
                            <?php echo esc_html( $f_excerpt ); ?>
                        </p>

                    <?php endif; ?>

                    <a class="hrk-ng-read-link"
                       href="<?php echo esc_url( $f_url ); ?>">

                        Read story &nbsp;&rarr;

                    </a>

                </div>

            </article>

            <!-- Sidebar Posts -->
            <div class="hrk-ng-sidebar">

                <?php foreach ( $sidebar as $s_post ) : ?>

                    <?php
                    $s_id    = $s_post->ID;
                    $s_title = get_the_title( $s_id );
                    $s_url   = get_permalink( $s_id );
                    $s_cat   = hrk_get_post_cat_label( $s_id );
                    $s_date  = hrk_get_post_short_date( $s_id );

                    $s_thumb = has_post_thumbnail( $s_id )
                        ? get_the_post_thumbnail_url( $s_id, 'medium' )
                        : '';
                    ?>

                    <article class="hrk-ng-small-post">

                        <a href="<?php echo esc_url( $s_url ); ?>"
                           class="hrk-ng-small-thumb-link">

                            <?php if ( ! empty( $s_thumb ) ) : ?>

                                <img class="hrk-ng-small-thumb"
                                     src="<?php echo esc_url( $s_thumb ); ?>"
                                     alt="<?php echo esc_attr( $s_title ); ?>" />

                            <?php else : ?>

                                <div class="hrk-ng-small-thumb hrk-ng-small-thumb-placeholder"></div>

                            <?php endif; ?>

                        </a>

                        <div class="hrk-ng-small-body">

                            <div class="hrk-ng-small-meta">

                                <?php if ( ! empty( $s_cat ) ) : ?>

                                    <span class="hrk-ng-small-cat">
                                        <?php echo esc_html( $s_cat ); ?>
                                    </span>

                                    <span class="hrk-ng-small-dot">&middot;</span>

                                <?php endif; ?>

                                <span class="hrk-ng-small-date">
                                    <?php echo esc_html( $s_date ); ?>
                                </span>

                            </div>

                            <h4 class="hrk-ng-small-title">

                                <a href="<?php echo esc_url( $s_url ); ?>">
                                    <?php echo esc_html( $s_title ); ?>
                                </a>

                            </h4>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode( 'news_grid', 'haraka_news_grid_shortcode' );