<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Template B: Full list renderer ────────────────────────────────────────────
if ( ! function_exists( 'haraka_tenders_render_template_b_list' ) ) {
    function haraka_tenders_render_template_b_list( $atts ) {

        $today  = date( 'Y-m-d' );
        $filter = sanitize_text_field( $atts['filter'] );

        // ── Meta query ────────────────────────────────────────────────────────
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => 'tender_close_date',
                'compare' => 'EXISTS',
            ),
        );

        if ( $filter === 'open' ) {
            $meta_query[] = array(
                'key'     => 'tender_close_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            );
        } elseif ( $filter === 'closed' ) {
            $meta_query[] = array(
                'key'     => 'tender_close_date',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE',
            );
        }

        // ── Always query hrk_tender CPT ───────────────────────────────────────
        $query_args = array(
            'post_type'      => 'hrk_tender',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'orderby'        => 'meta_value',
            'meta_key'       => 'tender_close_date',
            'order'          => sanitize_text_field( $atts['order'] ),
            'meta_query'     => $meta_query,
        );

        // ── Optional category filter ──────────────────────────────────────────
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

        $query   = new WP_Query( $query_args );
        $tenders = array();

        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) :
                $query->the_post();
                $post_id    = get_the_ID();
                $close_date = get_post_meta( $post_id, 'tender_close_date', true );
                $tenders[]  = array(
                    'id'        => $post_id,
                    'title'     => get_the_title(),
                    'permalink' => get_permalink(),
                    'ref'       => get_post_meta( $post_id, 'tender_ref',          true ),
                    'doc_url'   => get_post_meta( $post_id, 'tender_document_url', true ),
                    'close_date'=> $close_date,
                    'close_fmt' => haraka_format_tender_date( $close_date ),
                    'status'    => haraka_get_tender_status( $close_date ),
                );
            endwhile;
            wp_reset_postdata();
        endif;

        // ── Pull settings ─────────────────────────────────────────────────────
        $label           = ! empty( $atts['label'] )           ? $atts['label']           : get_option( 'haraka_tenders_b_label',           'Tender Opportunities' );
        $headline        = ! empty( $atts['headline'] )        ? $atts['headline']        : get_option( 'haraka_tenders_b_headline',        'Open procurement' );
        $headline_italic = ! empty( $atts['headline_italic'] ) ? $atts['headline_italic'] : get_option( 'haraka_tenders_b_headline_italic', 'across the group.' );
        $all_text        = ! empty( $atts['view_all_text'] )   ? $atts['view_all_text']   : get_option( 'haraka_tenders_b_all_text',        'All tenders' );
        $view_all_text   = get_option( 'haraka_tenders_b_view_all_text', 'View all' );
        $view_all_url    = get_option( 'haraka_tenders_page_url', home_url( '/tenders' ) );

        $uid = 'tdb-' . uniqid();

        ob_start();
        ?>

        <div class="hrk-tb-wrap" id="<?php echo esc_attr( $uid ); ?>">

            <div class="hrk-tb-header">
                <div class="hrk-tb-header-left">
                    <?php if ( ! empty( $label ) ) : ?>
                        <div class="hrk-tb-label">
                            <span class="hrk-tb-label-dash">&mdash;</span>
                            <?php echo esc_html( $label ); ?>
                        </div>
                    <?php endif; ?>
                    <h2 class="hrk-tb-headline">
                        <?php echo esc_html( $headline ); ?>
                        <?php if ( ! empty( $headline_italic ) ) : ?>
                            <em><?php echo esc_html( $headline_italic ); ?></em>
                        <?php endif; ?>
                    </h2>
                </div>
                <a class="hrk-tb-all-link"
                   href="<?php echo esc_url( $view_all_url ); ?>">
                    <?php echo esc_html( $all_text ); ?> &nbsp;&rarr;
                </a>
            </div>

            <div class="hrk-tb-panel">

                <div class="hrk-tb-tabs">
                    <button class="hrk-tb-tab active" data-tab="open">
                        <span class="hrk-tb-tab-dot hrk-tb-dot-green"></span>
                        Open Tenders
                    </button>
                    <button class="hrk-tb-tab" data-tab="closed">
                        <span class="hrk-tb-tab-dot hrk-tb-dot-gray"></span>
                        Closed
                    </button>
                    <a class="hrk-tb-tab-viewall"
                       href="<?php echo esc_url( $view_all_url ); ?>">
                        <?php echo esc_html( $view_all_text ); ?> &rarr;
                    </a>
                </div>

                <div class="hrk-tb-list" data-panel="open">
                    <?php
                    $open_tenders = array_filter( $tenders, function( $t ) {
                        return $t['status'] === 'open' || $t['status'] === 'soon';
                    } );
                    ?>
                    <?php if ( empty( $open_tenders ) ) : ?>
                        <div class="hrk-tb-empty">No open tenders at this time.</div>
                    <?php else : ?>
                        <?php foreach ( $open_tenders as $t ) : ?>
                            <div class="hrk-tb-row">
                                <span class="hrk-tb-ref">
                                    <?php echo esc_html( $t['ref'] ? $t['ref'] : '—' ); ?>
                                </span>
                                <a class="hrk-tb-title"
                                   href="<?php echo esc_url( $t['permalink'] ); ?>">
                                    <?php echo esc_html( $t['title'] ); ?>
                                </a>
                                <?php if ( $t['status'] === 'soon' ) : ?>
                                    <span class="hrk-tb-badge hrk-tb-badge-soon">Closing Soon</span>
                                <?php else : ?>
                                    <span class="hrk-tb-badge hrk-tb-badge-open">Open</span>
                                <?php endif; ?>
                                <span class="hrk-tb-date">
                                    Closes <?php echo esc_html( $t['close_fmt'] ); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="hrk-tb-list" data-panel="closed" style="display:none;">
                    <?php
                    $closed_tenders = array_filter( $tenders, function( $t ) {
                        return $t['status'] === 'closed';
                    } );
                    ?>
                    <?php if ( empty( $closed_tenders ) ) : ?>
                        <div class="hrk-tb-empty">No closed tenders found.</div>
                    <?php else : ?>
                        <?php foreach ( $closed_tenders as $t ) : ?>
                            <div class="hrk-tb-row">
                                <span class="hrk-tb-ref">
                                    <?php echo esc_html( $t['ref'] ? $t['ref'] : '—' ); ?>
                                </span>
                                <a class="hrk-tb-title"
                                   href="<?php echo esc_url( $t['permalink'] ); ?>">
                                    <?php echo esc_html( $t['title'] ); ?>
                                </a>
                                <span class="hrk-tb-badge hrk-tb-badge-closed">Closed</span>
                                <span class="hrk-tb-date">
                                    Closed <?php echo esc_html( $t['close_fmt'] ); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <script>
        (function() {
            var wrap = document.getElementById('<?php echo esc_js( $uid ); ?>');
            var tabs = wrap.querySelectorAll('.hrk-tb-tab');
            var panels = wrap.querySelectorAll('.hrk-tb-list');

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var target = tab.getAttribute('data-tab');
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    panels.forEach(function(panel) {
                        panel.style.display =
                            panel.getAttribute('data-panel') === target ? 'block' : 'none';
                    });
                });
            });
        })();
        </script>

        <?php
        return ob_get_clean();
    }
}


// ── Template B: Preview strip renderer ───────────────────────────────────────
if ( ! function_exists( 'haraka_tenders_render_template_b_preview' ) ) {
    function haraka_tenders_render_template_b_preview( $atts ) {

        $today = date( 'Y-m-d' );

        // ── Always query hrk_tender CPT ───────────────────────────────────────
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

        $query           = new WP_Query( $query_args );
        $label           = get_option( 'haraka_tenders_b_label',           'Tender Opportunities' );
        $headline        = get_option( 'haraka_tenders_b_headline',        'Open procurement' );
        $headline_italic = get_option( 'haraka_tenders_b_headline_italic', 'across the group.' );
        $all_text        = get_option( 'haraka_tenders_b_all_text',        'All tenders' );
        $view_all_text   = get_option( 'haraka_tenders_b_view_all_text',   'View all' );
        $view_all_url    = get_option( 'haraka_tenders_page_url',          home_url( '/tenders' ) );
        $total           = $query->post_count;
        $uid             = 'tbp-' . uniqid();

        ob_start();
        ?>

        <div class="hrk-tb-wrap" id="<?php echo esc_attr( $uid ); ?>">

            <div class="hrk-tb-header">
                <div class="hrk-tb-header-left">
                    <?php if ( ! empty( $label ) ) : ?>
                        <div class="hrk-tb-label">
                            <span class="hrk-tb-label-dash">&mdash;</span>
                            <?php echo esc_html( $label ); ?>
                        </div>
                    <?php endif; ?>
                    <h2 class="hrk-tb-headline">
                        <?php echo esc_html( $headline ); ?>
                        <?php if ( ! empty( $headline_italic ) ) : ?>
                            <em><?php echo esc_html( $headline_italic ); ?></em>
                        <?php endif; ?>
                    </h2>
                </div>
                <a class="hrk-tb-all-link"
                   href="<?php echo esc_url( $view_all_url ); ?>">
                    <?php echo esc_html( $all_text ); ?> &nbsp;&rarr;
                </a>
            </div>

            <div class="hrk-tb-panel">
                <div class="hrk-tb-tabs hrk-tb-tabs-preview">
                    <div class="hrk-tb-tab active hrk-tb-tab-static">
                        <span class="hrk-tb-tab-dot hrk-tb-dot-green"></span>
                        Open Tenders
                        <?php if ( $total > 0 ) : ?>
                            <span class="hrk-tb-tab-count">
                                <?php echo esc_html( $total ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <a class="hrk-tb-tab-viewall"
                       href="<?php echo esc_url( $view_all_url ); ?>">
                        <?php echo esc_html( $view_all_text ); ?> &rarr;
                    </a>
                </div>

                <div class="hrk-tb-list">
                    <?php if ( $query->have_posts() ) : ?>
                        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                            <?php
                            $post_id    = get_the_ID();
                            $close_date = get_post_meta( $post_id, 'tender_close_date', true );
                            $ref        = get_post_meta( $post_id, 'tender_ref',        true );
                            $status     = haraka_get_tender_status( $close_date );
                            $close_fmt  = haraka_format_tender_date( $close_date );
                            ?>
                            <div class="hrk-tb-row">
                                <span class="hrk-tb-ref">
                                    <?php echo esc_html( $ref ? $ref : '—' ); ?>
                                </span>
                                <a class="hrk-tb-title"
                                   href="<?php echo esc_url( get_permalink() ); ?>">
                                    <?php echo esc_html( get_the_title() ); ?>
                                </a>
                                <?php if ( $status === 'soon' ) : ?>
                                    <span class="hrk-tb-badge hrk-tb-badge-soon">Closing Soon</span>
                                <?php else : ?>
                                    <span class="hrk-tb-badge hrk-tb-badge-open">Open</span>
                                <?php endif; ?>
                                <span class="hrk-tb-date">
                                    Closes <?php echo esc_html( $close_fmt ); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <div class="hrk-tb-empty">No open tenders at this time.</div>
                    <?php endif; ?>

                    <div class="hrk-tb-footer-row">
                        <a class="hrk-tb-footer-link"
                           href="<?php echo esc_url( $view_all_url ); ?>">
                            View all tender opportunities &rarr;
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <?php
        return ob_get_clean();
    }
}