<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function haraka_tenders_preview_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'posts_per_page' => 4,
            'view_all_url'   => get_option( 'haraka_tenders_page_url', home_url( '/tenders' ) ),
            'template'       => '',
        ),
        $atts,
        'tenders_preview'
    );

    // ── Route to correct template ─────────────────────────────────────────────
    // An explicit template="a"|"b" overrides the global Active Template setting.
    $template = $atts['template'] !== ''
        ? strtolower( $atts['template'] )
        : get_option( 'haraka_tenders_template', 'a' );

    if ( $template === 'b' ) {
        return haraka_tenders_render_template_b_preview( $atts );
    }

    // ── Template A — always uses hrk_tender CPT ───────────────────────────────
    $today = date( 'Y-m-d' );

    $query_args = array(
        'post_type'      => 'hrk_tender',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'orderby'        => 'meta_value',
        'meta_key'       => 'tender_close_date',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'tender_close_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    );

    $query    = new WP_Query( $query_args );
    $total    = $query->post_count;
    $view_all = esc_url( $atts['view_all_url'] );

    ob_start();
    ?>

    <div class="tdp-wrap">

        <div class="tdp-header">
            <div class="tdp-header-left">
                <div class="tdp-dot"></div>
                <span class="tdp-header-title">Open Tenders</span>
                <?php if ( $total > 0 ) : ?>
                    <span class="tdp-header-count">
                        <?php echo esc_html( $total ); ?> active
                    </span>
                <?php endif; ?>
            </div>
            <a class="tdp-header-link" href="<?php echo $view_all; ?>">
                View all tenders &rarr;
            </a>
        </div>

        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php
                $post_id    = get_the_ID();
                $close_date = get_post_meta( $post_id, 'tender_close_date',   true );
                $ref        = get_post_meta( $post_id, 'tender_ref',          true );
                $doc_url    = get_post_meta( $post_id, 'tender_document_url', true );
                $status     = haraka_get_tender_status( $close_date );
                $close_fmt  = haraka_format_tender_date( $close_date );
                $link       = ! empty( $doc_url ) ? $doc_url : get_permalink();
                $target     = ! empty( $doc_url ) ? ' target="_blank" rel="noopener noreferrer"' : '';
                ?>
                <a href="<?php echo esc_url( $link ); ?>"
                   class="tdp-row"<?php echo $target; ?>>
                    <span class="tdp-ref">
                        <?php echo esc_html( $ref ? $ref : '—' ); ?>
                    </span>
                    <span class="tdp-title">
                        <?php echo esc_html( get_the_title() ); ?>
                    </span>
                    <span class="tdp-badge <?php echo $status === 'soon' ? 'tdp-badge-soon' : 'tdp-badge-open'; ?>">
                        <?php echo $status === 'soon' ? 'Closing Soon' : 'Open'; ?>
                    </span>
                    <span class="tdp-date">
                        Closes <?php echo esc_html( $close_fmt ); ?>
                    </span>
                </a>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="tdp-empty">No open tenders at this time.</div>
        <?php endif; ?>

        <div class="tdp-footer">
            <a class="tdp-footer-btn" href="<?php echo $view_all; ?>">
                View all tender opportunities &rarr;
            </a>
        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'tenders_preview', 'haraka_tenders_preview_shortcode' );