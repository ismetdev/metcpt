<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function haraka_category_posts_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'category_slug'  => '',
            'posts_per_page' => 10,
            'order'          => 'DESC',
            'orderby'        => 'date',
            'show_excerpt'   => 'yes',
            'show_date'      => 'yes',
        ),
        $atts,
        'category_posts'
    );

    if ( empty( $atts['category_slug'] ) ) {
        return '<p class="hrk-no-posts">Please provide a category_slug attribute.</p>';
    }

    $query_args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'order'          => sanitize_text_field( $atts['order'] ),
        'orderby'        => sanitize_text_field( $atts['orderby'] ),
        'tax_query'      => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category_slug'] ),
            ),
        ),
    );

    $query = new WP_Query( $query_args );

    ob_start();

    if ( $query->have_posts() ) :
        echo '<div class="hrk-post-list">';
        while ( $query->have_posts() ) :
            $query->the_post();
            ?>
            <div class="hrk-post-item">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="hrk-post-thumbnail">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="hrk-post-content">
                    <h3 class="hrk-post-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <?php if ( $atts['show_date'] === 'yes' ) : ?>
                        <p class="hrk-post-date">
                            <?php echo esc_html( get_the_date() ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $atts['show_excerpt'] === 'yes' ) : ?>
                        <div class="hrk-post-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                    <a class="hrk-read-more" href="<?php the_permalink(); ?>">
                        Read More &rarr;
                    </a>
                </div>
            </div>
            <?php
        endwhile;
        echo '</div>';
    else :
        echo '<p class="hrk-no-posts">No posts found.</p>';
    endif;

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'category_posts', 'haraka_category_posts_shortcode' );