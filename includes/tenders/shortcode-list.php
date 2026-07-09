<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function metcpt_tenders_list_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'posts_per_page'  => -1,
            'filter'          => 'all',
            'order'           => 'ASC',
            'category'        => '',
            'label'           => '',
            'headline'        => '',
            'headline_italic' => '',
            'view_all_text'   => '',
            'template'        => '',
        ),
        $atts,
        'tenders_list'
    );

    // ── Route to correct template ─────────────────────────────────────────────
    // An explicit template="a"|"b" on the shortcode overrides the global
    // Active Template setting, so different pages can show different layouts.
    $template = $atts['template'] !== ''
        ? strtolower( $atts['template'] )
        : get_option( 'metcpt_tenders_template', 'a' );

    if ( $template === 'b' ) {
        return metcpt_tenders_render_template_b_list( $atts );
    }

    // ── Template A — table layout ─────────────────────────────────────────────
    $today  = date( 'Y-m-d' );
    $filter = sanitize_text_field( $atts['filter'] );

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

    // ── Base query — always uses metcpt_tender CPT ───────────────────────────────
    $query_args = array(
        'post_type'      => 'metcpt_tender',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'orderby'        => 'meta_value',
        'meta_key'       => 'tender_close_date',
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
                'excerpt'   => get_the_excerpt(),
                'permalink' => get_permalink(),
                'ref'       => get_post_meta( $post_id, 'tender_ref',          true ),
                'issuer'    => get_post_meta( $post_id, 'tender_issuer',       true ),
                'location'  => get_post_meta( $post_id, 'tender_location',     true ),
                'category'  => get_post_meta( $post_id, 'tender_category',     true ),
                'doc_url'   => get_post_meta( $post_id, 'tender_document_url', true ),
                'close_date'=> $close_date,
                'close_fmt' => metcpt_format_tender_date( $close_date ),
                'status'    => metcpt_get_tender_status( $close_date ),
            );
        endwhile;
        wp_reset_postdata();
    endif;

    $uid = 'tdl-' . uniqid();

    ob_start();
    ?>

    <div class="tdl-wrap" id="<?php echo esc_attr( $uid ); ?>">

        <div class="tdl-toolbar">
            <input class="tdl-search"
                   type="text"
                   placeholder="Search by title, ref or issuer..."
                   aria-label="Search tenders" />
            <div class="tdl-tabs" role="tablist">
                <button class="tdl-tab active" data-filter="all"    role="tab">All</button>
                <button class="tdl-tab"        data-filter="open"   role="tab">Open</button>
                <button class="tdl-tab"        data-filter="soon"   role="tab">Closing Soon</button>
                <button class="tdl-tab"        data-filter="closed" role="tab">Closed</button>
            </div>
            <span class="tdl-count"></span>
        </div>

        <div class="tdl-table-wrap">
            <table class="tdl-table">
                <thead>
                    <tr>
                        <th class="col-ref">Ref No.</th>
                        <th class="col-title">Tender Title</th>
                        <th class="col-issuer">Issuer</th>
                        <th class="col-loc">Location</th>
                        <th class="col-date">Closing Date</th>
                        <th class="col-doc">Document</th>
                    </tr>
                </thead>
                <tbody class="tdl-tbody">
                <?php if ( empty( $tenders ) ) : ?>
                    <tr>
                        <td colspan="6" class="tdl-empty">No tenders found.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $tenders as $t ) : ?>
                    <tr data-status="<?php echo esc_attr( $t['status'] ); ?>"
                        data-search="<?php echo esc_attr( strtolower( $t['title'] . ' ' . $t['ref'] . ' ' . $t['issuer'] ) ); ?>">
                        <td>
                            <div class="tdl-ref">
                                <?php echo esc_html( $t['ref'] ? $t['ref'] : '—' ); ?>
                            </div>
                            <div class="tdl-badge-wrap">
                                <?php if ( $t['status'] === 'open' ) : ?>
                                    <span class="tdl-badge tdl-badge-open">Open</span>
                                <?php elseif ( $t['status'] === 'soon' ) : ?>
                                    <span class="tdl-badge tdl-badge-soon">Closing Soon</span>
                                <?php else : ?>
                                    <span class="tdl-badge tdl-badge-closed">Closed</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="tdl-title">
                                <a href="<?php echo esc_url( $t['permalink'] ); ?>">
                                    <?php echo esc_html( $t['title'] ); ?>
                                </a>
                            </div>
                            <?php if ( ! empty( $t['excerpt'] ) ) : ?>
                                <div class="tdl-desc">
                                    <?php echo esc_html( $t['excerpt'] ); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="tdl-issuer">
                            <?php echo esc_html( $t['issuer'] ? $t['issuer'] : '—' ); ?>
                        </td>
                        <td class="tdl-location">
                            <?php echo esc_html( $t['location'] ? $t['location'] : '—' ); ?>
                        </td>
                        <td class="tdl-date">
                            <?php echo esc_html( $t['close_fmt'] ); ?>
                        </td>
                        <td class="tdl-doc-cell">
                            <?php if ( ! empty( $t['doc_url'] ) ) : ?>
                                <a href="<?php echo esc_url( $t['doc_url'] ); ?>"
                                   class="tdl-btn<?php echo $t['status'] === 'closed' ? ' tdl-btn-closed' : ''; ?>"
                                   target="_blank"
                                   rel="noopener noreferrer">View</a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $t['permalink'] ); ?>"
                                   class="tdl-btn">Details</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="tdl-footer">
            <span class="tdl-result-count"></span>
        </div>

    </div>

    <script>
    (function() {
        var wrap   = document.getElementById('<?php echo esc_js( $uid ); ?>');
        var search = wrap.querySelector('.tdl-search');
        var tabs   = wrap.querySelectorAll('.tdl-tab');
        var rows   = wrap.querySelectorAll('.tdl-tbody tr[data-status]');
        var count  = wrap.querySelector('.tdl-count');
        var footer = wrap.querySelector('.tdl-result-count');
        var currentFilter = 'all';
        var currentSearch = '';

        function updateCount(n) {
            var label = n === 1 ? '1 tender' : n + ' tenders';
            count.textContent  = 'Showing ' + label;
            footer.textContent = 'Showing ' + label;
        }

        function applyFilters() {
            var visible = 0;
            rows.forEach(function(row) {
                var status = row.getAttribute('data-status');
                var text   = row.getAttribute('data-search');
                var matchF = currentFilter === 'all'
                             || status === currentFilter
                             || (currentFilter === 'open' && status === 'soon');
                var matchS = currentSearch === ''
                             || text.indexOf(currentSearch) > -1;
                var show   = matchF && matchS;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            updateCount(visible);
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabs.forEach(function(t) { t.classList.remove('active'); });
                tab.classList.add('active');
                currentFilter = tab.getAttribute('data-filter');
                applyFilters();
            });
        });

        search.addEventListener('input', function() {
            currentSearch = this.value.toLowerCase().trim();
            applyFilters();
        });

        applyFilters();
    })();
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode( 'tenders_list', 'metcpt_tenders_list_shortcode' );