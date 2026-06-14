<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Haraka Error Log — Admin UI
 *
 * Renders the Error Log tab inside Haraka Settings.
 * Handles the filterable log table, detail modal,
 * Mark Resolved action, and Copy for Claude button.
 *
 * @package Haraka
 * @subpackage Admin
 * @version 1.0.0
 */

// ── Fetch logs from DB ────────────────────────────────────────────────────────
function haraka_error_log_get_entries( $filters = array() ) {
    global $wpdb;

    $table  = haraka_error_log_table();
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
function haraka_render_error_log_tab() {

    $filter_level    = isset( $_GET['log_level'] )    ? sanitize_text_field( $_GET['log_level'] )    : 'all';
    $filter_resolved = isset( $_GET['log_resolved'] ) ? sanitize_text_field( $_GET['log_resolved'] ) : '0';

    $entries = haraka_error_log_get_entries( array(
        'level'    => $filter_level,
        'resolved' => $filter_resolved === 'all' ? 'all' : (int) $filter_resolved,
    ) );

    $nonce       = wp_create_nonce( 'haraka_error_log' );
    $total       = count( $entries );
    $level_opts  = array( 'all', 'error', 'warning', 'notice', 'exception', 'fatal' );
    ?>

    <div class="hrk-log-wrap" id="hrk-log-wrap">

        <div class="hrk-log-toolbar">

            <div class="hrk-log-filters">

                <div class="hrk-log-filter-group">
                    <label class="hrk-log-filter-label">Level</label>
                    <div class="hrk-log-btn-group">
                        <?php foreach ( $level_opts as $lvl ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $lvl, 'log_resolved' => $filter_resolved ) ) ); ?>"
                               class="hrk-log-btn-filter <?php echo $filter_level === $lvl ? 'active' : ''; ?>">
                                <?php echo esc_html( ucfirst( $lvl ) ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hrk-log-filter-group">
                    <label class="hrk-log-filter-label">Status</label>
                    <div class="hrk-log-btn-group">
                        <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $filter_level, 'log_resolved' => '0' ) ) ); ?>"
                           class="hrk-log-btn-filter <?php echo $filter_resolved === '0' ? 'active' : ''; ?>">
                            Unresolved
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $filter_level, 'log_resolved' => '1' ) ) ); ?>"
                           class="hrk-log-btn-filter <?php echo $filter_resolved === '1' ? 'active' : ''; ?>">
                            Resolved
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'error-log', 'log_level' => $filter_level, 'log_resolved' => 'all' ) ) ); ?>"
                           class="hrk-log-btn-filter <?php echo $filter_resolved === 'all' ? 'active' : ''; ?>">
                            All
                        </a>
                    </div>
                </div>

            </div>

            <div class="hrk-log-toolbar-right">
                <span class="hrk-log-count">
                    <?php echo esc_html( $total ); ?> entr<?php echo $total === 1 ? 'y' : 'ies'; ?>
                </span>
                <button type="button"
                        class="button hrk-log-clear-resolved"
                        data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    Clear Resolved
                </button>
            </div>

        </div>

        <?php if ( empty( $entries ) ) : ?>

            <div class="hrk-log-empty">
                <div class="hrk-log-empty-icon">✓</div>
                <p class="hrk-log-empty-title">No log entries found</p>
                <p class="hrk-log-empty-desc">
                    Haraka will capture errors, warnings, exceptions, and fatal errors
                    originating from its own files and display them here.
                </p>
            </div>

        <?php else : ?>

            <div class="hrk-log-table-wrap">
                <table class="hrk-log-table">
                    <thead>
                        <tr>
                            <th class="col-time">Timestamp</th>
                            <th class="col-level">Level</th>
                            <th class="col-msg">Message</th>
                            <th class="col-source">File / Line</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $entries as $entry ) : ?>
                            <tr class="hrk-log-row hrk-log-level-<?php echo esc_attr( $entry['level'] ); ?> <?php echo $entry['resolved'] ? 'hrk-log-resolved' : ''; ?>"
                                data-id="<?php echo esc_attr( $entry['id'] ); ?>">

                                <td class="col-time">
                                    <span class="hrk-log-time">
                                        <?php echo esc_html( date( 'd M Y', strtotime( $entry['created_at'] ) ) ); ?>
                                    </span>
                                    <span class="hrk-log-time-sub">
                                        <?php echo esc_html( date( 'H:i:s', strtotime( $entry['created_at'] ) ) ); ?>
                                    </span>
                                </td>

                                <td class="col-level">
                                    <span class="hrk-log-badge hrk-log-badge-<?php echo esc_attr( $entry['level'] ); ?>">
                                        <?php echo esc_html( strtoupper( $entry['level'] ) ); ?>
                                    </span>
                                </td>

                                <td class="col-msg">
                                    <div class="hrk-log-message">
                                        <?php echo esc_html( $entry['message'] ); ?>
                                    </div>
                                    <?php if ( ! empty( $entry['function'] ) ) : ?>
                                        <div class="hrk-log-function">
                                            <?php echo esc_html( $entry['function'] ); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="col-source">
                                    <div class="hrk-log-source">
                                        <?php echo esc_html( $entry['source'] ); ?>
                                    </div>
                                    <div class="hrk-log-line">
                                        Line <?php echo esc_html( $entry['line'] ); ?>
                                    </div>
                                </td>

                                <td class="col-actions">
                                    <div class="hrk-log-actions">

                                        <button type="button"
                                                class="button button-small hrk-log-view-btn"
                                                data-id="<?php echo esc_attr( $entry['id'] ); ?>"
                                                data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                            View Details
                                        </button>

                                        <?php if ( ! $entry['resolved'] ) : ?>
                                            <button type="button"
                                                    class="button button-small hrk-log-resolve-btn"
                                                    data-id="<?php echo esc_attr( $entry['id'] ); ?>"
                                                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                                Mark Resolved
                                            </button>
                                        <?php else : ?>
                                            <span class="hrk-log-resolved-label">Resolved</span>
                                        <?php endif; ?>

                                        <button type="button"
                                                class="button button-small hrk-log-copy-btn"
                                                data-id="<?php echo esc_attr( $entry['id'] ); ?>"
                                                data-level="<?php echo esc_attr( $entry['level'] ); ?>"
                                                data-message="<?php echo esc_attr( $entry['message'] ); ?>"
                                                data-file="<?php echo esc_attr( $entry['file'] ); ?>"
                                                data-source="<?php echo esc_attr( $entry['source'] ); ?>"
                                                data-line="<?php echo esc_attr( $entry['line'] ); ?>"
                                                data-function="<?php echo esc_attr( $entry['function'] ); ?>"
                                                data-trace="<?php echo esc_attr( $entry['trace'] ); ?>"
                                                data-context="<?php echo esc_attr( $entry['context'] ); ?>">
                                            Copy for Claude
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

    <?php /* ── Detail Modal ── */ ?>
    <div class="hrk-log-modal-overlay" id="hrk-log-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="hrk-log-modal-title">
        <div class="hrk-log-modal">

            <div class="hrk-log-modal-header">
                <h2 class="hrk-log-modal-title" id="hrk-log-modal-title">Error Details</h2>
                <button type="button" class="hrk-log-modal-close" aria-label="Close">&times;</button>
            </div>

            <div class="hrk-log-modal-body" id="hrk-log-modal-body">
                <div class="hrk-log-modal-loading">Loading&hellip;</div>
            </div>

        </div>
    </div>

    <script>
    (function() {

        var wrap    = document.getElementById('hrk-log-wrap');
        var modal   = document.getElementById('hrk-log-modal');
        var modalBody = document.getElementById('hrk-log-modal-body');
        var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

        // ── Helpers ───────────────────────────────────────────────────────────

        function escHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(String(str || '')));
            return d.innerHTML;
        }

        function openModal() {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            modalBody.innerHTML = '<div class="hrk-log-modal-loading">Loading&hellip;</div>';
        }

        // ── Close modal ───────────────────────────────────────────────────────

        document.querySelector('.hrk-log-modal-close').addEventListener('click', closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // ── View Details ──────────────────────────────────────────────────────

        wrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.hrk-log-view-btn');
            if (!btn) return;

            var id    = btn.getAttribute('data-id');
            var nonce = btn.getAttribute('data-nonce');

            openModal();
            modalBody.innerHTML = '<div class="hrk-log-modal-loading">Loading&hellip;</div>';

            var url = ajaxUrl + '?action=haraka_get_log_entry&id=' + id + '&nonce=' + nonce;

            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) {
                        modalBody.innerHTML = '<p class="hrk-log-modal-error">Failed to load entry.</p>';
                        return;
                    }
                    renderModal(data.data);
                })
                .catch(function() {
                    modalBody.innerHTML = '<p class="hrk-log-modal-error">Network error.</p>';
                });
        });

        function renderModal(entry) {
            var trace   = Array.isArray(entry.trace)   ? entry.trace   : [];
            var context = entry.context && typeof entry.context === 'object' ? entry.context : {};

            var traceHtml = trace.length
                ? trace.map(function(f, i) {
                    return '<div class="hrk-log-trace-frame">'
                         + '<span class="hrk-log-trace-num">#' + i + '</span>'
                         + '<span class="hrk-log-trace-file">' + escHtml(f.file || '') + ':' + escHtml(f.line || '') + '</span>'
                         + '<span class="hrk-log-trace-fn">' + escHtml((f.class || '') + (f.type || '') + (f.function || '') + '()') + '</span>'
                         + '</div>';
                  }).join('')
                : '<p class="hrk-log-muted">No stack trace available.</p>';

            var ctxHtml = Object.keys(context).map(function(k) {
                return '<div class="hrk-log-ctx-row">'
                     + '<span class="hrk-log-ctx-key">' + escHtml(k) + '</span>'
                     + '<span class="hrk-log-ctx-val">' + escHtml(context[k]) + '</span>'
                     + '</div>';
            }).join('');

            modalBody.innerHTML = ''
                + '<div class="hrk-log-modal-meta">'
                +   '<span class="hrk-log-badge hrk-log-badge-' + escHtml(entry.level) + '">' + escHtml(entry.level.toUpperCase()) + '</span>'
                +   '<span class="hrk-log-modal-time">' + escHtml(entry.created_at) + '</span>'
                + '</div>'
                + '<div class="hrk-log-modal-section">'
                +   '<div class="hrk-log-modal-section-title">Message</div>'
                +   '<p class="hrk-log-modal-message">' + escHtml(entry.message) + '</p>'
                + '</div>'
                + '<div class="hrk-log-modal-section">'
                +   '<div class="hrk-log-modal-section-title">Location</div>'
                +   '<p class="hrk-log-muted">' + escHtml(entry.file) + ' &mdash; Line ' + escHtml(entry.line) + '</p>'
                +   (entry.function ? '<p class="hrk-log-muted">Function: ' + escHtml(entry.function) + '</p>' : '')
                + '</div>'
                + '<div class="hrk-log-modal-section">'
                +   '<div class="hrk-log-modal-section-title">Stack Trace</div>'
                +   '<div class="hrk-log-trace">' + traceHtml + '</div>'
                + '</div>'
                + '<div class="hrk-log-modal-section">'
                +   '<div class="hrk-log-modal-section-title">Request Context</div>'
                +   '<div class="hrk-log-ctx">' + ctxHtml + '</div>'
                + '</div>';
        }

        // ── Mark Resolved ─────────────────────────────────────────────────────

        wrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.hrk-log-resolve-btn');
            if (!btn) return;

            var id    = btn.getAttribute('data-id');
            var nonce = btn.getAttribute('data-nonce');
            var row   = btn.closest('.hrk-log-row');

            btn.disabled = true;
            btn.textContent = 'Resolving…';

            var body = new FormData();
            body.append('action', 'haraka_resolve_log');
            body.append('nonce',  nonce);
            body.append('id',     id);

            fetch(ajaxUrl, { method: 'POST', body: body })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        row.classList.add('hrk-log-resolved');
                        btn.replaceWith(Object.assign(
                            document.createElement('span'),
                            { className: 'hrk-log-resolved-label', textContent: 'Resolved' }
                        ));
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Mark Resolved';
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.textContent = 'Mark Resolved';
                });
        });

        // ── Copy for Claude ───────────────────────────────────────────────────

        wrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.hrk-log-copy-btn');
            if (!btn) return;

            var level    = btn.getAttribute('data-level')    || '';
            var message  = btn.getAttribute('data-message')  || '';
            var file     = btn.getAttribute('data-file')     || '';
            var source   = btn.getAttribute('data-source')   || '';
            var line     = btn.getAttribute('data-line')     || '';
            var fn       = btn.getAttribute('data-function') || '';
            var traceRaw = btn.getAttribute('data-trace')    || '[]';
            var ctxRaw   = btn.getAttribute('data-context')  || '{}';

            var trace   = [];
            var context = {};
            try { trace   = JSON.parse(traceRaw); } catch(e) {}
            try { context = JSON.parse(ctxRaw);   } catch(e) {}

            var traceText = trace.slice(0, 10).map(function(f, i) {
                return '#' + i + ' ' + (f.file || '') + ':' + (f.line || '') + ' '
                     + ((f.class || '') + (f.type || '') + (f.function || '') + '()');
            }).join('\n');

            var ctxText = Object.keys(context).map(function(k) {
                return k + ': ' + context[k];
            }).join('\n');

            var prompt = [
                '## Haraka Plugin Error Report',
                '',
                '**Level:** ' + level.toUpperCase(),
                '**Message:** ' + message,
                '**File:** ' + file,
                '**Source:** ' + source,
                '**Line:** ' + line,
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
                    btn.textContent = 'Copied!';
                    btn.classList.add('hrk-log-copied');
                    setTimeout(function() {
                        btn.textContent = orig;
                        btn.classList.remove('hrk-log-copied');
                    }, 2000);
                })
                .catch(function() {
                    // Fallback for older browsers
                    var ta = document.createElement('textarea');
                    ta.value = prompt;
                    ta.style.position = 'fixed';
                    ta.style.opacity  = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    btn.textContent = 'Copied!';
                    setTimeout(function() { btn.textContent = 'Copy for Claude'; }, 2000);
                });
        });

        // ── Clear Resolved ────────────────────────────────────────────────────

        var clearBtn = document.querySelector('.hrk-log-clear-resolved');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (!confirm('Delete all resolved log entries? This cannot be undone.')) return;

                var nonce = clearBtn.getAttribute('data-nonce');
                clearBtn.disabled = true;
                clearBtn.textContent = 'Clearing…';

                var body = new FormData();
                body.append('action', 'haraka_clear_resolved');
                body.append('nonce',  nonce);

                fetch(ajaxUrl, { method: 'POST', body: body })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            clearBtn.disabled = false;
                            clearBtn.textContent = 'Clear Resolved';
                        }
                    })
                    .catch(function() {
                        clearBtn.disabled = false;
                        clearBtn.textContent = 'Clear Resolved';
                    });
            });
        }

    })();
    </script>

    <?php
}