<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Haraka Error Log
 *
 * Handles database table creation, PHP error capture,
 * exception handling, AJAX actions, and cron cleanup.
 * Scope is strictly limited to Haraka plugin files only.
 *
 * @package Haraka
 * @subpackage Admin
 * @version 1.0.4
 */

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'HARAKA_ERROR_LOG_TABLE_VERSION', '1.0.0' );
define( 'HARAKA_ERROR_LOG_PURGE_DAYS',   30 );

// ── Table name helper ─────────────────────────────────────────────────────────
function haraka_error_log_table() {
    global $wpdb;
    return $wpdb->prefix . 'haraka_error_log';
}

// ── Create table on plugin activation ────────────────────────────────────────
function haraka_error_log_create_table() {
    global $wpdb;

    $table      = haraka_error_log_table();
    $charset    = $wpdb->get_charset_collate();
    $db_version = get_option( 'haraka_error_log_db_version', '' );

    // Check version AND confirm table actually exists
    // Prevents stale version option blocking recreation after failed install
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
    if ( $db_version === HARAKA_ERROR_LOG_TABLE_VERSION && $table_exists ) {
        return;
    }

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

    update_option( 'haraka_error_log_db_version', HARAKA_ERROR_LOG_TABLE_VERSION );
}
register_activation_hook( HARAKA_PLUGIN_FILE, 'haraka_error_log_create_table' );

// ── Scope guard — only log Haraka files ──────────────────────────────────────
function haraka_error_is_in_scope( $file ) {
    if ( empty( $file ) ) {
        return false;
    }
    return strpos( realpath( $file ) ?: $file, realpath( HARAKA_PLUGIN_DIR ) ?: HARAKA_PLUGIN_DIR ) === 0;
}

// ── Resolve relative source path ─────────────────────────────────────────────
function haraka_error_source( $file ) {
    $plugin_dir = realpath( HARAKA_PLUGIN_DIR ) ?: HARAKA_PLUGIN_DIR;
    $real_file  = realpath( $file ) ?: $file;
    return ltrim( str_replace( $plugin_dir, '', $real_file ), DIRECTORY_SEPARATOR );
}

// ── Resolve calling function from trace ──────────────────────────────────────
function haraka_error_calling_function( $trace ) {
    if ( empty( $trace ) || ! is_array( $trace ) ) {
        return '';
    }
    foreach ( $trace as $frame ) {
        if ( isset( $frame['file'] ) && haraka_error_is_in_scope( $frame['file'] ) ) {
            $class    = isset( $frame['class'] )    ? $frame['class'] . '::' : '';
            $function = isset( $frame['function'] ) ? $frame['function']     : '';
            return $class . $function . '()';
        }
    }
    return '';
}

// ── Sanitise trace for storage ────────────────────────────────────────────────
function haraka_error_sanitise_trace( $trace ) {
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
            'args'     => isset( $frame['args'] )     ? array_map( 'haraka_error_arg_summary', $frame['args'] ) : array(),
        );
    }
    return $clean;
}

// ── Summarise arg to avoid storing large objects ──────────────────────────────
function haraka_error_arg_summary( $arg ) {
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
function haraka_error_request_context() {
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
function haraka_error_log_insert( $level, $message, $file, $line, $trace = array(), $extra_context = array() ) {
    global $wpdb;

    if ( ! haraka_error_is_in_scope( $file ) ) {
        return false;
    }

    $context = array_merge( haraka_error_request_context(), $extra_context );
    $table   = haraka_error_log_table();

    return $wpdb->insert(
        $table,
        array(
            'created_at' => current_time( 'mysql' ),
            'level'      => sanitize_text_field( $level ),
            'source'     => haraka_error_source( $file ),
            'message'    => wp_strip_all_tags( $message ),
            'file'       => $file,
            'function'   => haraka_error_calling_function( $trace ),
            'line'       => absint( $line ),
            'trace'      => wp_json_encode( haraka_error_sanitise_trace( $trace ) ),
            'context'    => wp_json_encode( $context ),
            'resolved'   => 0,
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' )
    );
}

// ── PHP error handler ─────────────────────────────────────────────────────────
function haraka_php_error_handler( $errno, $errstr, $errfile, $errline ) {

    // Respect @ operator (error suppression)
    if ( ! ( error_reporting() & $errno ) ) {
        return false;
    }

    if ( ! haraka_error_is_in_scope( $errfile ) ) {
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

    haraka_error_log_insert( $level, $errstr, $errfile, $errline, $trace );

    // Return false to allow WordPress default handler to also run
    return false;
}
set_error_handler( 'haraka_php_error_handler' );

// ── Uncaught exception handler ────────────────────────────────────────────────
function haraka_exception_handler( $exception ) {
    $file  = $exception->getFile();
    $line  = $exception->getLine();
    $trace = $exception->getTrace();

    if ( haraka_error_is_in_scope( $file ) ) {
        haraka_error_log_insert(
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
set_exception_handler( 'haraka_exception_handler' );

// ── Fatal error handler — runs on shutdown ────────────────────────────────────
function haraka_fatal_error_handler() {
    $error = error_get_last();

    if ( empty( $error ) ) {
        return;
    }

    $fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );

    if ( ! in_array( $error['type'], $fatal_types, true ) ) {
        return;
    }

    if ( ! haraka_error_is_in_scope( $error['file'] ) ) {
        return;
    }

    haraka_error_log_insert(
        'fatal',
        $error['message'],
        $error['file'],
        $error['line'],
        array()
    );
}
register_shutdown_function( 'haraka_fatal_error_handler' );

// ── WP_Error capture — call this manually where WP_Error may occur ───────────
function haraka_capture_wp_error( $result, $context_label = '' ) {
    if ( ! is_wp_error( $result ) ) {
        return $result;
    }

    $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );
    $file  = isset( $trace[0]['file'] ) ? $trace[0]['file'] : '';
    $line  = isset( $trace[0]['line'] ) ? $trace[0]['line'] : 0;

    if ( haraka_error_is_in_scope( $file ) ) {
        haraka_error_log_insert(
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
function haraka_ajax_resolve_log() {
    check_ajax_referer( 'haraka_error_log', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

    if ( ! $id ) {
        wp_send_json_error( array( 'message' => 'Invalid ID.' ) );
    }

    global $wpdb;
    $updated = $wpdb->update(
        haraka_error_log_table(),
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
add_action( 'wp_ajax_haraka_resolve_log',   'haraka_ajax_resolve_log' );

// ── AJAX: fetch single log entry for modal ────────────────────────────────────
function haraka_ajax_get_log_entry() {
    check_ajax_referer( 'haraka_error_log', 'nonce' );

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
            'SELECT * FROM ' . haraka_error_log_table() . ' WHERE id = %d',
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
add_action( 'wp_ajax_haraka_get_log_entry', 'haraka_ajax_get_log_entry' );

// ── AJAX: clear all resolved logs ─────────────────────────────────────────────
function haraka_ajax_clear_resolved() {
    check_ajax_referer( 'haraka_error_log', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    global $wpdb;
    $deleted = $wpdb->query(
        'DELETE FROM ' . haraka_error_log_table() . ' WHERE resolved = 1'
    );

    wp_send_json_success( array( 'deleted' => $deleted ) );
}
add_action( 'wp_ajax_haraka_clear_resolved', 'haraka_ajax_clear_resolved' );

// ── Cron: purge resolved logs older than 30 days ─────────────────────────────
function haraka_purge_old_error_logs() {
    global $wpdb;

    $wpdb->query(
        $wpdb->prepare(
            'DELETE FROM ' . haraka_error_log_table() . '
             WHERE resolved = 1
             AND created_at < %s',
            gmdate( 'Y-m-d H:i:s', strtotime( '-' . HARAKA_ERROR_LOG_PURGE_DAYS . ' days' ) )
        )
    );
}
add_action( 'haraka_purge_error_log', 'haraka_purge_old_error_logs' );

// ── Schedule purge cron ───────────────────────────────────────────────────────
function haraka_schedule_error_log_purge() {
    if ( ! wp_next_scheduled( 'haraka_purge_error_log' ) ) {
        wp_schedule_event( time(), 'daily', 'haraka_purge_error_log' );
    }
}
add_action( 'wp', 'haraka_schedule_error_log_purge' );