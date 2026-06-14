<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Archive Template for hrk_tender
 *
 * Automatically used when users navigate to the tender archive URL
 * or press Back from a single tender page.
 *
 * @package Haraka
 * @version 1.0.0
 */

// ── Hook into WordPress archive template filter ───────────────────────────────
if ( ! function_exists( 'haraka_tender_archive_template' ) ) {
    function haraka_tender_archive_template( $template ) {
        if ( is_post_type_archive( 'hrk_tender' ) ) {
            if ( ! defined( 'HARAKA_TENDER_ARCHIVE_LOADED' ) ) {
                define( 'HARAKA_TENDER_ARCHIVE_LOADED', true );
                return HARAKA_PLUGIN_DIR . 'includes/tenders/template-archive.php';
            }
        }
        return $template;
    }
    add_filter( 'archive_template', 'haraka_tender_archive_template' );
}

// ── Render the archive page ───────────────────────────────────────────────────
if ( ! function_exists( 'haraka_render_tender_archive' ) ) {
    function haraka_render_tender_archive() {
        if ( ! is_post_type_archive( 'hrk_tender' ) ) return;
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
        <div class="hrk-t-archive-wrap">

            <div class="hrk-t-archive-header">
                <div class="hrk-t-archive-label">&mdash; Tender Opportunities</div>
                <h1 class="hrk-t-archive-title">Procurement &amp; Tenders</h1>
                <p class="hrk-t-archive-desc">
                    Explore current tender opportunities across IIUM Holdings and subsidiary companies.
                    All tenders are open for submission unless marked as closed.
                </p>
            </div>

            <div class="hrk-t-archive-filters">
                <button class="hrk-t-filter-btn active" data-filter="all">All Tenders</button>
                <button class="hrk-t-filter-btn" data-filter="open">Open</button>
                <button class="hrk-t-filter-btn" data-filter="soon">Closing Soon</button>
                <button class="hrk-t-filter-btn" data-filter="closed">Closed</button>
            </div>

            <div class="hrk-t-archive-grid">
                <?php
                $query = new WP_Query( array(
                    'post_type'      => 'hrk_tender',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'orderby'        => 'meta_value',
                    'meta_key'       => 'tender_close_date',
                    'order'          => 'ASC',
                    'meta_query'     => array(
                        array(
                            'key'     => 'tender_close_date',
                            'compare' => 'EXISTS',
                        ),
                    ),
                ) );

                if ( $query->have_posts() ) :
                    while ( $query->have_posts() ) : $query->the_post();
                        $post_id    = get_the_ID();
                        $ref        = get_post_meta( $post_id, 'tender_ref',        true );
                        $issuer     = get_post_meta( $post_id, 'tender_issuer',     true );
                        $location   = get_post_meta( $post_id, 'tender_location',   true );
                        $category   = get_post_meta( $post_id, 'tender_category',   true );
                        $close_date = get_post_meta( $post_id, 'tender_close_date', true );

                        if ( empty( $close_date ) ) continue;

                        $status    = haraka_get_tender_status( $close_date );
                        $close_fmt = haraka_format_tender_date( $close_date );

                        $status_label = 'Open';
                        $status_class = 'hrk-t-status-open';
                        $date_prefix  = 'Closes';
                        if ( $status === 'soon' ) {
                            $status_label = 'Closing Soon';
                            $status_class = 'hrk-t-status-soon';
                        } elseif ( $status === 'closed' ) {
                            $status_label = 'Closed';
                            $status_class = 'hrk-t-status-closed';
                            $date_prefix  = 'Closed';
                        }
                        ?>

                        <a href="<?php echo esc_url( get_permalink() ); ?>"
                           class="hrk-t-archive-card"
                           data-status="<?php echo esc_attr( $status ); ?>">

                            <div class="hrk-t-card-header">
                                <?php if ( ! empty( $ref ) ) : ?>
                                    <div class="hrk-t-card-ref"><?php echo esc_html( $ref ); ?></div>
                                <?php endif; ?>
                                <span class="hrk-t-card-status <?php echo esc_attr( $status_class ); ?>">
                                    <span class="hrk-t-dot"></span>
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </div>

                            <?php if ( ! empty( $category ) ) : ?>
                                <div class="hrk-t-card-cat"><?php echo esc_html( $category ); ?></div>
                            <?php endif; ?>

                            <h2 class="hrk-t-card-title"><?php echo esc_html( get_the_title() ); ?></h2>

                            <div class="hrk-t-card-meta">
                                <?php if ( ! empty( $issuer ) ) : ?>
                                    <div class="hrk-t-card-meta-item">
                                        <div class="hrk-t-card-meta-label">Issuer</div>
                                        <div class="hrk-t-card-meta-value"><?php echo esc_html( $issuer ); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $location ) ) : ?>
                                    <div class="hrk-t-card-meta-item">
                                        <div class="hrk-t-card-meta-label">Location</div>
                                        <div class="hrk-t-card-meta-value"><?php echo esc_html( $location ); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="hrk-t-card-footer">
                                <div class="hrk-t-card-date">
                                    <?php echo esc_html( $date_prefix . ' ' . $close_fmt ); ?>
                                </div>
                                <div class="hrk-t-card-arrow">&rarr;</div>
                            </div>

                        </a>

                    <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="hrk-t-archive-empty" style="grid-column: 1 / -1;">
                        <div class="hrk-t-empty-icon">📋</div>
                        <h3 class="hrk-t-empty-title">No Tenders Available</h3>
                        <p class="hrk-t-empty-text">There are currently no tender opportunities published.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        </main>

        <script>
        (function() {
            var filters = document.querySelectorAll('.hrk-t-filter-btn');
            var cards   = document.querySelectorAll('.hrk-t-archive-card');

            filters.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var filter = btn.getAttribute('data-filter');

                    filters.forEach(function(f) { f.classList.remove('active'); });
                    btn.classList.add('active');

                    cards.forEach(function(card) {
                        var status = card.getAttribute('data-status');

                        if (filter === 'all') {
                            card.style.display = 'block';
                        } else if (filter === 'open' && (status === 'open' || status === 'soon')) {
                            card.style.display = 'block';
                        } else if (filter === status) {
                            card.style.display = 'block';
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

// Only render when loaded as the active template
if ( defined( 'HARAKA_TENDER_ARCHIVE_LOADED' ) ) {
    haraka_render_tender_archive();
}