<?php
/**
 * Backward-compatible loader for legacy plugin entrypoint.
 *
 * @package TracePilot_For_WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/tracepilot-for-wordpress.php';
