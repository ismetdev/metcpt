<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bulk Posts Importer — AJAX endpoints: parse/preview, and the per-row import.
 *
 * Rows live in a transient between the Preview and Import steps, keyed by a
 * batch ID handed to the browser. This is deliberately not a new database
 * table (see PLAN/PLAN-bulk-posts-importer.md, section 6): a transient is
 * self-cleaning, needs no schema, and the row counts here (tens, not
 * thousands) are well inside what get_option()'s autoload cache handles
 * without a second thought.
 *
 * The import itself runs one row per AJAX request (see class-metcpt.php for
 * why: PHP time limit and memory limit on the production host). A failed row
 * never stops the loop — the browser drives the loop, one request at a time,
 * and moves to the next row regardless of the previous result.
 *
 * @package MetCPT
 * @subpackage Admin
 */

define( 'METCPT_BULK_IMPORT_TRANSIENT_TTL', 12 * HOUR_IN_SECONDS );

/**
 * Yoast SEO meta keys this importer writes.
 *
 * Confirmed against Yoast SEO 28.5 (the version installed on this site's
 * local, staging and production copies as of 2026-09-16). These keys have
 * been stable across Yoast versions for years; if a future major version
 * changes them, this is the one place to update.
 *
 * @return array<string,string> Field name => meta key.
 */
function metcpt_bulk_import_yoast_meta_keys() {
    return array(
        'focus_keyphrase'  => '_yoast_wpseo_focuskw',
        'seo_title'        => '_yoast_wpseo_title',
        'meta_description' => '_yoast_wpseo_metadesc',
    );
}

/**
 * AJAX: parse an uploaded or pasted CSV and validate every row.
 *
 * Writes nothing to wp_posts. Stores the parsed rows and batch defaults in a
 * transient and returns a batch ID plus the per-row verdicts for the Preview
 * table.
 */
function metcpt_ajax_bulk_import_parse() {
    check_ajax_referer( 'metcpt_bulk_import', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    $raw_csv = '';
    if ( ! empty( $_FILES['csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
        $raw_csv = (string) file_get_contents( $_FILES['csv_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    } elseif ( isset( $_POST['csv_text'] ) ) {
        $raw_csv = wp_unslash( (string) $_POST['csv_text'] );
    }

    if ( '' === trim( $raw_csv ) ) {
        wp_send_json_error( array( 'message' => 'No CSV file or text was provided.' ) );
    }

    $parsed = metcpt_bulk_import_parse_csv( $raw_csv );
    if ( '' !== $parsed['error'] ) {
        wp_send_json_error( array( 'message' => $parsed['error'] ) );
    }
    if ( empty( $parsed['rows'] ) ) {
        wp_send_json_error( array( 'message' => 'The CSV has a header row but no data rows.' ) );
    }

    $defaults = array(
        'author'   => isset( $_POST['default_author'] ) ? sanitize_text_field( wp_unslash( $_POST['default_author'] ) ) : '',
        'category' => isset( $_POST['default_category'] ) ? sanitize_text_field( wp_unslash( $_POST['default_category'] ) ) : '',
        'comments' => isset( $_POST['default_comments'] ) ? sanitize_text_field( wp_unslash( $_POST['default_comments'] ) ) : 'closed',
        'status'   => isset( $_POST['default_status'] ) && 'publish' === $_POST['default_status'] ? 'publish' : 'draft',
    );

    $preview = array();
    foreach ( $parsed['rows'] as $index => $row ) {
        $result   = metcpt_bulk_import_validate_row( $row, $defaults );
        $hash     = metcpt_bulk_import_row_hash( $row['title'], $row['date'] );
        $existing = metcpt_bulk_import_find_existing( $hash );

        $preview[] = array(
            'index'      => $index,
            'title'      => $result['title'] ? $result['title'] : '(no title)',
            'verdict'    => $existing ? 'skip' : $result['verdict'],
            'errors'     => $result['errors'],
            'notes'      => $result['notes'],
            'existing_id'    => $existing,
            'existing_edit'  => $existing ? get_edit_post_link( $existing, 'raw' ) : '',
        );
    }

    $batch_id = wp_generate_password( 12, false, false );
    set_transient(
        'metcpt_bulk_import_' . $batch_id,
        array(
            'rows'     => $parsed['rows'],
            'defaults' => $defaults,
        ),
        METCPT_BULK_IMPORT_TRANSIENT_TTL
    );

    wp_send_json_success( array(
        'batch_id' => $batch_id,
        'rows'     => $preview,
    ) );
}
add_action( 'wp_ajax_metcpt_bulk_import_parse', 'metcpt_ajax_bulk_import_parse' );


/**
 * AJAX: import a single row from a previously parsed batch.
 *
 * Idempotent: a row whose title+date hash already exists on a post is
 * reported as skipped rather than duplicated, so re-running the same batch
 * (deliberately, or after a broken connection) never creates a second copy.
 */
function metcpt_ajax_bulk_import_row() {
    check_ajax_referer( 'metcpt_bulk_import', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    $batch_id = isset( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';
    $row_index = isset( $_POST['row_index'] ) ? absint( $_POST['row_index'] ) : -1;

    $batch = get_transient( 'metcpt_bulk_import_' . $batch_id );
    if ( false === $batch || ! isset( $batch['rows'][ $row_index ] ) ) {
        wp_send_json_error( array( 'message' => 'This batch has expired. Upload the CSV again.' ) );
    }

    $row      = $batch['rows'][ $row_index ];
    $defaults = $batch['defaults'];

    $result = metcpt_bulk_import_create_row( $row, $defaults );

    wp_send_json_success( $result );
}
add_action( 'wp_ajax_metcpt_bulk_import_row', 'metcpt_ajax_bulk_import_row' );


/**
 * Create one post from one validated CSV row.
 *
 * Re-runs validation rather than trusting the Preview response, since the
 * category set may have changed (another row in the same batch may have just
 * created it) between Preview and Import.
 *
 * @param array $row      Raw row from metcpt_bulk_import_parse_csv().
 * @param array $defaults Batch defaults: author, category, comments, status.
 * @return array Result for the row report: status, message, and links on success.
 */
function metcpt_bulk_import_create_row( $row, $defaults ) {
    $title = sanitize_text_field( $row['title'] );

    $hash     = metcpt_bulk_import_row_hash( $row['title'], $row['date'] );
    $existing = metcpt_bulk_import_find_existing( $hash );
    if ( $existing ) {
        return array(
            'status'    => 'skipped',
            'title'     => $title ? $title : '(no title)',
            'message'   => 'Already imported.',
            'edit_link' => get_edit_post_link( $existing, 'raw' ),
            'view_link' => get_permalink( $existing ),
        );
    }

    $validated = metcpt_bulk_import_validate_row( $row, $defaults );
    if ( ! empty( $validated['errors'] ) ) {
        return array(
            'status'  => 'failed',
            'title'   => $title ? $title : '(no title)',
            'message' => implode( ' ', $validated['errors'] ),
        );
    }

    // ── Category: resolve or create ─────────────────────────────────────────
    $category_id = 0;
    $term        = term_exists( $validated['category_name'], 'category' );
    if ( $term ) {
        $category_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
    } else {
        $created = wp_insert_term( $validated['category_name'], 'category' );
        if ( is_wp_error( $created ) ) {
            metcpt_capture_wp_error( $created, 'Bulk Posts Importer: create category' );
            return array(
                'status'  => 'failed',
                'title'   => $title,
                'message' => 'Could not create category "' . $validated['category_name'] . '": ' . $created->get_error_message(),
            );
        }
        $category_id = (int) $created['term_id'];
    }

    // ── Dates ─────────────────────────────────────────────────────────────────
    // $local_date is already the literal wall-clock string from the CSV (see
    // metcpt_bulk_import_normalize_date()); only the GMT counterpart needs
    // converting, using the site's own timezone option.
    $local_date = $validated['local_date'];
    $gmt_date   = get_gmt_from_date( $local_date );

    // ── Body ──────────────────────────────────────────────────────────────────
    // Matches core's own rule (wp_insert_post filters content the same way):
    // only a user without unfiltered_html has their markup stripped down to
    // the post-safe tag set. See PLAN/PLAN-bulk-posts-importer.md, 3.8.
    $content = current_user_can( 'unfiltered_html' )
        ? $row['content']
        : wp_kses_post( $row['content'] );

    $postarr = array(
        'post_title'     => $title,
        'post_content'   => $content,
        'post_excerpt'   => sanitize_textarea_field( $row['excerpt'] ),
        'post_status'    => $defaults['status'],
        'post_author'    => $validated['author_id'],
        'post_category'  => array( $category_id ),
        'comment_status' => $validated['comments'],
        'ping_status'    => 'closed',
        'post_date'      => $local_date,
        'post_date_gmt'  => $gmt_date,
    );

    $slug = sanitize_title( $row['slug'] );
    if ( '' !== $slug ) {
        $postarr['post_name'] = $slug;
    }

    $post_id = wp_insert_post( $postarr, true );

    if ( is_wp_error( $post_id ) ) {
        metcpt_capture_wp_error( $post_id, 'Bulk Posts Importer: create post' );
        return array(
            'status'  => 'failed',
            'title'   => $title,
            'message' => $post_id->get_error_message(),
        );
    }

    update_post_meta( $post_id, 'metcpt_import_hash', $hash );

    // ── Featured image ────────────────────────────────────────────────────────
    if ( $validated['attachment_id'] ) {
        set_post_thumbnail( $post_id, $validated['attachment_id'] );
        $alt = sanitize_text_field( $row['image_alt'] );
        if ( '' !== $alt ) {
            update_post_meta( $validated['attachment_id'], '_wp_attachment_image_alt', $alt );
        }
    }

    // ── Yoast SEO meta ────────────────────────────────────────────────────────
    foreach ( metcpt_bulk_import_yoast_meta_keys() as $field => $meta_key ) {
        $value = sanitize_text_field( $row[ $field ] );
        if ( '' !== $value ) {
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    $warnings = $validated['notes'];

    return array(
        'status'    => empty( $warnings ) ? 'success' : 'warning',
        'title'     => $title,
        'message'   => empty( $warnings ) ? '' : implode( ' ', $warnings ),
        'post_id'   => $post_id,
        'edit_link' => get_edit_post_link( $post_id, 'raw' ),
        'view_link' => get_permalink( $post_id ),
        'post_status' => $defaults['status'],
    );
}
