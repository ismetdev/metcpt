<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bulk Posts Importer — CSV parsing and per-row validation.
 *
 * Turns an uploaded or pasted CSV into an array of rows, each already checked
 * against what the importer needs to create a post. Nothing here writes to the
 * database — see bulk-import-runner.php for that. Keeping parse/validate
 * separate from the write step is what makes the Preview screen possible:
 * every row is judged before anything is created.
 *
 * @package MetCPT
 * @subpackage Admin
 */

/**
 * The fixed CSV column order the importer understands.
 *
 * Order-independent on read (columns are matched by name, not position), but
 * this is also the order shown in the on-screen template and the ChatGPT
 * prompt, so the three stay in sync from one place.
 *
 * @return array
 */
function metcpt_bulk_import_columns() {
    return array(
        'no', 'title', 'featured_image', 'image_alt', 'date', 'excerpt',
        'content', 'focus_keyphrase', 'seo_title', 'meta_description',
        'slug', 'author', 'category', 'comments',
    );
}

/**
 * Parse raw CSV text into rows keyed by column name.
 *
 * Accepts a BOM (Excel/ChatGPT downloads sometimes carry one) and strips it.
 * Header matching is case-insensitive and ignores columns this importer does
 * not know, so an operator's extra reference column does no harm. A row
 * shorter than the header (a trailing blank cell dropped by the source tool)
 * is padded rather than rejected outright — that is a data problem, not a
 * structural one, and belongs to validation, not parsing.
 *
 * @param string $raw_csv Raw CSV file contents.
 * @return array{rows: array, error: string} 'error' is empty on success.
 */
function metcpt_bulk_import_parse_csv( $raw_csv ) {
    // Strip UTF-8 BOM if present.
    if ( substr( $raw_csv, 0, 3 ) === "\xEF\xBB\xBF" ) {
        $raw_csv = substr( $raw_csv, 3 );
    }

    $lines = preg_split( '/\r\n|\r|\n/', trim( $raw_csv ) );
    if ( empty( $lines ) || ( 1 === count( $lines ) && '' === trim( $lines[0] ) ) ) {
        return array( 'rows' => array(), 'error' => 'The file is empty.' );
    }

    // fgetcsv() needs a stream, not a string. Rebuild the file in memory
    // instead of parsing line by line, since a quoted field legitimately
    // contains newlines (the HTML body column).
    $stream = fopen( 'php://temp', 'r+' );
    fwrite( $stream, $raw_csv );
    rewind( $stream );

    $header_row = fgetcsv( $stream );
    if ( false === $header_row || empty( $header_row ) ) {
        fclose( $stream );
        return array( 'rows' => array(), 'error' => 'Could not read a header row.' );
    }

    $known_columns = metcpt_bulk_import_columns();
    $header_map    = array(); // column index => known column name.
    foreach ( $header_row as $index => $label ) {
        $label = strtolower( trim( $label ) );
        if ( in_array( $label, $known_columns, true ) ) {
            $header_map[ $index ] = $label;
        }
    }

    if ( ! in_array( 'title', $header_map, true ) ) {
        fclose( $stream );
        return array( 'rows' => array(), 'error' => 'The header row has no "title" column.' );
    }

    $rows = array();
    while ( false !== ( $line = fgetcsv( $stream ) ) ) {
        // Skip a fully blank line (trailing newline in the file, etc).
        if ( 1 === count( $line ) && ( null === $line[0] || '' === trim( (string) $line[0] ) ) ) {
            continue;
        }

        $row = array_fill_keys( $known_columns, '' );
        foreach ( $header_map as $index => $column ) {
            if ( isset( $line[ $index ] ) ) {
                $row[ $column ] = trim( (string) $line[ $index ] );
            }
        }
        $rows[] = $row;
    }
    fclose( $stream );

    return array( 'rows' => $rows, 'error' => '' );
}

/**
 * Validate one parsed row against what the importer needs to create a post.
 *
 * Returns a verdict of 'ok', 'warning', or 'error', plus the resolved IDs a
 * warning-free row will need at import time (author, category, attachment),
 * so the runner does not have to repeat this resolution.
 *
 * @param array $row     One row from metcpt_bulk_import_parse_csv().
 * @param array $defaults Batch-level defaults: author, category, comments, status.
 * @return array
 */
function metcpt_bulk_import_validate_row( $row, $defaults ) {
    $notes  = array();
    $errors = array();

    // ── Title ─────────────────────────────────────────────────────────────────
    $title = sanitize_text_field( $row['title'] );
    if ( '' === $title ) {
        $errors[] = 'Title is missing.';
    }

    // ── Content ───────────────────────────────────────────────────────────────
    if ( '' === trim( $row['content'] ) ) {
        $errors[] = 'Content is missing.';
    }

    // ── Date ──────────────────────────────────────────────────────────────────
    // The CSV date is read as the site's own local wall-clock time (the same
    // way wp-admin's Publish date field works), never round-tripped through
    // strtotime()/wp_date(). Those read and format a Unix timestamp using
    // PHP's date.timezone ini setting, which on this host is UTC — not the
    // site's Asia/Kuala_Lumpur — so that path silently shifted every post's
    // time by the site's UTC offset. See metcpt_bulk_import_normalize_date().
    $date_value = trim( $row['date'] );
    $local_date = '';
    if ( '' === $date_value ) {
        $errors[] = 'Date is missing.';
    } else {
        $local_date = metcpt_bulk_import_normalize_date( $date_value );
        if ( '' === $local_date ) {
            $errors[] = 'Date "' . $date_value . '" could not be understood. Use YYYY-MM-DD HH:MM.';
        }
    }

    // ── Author ────────────────────────────────────────────────────────────────
    $author_input = '' !== trim( $row['author'] ) ? trim( $row['author'] ) : trim( (string) $defaults['author'] );
    $author_id    = metcpt_bulk_import_resolve_author( $author_input );
    if ( '' === $author_input ) {
        $errors[] = 'No author given and no batch default set.';
    } elseif ( 0 === $author_id ) {
        $errors[] = 'Author "' . $author_input . '" does not match any user.';
    }

    // ── Category ──────────────────────────────────────────────────────────────
    $category_input = '' !== trim( $row['category'] ) ? trim( $row['category'] ) : trim( (string) $defaults['category'] );
    if ( '' === $category_input ) {
        $errors[] = 'No category given and no batch default set.';
    } elseif ( ! term_exists( $category_input, 'category' ) ) {
        $notes[] = 'Category "' . $category_input . '" does not exist yet. It will be created.';
    }

    // ── Comments ──────────────────────────────────────────────────────────────
    $comments_input = strtolower( trim( '' !== trim( $row['comments'] ) ? $row['comments'] : (string) $defaults['comments'] ) );
    if ( ! in_array( $comments_input, array( 'open', 'closed' ), true ) ) {
        $comments_input = 'closed';
    }

    // ── Featured image ────────────────────────────────────────────────────────
    $image_input      = trim( $row['featured_image'] );
    $attachment_id     = 0;
    if ( '' === $image_input ) {
        $notes[] = 'No featured image given.';
    } else {
        $attachment_id = metcpt_bulk_import_resolve_attachment( $image_input );
        if ( 0 === $attachment_id ) {
            $notes[] = 'Image "' . $image_input . '" was not found in the Media Library.';
        }
    }
    if ( $attachment_id && '' === trim( $row['image_alt'] ) ) {
        $notes[] = 'No alt text given for the featured image.';
    }

    // ── SEO fields, warn only ────────────────────────────────────────────────
    foreach ( array(
        'excerpt'          => 'Excerpt',
        'focus_keyphrase'  => 'Focus keyphrase',
        'seo_title'        => 'SEO title',
        'meta_description' => 'Meta description',
    ) as $key => $label ) {
        if ( '' === trim( $row[ $key ] ) ) {
            $notes[] = $label . ' is empty.';
        }
    }

    $verdict = 'ok';
    if ( ! empty( $errors ) ) {
        $verdict = 'error';
    } elseif ( ! empty( $notes ) ) {
        $verdict = 'warning';
    }

    return array(
        'verdict'       => $verdict,
        'errors'        => $errors,
        'notes'         => $notes,
        'title'         => $title,
        'author_id'     => $author_id,
        'category_name' => $category_input,
        'comments'      => $comments_input,
        'attachment_id' => $attachment_id,
        'local_date'    => $local_date,
    );
}

/**
 * Turn a CSV date string into a normalised 'Y-m-d H:i:s' string, read as the
 * site's own local wall-clock time — the same way wp-admin's own Publish
 * date field treats what an editor types in.
 *
 * Deliberately not strtotime() + wp_date(): that pair parses the string using
 * PHP's date.timezone ini setting, then re-renders it in the site's
 * timezone_string/gmt_offset option, and the two are not required to match.
 * On this host date.timezone is UTC while the site is Asia/Kuala_Lumpur
 * (UTC+8), so that path silently shifted every imported post's time by 8
 * hours. Parsing with an explicit UTC DateTimeZone here means the literal
 * numbers the operator typed come back unchanged, regardless of the PHP
 * process's own ini timezone. get_gmt_from_date(), used on the result by the
 * caller, does the real site-timezone-to-GMT conversion, and that function
 * reads the site's own option rather than PHP's ini setting, so it is safe.
 *
 * @param string $date_value
 * @return string Empty string when the date cannot be parsed.
 */
function metcpt_bulk_import_normalize_date( $date_value ) {
    try {
        $dt = new DateTime( $date_value, new DateTimeZone( 'UTC' ) );
    } catch ( Exception $e ) {
        return '';
    }
    return $dt->format( 'Y-m-d H:i:s' );
}

/**
 * Resolve an author column value (username, display name, or numeric ID) to
 * a user ID. Returns 0 when nothing matches.
 *
 * @param string $value
 * @return int
 */
function metcpt_bulk_import_resolve_author( $value ) {
    $value = trim( $value );
    if ( '' === $value ) {
        return 0;
    }

    if ( ctype_digit( $value ) ) {
        $user = get_user_by( 'id', (int) $value );
        return $user ? $user->ID : 0;
    }

    $user = get_user_by( 'login', $value );
    if ( ! $user ) {
        $user = get_user_by( 'email', $value );
    }
    if ( ! $user ) {
        // Fall back to a display-name match. There is no core lookup for
        // this, so a small user_search covers the operator typing "Ismet
        // Office" instead of a login.
        $matches = get_users( array(
            'search'         => $value,
            'search_columns' => array( 'display_name' ),
            'number'         => 1,
            'fields'         => 'ID',
        ) );
        if ( ! empty( $matches ) ) {
            return (int) $matches[0];
        }
    }

    return $user ? $user->ID : 0;
}

/**
 * Resolve a featured_image column value to an attachment ID.
 *
 * Accepts a bare numeric attachment ID (reuse an already-uploaded image), or
 * a filename matched against the sanitised basename WordPress stored it
 * under. Matching is by basename only — the operator uploads through the
 * normal Media Library, WordPress owns the final storage path.
 *
 * @param string $value
 * @return int 0 when nothing matches.
 */
function metcpt_bulk_import_resolve_attachment( $value ) {
    $value = trim( $value );
    if ( '' === $value ) {
        return 0;
    }

    if ( ctype_digit( $value ) && get_post_type( (int) $value ) === 'attachment' ) {
        return (int) $value;
    }

    // sanitize_file_name() applies the same transform WordPress used when it
    // stored the upload (spaces to dashes, unsafe characters stripped), so a
    // CSV filename typed with spaces still matches.
    $wanted = sanitize_file_name( wp_basename( $value ) );

    global $wpdb;
    $like = '%' . $wpdb->esc_like( $wanted ) . '%';
    $id   = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
         ORDER BY post_id DESC LIMIT 1",
        $like
    ) );

    return $id ? (int) $id : 0;
}

/**
 * A stable fingerprint for one row, used to skip a post already imported.
 *
 * Title plus date is enough to identify "this CSV row" without depending on
 * row order, which shifts if the operator reorders or splits a CSV across
 * two uploads. See metcpt_bulk_import_find_existing().
 *
 * @param string $title
 * @param string $date_value
 * @return string
 */
function metcpt_bulk_import_row_hash( $title, $date_value ) {
    return md5( strtolower( trim( $title ) ) . '|' . trim( $date_value ) );
}

/**
 * Find a previously imported post with the given row hash.
 *
 * @param string $hash
 * @return int Post ID, or 0 when not found.
 */
function metcpt_bulk_import_find_existing( $hash ) {
    global $wpdb;
    $id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = 'metcpt_import_hash' AND meta_value = %s LIMIT 1",
        $hash
    ) );
    return $id ? (int) $id : 0;
}
