<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MetCPT Error Log
 *
 * Handles database table creation, PHP error capture,
 * exception handling, AJAX actions, and cron cleanup.
 * Scope is strictly limited to MetCPT plugin files only.
 *
 * @package MetCPT
 * @subpackage Admin
 * @version 1.0.4
 */

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'METCPT_ERROR_LOG_TABLE_VERSION', '1.0.0' );
define( 'METCPT_ERROR_LOG_PURGE_DAYS',   30 );
// Flood guard: max rows a single request may write, so a runaway error (e.g. a
// notice inside a loop) cannot fill the table and slow the whole site.
define( 'METCPT_ERROR_LOG_MAX_PER_REQUEST', 25 );
// Hard ceiling on total rows kept, pruned daily by cron. Bounds table growth
// (including unresolved rows) and keeps the stats read cheap. Generous enough to
// never trigger in normal operation.
define( 'METCPT_ERROR_LOG_MAX_ROWS', 5000 );

// ── Table name helper ─────────────────────────────────────────────────────────
function metcpt_error_log_table() {
    global $wpdb;
    return $wpdb->prefix . 'metcpt_error_log';
}

// ── Create table on plugin activation ────────────────────────────────────────
function metcpt_error_log_create_table() {
    global $wpdb;

    // Fast path: when the stored schema version already matches, the table is in
    // place and current, so return immediately. This runs on every request (see
    // bootstrap in metcpt.php), so we must NOT hit the database here in the common
    // case. get_option() is served from the autoloaded options cache. The heavier
    // SHOW TABLES probe below only runs right after install/upgrade, when the
    // version differs or is unset.
    $db_version = get_option( 'metcpt_error_log_db_version', '' );
    if ( $db_version === METCPT_ERROR_LOG_TABLE_VERSION ) {
        return;
    }

    $table   = metcpt_error_log_table();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id          BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        level       VARCHAR(20)  NOT NULL DEFAULT 'error',
        source      VARCHAR(255) NOT NULL DEFAULT '',
        message     TEXT         NOT NULL,
        file        VARCHAR(255) NOT NULL DEFAULT '',
        `function`  VARCHAR(255) NOT NULL DEFAULT '',
        `line`      INT(11)      UNSIGNED NOT NULL DEFAULT 0,
        trace       LONGTEXT     NULL,
        context     LONGTEXT     NULL,
        resolved    TINYINT(1)   UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_level     (level),
        KEY idx_resolved  (resolved),
        KEY idx_created   (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'metcpt_error_log_db_version', METCPT_ERROR_LOG_TABLE_VERSION );
}
register_activation_hook( METCPT_FILE, 'metcpt_error_log_create_table' );

// ── Scope guard — only log MetCPT files ──────────────────────────────────────
function metcpt_error_is_in_scope( $file ) {
    if ( empty( $file ) ) {
        return false;
    }
    return strpos( realpath( $file ) ?: $file, realpath( METCPT_PATH ) ?: METCPT_PATH ) === 0;
}

// ── Resolve relative source path ─────────────────────────────────────────────
function metcpt_error_source( $file ) {
    $plugin_dir = realpath( METCPT_PATH ) ?: METCPT_PATH;
    $real_file  = realpath( $file ) ?: $file;
    return ltrim( str_replace( $plugin_dir, '', $real_file ), DIRECTORY_SEPARATOR );
}

// ── Resolve calling function from trace ──────────────────────────────────────
function metcpt_error_calling_function( $trace ) {
    if ( empty( $trace ) || ! is_array( $trace ) ) {
        return '';
    }
    foreach ( $trace as $frame ) {
        if ( isset( $frame['file'] ) && metcpt_error_is_in_scope( $frame['file'] ) ) {
            $class    = isset( $frame['class'] )    ? $frame['class'] . '::' : '';
            $function = isset( $frame['function'] ) ? $frame['function']     : '';
            return $class . $function . '()';
        }
    }
    return '';
}

// ── Sanitise trace for storage ────────────────────────────────────────────────
function metcpt_error_sanitise_trace( $trace ) {
    if ( empty( $trace ) || ! is_array( $trace ) ) {
        return array();
    }
    $clean = array();
    foreach ( array_slice( $trace, 0, 20 ) as $frame ) {
        $clean[] = array(
            'file'     => isset( $frame['file'] )     ? $frame['file']                            : '',
            'line'     => isset( $frame['line'] )     ? (int) $frame['line']                      : 0,
            'function' => isset( $frame['function'] ) ? $frame['function']                        : '',
            'class'    => isset( $frame['class'] )    ? $frame['class']                           : '',
            'type'     => isset( $frame['type'] )     ? $frame['type']                            : '',
            'args'     => isset( $frame['args'] )     ? array_map( 'metcpt_error_arg_summary', $frame['args'] ) : array(),
        );
    }
    return $clean;
}

// ── Summarise arg to avoid storing large objects ──────────────────────────────
function metcpt_error_arg_summary( $arg ) {
    if ( is_null( $arg ) )    return 'null';
    if ( is_bool( $arg ) )    return $arg ? 'true' : 'false';
    if ( is_int( $arg ) )     return $arg;
    if ( is_float( $arg ) )   return $arg;
    if ( is_string( $arg ) )  return strlen( $arg ) > 100 ? substr( $arg, 0, 100 ) . '…' : $arg;
    if ( is_array( $arg ) )   return 'array(' . count( $arg ) . ')';
    if ( is_object( $arg ) )  return 'object(' . get_class( $arg ) . ')';
    return gettype( $arg );
}

// ── Build request context ─────────────────────────────────────────────────────
function metcpt_error_request_context() {
    return array(
        'url'        => isset( $_SERVER['REQUEST_URI'] )  ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )  : '',
        'method'     => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
        'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        'user_id'    => get_current_user_id(),
        'wp_version' => get_bloginfo( 'version' ),
        'php_version'=> PHP_VERSION,
    );
}

// ── Core insert function ──────────────────────────────────────────────────────
function metcpt_error_log_insert( $level, $message, $file, $line, $trace = array(), $extra_context = array() ) {
    global $wpdb;

    if ( ! metcpt_error_is_in_scope( $file ) ) {
        return false;
    }

    // ── Flood guard ───────────────────────────────────────────────────────────
    // The global error handlers can fire many times in a single request (e.g. a
    // repeated notice inside a loop). Without a limit, one runaway error could
    // insert thousands of rows in one page load, bloating the table and slowing
    // the whole site. Cap the rows written per request, and collapse exact
    // duplicates (same level + file + line + message) within the request. Both
    // guards are per-request (static), so recurring errors are still recorded on
    // later requests — only same-request floods are suppressed.
    static $insert_count = 0;
    static $seen = array();

    if ( $insert_count >= METCPT_ERROR_LOG_MAX_PER_REQUEST ) {
        return false;
    }
    $dedup_key = md5( $level . '|' . $file . '|' . $line . '|' . $message );
    if ( isset( $seen[ $dedup_key ] ) ) {
        return false;
    }
    $seen[ $dedup_key ] = true;
    $insert_count++;

    $context = array_merge( metcpt_error_request_context(), $extra_context );
    $table   = metcpt_error_log_table();

    return $wpdb->insert(
        $table,
        array(
            'created_at' => current_time( 'mysql' ),
            'level'      => sanitize_text_field( $level ),
            'source'     => metcpt_error_source( $file ),
            'message'    => wp_strip_all_tags( $message ),
            'file'       => $file,
            'function'   => metcpt_error_calling_function( $trace ),
            'line'       => absint( $line ),
            'trace'      => wp_json_encode( metcpt_error_sanitise_trace( $trace ) ),
            'context'    => wp_json_encode( $context ),
            'resolved'   => 0,
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' )
    );
}

// ── PHP error handler ─────────────────────────────────────────────────────────
function metcpt_php_error_handler( $errno, $errstr, $errfile, $errline ) {

    // Respect @ operator (error suppression)
    if ( ! ( error_reporting() & $errno ) ) {
        return false;
    }

    if ( ! metcpt_error_is_in_scope( $errfile ) ) {
        return false;
    }

    $level_map = array(
        E_ERROR             => 'error',
        E_WARNING           => 'warning',
        E_NOTICE            => 'notice',
        E_USER_ERROR        => 'error',
        E_USER_WARNING      => 'warning',
        E_USER_NOTICE       => 'notice',
        E_RECOVERABLE_ERROR => 'error',
        E_DEPRECATED        => 'notice',
        E_USER_DEPRECATED   => 'notice',
    );

    $level = isset( $level_map[ $errno ] ) ? $level_map[ $errno ] : 'error';
    $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );

    metcpt_error_log_insert( $level, $errstr, $errfile, $errline, $trace );

    // Return false to allow WordPress default handler to also run
    return false;
}
set_error_handler( 'metcpt_php_error_handler' );

// ── Uncaught exception handler ────────────────────────────────────────────────
function metcpt_exception_handler( $exception ) {
    $file  = $exception->getFile();
    $line  = $exception->getLine();
    $trace = $exception->getTrace();

    if ( metcpt_error_is_in_scope( $file ) ) {
        metcpt_error_log_insert(
            'exception',
            get_class( $exception ) . ': ' . $exception->getMessage(),
            $file,
            $line,
            $trace
        );
    }

    // Log and get out of the way. Returning here would silently swallow the
    // exception (PHP terminates the request with no output once a custom
    // exception handler returns), producing an unexplained white screen.
    // Restoring and rethrowing lets PHP's own fatal-error handling run, which
    // WordPress's fatal-error-handler.php can then catch and report normally.
    restore_exception_handler();
    throw $exception;
}
set_exception_handler( 'metcpt_exception_handler' );

// ── Fatal error handler — runs on shutdown ────────────────────────────────────
function metcpt_fatal_error_handler() {
    $error = error_get_last();

    if ( empty( $error ) ) {
        return;
    }

    $fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );

    if ( ! in_array( $error['type'], $fatal_types, true ) ) {
        return;
    }

    if ( ! metcpt_error_is_in_scope( $error['file'] ) ) {
        return;
    }

    metcpt_error_log_insert(
        'fatal',
        $error['message'],
        $error['file'],
        $error['line'],
        array()
    );
}
register_shutdown_function( 'metcpt_fatal_error_handler' );

// ── WP_Error capture — call this manually where WP_Error may occur ───────────
function metcpt_capture_wp_error( $result, $context_label = '' ) {
    if ( ! is_wp_error( $result ) ) {
        return $result;
    }

    $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );
    $file  = isset( $trace[0]['file'] ) ? $trace[0]['file'] : '';
    $line  = isset( $trace[0]['line'] ) ? $trace[0]['line'] : 0;

    if ( metcpt_error_is_in_scope( $file ) ) {
        metcpt_error_log_insert(
            'error',
            ( $context_label ? "[{$context_label}] " : '' ) . $result->get_error_message(),
            $file,
            $line,
            $trace
        );
    }

    return $result;
}

// ── AJAX: mark log entry as resolved ─────────────────────────────────────────
function metcpt_ajax_resolve_log() {
    check_ajax_referer( 'metcpt_error_log', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

    if ( ! $id ) {
        wp_send_json_error( array( 'message' => 'Invalid ID.' ) );
    }

    global $wpdb;
    $updated = $wpdb->update(
        metcpt_error_log_table(),
        array( 'resolved' => 1 ),
        array( 'id'       => $id ),
        array( '%d' ),
        array( '%d' )
    );

    if ( $updated === false ) {
        wp_send_json_error( array( 'message' => 'Database error.' ) );
    }

    wp_send_json_success( array( 'id' => $id ) );
}
add_action( 'wp_ajax_metcpt_resolve_log',   'metcpt_ajax_resolve_log' );

// ── AJAX: fetch single log entry for modal ────────────────────────────────────
function metcpt_ajax_get_log_entry() {
    check_ajax_referer( 'metcpt_error_log', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    if ( ! $id ) {
        wp_send_json_error( array( 'message' => 'Invalid ID.' ) );
    }

    global $wpdb;
    $row = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT * FROM ' . metcpt_error_log_table() . ' WHERE id = %d',
            $id
        ),
        ARRAY_A
    );

    if ( ! $row ) {
        wp_send_json_error( array( 'message' => 'Entry not found.' ) );
    }

    $row['trace']   = json_decode( $row['trace'],   true );
    $row['context'] = json_decode( $row['context'], true );

    wp_send_json_success( $row );
}
add_action( 'wp_ajax_metcpt_get_log_entry', 'metcpt_ajax_get_log_entry' );

// ── AJAX: clear all resolved logs ─────────────────────────────────────────────
function metcpt_ajax_clear_resolved() {
    check_ajax_referer( 'metcpt_error_log', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    global $wpdb;
    $deleted = $wpdb->query(
        'DELETE FROM ' . metcpt_error_log_table() . ' WHERE resolved = 1'
    );

    wp_send_json_success( array( 'deleted' => $deleted ) );
}
add_action( 'wp_ajax_metcpt_clear_resolved', 'metcpt_ajax_clear_resolved' );

// ── Cron: purge resolved logs older than 30 days + cap total rows ────────────
function metcpt_purge_old_error_logs() {
    global $wpdb;

    $table = metcpt_error_log_table();

    // 1. Clear out resolved entries older than the retention window.
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table}
             WHERE resolved = 1
             AND created_at < %s",
            gmdate( 'Y-m-d H:i:s', strtotime( '-' . METCPT_ERROR_LOG_PURGE_DAYS . ' days' ) )
        )
    );

    // 2. Hard ceiling: keep only the most recent MAX_ROWS entries. The resolved
    //    purge above never removes unresolved rows, so without this the table
    //    could grow without bound. id is the auto-increment PRIMARY KEY, so
    //    ordering by it matches insertion order and the delete is index-driven.
    //    With MAX_ROWS+1 rows or fewer, OFFSET returns NULL and nothing is cut.
    $threshold_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET %d",
            METCPT_ERROR_LOG_MAX_ROWS
        )
    );
    if ( $threshold_id ) {
        $wpdb->query(
            $wpdb->prepare( "DELETE FROM {$table} WHERE id <= %d", $threshold_id )
        );
    }
}
add_action( 'metcpt_purge_error_log', 'metcpt_purge_old_error_logs' );

// ── Schedule purge cron ───────────────────────────────────────────────────────
function metcpt_schedule_error_log_purge() {
    if ( ! wp_next_scheduled( 'metcpt_purge_error_log' ) ) {
        wp_schedule_event( time(), 'daily', 'metcpt_purge_error_log' );
    }
}
add_action( 'wp', 'metcpt_schedule_error_log_purge' );