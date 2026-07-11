<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MetCPT Error Log — Admin Dashboard UI
 *
 * Renders the Error Log tab inside MetCPT Settings.
 * Includes summary cards, 7-day trend chart, level
 * distribution, filterable log table, detail modal,
 * Mark Resolved, and Copy for Claude.
 *
 * @package MetCPT
 * @subpackage Admin
 * @version 1.0.4
 */

// ── Fetch all logs (unfiltered) for stats ─────────────────────────────────────
function metcpt_error_log_get_all_stats() {
    global $wpdb;
    $table = metcpt_error_log_table();
    return $wpdb->get_results( "SELECT level, resolved, created_at FROM {$table} ORDER BY created_at DESC", ARRAY_A );
}

// ── Fetch filtered logs for table ─────────────────────────────────────────────
function metcpt_error_log_get_entries( $filters = array() ) {
    global $wpdb;

    $table  = metcpt_error_log_table();
    $where  = array( '1=1' );
    $values = array();

    if ( ! empty( $filters['level'] ) && $filters['level'] !== 'all' ) {
        $where[]  = 'level = %s';
        $values[] = sanitize_text_field( $filters['level'] );
    }

    if ( isset( $filters['resolved'] ) && $filters['resolved'] !== 'all' ) {
        $where[]  = 'resolved = %d';
        $values[] = (int) $filters['resolved'];
    }

    $where_sql = implode( ' AND ', $where );
    $sql       = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT 200";

    if ( ! empty( $values ) ) {
        $sql = $wpdb->prepare( $sql, ...$values );
    }

    return $wpdb->get_results( $sql, ARRAY_A );
}

// ── Render the Error Log tab ──────────────────────────────────────────────────
function metcpt_render_error_log_tab() {

    $filter_level    = isset( $_GET['log_level'] )    ? sanitize_text_field( $_GET['log_level'] )    : 'all';
    $filter_resolved = isset( $_GET['log_resolved'] ) ? sanitize_text_field( $_GET['log_resolved'] ) : '0';

    $all_stats = metcpt_error_log_get_all_stats();
    $entries   = metcpt_error_log_get_entries( array(
        'level'    => $filter_level,
        'resolved' => $filter_resolved === 'all' ? 'all' : (int) $filter_resolved,
    ) );

    $nonce = wp_create_nonce( 'metcpt_error_log' );

    // ── Compute summary stats ─────────────────────────────────────────────────
    $stat_total      = 0;
    $stat_critical   = 0;
    $stat_warnings   = 0;
    $stat_resolved_today = 0;
    $today_str       = date( 'Y-m-d' );

    $level_counts = array(
        'fatal'     => 0,
        'error'     => 0,
        'exception' => 0,
        'warning'   => 0,
        'notice'    => 0,
    );

    // ── 7-day trend data ──────────────────────────────────────────────────────
    $trend = array();
    for ( $i = 6; $i >= 0; $i-- ) {
        $day = date( 'Y-m-d', strtotime( "-{$i} days" ) );
        $trend[ $day ] = array(
            'label'     => date( 'D', strtotime( $day ) ),
            'fatal'     => 0,
            'error'     => 0,
            'exception' => 0,
            'warning'   => 0,
            'notice'    => 0,
            'total'     => 0,
        );
    }

    foreach ( $all_stats as $row ) {
        $level    = $row['level'];
        $resolved = (int) $row['resolved'];
        $day      = substr( $row['created_at'], 0, 10 );

        // Unresolved totals
        if ( ! $resolved ) {
            $stat_total++;
            if ( in_array( $level, array( 'fatal', 'exception' ), true ) ) {
                $stat_critical++;
            }
            if ( $level === 'warning' ) {
                $stat_warnings++;
            }
        }

        // Resolved today
        if ( $resolved && $day === $today_str ) {
            $stat_resolved_today++;
        }

        // Level distribution (all entries)
        if ( isset( $level_counts[ $level ] ) ) {
            $level_counts[ $level ]++;
        }

        // Trend (all entries within 7 days)
        if ( isset( $trend[ $day ] ) && isset( $trend[ $day ][ $level ] ) ) {
            $trend[ $day ][ $level ]++;
            $trend[ $day ]['total']++;
        }
    }

    $total_all   = array_sum( $level_counts );
    $trend_max   = max( array_column( $trend, 'total' ) );
    $trend_max   = $trend_max > 0 ? $trend_max : 1;

    $level_opts  = array( 'all', 'error', 'warning', 'notice', 'exception', 'fatal' );
    $total_table = count( $entries );
    ?>

    <div class="mcpt-eld-wrap" id="mcpt-log-wrap">

        <?php /* ── SECTION 1: Summary Cards ── */ ?>
        <div class="mcpt-eld-cards">

            <div class="mcpt-eld-card mcpt-eld-card-total">
                <div class="mcpt-eld-card-icon">⚠</div>
                <div class="mcpt-eld-card-body">
                    <div class="mcpt-eld-card-value"><?php echo esc_html( $stat_total ); ?></div>
                    <div class="mcpt-eld-card-label">Unresolved Issues</div>
                </div>
            </div>

            <div class="mcpt-eld-card mcpt-eld-card-critical">
                <div class="mcpt-eld-card-icon">✕</div>
                <div class="mcpt-eld-card-body">
                    <div class="mcpt-eld-card-value"><?php echo esc_html( $stat_critical ); ?></div>
                    <div class="mcpt-eld-card-label">Critical (Fatal + Exception)</div>
                </div>
            </div>

            <div class="mcpt-eld-card mcpt-eld-card-warning">
                <div class="mcpt-eld-card-icon">△</div>
                <div class="mcpt-eld-card-body">
                    <div class="mcpt-eld-card-value"><?php echo esc_html( $stat_warnings ); ?></div>
                    <div class="mcpt-eld-card-label">Warnings</div>
                </div>
            </div>

            <div class="mcpt-eld-card mcpt-eld-card-resolved">
                <div class="mcpt-eld-card-icon">✓</div>
                <div class="mcpt-eld-card-body">
                    <div class="mcpt-eld-card-value"><?php echo esc_html( $stat_resolved_today ); ?></div>
                    <div class="mcpt-eld-card-label">Resolved Today</div>
                </div>
            </div>

        </div>

        <?php /* ── SECTION 2 + 3: Charts row ── */ ?>
        <div class="mcpt-eld-charts-row">

            <?php /* ── 7-day trend ── */ ?>
            <div class="mcpt-eld-panel mcpt-eld-panel-trend">
                <div class="mcpt-eld-panel-header">
                    <div class="mcpt-eld-panel-title">7-Day Error Trend</div>
                    <div class="mcpt-eld-trend-legend">
                        <span class="mcpt-eld-legend-dot" style="background:#dc2626;"></span> Fatal/Error
                        <span class="mcpt-eld-legend-dot" style="background:#a855f7;"></span> Exception
                        <span class="mcpt-eld-legend-dot" style="background:#eab308;"></span> Warning
                        <span class="mcpt-eld-legend-dot" style="background:#64748b;"></span> Notice
                    </div>
                </div>
                <div class="mcpt-eld-chart-area">
                    <?php foreach ( $trend as $day => $data ) :
                        $bar_pct   = $trend_max > 0 ? round( ( $data['total'] / $trend_max ) * 100 ) : 0;
                        $is_today  = ( $day === $today_str );
                    ?>
                    <div class="mcpt-eld-bar-col <?php echo $is_today ? 'mcpt-eld-bar-today' : ''; ?>">
                        <div class="mcpt-eld-bar-count">
                            <?php echo $data['total'] > 0 ? esc_html( $data['total'] ) : ''; ?>
                        </div>
                        <div class="mcpt-eld-bar-track">
                            <div class="mcpt-eld-bar-stack" style="height:<?php echo esc_attr( $bar_pct ); ?>%;">
                                <?php if ( ( $data['fatal'] + $data['error'] ) > 0 ) :
                                    $pct = round( ( ( $data['fatal'] + $data['error'] ) / $data['total'] ) * 100 );
                                ?>
                                    <div class="mcpt-eld-bar-seg mcpt-eld-seg-error" style="height:<?php echo esc_attr( $pct ); ?>%;"></div>
                                <?php endif; ?>
                                <?php if ( $data['exception'] > 0 ) :
                                    $pct = round( ( $data['exception'] / $data['total'] ) * 100 );
                                ?>
                                    <div class="mcpt-eld-bar-seg mcpt-eld-seg-exception" style="height:<?php echo esc_attr( $pct ); ?>%;"></div>
                                <?php endif; ?>
                                <?php if ( $data['warning'] > 0 ) :
                                    $pct = round( ( $data['warning'] / $data['total'] ) * 100 );
                                ?>
                                    <div class="mcpt-eld-bar-seg mcpt-eld-seg-warning" style="height:<?php echo esc_attr( $pct ); ?>%;"></div>
                                <?php endif; ?>
                                <?php if ( $data['notice'] > 0 ) :
                                    $pct = round( ( $data['notice'] / $data['total'] ) * 100 );
                                ?>
                                    <div class="mcpt-eld-bar-seg mcpt-eld-seg-notice" style="height:<?php echo esc_attr( $pct ); ?>%;"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mcpt-eld-bar-label">
                            <?php echo esc_html( $data['label'] ); ?>
                            <?php if ( $is_today ) : ?>
                                <span class="mcpt-eld-today-dot"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php /* ── Level distribution ── */ ?>
            <div class="mcpt-eld-panel mcpt-eld-panel-dist">
                <div class="mcpt-eld-panel-header">
                    <div class="mcpt-eld-panel-title">Level Distribution</div>
                    <div class="mcpt-eld-panel-sub"><?php echo esc_html( $total_all ); ?> total entries</div>
                </div>
                <div class="mcpt-eld-dist-list">
                    <?php
                    $dist_levels = array(
                        'fatal'     => array( 'label' => 'Fatal',     'color' => '#dc2626' ),
                        'error'     => array( 'label' => 'Error',     'color' => '#f97316' ),
                        'exception' => array( 'label' => 'Exception', 'color' => '#a855f7' ),
                        'warning'   => array( 'label' => 'Warning',   'color' => '#eab308' ),
                        'notice'    => array( 'label' => 'Notice',    'color' => '#64748b' ),
                    );
                    foreach ( $dist_levels as $lvl => $meta ) :
                        $count = $level_counts[ $lvl ];
                        $pct   = $total_all > 0 ? round( ( $count / $total_all ) * 100 ) : 0;
                    ?>
                    <div class="mcpt-eld-dist-row">
                        <div class="mcpt-eld-dist-label"><?php echo esc_html( $meta['label'] ); ?></div>
                        <div class="mcpt-eld-dist-bar-track">
                            <div class="mcpt-eld-dist-bar-fill"
                                 style="width:<?php echo esc_attr( $pct ); ?>%; background:<?php echo esc_attr( $meta['color'] ); ?>;"></div>
                        </div>
                        <div class="mcpt-eld-dist-count">
                            <span class="mcpt-eld-dist-num"><?php echo esc_html( $count ); ?></span>
                            <span class="mcpt-eld-dist-pct"><?php echo esc_html( $pct ); ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mcpt-eld-health">
                    <?php if ( $stat_total === 0 ) : ?>
                        <div class="mcpt-eld-health-ok">
                            <span class="mcpt-eld-health-icon">✓</span>
                            System healthy — no unresolved issues
                        </div>
                    <?php elseif ( $stat_critical > 0 ) : ?>
                        <div class="mcpt-eld-health-critical">
                            <span class="mcpt-eld-health-icon">✕</span>
                            <?php echo esc_html( $stat_critical ); ?> critical issue<?php echo $stat_critical > 1 ? 's' : ''; ?> need attention
                        </div>
                    <?php else : ?>
                        <div class="mcpt-eld-health-warn">
                            <span class="mcpt-eld-health-icon">△</span>
                            <?php echo esc_html( $stat_total ); ?> issue<?php echo $stat_total > 1 ? 's' : ''; ?> unresolved
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <?php /* ── SECTION 4: Log Table ── */ ?>
        <div class="mcpt-eld-panel mcpt-eld-panel-table">

            <div class="mcpt-eld-panel-header mcpt-eld-table-header">
                <div class="mcpt-eld-panel-title">Log Entries</div>
                <div class="mcpt-eld-table-controls">

                    <div class="mcpt-eld-filter-row">
                        <?php foreach ( $level_opts as $lvl ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $lvl, 'log_resolved' => $filter_resolved ) ) ); ?>"
                               class="mcpt-eld-filter-btn <?php echo $filter_level === $lvl ? 'active' : ''; ?>">
                                <?php echo esc_html( ucfirst( $lvl ) ); ?>
                            </a>
                        <?php endforeach; ?>
                        <span class="mcpt-eld-filter-sep">|</span>
                        <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $filter_level, 'log_resolved' => '0' ) ) ); ?>"
                           class="mcpt-eld-filter-btn <?php echo $filter_resolved === '0' ? 'active' : ''; ?>">
                            Unresolved
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $filter_level, 'log_resolved' => '1' ) ) ); ?>"
                           class="mcpt-eld-filter-btn <?php echo $filter_resolved === '1' ? 'active' : ''; ?>">
                            Resolved
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $filter_level, 'log_resolved' => 'all' ) ) ); ?>"
                           class="mcpt-eld-filter-btn <?php echo $filter_resolved === 'all' ? 'active' : ''; ?>">
                            All
                        </a>
                    </div>

                    <div class="mcpt-eld-table-actions">
                        <span class="mcpt-eld-entry-count">
                            <?php echo esc_html( $total_table ); ?> entr<?php echo $total_table === 1 ? 'y' : 'ies'; ?>
                        </span>
                        <button type="button"
                                class="button mcpt-log-clear-resolved"
                                data-nonce="<?php echo esc_attr( $nonce ); ?>">
                            Clear Resolved
                        </button>
                    </div>

                </div>
            </div>

            <?php if ( empty( $entries ) ) : ?>
                <div class="mcpt-eld-empty">
                    <div class="mcpt-eld-empty-icon">✓</div>
                    <p class="mcpt-eld-empty-title">No log entries found</p>
                    <p class="mcpt-eld-empty-desc">
                        MetCPT will capture errors, warnings, exceptions, and fatal errors
                        from its own files and display them here.
                    </p>
                </div>
            <?php else : ?>
                <div class="mcpt-eld-table-wrap">
                    <table class="mcpt-eld-table">
                        <thead>
                            <tr>
                                <th class="col-time">Timestamp</th>
                                <th class="col-level">Level</th>
                                <th class="col-msg">Message</th>
                                <th class="col-source">Source</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $entries as $entry ) : ?>
                                <tr class="mcpt-eld-row mcpt-eld-level-<?php echo esc_attr( $entry['level'] ); ?> <?php echo $entry['resolved'] ? 'mcpt-eld-resolved' : ''; ?>"
                                    data-id="<?php echo esc_attr( $entry['id'] ); ?>">

                                    <td class="col-time">
                                        <span class="mcpt-eld-time-date">
                                            <?php echo esc_html( date( 'd M Y', strtotime( $entry['created_at'] ) ) ); ?>
                                        </span>
                                        <span class="mcpt-eld-time-clock">
                                            <?php echo esc_html( date( 'H:i:s', strtotime( $entry['created_at'] ) ) ); ?>
                                        </span>
                                    </td>

                                    <td class="col-level">
                                        <span class="mcpt-log-badge mcpt-log-badge-<?php echo esc_attr( $entry['level'] ); ?>">
                                            <?php echo esc_html( strtoupper( $entry['level'] ) ); ?>
                                        </span>
                                    </td>

                                    <td class="col-msg">
                                        <div class="mcpt-eld-msg"><?php echo esc_html( $entry['message'] ); ?></div>
                                        <?php if ( ! empty( $entry['function'] ) ) : ?>
                                            <div class="mcpt-eld-fn"><?php echo esc_html( $entry['function'] ); ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="col-source">
                                        <div class="mcpt-eld-src"><?php echo esc_html( $entry['source'] ); ?></div>
                                        <div class="mcpt-eld-src-line">Line <?php echo esc_html( $entry['line'] ); ?></div>
                                    </td>

                                    <td class="col-actions">
                                        <div class="mcpt-eld-actions">
                                            <button type="button"
                                                    class="mcpt-eld-btn mcpt-eld-btn-view mcpt-log-view-btn"
                                                    data-id="<?php echo esc_attr( $entry['id'] ); ?>"
                                                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                                Details
                                            </button>
                                            <?php if ( ! $entry['resolved'] ) : ?>
                                                <button type="button"
                                                        class="mcpt-eld-btn mcpt-eld-btn-resolve mcpt-log-resolve-btn"
                                                        data-id="<?php echo esc_attr( $entry['id'] ); ?>"
                                                        data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                                    Resolve
                                                </button>
                                            <?php else : ?>
                                                <span class="mcpt-eld-resolved-tag">✓ Resolved</span>
                                            <?php endif; ?>
                                            <button type="button"
                                                    class="mcpt-eld-btn mcpt-eld-btn-copy mcpt-log-copy-btn"
                                                    data-id="<?php echo esc_attr( $entry['id'] ); ?>"
                                                    data-level="<?php echo esc_attr( $entry['level'] ); ?>"
                                                    data-message="<?php echo esc_attr( $entry['message'] ); ?>"
                                                    data-file="<?php echo esc_attr( $entry['file'] ); ?>"
                                                    data-source="<?php echo esc_attr( $entry['source'] ); ?>"
                                                    data-line="<?php echo esc_attr( $entry['line'] ); ?>"
                                                    data-function="<?php echo esc_attr( $entry['function'] ); ?>"
                                                    data-trace="<?php echo esc_attr( $entry['trace'] ); ?>"
                                                    data-context="<?php echo esc_attr( $entry['context'] ); ?>">
                                                Claude
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <?php /* ── Detail Modal ── */ ?>
    <div class="mcpt-log-modal-overlay" id="mcpt-log-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="mcpt-log-modal-title">
        <div class="mcpt-log-modal">
            <div class="mcpt-log-modal-header">
                <h2 class="mcpt-log-modal-title" id="mcpt-log-modal-title">Error Details</h2>
                <button type="button" class="mcpt-log-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="mcpt-log-modal-body" id="mcpt-log-modal-body">
                <div class="mcpt-log-modal-loading">Loading&hellip;</div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    (function() {

        var wrap      = document.getElementById('mcpt-log-wrap');
        var modal     = document.getElementById('mcpt-log-modal');
        var modalBody = document.getElementById('mcpt-log-modal-body');
        var ajaxUrl   = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

        function escHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(String(str || '')));
            return d.innerHTML;
        }

        function openModal()  { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            modalBody.innerHTML = '<div class="mcpt-log-modal-loading">Loading&hellip;</div>';
        }

        document.querySelector('.mcpt-log-modal-close').addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });

        // ── View Details ──────────────────────────────────────────────────────
        wrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.mcpt-log-view-btn');
            if (!btn) return;
            var id    = btn.getAttribute('data-id');
            var nonce = btn.getAttribute('data-nonce');
            openModal();
            fetch(ajaxUrl + '?action=metcpt_get_log_entry&id=' + id + '&nonce=' + nonce)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) { modalBody.innerHTML = '<p style="color:#dc2626;padding:20px;">Failed to load.</p>'; return; }
                    renderModal(data.data);
                })
                .catch(function() { modalBody.innerHTML = '<p style="color:#dc2626;padding:20px;">Network error.</p>'; });
        });

        function renderModal(entry) {
            var trace   = Array.isArray(entry.trace)   ? entry.trace   : [];
            var context = entry.context && typeof entry.context === 'object' ? entry.context : {};

            var traceHtml = trace.length
                ? trace.map(function(f, i) {
                    return '<div class="mcpt-log-trace-frame">'
                         + '<span class="mcpt-log-trace-num">#' + i + '</span>'
                         + '<span class="mcpt-log-trace-file">' + escHtml(f.file || '') + ':' + escHtml(f.line || '') + '</span>'
                         + '<span class="mcpt-log-trace-fn">'   + escHtml((f.class||'') + (f.type||'') + (f.function||'') + '()') + '</span>'
                         + '</div>';
                  }).join('')
                : '<p class="mcpt-log-muted">No stack trace available.</p>';

            var ctxHtml = Object.keys(context).map(function(k) {
                return '<div class="mcpt-log-ctx-row">'
                     + '<span class="mcpt-log-ctx-key">' + escHtml(k)            + '</span>'
                     + '<span class="mcpt-log-ctx-val">' + escHtml(context[k])   + '</span>'
                     + '</div>';
            }).join('');

            modalBody.innerHTML = ''
                + '<div class="mcpt-log-modal-meta">'
                +   '<span class="mcpt-log-badge mcpt-log-badge-' + escHtml(entry.level) + '">' + escHtml(entry.level.toUpperCase()) + '</span>'
                +   '<span class="mcpt-log-modal-time">' + escHtml(entry.created_at) + '</span>'
                + '</div>'
                + '<div class="mcpt-log-modal-section"><div class="mcpt-log-modal-section-title">Message</div>'
                +   '<p class="mcpt-log-modal-message">' + escHtml(entry.message) + '</p></div>'
                + '<div class="mcpt-log-modal-section"><div class="mcpt-log-modal-section-title">Location</div>'
                +   '<p class="mcpt-log-muted">' + escHtml(entry.file) + ' &mdash; Line ' + escHtml(entry.line) + '</p>'
                +   (entry.function ? '<p class="mcpt-log-muted">Function: ' + escHtml(entry.function) + '</p>' : '')
                + '</div>'
                + '<div class="mcpt-log-modal-section"><div class="mcpt-log-modal-section-title">Stack Trace</div>'
                +   '<div class="mcpt-log-trace">' + traceHtml + '</div></div>'
                + '<div class="mcpt-log-modal-section"><div class="mcpt-log-modal-section-title">Request Context</div>'
                +   '<div class="mcpt-log-ctx">' + ctxHtml + '</div></div>';
        }

        // ── Mark Resolved ─────────────────────────────────────────────────────
        wrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.mcpt-log-resolve-btn');
            if (!btn) return;
            var id    = btn.getAttribute('data-id');
            var nonce = btn.getAttribute('data-nonce');
            var row   = btn.closest('.mcpt-eld-row');
            btn.disabled = true;
            btn.textContent = '…';
            var body = new FormData();
            body.append('action', 'metcpt_resolve_log');
            body.append('nonce',  nonce);
            body.append('id',     id);
            fetch(ajaxUrl, { method: 'POST', body: body })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        row.classList.add('mcpt-eld-resolved');
                        btn.replaceWith(Object.assign(
                            document.createElement('span'),
                            { className: 'mcpt-eld-resolved-tag', textContent: '✓ Resolved' }
                        ));
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Resolve';
                    }
                })
                .catch(function() { btn.disabled = false; btn.textContent = 'Resolve'; });
        });

        // ── Copy for Claude ───────────────────────────────────────────────────
        wrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.mcpt-log-copy-btn');
            if (!btn) return;

            var level    = btn.getAttribute('data-level')    || '';
            var message  = btn.getAttribute('data-message')  || '';
            var file     = btn.getAttribute('data-file')     || '';
            var source   = btn.getAttribute('data-source')   || '';
            var line     = btn.getAttribute('data-line')     || '';
            var fn       = btn.getAttribute('data-function') || '';
            var traceRaw = btn.getAttribute('data-trace')    || '[]';
            var ctxRaw   = btn.getAttribute('data-context')  || '{}';

            var trace = []; var context = {};
            try { trace   = JSON.parse(traceRaw); } catch(x) {}
            try { context = JSON.parse(ctxRaw);   } catch(x) {}

            var traceText = trace.slice(0,10).map(function(f,i) {
                return '#'+i+' '+(f.file||'')+':'+(f.line||'')+' '+((f.class||'')+(f.type||'')+(f.function||'')+'()');
            }).join('\n');

            var ctxText = Object.keys(context).map(function(k) {
                return k+': '+context[k];
            }).join('\n');

            var prompt = [
                '## MetCPT Plugin Error Report',
                '',
                '**Level:** '    + level.toUpperCase(),
                '**Message:** '  + message,
                '**File:** '     + file,
                '**Source:** '   + source,
                '**Line:** '     + line,
                '**Function:** ' + (fn || 'N/A'),
                '',
                '### Stack Trace',
                '```',
                traceText || 'No trace available.',
                '```',
                '',
                '### Request Context',
                '```',
                ctxText || 'No context available.',
                '```',
                '',
                'Please diagnose this error and provide the fix.',
            ].join('\n');

            navigator.clipboard.writeText(prompt)
                .then(function() {
                    var orig = btn.textContent;
                    btn.textContent = '✓';
                    setTimeout(function() { btn.textContent = orig; }, 2000);
                })
                .catch(function() {
                    var ta = document.createElement('textarea');
                    ta.value = prompt; ta.style.position='fixed'; ta.style.opacity='0';
                    document.body.appendChild(ta); ta.select(); document.execCommand('copy');
                    document.body.removeChild(ta);
                    var orig = btn.textContent;
                    btn.textContent = '✓';
                    setTimeout(function() { btn.textContent = orig; }, 2000);
                });
        });

        // ── Clear Resolved ────────────────────────────────────────────────────
        var clearBtn = document.querySelector('.mcpt-log-clear-resolved');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (!confirm('Delete all resolved log entries? This cannot be undone.')) return;
                var nonce = clearBtn.getAttribute('data-nonce');
                clearBtn.disabled = true;
                clearBtn.textContent = 'Clearing…';
                var body = new FormData();
                body.append('action', 'metcpt_clear_resolved');
                body.append('nonce',  nonce);
                fetch(ajaxUrl, { method: 'POST', body: body })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) { window.location.reload(); }
                        else { clearBtn.disabled = false; clearBtn.textContent = 'Clear Resolved'; }
                    })
                    .catch(function() { clearBtn.disabled = false; clearBtn.textContent = 'Clear Resolved'; });
            });
        }

    })();
    }); // DOMContentLoaded
    </script>

    <?php
}