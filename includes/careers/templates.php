<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loads the single and archive template overrides for metcpt_career.
 *
 * The markup lives in templates/careers/. Each of those files defines its
 * own single_template/archive_template filter and, when WordPress later
 * includes the path that filter returned, renders itself. Requiring both
 * here registers the filters during module bootstrap.
 */
require_once METCPT_PATH . 'templates/careers/single.php';
require_once METCPT_PATH . 'templates/careers/archive.php';
