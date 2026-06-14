<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Archive Template for hrk_career
 *
 * Automatically used when users navigate to the career archive URL
 * or press Back from a single career page.
 *
 * @package Haraka
 * @version 2.4.0
 */

if ( ! function_exists( 'haraka_career_archive_template' ) ) {
    function haraka_career_archive_template( $template ) {
        if ( is_post_type_archive( 'hrk_career' ) ) {
            if ( ! defined( 'HARAKA_CAREER_ARCHIVE_LOADED' ) ) {
                define( 'HARAKA_CAREER_ARCHIVE_LOADED', true );
                return HARAKA_PLUGIN_DIR . 'includes/careers/template-archive.php';
            }
        }
        return $template;
    }
    add_filter( 'archive_template', 'haraka_career_archive_template' );
}

if ( ! function_exists( 'haraka_render_career_archive' ) ) {
    function haraka_render_career_archive() {
        if ( ! is_post_type_archive( 'hrk_career' ) ) return;
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
        <div class="hrk-cr-archive-wrap">

            <div class="hrk-cr-archive-header">
                <div class="hrk-cr-archive-label">&mdash; Career Opportunities</div>
                <h1 class="hrk-cr-archive-title">Join our team</h1>
                <p class="hrk-cr-archive-desc">
                    Explore open positions across IIUM Holdings and its subsidiary companies.
                    Build a meaningful career in service of the ummah.
                </p>
            </div>

            <div class="hrk-cr-archive-filters">
                <button class="hrk-cr-filter-btn active" data-filter="all">All Positions</button>
                <button class="hrk-cr-filter-btn" data-filter="open">Open</button>
                <button class="hrk-cr-filter-btn" data-filter="soon">Closing Soon</button>
                <button class="hrk-cr-filter-btn" data-filter="closed">Closed</button>
            </div>

            <div class="hrk-cr-archive-grid">
                <?php
                $today     = date( 'Y-m-d' );
                $threshold = (int) get_option( 'haraka_closing_soon_days', 7 );

                $query = new WP_Query( array(
                    'post_type'      => 'hrk_career',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_key'       => 'career_close_date',
                    'orderby'        => 'meta_value',
                    'order'          => 'ASC',
                    'meta_query'     => array(
                        array(
                            'key'     => 'career_close_date',
                            'compare' => 'EXISTS',
                        ),
                    ),
                ) );

                if ( $query->have_posts() ) :
                    while ( $query->have_posts() ) : $query->the_post();
                        $post_id    = get_the_ID();
                        $company_id = get_post_meta( $post_id, 'career_company_id', true );
                        $department = get_post_meta( $post_id, 'career_department',  true );
                        $location   = get_post_meta( $post_id, 'career_location',    true );
                        $type       = get_post_meta( $post_id, 'career_type',        true );
                        $close_date = get_post_meta( $post_id, 'career_close_date',  true );
                        $salary     = get_post_meta( $post_id, 'career_salary',      true );

                        // Company name
                        $company_name = '';
                        if ( ! empty( $company_id ) ) {
                            $company_short = get_post_meta( $company_id, 'company_short_name', true );
                            $company_name  = $company_short ? $company_short : get_the_title( $company_id );
                        }

                        // Status
                        $status       = 'open';
                        $status_label = 'Open';
                        $close_fmt    = '';
                        $date_prefix  = 'Closes';

                        if ( ! empty( $close_date ) ) {
                            $date_obj  = date_create( $close_date );
                            if ( $date_obj ) {
                                $close_fmt = $date_obj->format( 'd M Y' );
                                $today_obj = new DateTime( 'today' );
                                $is_past   = $date_obj < $today_obj;
                                $days_left = (int) $today_obj->diff( $date_obj )->days;

                                if ( $is_past ) {
                                    $status       = 'closed';
                                    $status_label = 'Closed';
                                    $date_prefix  = 'Closed';
                                } elseif ( $days_left <= $threshold ) {
                                    $status       = 'soon';
                                    $status_label = 'Closing Soon';
                                }
                            }
                        }
                        ?>

                        <a href="<?php echo esc_url( get_permalink() ); ?>"
                           class="hrk-cr-archive-card"
                           data-status="<?php echo esc_attr( $status ); ?>">

                            <div class="hrk-cr-card-top">
                                <div class="hrk-cr-card-type"><?php echo esc_html( $type ? $type : 'Full Time' ); ?></div>
                                <span class="hrk-cr-card-status hrk-cr-status-<?php echo esc_attr( $status ); ?>">
                                    <span class="hrk-cr-status-dot"></span>
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </div>

                            <h2 class="hrk-cr-card-title"><?php echo esc_html( get_the_title() ); ?></h2>

                            <?php if ( ! empty( $company_name ) ) : ?>
                                <div class="hrk-cr-card-company"><?php echo esc_html( $company_name ); ?></div>
                            <?php endif; ?>

                            <div class="hrk-cr-card-meta">
                                <?php if ( ! empty( $department ) ) : ?>
                                    <div class="hrk-cr-card-meta-item">
                                        <div class="hrk-cr-card-meta-label">Department</div>
                                        <div class="hrk-cr-card-meta-value"><?php echo esc_html( $department ); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $location ) ) : ?>
                                    <div class="hrk-cr-card-meta-item">
                                        <div class="hrk-cr-card-meta-label">Location</div>
                                        <div class="hrk-cr-card-meta-value"><?php echo esc_html( $location ); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $salary ) ) : ?>
                                    <div class="hrk-cr-card-meta-item">
                                        <div class="hrk-cr-card-meta-label">Salary</div>
                                        <div class="hrk-cr-card-meta-value"><?php echo esc_html( $salary ); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="hrk-cr-card-footer">
                                <?php if ( ! empty( $close_fmt ) ) : ?>
                                    <div class="hrk-cr-card-date">
                                        <?php echo esc_html( $date_prefix . ' ' . $close_fmt ); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="hrk-cr-card-arrow">&rarr;</div>
                            </div>

                        </a>

                    <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="hrk-cr-archive-empty" style="grid-column: 1 / -1;">
                        <div class="hrk-cr-empty-icon">💼</div>
                        <h3 class="hrk-cr-empty-title">No Positions Available</h3>
                        <p class="hrk-cr-empty-text">There are currently no open positions published.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        </main>

        <script>
        (function() {
            var filters = document.querySelectorAll('.hrk-cr-filter-btn');
            var cards   = document.querySelectorAll('.hrk-cr-archive-card');

            filters.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var filter = btn.getAttribute('data-filter');
                    filters.forEach(function(f) { f.classList.remove('active'); });
                    btn.classList.add('active');
                    cards.forEach(function(card) {
                        var status = card.getAttribute('data-status');
                        if ( filter === 'all' ) {
                            card.style.display = 'flex';
                        } else if ( filter === 'open' && ( status === 'open' || status === 'soon' ) ) {
                            card.style.display = 'flex';
                        } else if ( filter === status ) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
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

if ( defined( 'HARAKA_CAREER_ARCHIVE_LOADED' ) ) {
    haraka_render_career_archive();
}