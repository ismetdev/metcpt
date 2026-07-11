<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Archive Template for metcpt_tender
 *
 * Automatically used when users navigate to the tender archive URL
 * or press Back from a single tender page.
 *
 * @package MetCPT
 * @version 1.0.4
 */

// ── Hook into WordPress archive template filter ───────────────────────────────
if ( ! function_exists( 'metcpt_tender_archive_template' ) ) {
    function metcpt_tender_archive_template( $template ) {
        if ( is_post_type_archive( 'metcpt_tender' ) ) {
            if ( ! defined( 'METCPT_TENDER_ARCHIVE_LOADED' ) ) {
                define( 'METCPT_TENDER_ARCHIVE_LOADED', true );
                return METCPT_PATH . 'includes/tenders/template-archive.php';
            }
        }
        return $template;
    }
    add_filter( 'archive_template', 'metcpt_tender_archive_template' );
}

// ── Render the archive page ───────────────────────────────────────────────────
if ( ! function_exists( 'metcpt_render_tender_archive' ) ) {
    function metcpt_render_tender_archive() {
        if ( ! is_post_type_archive( 'metcpt_tender' ) ) return;
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
        <div class="mcpt-t-archive-wrap">

            <div class="mcpt-t-archive-header">
                <div class="mcpt-t-archive-label">&mdash; Tender Opportunities</div>
                <h1 class="mcpt-t-archive-title">Procurement &amp; Tenders</h1>
                <p class="mcpt-t-archive-desc">
                    Explore current tender opportunities across IIUM Holdings and subsidiary companies.
                    All tenders are open for submission unless marked as closed.
                </p>
            </div>

            <div class="mcpt-t-archive-filters">
                <button class="mcpt-t-filter-btn active" data-filter="all">All Tenders</button>
                <button class="mcpt-t-filter-btn" data-filter="open">Open</button>
                <button class="mcpt-t-filter-btn" data-filter="soon">Closing Soon</button>
                <button class="mcpt-t-filter-btn" data-filter="closed">Closed</button>
            </div>

            <div class="mcpt-t-archive-grid">
                <?php
                $query = new WP_Query( array(
                    'post_type'      => 'metcpt_tender',
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

                        $status    = metcpt_get_tender_status( $close_date );
                        $close_fmt = metcpt_format_tender_date( $close_date );

                        $status_label = 'Open';
                        $status_class = 'mcpt-t-status-open';
                        $date_prefix  = 'Closes';
                        if ( $status === 'soon' ) {
                            $status_label = 'Closing Soon';
                            $status_class = 'mcpt-t-status-soon';
                        } elseif ( $status === 'closed' ) {
                            $status_label = 'Closed';
                            $status_class = 'mcpt-t-status-closed';
                            $date_prefix  = 'Closed';
                        }
                        ?>

                        <a href="<?php echo esc_url( get_permalink() ); ?>"
                           class="mcpt-t-archive-card"
                           data-status="<?php echo esc_attr( $status ); ?>">

                            <div class="mcpt-t-card-header">
                                <?php if ( ! empty( $ref ) ) : ?>
                                    <div class="mcpt-t-card-ref"><?php echo esc_html( $ref ); ?></div>
                                <?php endif; ?>
                                <span class="mcpt-t-card-status <?php echo esc_attr( $status_class ); ?>">
                                    <span class="mcpt-t-dot"></span>
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </div>

                            <?php if ( ! empty( $category ) ) : ?>
                                <div class="mcpt-t-card-cat"><?php echo esc_html( $category ); ?></div>
                            <?php endif; ?>

                            <h2 class="mcpt-t-card-title"><?php echo esc_html( get_the_title() ); ?></h2>

                            <div class="mcpt-t-card-meta">
                                <?php if ( ! empty( $issuer ) ) : ?>
                                    <div class="mcpt-t-card-meta-item">
                                        <div class="mcpt-t-card-meta-label">Issuer</div>
                                        <div class="mcpt-t-card-meta-value"><?php echo esc_html( $issuer ); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $location ) ) : ?>
                                    <div class="mcpt-t-card-meta-item">
                                        <div class="mcpt-t-card-meta-label">Location</div>
                                        <div class="mcpt-t-card-meta-value"><?php echo esc_html( $location ); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mcpt-t-card-footer">
                                <div class="mcpt-t-card-date">
                                    <?php echo esc_html( $date_prefix . ' ' . $close_fmt ); ?>
                                </div>
                                <div class="mcpt-t-card-arrow">&rarr;</div>
                            </div>

                        </a>

                    <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="mcpt-t-archive-empty" style="grid-column: 1 / -1;">
                        <div class="mcpt-t-empty-icon">📋</div>
                        <h3 class="mcpt-t-empty-title">No Tenders Available</h3>
                        <p class="mcpt-t-empty-text">There are currently no tender opportunities published.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        </main>

        <script>
        (function() {
            var filters = document.querySelectorAll('.mcpt-t-filter-btn');
            var cards   = document.querySelectorAll('.mcpt-t-archive-card');

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
if ( defined( 'METCPT_TENDER_ARCHIVE_LOADED' ) ) {
    metcpt_render_tender_archive();
}