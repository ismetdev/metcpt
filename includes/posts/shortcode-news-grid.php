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
        'posts_per_page' => max( 6, intval( $atts['posts_per_page'] ) ),
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
    $posts = $query->posts;

    wp_reset_postdata();

    ob_start();
    ?>

    <div class="hrk-ng-wrap hrk-v2">

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

        <?php if ( empty( $posts ) ) : ?>

            <p class="hrk-ng-empty">No posts found.</p>

        <?php else : ?>

            <!-- Main Grid -->
            <div class="hrk-ng-grid">

                <?php foreach ( $posts as $n_post ) : ?>

                    <?php
                    $n_id    = $n_post->ID;
                    $n_title = get_the_title( $n_id );
                    $n_url   = get_permalink( $n_id );
                    $n_cat   = hrk_get_post_cat_label( $n_id );
                    $n_date  = hrk_get_post_short_date( $n_id );

                    $n_thumb = has_post_thumbnail( $n_id )
                        ? get_the_post_thumbnail_url( $n_id, 'medium' )
                        : '';
                    ?>

                    <article class="hrk-ng-card">

                        <a href="<?php echo esc_url( $n_url ); ?>"
                           class="hrk-ng-card-media-link">

                            <?php if ( ! empty( $n_thumb ) ) : ?>

                                <img class="hrk-ng-card-media"
                                     src="<?php echo esc_url( $n_thumb ); ?>"
                                     alt="<?php echo esc_attr( $n_title ); ?>" />

                            <?php else : ?>

                                <div class="hrk-ng-card-media hrk-ng-card-media-placeholder"></div>

                            <?php endif; ?>

                        </a>

                        <div class="hrk-ng-card-body">

                            <?php if ( ! empty( $n_cat ) ) : ?>
                                <div class="hrk-ng-card-tag">
                                    <?php echo esc_html( $n_cat ); ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="hrk-ng-card-title">
                                <a href="<?php echo esc_url( $n_url ); ?>">
                                    <?php echo esc_html( $n_title ); ?>
                                </a>
                            </h3>

                            <div class="hrk-ng-card-date">
                                <?php echo esc_html( $n_date ); ?>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode( 'news_grid', 'haraka_news_grid_shortcode' );