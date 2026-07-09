<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── [careers_preview] shortcode ───────────────────────────────────────────────
function metcpt_careers_preview_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'posts_per_page' => 4,
            'company'        => '',
            'view_all_url'   => get_option( 'metcpt_careers_page_url', '/careers' ),
        ),
        $atts,
        'careers_preview'
    );

    $today = date( 'Y-m-d' );

    // ── Base meta query — only open positions ─────────────────────────────────
    $meta_query = array(
        'relation' => 'AND',
        array(
            'key'     => 'career_close_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        ),
    );

    // ── Optional company filter ───────────────────────────────────────────────
    if ( ! empty( $atts['company'] ) ) {
        $company_post = get_page_by_path(
            sanitize_text_field( $atts['company'] ),
            OBJECT,
            'metcpt_company'
        );
        if ( $company_post ) {
            $meta_query[] = array(
                'key'     => 'career_company_id',
                'value'   => $company_post->ID,
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }
    }

    $query_args = array(
        'post_type'      => 'metcpt_career',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'orderby'        => 'meta_value',
        'meta_key'       => 'career_close_date',
        'order'          => 'ASC',
        'meta_query'     => $meta_query,
    );

    $query    = new WP_Query( $query_args );
    $total    = $query->post_count;
    $view_all = esc_url( $atts['view_all_url'] );

    ob_start();
    ?>

    <div class="crp-wrap">

        <div class="crp-header">
            <div class="crp-header-left">
                <div class="crp-dot"></div>
                <span class="crp-header-title">Open Positions</span>
                <?php if ( $total > 0 ) : ?>
                    <span class="crp-header-count">
                        <?php echo esc_html( $total ); ?> available
                    </span>
                <?php endif; ?>
            </div>
            <a class="crp-header-link"
               href="<?php echo $view_all; ?>">
                View all positions &rarr;
            </a>
        </div>

        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php
                $post_id    = get_the_ID();
                $company_id = get_post_meta( $post_id, 'career_company_id', true );
                $close_date = get_post_meta( $post_id, 'career_close_date', true );
                $department = get_post_meta( $post_id, 'career_department',  true );
                $type       = get_post_meta( $post_id, 'career_type',        true );
                $status     = metcpt_get_career_status( $close_date );
                $close_fmt  = metcpt_format_career_date( $close_date );

                // Resolve company name
                $company_name = '';
                if ( ! empty( $company_id ) ) {
                    $short = get_post_meta( (int) $company_id, 'company_short_name', true );
                    $company_name = $short ? $short : get_the_title( (int) $company_id );
                }
                ?>
                <a href="<?php echo esc_url( get_permalink() ); ?>"
                   class="crp-row">
                    <div class="crp-row-main">
                        <span class="crp-title">
                            <?php echo esc_html( get_the_title() ); ?>
                        </span>
                        <span class="crp-meta">
                            <?php if ( ! empty( $company_name ) ) : ?>
                                <?php echo esc_html( $company_name ); ?>
                            <?php endif; ?>
                            <?php if ( ! empty( $department ) ) : ?>
                                <span class="crp-sep">&middot;</span>
                                <?php echo esc_html( $department ); ?>
                            <?php endif; ?>
                            <?php if ( ! empty( $type ) ) : ?>
                                <span class="crp-sep">&middot;</span>
                                <?php echo esc_html( $type ); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="crp-row-right">
                        <span class="crp-badge <?php echo $status === 'soon' ? 'crp-badge-soon' : 'crp-badge-open'; ?>">
                            <?php echo $status === 'soon' ? 'Closing Soon' : 'Open'; ?>
                        </span>
                        <span class="crp-date">
                            Closes <?php echo esc_html( $close_fmt ); ?>
                        </span>
                    </div>
                </a>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="crp-empty">No open positions at this time.</div>
        <?php endif; ?>

        <div class="crp-footer">
            <a class="crp-footer-btn" href="<?php echo $view_all; ?>">
                View all career opportunities &rarr;
            </a>
        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'careers_preview', 'metcpt_careers_preview_shortcode' );