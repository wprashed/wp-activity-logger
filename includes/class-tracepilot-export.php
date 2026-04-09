<?php
/**
 * Export class for TracePilot for WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TracePilot_Export {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_export_page'), 35);
        add_action('network_admin_menu', array($this, 'add_export_page'), 35);
        add_action('wp_ajax_tracepilot_export_logs', array($this, 'export_logs'));
    }

    /**
     * Register export page.
     */
    public function add_export_page() {
        add_submenu_page(
            'wp-activity-logger-pro',
            __('Export', 'wp-activity-logger-pro'),
            __('Export', 'wp-activity-logger-pro'),
            TracePilot_Helpers::get_admin_capability(),
            'wp-activity-logger-pro-export',
            array($this, 'render_export_page')
        );
    }

    /**
     * Render export page.
     */
    public function render_export_page() {
        include TracePilot_PLUGIN_DIR . 'templates/tracepilot-export.php';
    }

    /**
     * Export logs.
     */
    public function export_logs() {
        check_ajax_referer('tracepilot_nonce', 'nonce');

        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_die(esc_html__('You do not have permission to export logs.', 'wp-activity-logger-pro'));
        }

        $format = isset($_POST['format']) ? sanitize_key(wp_unslash($_POST['format'])) : 'csv';
        $format = in_array($format, array('csv', 'json', 'xml', 'pdf'), true) ? $format : 'csv';

        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;

        $where = array('1=1');
        $args = array();

        $filters = array(
            'user' => 'username = %s',
            'action_filter' => 'action = %s',
            'severity' => 'severity = %s',
        );

        foreach ($filters as $field => $sql) {
            if (!empty($_POST[$field])) {
                $where[] = $sql;
                $args[] = sanitize_text_field(wp_unslash($_POST[$field]));
            }
        }

        if (!empty($_POST['date_from'])) {
            $where[] = 'time >= %s';
            $args[] = sanitize_text_field(wp_unslash($_POST['date_from'])) . ' 00:00:00';
        }

        if (!empty($_POST['date_to'])) {
            $where[] = 'time <= %s';
            $args[] = sanitize_text_field(wp_unslash($_POST['date_to'])) . ' 23:59:59';
        }

        $query = "SELECT * FROM $table_name WHERE " . implode(' AND ', $where) . ' ORDER BY time DESC';
        if (!empty($args)) {
            $query = $wpdb->prepare($query, $args);
        }

        $logs = $wpdb->get_results($query, ARRAY_A);
        $settings = TracePilot_Helpers::get_settings();
        $mask_ip_in_exports = !empty($settings['gdpr_mode']) || !empty($settings['anonymize_ip']) || !empty($settings['mask_ip_in_ui']);
        if ($mask_ip_in_exports && !empty($logs)) {
            foreach ($logs as &$log) {
                $log['ip'] = isset($log['ip']) ? TracePilot_Helpers::format_ip_for_display($log['ip']) : '';
            }
            unset($log);
        }
        $filename = sanitize_file_name('activity-logs-' . gmdate('Y-m-d'));

        nocache_headers();

        switch ($format) {
            case 'json':
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename . '.json');
                echo wp_json_encode($logs);
                break;

            case 'xml':
                header('Content-Type: application/xml; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename . '.xml');
                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><logs></logs>');
                foreach ($logs as $log) {
                    $node = $xml->addChild('log');
                    foreach ($log as $key => $value) {
                        $node->addChild($key, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
                    }
                }
                echo $xml->asXML();
                break;

            case 'pdf':
                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename . '.txt');
                echo esc_html__("TracePilot Export\n\n", 'wp-activity-logger-pro');
                foreach ($logs as $log) {
                    echo esc_html(
                        sprintf(
                            '[%1$s] %2$s | %3$s | %4$s | %5$s',
                            $log['time'],
                            $log['severity'],
                            $log['username'],
                            $log['action'],
                            $log['description']
                        )
                    ) . "\n";
                }
                break;

            case 'csv':
            default:
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename . '.csv');
                $output = fopen('php://output', 'w');
                fputcsv($output, array('ID', 'Time', 'User', 'Role', 'Action', 'Severity', 'IP', 'Browser', 'Object', 'Description'));
                foreach ($logs as $log) {
                    fputcsv(
                        $output,
                        array(
                            $log['id'],
                            $log['time'],
                            $log['username'],
                            $log['user_role'],
                            $log['action'],
                            $log['severity'],
                            $log['ip'],
                            $log['browser'],
                            $log['object_name'],
                            $log['description'],
                        )
                    );
                }
                fclose($output);
                break;
        }

        exit;
    }
}
