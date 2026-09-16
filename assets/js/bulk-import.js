/**
 * MetCPT — Bulk Posts Importer
 *
 * Drives the four-step screen: Upload, Preview, Import, Done. Talks to
 * admin-ajax.php via metcpt_bulk_import_parse (once) and
 * metcpt_bulk_import_row (once per row, run sequentially so the server never
 * has to hold more than one post's work at a time — see
 * PLAN/PLAN-bulk-posts-importer.md, 3.5, for why that matters on this host).
 *
 * metcptBulkImport (ajaxUrl, nonce) is localized in
 * includes/core/class-metcpt.php, enqueue_admin_styles().
 *
 * @package MetCPT
 * @subpackage Admin
 */
( function () {
    'use strict';

    if ( typeof metcptBulkImport === 'undefined' ) {
        return;
    }

    var ajaxUrl = metcptBulkImport.ajaxUrl;
    var nonce   = metcptBulkImport.nonce;

    var state = {
        batchId: '',
        rows: [],   // preview rows returned by the parse step
        results: [], // per-row result once import has run
    };

    // ── Step switching ───────────────────────────────────────────────────────
    // Panel 3 doubles as both "3. Import" and "4. Done": the per-row report
    // with each post's Edit/View links is the reason this screen exists, and
    // it must stay on screen once the run finishes, not get swapped away for
    // a bare summary panel. So there is no separate #mcpt-bi-step-4 element —
    // requesting step 4 keeps panel 3 visible and only advances the chip.
    function showStep( n ) {
        for ( var i = 1; i <= 3; i++ ) {
            var panel = document.getElementById( 'mcpt-bi-step-' + i );
            if ( panel ) {
                var visible = ( 3 === i ) ? ( 3 === n || 4 === n ) : ( i === n );
                panel.hidden = ! visible;
            }
        }
        for ( var s = 1; s <= 4; s++ ) {
            var chip = document.querySelector( '.mcpt-bi-step[data-step="' + s + '"]' );
            if ( chip ) {
                chip.classList.toggle( 'is-active', s === n );
                chip.classList.toggle( 'is-done', s < n );
            }
        }
    }

    function escapeHtml( str ) {
        var div = document.createElement( 'div' );
        div.textContent = String( str == null ? '' : str );
        return div.innerHTML;
    }

    // ── Step 1: copy prompt / download template ─────────────────────────────
    var copyBtn    = document.getElementById( 'mcpt-bi-copy-prompt' );
    var copyStatus = document.getElementById( 'mcpt-bi-copy-status' );
    if ( copyBtn ) {
        copyBtn.addEventListener( 'click', function () {
            var source = document.getElementById( 'mcpt-bi-prompt-source' );
            var text   = source ? source.value : '';

            var done = function () {
                if ( copyStatus ) {
                    copyStatus.textContent = 'Copied.';
                    copyStatus.className   = 'mcpt-dummy-status mcpt-dummy-ok';
                }
            };
            var fail = function () {
                if ( copyStatus ) {
                    copyStatus.textContent = 'Could not copy. Select the text and copy it manually.';
                    copyStatus.className   = 'mcpt-dummy-status mcpt-dummy-err';
                }
            };

            if ( navigator.clipboard && navigator.clipboard.writeText ) {
                navigator.clipboard.writeText( text ).then( done, fail );
            } else {
                try {
                    source.hidden = false;
                    source.select();
                    document.execCommand( 'copy' );
                    source.hidden = true;
                    done();
                } catch ( e ) {
                    fail();
                }
            }
        } );
    }

    var templateBtn = document.getElementById( 'mcpt-bi-download-template' );
    if ( templateBtn ) {
        templateBtn.addEventListener( 'click', function () {
            var header = 'no,title,featured_image,image_alt,date,excerpt,content,focus_keyphrase,seo_title,meta_description,slug';
            var example = '1,"Example post title","example-image.jpg","Example image alt text","2026-04-12 09:00","One line summary of the post.","<p>Body goes here, as HTML.</p>","example keyphrase","Example SEO Title","Example meta description under 150 characters.","example-post-slug"';
            var csv = header + '\r\n' + example + '\r\n';
            var blob = new Blob( [ csv ], { type: 'text/csv;charset=utf-8;' } );
            var url  = URL.createObjectURL( blob );
            var a    = document.createElement( 'a' );
            a.href = url;
            a.download = 'metcpt-bulk-posts-template.csv';
            document.body.appendChild( a );
            a.click();
            document.body.removeChild( a );
            URL.revokeObjectURL( url );
        } );
    }

    // ── Step 1: parse / preview ──────────────────────────────────────────────
    var parseBtn    = document.getElementById( 'mcpt-bi-parse-btn' );
    var parseStatus = document.getElementById( 'mcpt-bi-parse-status' );

    function currentDefaults() {
        return {
            author:   document.getElementById( 'mcpt-bi-default-author' ).value,
            category: document.getElementById( 'mcpt-bi-default-category' ).value,
            comments: document.getElementById( 'mcpt-bi-default-comments' ).value,
            status:   document.getElementById( 'mcpt-bi-default-status' ).value,
        };
    }

    if ( parseBtn ) {
        parseBtn.addEventListener( 'click', function () {
            var fileInput = document.getElementById( 'mcpt-bi-file' );
            var pasteText = document.getElementById( 'mcpt-bi-paste' ).value;
            var hasFile   = fileInput.files && fileInput.files.length > 0;

            if ( ! hasFile && ! pasteText.trim() ) {
                parseStatus.textContent = 'Choose a CSV file or paste CSV text first.';
                parseStatus.className   = 'mcpt-dummy-status mcpt-dummy-err';
                return;
            }

            var defaults = currentDefaults();
            if ( ! defaults.author ) {
                parseStatus.textContent = 'Choose a batch default author first.';
                parseStatus.className   = 'mcpt-dummy-status mcpt-dummy-err';
                return;
            }
            if ( ! defaults.category ) {
                parseStatus.textContent = 'Set a batch default category first.';
                parseStatus.className   = 'mcpt-dummy-status mcpt-dummy-err';
                return;
            }

            var body = new FormData();
            body.append( 'action', 'metcpt_bulk_import_parse' );
            body.append( 'nonce', nonce );
            body.append( 'default_author', defaults.author );
            body.append( 'default_category', defaults.category );
            body.append( 'default_comments', defaults.comments );
            body.append( 'default_status', defaults.status );
            if ( hasFile ) {
                body.append( 'csv_file', fileInput.files[0] );
            } else {
                body.append( 'csv_text', pasteText );
            }

            parseBtn.disabled = true;
            parseStatus.textContent = 'Reading CSV…';
            parseStatus.className   = 'mcpt-dummy-status';

            fetch( ajaxUrl, { method: 'POST', body: body } )
                .then( function ( r ) { return r.json(); } )
                .then( function ( data ) {
                    parseBtn.disabled = false;
                    if ( ! data.success ) {
                        parseStatus.textContent = '✗ ' + ( data.data && data.data.message ? data.data.message : 'Could not read the CSV.' );
                        parseStatus.className   = 'mcpt-dummy-status mcpt-dummy-err';
                        return;
                    }
                    parseStatus.textContent = '';
                    state.batchId = data.data.batch_id;
                    state.rows    = data.data.rows;
                    state.defaults = defaults;
                    renderPreview();
                    showStep( 2 );
                } )
                .catch( function () {
                    parseBtn.disabled = false;
                    parseStatus.textContent = '✗ Network error.';
                    parseStatus.className   = 'mcpt-dummy-status mcpt-dummy-err';
                } );
        } );
    }

    // ── Step 2: preview table ────────────────────────────────────────────────
    function badgeFor( verdict ) {
        var labels = { ok: 'OK', warning: 'Warning', error: 'Error', skip: 'Already imported' };
        var label  = labels[ verdict ] || verdict;
        return '<span class="mcpt-bi-badge mcpt-bi-badge-' + verdict + '">' + escapeHtml( label ) + '</span>';
    }

    function renderPreview() {
        var tbody = document.querySelector( '#mcpt-bi-preview-table tbody' );
        var summary = document.getElementById( 'mcpt-bi-preview-summary' );
        var counts = { ok: 0, warning: 0, error: 0, skip: 0 };

        var html = '';
        state.rows.forEach( function ( row ) {
            counts[ row.verdict ] = ( counts[ row.verdict ] || 0 ) + 1;
            var details = [];
            ( row.errors || [] ).forEach( function ( m ) { details.push( escapeHtml( m ) ); } );
            ( row.notes || [] ).forEach( function ( m ) { details.push( escapeHtml( m ) ); } );
            if ( 'skip' === row.verdict && row.existing_edit ) {
                details.push( 'Already on <a href="' + row.existing_edit + '" target="_blank" rel="noopener">post #' + row.existing_id + '</a>.' );
            }
            html += '<tr>' +
                '<td>' + ( row.index + 1 ) + '</td>' +
                '<td>' + escapeHtml( row.title ) + '</td>' +
                '<td>' + badgeFor( row.verdict ) + '</td>' +
                '<td class="mcpt-bi-row-detail">' + ( details.join( '<br>' ) || '—' ) + '</td>' +
                '</tr>';
        } );
        tbody.innerHTML = html;

        var total = state.rows.length;
        var importable = total - counts.error;
        summary.innerHTML = total + ' row' + ( 1 === total ? '' : 's' ) + ' read. ' +
            counts.ok + ' ready, ' + counts.warning + ' with warnings, ' +
            counts.skip + ' already imported, ' + counts.error + ' will be skipped for an error.' +
            ( importable > 0 ? ' Import will attempt ' + importable + '.' : ' Nothing can be imported until the errors are fixed.' );

        var importBtn = document.getElementById( 'mcpt-bi-import-btn' );
        importBtn.disabled = ( importable <= 0 );
    }

    var backBtn = document.getElementById( 'mcpt-bi-back-btn' );
    if ( backBtn ) {
        backBtn.addEventListener( 'click', function () { showStep( 1 ); } );
    }

    // ── Step 3: import loop, one row per request ─────────────────────────────
    var importBtn = document.getElementById( 'mcpt-bi-import-btn' );

    function importRow( index ) {
        var body = new FormData();
        body.append( 'action', 'metcpt_bulk_import_row' );
        body.append( 'nonce', nonce );
        body.append( 'batch_id', state.batchId );
        body.append( 'row_index', index );

        return fetch( ajaxUrl, { method: 'POST', body: body } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                if ( data.success ) {
                    return data.data;
                }
                return {
                    status:  'failed',
                    title:   ( state.rows[ index ] && state.rows[ index ].title ) || '(no title)',
                    message: ( data.data && data.data.message ) || 'Unknown error.',
                };
            } )
            .catch( function () {
                return {
                    status:  'failed',
                    title:   ( state.rows[ index ] && state.rows[ index ].title ) || '(no title)',
                    message: 'Network error.',
                };
            } );
    }

    function runBadge( status ) {
        var labels = { success: 'Imported', warning: 'Imported', skipped: 'Skipped', failed: 'Failed', pending: 'Waiting' };
        return '<span class="mcpt-bi-badge mcpt-bi-badge-' + status + '">' + escapeHtml( labels[ status ] || status ) + '</span>';
    }

    function runLinks( result ) {
        var links = [];
        if ( result.edit_link ) {
            links.push( '<a href="' + result.edit_link + '" target="_blank" rel="noopener">Edit</a>' );
        }
        if ( result.view_link ) {
            links.push( '<a href="' + result.view_link + '" target="_blank" rel="noopener">View</a>' );
        }
        return links.join( ' | ' ) || '—';
    }

    if ( importBtn ) {
        importBtn.addEventListener( 'click', function () {
            var runnable = state.rows.filter( function ( row ) { return 'error' !== row.verdict; } );

            document.getElementById( 'mcpt-bi-run-title' ).textContent = 'Importing';
            document.getElementById( 'mcpt-bi-done-summary' ).hidden = true;
            document.getElementById( 'mcpt-bi-done-actions' ).hidden = true;

            showStep( 3 );

            var tbody = document.querySelector( '#mcpt-bi-run-table tbody' );
            tbody.innerHTML = '';
            var rowEls = {};
            runnable.forEach( function ( row ) {
                var tr = document.createElement( 'tr' );
                tr.innerHTML = '<td>' + ( row.index + 1 ) + '</td>' +
                    '<td>' + escapeHtml( row.title ) + '</td>' +
                    '<td class="mcpt-bi-run-status">' + runBadge( 'pending' ) + '</td>' +
                    '<td class="mcpt-bi-run-links">—</td>';
                tbody.appendChild( tr );
                rowEls[ row.index ] = tr;
            } );

            var fill = document.getElementById( 'mcpt-bi-progress-fill' );
            var text = document.getElementById( 'mcpt-bi-progress-text' );
            var total = runnable.length;
            var done  = 0;
            state.results = [];

            function next( i ) {
                if ( i >= runnable.length ) {
                    renderDone();
                    showStep( 4 );
                    return;
                }
                var row = runnable[ i ];
                importRow( row.index ).then( function ( result ) {
                    done++;
                    state.results.push( result );
                    var tr = rowEls[ row.index ];
                    if ( tr ) {
                        tr.querySelector( '.mcpt-bi-run-status' ).innerHTML =
                            runBadge( result.status ) +
                            ( result.message ? '<div class="mcpt-bi-row-detail">' + escapeHtml( result.message ) + '</div>' : '' );
                        tr.querySelector( '.mcpt-bi-run-links' ).innerHTML = runLinks( result );
                    }
                    fill.style.width = Math.round( ( done / total ) * 100 ) + '%';
                    text.textContent = done + ' / ' + total;
                    next( i + 1 );
                } );
            }

            next( 0 );
        } );
    }

    // ── Step 4: done summary ─────────────────────────────────────────────────
    function renderDone() {
        var counts = { success: 0, warning: 0, skipped: 0, failed: 0 };
        state.results.forEach( function ( r ) {
            counts[ r.status ] = ( counts[ r.status ] || 0 ) + 1;
        } );
        document.getElementById( 'mcpt-bi-run-title' ).textContent = 'Done';

        var summary = document.getElementById( 'mcpt-bi-done-summary' );
        summary.hidden = false;
        summary.innerHTML = counts.success + ' imported clean, ' +
            counts.warning + ' imported with a warning, ' +
            counts.skipped + ' already imported, ' +
            counts.failed + ' failed.';

        document.getElementById( 'mcpt-bi-done-actions' ).hidden = false;
    }

    var restartBtn = document.getElementById( 'mcpt-bi-restart-btn' );
    if ( restartBtn ) {
        restartBtn.addEventListener( 'click', function () {
            document.getElementById( 'mcpt-bi-file' ).value = '';
            document.getElementById( 'mcpt-bi-paste' ).value = '';
            state.batchId = '';
            state.rows = [];
            state.results = [];
            showStep( 1 );
        } );
    }
} )();
