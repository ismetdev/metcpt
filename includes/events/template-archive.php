<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Archive Template for hrk_event
 *
 * Automatically used when users navigate to the event archive URL
 * or press Back from a single event page.
 *
 * @package Haraka
 * @version 2.4.0
 */

if ( ! function_exists( 'haraka_event_archive_template' ) ) {
    function haraka_event_archive_template( $template ) {
        if ( is_post_type_archive( 'hrk_event' ) ) {
            if ( ! defined( 'HARAKA_EVENT_ARCHIVE_LOADED' ) ) {
                define( 'HARAKA_EVENT_ARCHIVE_LOADED', true );
                return HARAKA_PLUGIN_DIR . 'includes/events/template-archive.php';
            }
        }
        return $template;
    }
    add_filter( 'archive_template', 'haraka_event_archive_template' );
}

if ( ! function_exists( 'haraka_render_event_archive' ) ) {
    function haraka_render_event_archive() {
        if ( ! is_post_type_archive( 'hrk_event' ) ) return;

        $today = date( 'Y-m-d' );
        ?>

        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
        <?php wp_body_open(); ?>
        <?php get_header(); ?>

        <main>
        <div class="hrk-ev-archive-wrap">

            <div class="hrk-ev-archive-header">
                <div class="hrk-ev-archive-label">&mdash; Corporate Events</div>
                <h1 class="hrk-ev-archive-title">Events &amp; programmes</h1>
                <p class="hrk-ev-archive-desc">
                    Corporate events, ceremonies, and programmes hosted by IIUM Holdings
                    and its subsidiary companies.
                </p>
            </div>

            <div class="hrk-ev-archive-filters">
                <button class="hrk-ev-filter-btn active" data-filter="all">All Events</button>
                <button class="hrk-ev-filter-btn" data-filter="upcoming">Upcoming</button>
                <button class="hrk-ev-filter-btn" data-filter="today">Today</button>
                <button class="hrk-ev-filter-btn" data-filter="past">Past</button>
            </div>

            <div class="hrk-ev-archive-grid">
                <?php
                $query = new WP_Query( array(
                    'post_type'      => 'hrk_event',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_key'       => 'event_date',
                    'orderby'        => 'meta_value',
                    'order'          => 'DESC',
                ) );

                if ( $query->have_posts() ) :
                    while ( $query->have_posts() ) : $query->the_post();
                        $post_id    = get_the_ID();
                        $event_date = get_post_meta( $post_id, 'event_date',      true );
                        $event_time = get_post_meta( $post_id, 'event_time',      true );
                        $venue      = get_post_meta( $post_id, 'event_venue',     true );
                        $organiser  = get_post_meta( $post_id, 'event_organiser', true );
                        $thumb      = get_the_post_thumbnail_url( $post_id, 'medium' );

                        // Status
                        $status       = 'upcoming';
                        $status_label = 'Upcoming';
                        $date_fmt     = '';

                        if ( ! empty( $event_date ) ) {
                            $date_obj = date_create( $event_date );
                            if ( $date_obj ) {
                                $date_fmt  = $date_obj->format( 'd M Y' );
                                $day_num   = $date_obj->format( 'd' );
                                $month_str = $date_obj->format( 'M' );
                                $year_str  = $date_obj->format( 'Y' );

                                if ( $event_date < $today ) {
                                    $status       = 'past';
                                    $status_label = 'Past';
                                } elseif ( $event_date === $today ) {
                                    $status       = 'today';
                                    $status_label = 'Today';
                                }
                            }
                        }
                        ?>

                        <a href="<?php echo esc_url( get_permalink() ); ?>"
                           class="hrk-ev-archive-card"
                           data-status="<?php echo esc_attr( $status ); ?>">

                            <?php if ( $thumb ) : ?>
                                <div class="hrk-ev-card-thumb"
                                     style="background-image: url('<?php echo esc_url( $thumb ); ?>');">
                                </div>
                            <?php else : ?>
                                <div class="hrk-ev-card-thumb hrk-ev-card-thumb-placeholder">
                                    <span><?php echo esc_html( $month_str ?? 'EVT' ); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="hrk-ev-card-body">

                                <div class="hrk-ev-card-header">
                                    <?php if ( ! empty( $date_fmt ) ) : ?>
                                        <div class="hrk-ev-card-date-badge">
                                            <span class="hrk-ev-date-day"><?php echo esc_html( $day_num ); ?></span>
                                            <span class="hrk-ev-date-month"><?php echo esc_html( $month_str . ' ' . $year_str ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="hrk-ev-card-status hrk-ev-status-<?php echo esc_attr( $status ); ?>">
                                        <span class="hrk-ev-status-dot"></span>
                                        <?php echo esc_html( $status_label ); ?>
                                    </span>
                                </div>

                                <h2 class="hrk-ev-card-title"><?php echo esc_html( get_the_title() ); ?></h2>

                                <div class="hrk-ev-card-meta">
                                    <?php if ( ! empty( $venue ) ) : ?>
                                        <div class="hrk-ev-card-meta-item">
                                            <div class="hrk-ev-card-meta-label">Venue</div>
                                            <div class="hrk-ev-card-meta-value"><?php echo esc_html( $venue ); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $organiser ) ) : ?>
                                        <div class="hrk-ev-card-meta-item">
                                            <div class="hrk-ev-card-meta-label">Organiser</div>
                                            <div class="hrk-ev-card-meta-value"><?php echo esc_html( $organiser ); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="hrk-ev-card-footer">
                                    <?php if ( ! empty( $event_time ) ) : ?>
                                        <div class="hrk-ev-card-time"><?php echo esc_html( $event_time ); ?></div>
                                    <?php endif; ?>
                                    <div class="hrk-ev-card-arrow">&rarr;</div>
                                </div>

                            </div>

                        </a>

                    <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="hrk-ev-archive-empty" style="grid-column: 1 / -1;">
                        <div class="hrk-ev-empty-icon">📅</div>
                        <h3 class="hrk-ev-empty-title">No Events Available</h3>
                        <p class="hrk-ev-empty-text">There are currently no events published.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        </main>

        <script>
        (function() {
            var filters = document.querySelectorAll('.hrk-ev-filter-btn');
            var cards   = document.querySelectorAll('.hrk-ev-archive-card');

            filters.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var filter = btn.getAttribute('data-filter');
                    filters.forEach(function(f) { f.classList.remove('active'); });
                    btn.classList.add('active');
                    cards.forEach(function(card) {
                        var status = card.getAttribute('data-status');
                        card.style.display =
                            ( filter === 'all' || filter === status )
                            ? 'flex' : 'none';
                    });
                });
            });
        })();
        </script>

        <?php get_footer(); ?>
        <?php wp_footer(); ?>
        </body>
        </html>

        <?php
    }
}

if ( defined( 'HARAKA_EVENT_ARCHIVE_LOADED' ) ) {
    haraka_render_event_archive();
}