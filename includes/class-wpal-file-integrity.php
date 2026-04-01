<?php
/**
 * File integrity monitoring for WP Activity Logger Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAL_File_Integrity {
    /**
     * Option key for baseline.
     */
    const OPTION_KEY = 'wpal_file_integrity_baseline';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('wp_ajax_wpal_build_file_baseline', array($this, 'ajax_build_baseline'));
        add_action('wp_ajax_wpal_scan_file_integrity', array($this, 'ajax_scan_integrity'));
        add_action('wpal_daily_cron', array($this, 'scheduled_scan'));
    }

    /**
     * AJAX build baseline.
     */
    public function ajax_build_baseline() {
        check_ajax_referer('wpal_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $baseline = $this->build_baseline();
        update_option(self::OPTION_KEY, $baseline, false);
        WPAL_Helpers::log_activity('file_integrity_baseline_built', __('File integrity baseline created', 'wp-activity-logger-pro'), 'info');

        wp_send_json_success(array('message' => __('Baseline created successfully.', 'wp-activity-logger-pro'), 'count' => count($baseline['files'])));
    }

    /**
     * AJAX scan.
     */
    public function ajax_scan_integrity() {
        check_ajax_referer('wpal_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $results = $this->scan_against_baseline();
        wp_send_json_success($results);
    }

    /**
     * Scheduled scan.
     */
    public function scheduled_scan() {
        $settings = WPAL_Helpers::get_settings();
        if (empty($settings['monitor_file_integrity'])) {
            return;
        }

        $this->scan_against_baseline(true);
    }

    /**
     * Build baseline from tracked paths.
     *
     * @return array
     */
    public function build_baseline() {
        $files = array();
        foreach ($this->get_target_paths() as $label => $path) {
            foreach ($this->get_files($path) as $file) {
                $files[$this->relative_path($file)] = array(
                    'checksum' => md5_file($file),
                    'group' => $label,
                );
            }
        }

        return array(
            'created_at' => current_time('mysql'),
            'files' => $files,
        );
    }

    /**
     * Scan filesystem against saved baseline.
     *
     * @param bool $log_results Whether to create threat/activity records.
     * @return array
     */
    public function scan_against_baseline($log_results = false) {
        $baseline = get_option(self::OPTION_KEY, array());
        if (empty($baseline['files'])) {
            return array(
                'message' => __('No baseline found. Build a baseline first.', 'wp-activity-logger-pro'),
                'changes' => array(),
            );
        }

        $current = $this->build_baseline();
        $changes = array();

        foreach ($baseline['files'] as $path => $meta) {
            if (!isset($current['files'][$path])) {
                $changes[] = array('type' => 'deleted', 'path' => $path, 'group' => $meta['group']);
            } elseif ($current['files'][$path]['checksum'] !== $meta['checksum']) {
                $changes[] = array('type' => 'modified', 'path' => $path, 'group' => $meta['group']);
            }
        }

        foreach ($current['files'] as $path => $meta) {
            if (!isset($baseline['files'][$path])) {
                $changes[] = array('type' => 'new', 'path' => $path, 'group' => $meta['group']);
            }
        }

        if ($log_results && !empty($changes)) {
            foreach ($changes as $change) {
                WPAL_Helpers::log_activity(
                    'file_integrity_' . $change['type'],
                    sprintf(__('File integrity alert: %s file %s', 'wp-activity-logger-pro'), $change['type'], $change['path']),
                    'error',
                    array(
                        'object_name' => $change['path'],
                        'object_type' => 'file',
                        'context' => $change,
                    )
                );
            }
        }

        return array(
            'message' => empty($changes) ? __('No integrity changes detected.', 'wp-activity-logger-pro') : __('Integrity changes detected.', 'wp-activity-logger-pro'),
            'baseline_created_at' => isset($baseline['created_at']) ? $baseline['created_at'] : '',
            'changes' => $changes,
        );
    }

    /**
     * Get baseline metadata.
     *
     * @return array
     */
    public function get_baseline_status() {
        $baseline = get_option(self::OPTION_KEY, array());
        return array(
            'exists' => !empty($baseline['files']),
            'created_at' => isset($baseline['created_at']) ? $baseline['created_at'] : '',
            'count' => !empty($baseline['files']) ? count($baseline['files']) : 0,
        );
    }

    /**
     * Target paths for monitoring.
     *
     * @return array
     */
    private function get_target_paths() {
        $paths = array(
            'core' => ABSPATH . 'wp-admin',
            'includes' => ABSPATH . 'wp-includes',
            'plugins' => WP_PLUGIN_DIR,
        );

        $theme = get_stylesheet_directory();
        if ($theme && is_dir($theme)) {
            $paths['theme'] = $theme;
        }

        return $paths;
    }

    /**
     * Enumerate files.
     *
     * @param string $path Base path.
     * @return array
     */
    private function get_files($path) {
        $allowed_extensions = array('php', 'js', 'css');
        $results = array();
        if (!is_dir($path)) {
            return $results;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (!in_array($extension, $allowed_extensions, true)) {
                continue;
            }

            $pathname = $file->getPathname();
            if (false !== strpos($pathname, '/vendor/') || false !== strpos($pathname, '/node_modules/')) {
                continue;
            }

            $results[] = $pathname;
        }

        return $results;
    }

    /**
     * Create relative path from ABSPATH.
     *
     * @param string $path Path.
     * @return string
     */
    private function relative_path($path) {
        return ltrim(str_replace(ABSPATH, '', $path), '/');
    }
}
