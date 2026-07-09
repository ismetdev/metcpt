<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function metcpt_events_list_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'filter'         => 'all',
            'category'       => '',
            'posts_per_page' => -1,
            'order'          => 'ASC',
            'show_excerpt'   => 'yes',
            'show_date'      => 'yes',
        ),
        $atts,
        'events_list'
    );

    $today  = date( 'Y-m-d' );
    $filter = sanitize_text_field( $atts['filter'] );

    // ── Build meta query based on filter ─────────────────────────────────────
    $meta_query = array(
        array(
            'key'     => 'event_date',
            'compare' => 'EXISTS',
        ),
    );

    if ( $filter === 'upcoming' ) {
        $meta_query[] = array(
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        );
    } elseif ( $filter === 'past' ) {
        $meta_query[] = array(
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '<',
            'type'    => 'DATE',
        );
    }

    // ── Base query — always uses metcpt_event CPT ────────────────────────────────
    $query_args = array(
        'post_type'      => 'metcpt_event',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'orderby'        => 'meta_value',
        'meta_key'       => 'event_date',
        'order'          => sanitize_text_field( $atts['order'] ),
        'meta_query'     => $meta_query,
    );

    // ── Optional category filter ──────────────────────────────────────────────
    if ( ! empty( $atts['category'] ) ) {
        $term = get_term_by(
            'slug',
            sanitize_text_field( $atts['category'] ),
            'category'
        );
        if ( $term && ! is_wp_error( $term ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy'         => 'category',
                    'field'            => 'term_id',
                    'terms'            => $term->term_id,
                    'include_children' => true,
                ),
            );
        }
    }

    $query = new WP_Query( $query_args );

    ob_start();

    if ( $query->have_posts() ) :

        echo '<div class="ev-wrap">';

        $current_month = '';

        while ( $query->have_posts() ) :
            $query->the_post();

            $post_id  = get_the_ID();
            $raw_date = get_post_meta( $post_id, 'event_date',    true );
            $time     = get_post_meta( $post_id, 'event_time',    true );
            $location = get_post_meta( $post_id, 'event_venue',   true );
            $excerpt  = get_the_excerpt();
            $thumb    = has_post_thumbnail()
                        ? get_the_post_thumbnail_url( $post_id, 'medium' )
                        : '';

            // ── Parse date ────────────────────────────────────────────────────
            if ( ! empty( $raw_date ) ) {
                $date_obj    = date_create( $raw_date );
                $day_num     = $date_obj ? $date_obj->format( 'd' )                 : '--';
                $day_name    = $date_obj ? strtoupper( $date_obj->format( 'D' ) )   : '';
                $month_long  = $date_obj ? $date_obj->format( 'M' )                 : '';
                $month_key   = $date_obj ? $date_obj->format( 'Y-m' )               : '';
                $month_label = $date_obj ? strtoupper( $date_obj->format( 'F Y' ) ) : '';
                $meta_line   = $month_long . ' ' . ltrim( $day_num, '0' );
                if ( ! empty( $time ) ) {
                    $meta_line .= ' @ ' . $time;
                }
            } else {
                $day_num     = '--';
                $day_name    = '';
                $month_key   = 'unknown';
                $month_label = 'Date TBC';
                $meta_line   = 'Date to be confirmed';
            }

            // ── Month divider ─────────────────────────────────────────────────
            if ( $month_key !== $current_month ) {
                $current_month = $month_key;
                echo '<div class="ev-month-divider">';
                echo '<span class="ev-month-label">' . esc_html( $month_label ) . '</span>';
                echo '<div class="ev-month-line"></div>';
                echo '</div>';
            }
            ?>

            <a href="<?php echo esc_url( get_permalink() ); ?>" class="ev-row">
                <div class="ev-date-col">
                    <span class="ev-day-name"><?php echo esc_html( $day_name ); ?></span>
                    <span class="ev-day-num"><?php echo esc_html( $day_num ); ?></span>
                </div>
                <div class="ev-content">
                    <?php if ( $atts['show_date'] === 'yes' ) : ?>
                        <span class="ev-meta"><?php echo esc_html( $meta_line ); ?></span>
                    <?php endif; ?>
                    <div class="ev-title"><?php echo esc_html( get_the_title() ); ?></div>
                    <?php if ( ! empty( $location ) ) : ?>
                        <div class="ev-venue"><?php echo esc_html( $location ); ?></div>
                    <?php endif; ?>
                    <?php if ( $atts['show_excerpt'] === 'yes' && ! empty( $excerpt ) ) : ?>
                        <div class="ev-excerpt"><?php echo esc_html( $excerpt ); ?></div>
                    <?php endif; ?>
                </div>
                <?php if ( ! empty( $thumb ) ) : ?>
                    <div class="ev-img">
                        <img src="<?php echo esc_url( $thumb ); ?>"
                             alt="<?php echo esc_attr( get_the_title() ); ?>">
                    </div>
                <?php endif; ?>
            </a>

            <?php
        endwhile;

        echo '</div>';

    else :
        if ( $filter === 'upcoming' ) {
            echo '<p class="cpl-no-posts">No upcoming events at this time.</p>';
        } elseif ( $filter === 'past' ) {
            echo '<p class="cpl-no-posts">No past events found.</p>';
        } else {
            echo '<p class="cpl-no-posts">No events found.</p>';
        }
    endif;

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'events_list', 'metcpt_events_list_shortcode' );