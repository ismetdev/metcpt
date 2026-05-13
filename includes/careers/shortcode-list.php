<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Helper: career status from close date ─────────────────────────────────────
if ( ! function_exists( 'haraka_get_career_status' ) ) {
    function haraka_get_career_status( $close_date_str ) {
        if ( empty( $close_date_str ) ) {
            return 'open';
        }
        $today      = new DateTime( 'today' );
        $close_date = date_create( $close_date_str );
        if ( ! $close_date ) {
            return 'open';
        }
        if ( $close_date < $today ) {
            return 'closed';
        }
        $diff = (int) $today->diff( $close_date )->days;
        if ( $diff <= 7 ) {
            return 'soon';
        }
        return 'open';
    }
}

// ── Helper: format career close date ─────────────────────────────────────────
if ( ! function_exists( 'haraka_format_career_date' ) ) {
    function haraka_format_career_date( $close_date_str ) {
        if ( empty( $close_date_str ) ) {
            return 'TBC';
        }
        $date = date_create( $close_date_str );
        return $date ? $date->format( 'd M Y' ) : esc_html( $close_date_str );
    }
}


// ── [careers_list] shortcode ──────────────────────────────────────────────────
function haraka_careers_list_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'filter'         => 'all',   // all | open | closed
            'company'        => '',      // hrk_company post slug
            'department'     => '',      // e.g. ICT
            'location'       => '',      // partial text match
            'type'           => '',      // Full Time | Part Time | Contract | Internship
            'posts_per_page' => -1,
            'order'          => 'ASC',
        ),
        $atts,
        'careers_list'
    );

    $today  = date( 'Y-m-d' );
    $filter = sanitize_text_field( $atts['filter'] );

    // ── Build meta query ──────────────────────────────────────────────────────
    $meta_query = array(
        'relation' => 'AND',
        array(
            'key'     => 'career_close_date',
            'compare' => 'EXISTS',
        ),
    );

    // Status filter
    if ( $filter === 'open' ) {
        $meta_query[] = array(
            'key'     => 'career_close_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        );
    } elseif ( $filter === 'closed' ) {
        $meta_query[] = array(
            'key'     => 'career_close_date',
            'value'   => $today,
            'compare' => '<',
            'type'    => 'DATE',
        );
    }

    // Department filter
    if ( ! empty( $atts['department'] ) ) {
        $meta_query[] = array(
            'key'     => 'career_department',
            'value'   => sanitize_text_field( $atts['department'] ),
            'compare' => '=',
        );
    }

    // Job type filter
    if ( ! empty( $atts['type'] ) ) {
        $meta_query[] = array(
            'key'     => 'career_type',
            'value'   => sanitize_text_field( $atts['type'] ),
            'compare' => '=',
        );
    }

    // Location filter — partial match
    if ( ! empty( $atts['location'] ) ) {
        $meta_query[] = array(
            'key'     => 'career_location',
            'value'   => sanitize_text_field( $atts['location'] ),
            'compare' => 'LIKE',
        );
    }

    // Company filter — resolve slug to post ID
    if ( ! empty( $atts['company'] ) ) {
        $company_post = get_page_by_path(
            sanitize_text_field( $atts['company'] ),
            OBJECT,
            'hrk_company'
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
        'post_type'      => 'hrk_career',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'orderby'        => 'meta_value',
        'meta_key'       => 'career_close_date',
        'order'          => sanitize_text_field( $atts['order'] ),
        'meta_query'     => $meta_query,
    );

    $query   = new WP_Query( $query_args );
    $careers = array();

    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) :
            $query->the_post();
            $post_id    = get_the_ID();
            $company_id = get_post_meta( $post_id, 'career_company_id', true );
            $close_date = get_post_meta( $post_id, 'career_close_date', true );

            // Resolve company name from post ID
            // Resolve company name from post ID
            $company_name = '';
            if ( ! empty( $company_id ) ) {
                $short = get_post_meta( (int) $company_id, 'company_short_name', true );
                $company_name = $short ? $short : get_the_title( (int) $company_id );
            }

            $careers[] = array(
                'id'           => $post_id,
                'title'        => get_the_title(),
                'permalink'    => get_permalink(),
                'company_name' => $company_name,
                'department'   => get_post_meta( $post_id, 'career_department',  true ),
                'location'     => get_post_meta( $post_id, 'career_location',    true ),
                'type'         => get_post_meta( $post_id, 'career_type',        true ),
                'close_date'   => $close_date,
                'close_fmt'    => haraka_format_career_date( $close_date ),
                'status'       => haraka_get_career_status( $close_date ),
            );
        endwhile;
        wp_reset_postdata();
    endif;

    $uid = 'crl-' . uniqid();

    ob_start();
    ?>

    <div class="crl-wrap" id="<?php echo esc_attr( $uid ); ?>">

        <div class="crl-toolbar">
            <input class="crl-search"
                   type="text"
                   placeholder="Search by title, company or department..."
                   aria-label="Search careers" />
            <div class="crl-tabs" role="tablist">
                <button class="crl-tab active" data-filter="all"    role="tab">All</button>
                <button class="crl-tab"        data-filter="open"   role="tab">Open</button>
                <button class="crl-tab"        data-filter="soon"   role="tab">Closing Soon</button>
                <button class="crl-tab"        data-filter="closed" role="tab">Closed</button>
            </div>
            <span class="crl-count"></span>
        </div>

        <div class="crl-table-wrap">
            <table class="crl-table">
                <thead>
                    <tr>
                        <th class="col-title">Position</th>
                        <th class="col-company">Company</th>
                        <th class="col-dept">Department</th>
                        <th class="col-loc">Location</th>
                        <th class="col-type">Type</th>
                        <th class="col-date">Closes</th>
                    </tr>
                </thead>
                <tbody class="crl-tbody">
                <?php if ( empty( $careers ) ) : ?>
                    <tr>
                        <td colspan="6" class="crl-empty">No positions found.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $careers as $c ) : ?>
                    <tr data-status="<?php echo esc_attr( $c['status'] ); ?>"
                        data-search="<?php echo esc_attr( strtolower(
                            $c['title'] . ' ' .
                            $c['company_name'] . ' ' .
                            $c['department'] . ' ' .
                            $c['location']
                        ) ); ?>">
                        <td>
                            <div class="crl-title">
                                <a href="<?php echo esc_url( $c['permalink'] ); ?>">
                                    <?php echo esc_html( $c['title'] ); ?>
                                </a>
                            </div>
                            <div class="crl-badge-wrap">
                                <?php if ( $c['status'] === 'open' ) : ?>
                                    <span class="crl-badge crl-badge-open">Open</span>
                                <?php elseif ( $c['status'] === 'soon' ) : ?>
                                    <span class="crl-badge crl-badge-soon">Closing Soon</span>
                                <?php else : ?>
                                    <span class="crl-badge crl-badge-closed">Closed</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="crl-company"><?php echo esc_html( $c['company_name'] ? $c['company_name'] : '—' ); ?></td>
                        <td class="crl-dept"><?php echo esc_html( $c['department'] ? $c['department'] : '—' ); ?></td>
                        <td class="crl-location"><?php echo esc_html( $c['location'] ? $c['location'] : '—' ); ?></td>
                        <td class="crl-type"><?php echo esc_html( $c['type'] ? $c['type'] : '—' ); ?></td>
                        <td class="crl-date"><?php echo esc_html( $c['close_fmt'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="crl-footer">
            <span class="crl-result-count"></span>
        </div>

    </div>

    <script>
    (function() {
        var wrap   = document.getElementById('<?php echo esc_js( $uid ); ?>');
        var search = wrap.querySelector('.crl-search');
        var tabs   = wrap.querySelectorAll('.crl-tab');
        var rows   = wrap.querySelectorAll('.crl-tbody tr[data-status]');
        var count  = wrap.querySelector('.crl-count');
        var footer = wrap.querySelector('.crl-result-count');
        var currentFilter = 'all';
        var currentSearch = '';

        function updateCount(n) {
            var label = n === 1 ? '1 position' : n + ' positions';
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
add_shortcode( 'careers_list', 'haraka_careers_list_shortcode' );