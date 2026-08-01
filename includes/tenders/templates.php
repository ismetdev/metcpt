<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loads the single and archive template overrides for metcpt_tender.
 *
 * The markup lives in templates/tenders/. Each of those files defines its
 * own single_template/archive_template filter and, when WordPress later
 * includes the path that filter returned, renders itself. Requiring both
 * here registers the filters during module bootstrap.
 */
require_once METCPT_PATH . 'templates/tenders/single.php';
require_once METCPT_PATH . 'templates/tenders/archive.php';
