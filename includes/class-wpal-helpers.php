<?php
/**
 * WP Activity Logger Helpers
 *
 * @package WP Activity Logger
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit();
}

class WPAL_Helpers {
    /**
     * Database table name.
     *
     * @var string
     */
    public static $db_table;

    /**
     * Initialize table names.
     */
    public static function init() {
        global $wpdb;
        self::$db_table = $wpdb->prefix . 'wpal_logs';
    }

    /**
     * Default plugin settings.
     *
     * @return array
     */
    public static function get_default_settings() {
        return array(
            'log_user_actions' => 1,
            'log_system_actions' => 1,
            'log_retention' => 30,
            'enable_notifications' => 0,
            'notification_email' => get_option('admin_email'),
            'notification_events' => array('login_failed', 'plugin_activated', 'plugin_deactivated', 'theme_switched'),
            'notification_severities' => array('error', 'warning'),
            'enable_webhook_notifications' => 0,
            'webhook_url' => '',
            'slack_webhook_url' => '',
            'discord_webhook_url' => '',
            'daily_summary_enabled' => 0,
            'daily_summary_email' => get_option('admin_email'),
            'daily_summary_include_threats' => 1,
            'enable_threat_detection' => 1,
            'enable_threat_notifications' => 1,
            'monitor_failed_logins' => 1,
            'monitor_unusual_logins' => 1,
            'monitor_file_changes' => 1,
            'monitor_privilege_escalation' => 1,
            'enable_geolocation' => 1,
            'anonymize_ip' => 0,
            'exclude_roles' => array(),
            'excluded_actions' => '',
            'suppressed_severities' => array(),
            'timeline_window_hours' => 12,
            'default_export_format' => 'csv',
        );
    }

    /**
     * Get settings merged with legacy option names.
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = self::get_default_settings();
        $legacy = get_option('wpal_settings', array());
        $settings = get_option('wpal_options', array());

        if (!empty($legacy) && empty($settings)) {
            $settings = $legacy;
        }

        return wp_parse_args($settings, $defaults);
    }

    /**
     * Create or upgrade plugin tables.
     */
    public static function create_tables() {
        global $wpdb;

        self::init();
        $table_name = self::$db_table;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            time datetime NOT NULL,
            site_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            username varchar(60) DEFAULT NULL,
            user_role varchar(60) DEFAULT NULL,
            action varchar(255) NOT NULL,
            description text NOT NULL,
            severity varchar(20) NOT NULL DEFAULT 'info',
            ip varchar(45) DEFAULT NULL,
            browser varchar(255) DEFAULT NULL,
            location varchar(255) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            country_code varchar(10) DEFAULT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            object_name varchar(255) DEFAULT NULL,
            context longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY time (time),
            KEY site_id (site_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY severity (severity),
            KEY ip (ip),
            KEY country_code (country_code),
            KEY object_type (object_type),
            KEY object_id (object_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Log activity.
     *
     * @param string $action Action key.
     * @param string $description Human-readable description.
     * @param string $severity Severity.
     * @param array  $args Extra data.
     * @return int|false
     */
    public static function log_activity($action, $description, $severity = 'info', $args = array()) {
        global $wpdb;

        self::init();
        $settings = self::get_settings();

        if (!self::should_log_action($action, $severity, $args, $settings)) {
            return false;
        }

        if (!isset($args['user_id'])) {
            $user = wp_get_current_user();
            $args['user_id'] = $user->ID;
            $args['username'] = $user->ID ? $user->user_login : __('Guest', 'wp-activity-logger-pro');
            $args['user_role'] = $user->ID && !empty($user->roles) ? $user->roles[0] : 'guest';
        }

        if (!isset($args['ip'])) {
            $args['ip'] = self::get_ip_address();
        }

        if (!isset($args['browser'])) {
            $args['browser'] = self::get_browser();
        }

        if (!empty($settings['anonymize_ip']) && !empty($args['ip'])) {
            $args['ip'] = self::anonymize_ip($args['ip']);
        }

        $data = array(
            'time' => current_time('mysql'),
            'site_id' => function_exists('get_current_blog_id') ? get_current_blog_id() : 1,
            'user_id' => isset($args['user_id']) ? absint($args['user_id']) : null,
            'username' => isset($args['username']) ? sanitize_text_field($args['username']) : null,
            'user_role' => isset($args['user_role']) ? sanitize_text_field($args['user_role']) : null,
            'action' => sanitize_key($action),
            'description' => wp_strip_all_tags($description),
            'severity' => sanitize_key($severity),
            'ip' => isset($args['ip']) ? sanitize_text_field($args['ip']) : null,
            'browser' => isset($args['browser']) ? sanitize_text_field($args['browser']) : null,
            'location' => isset($args['location']) ? sanitize_text_field($args['location']) : null,
            'country' => isset($args['country']) ? sanitize_text_field($args['country']) : null,
            'country_code' => isset($args['country_code']) ? sanitize_text_field($args['country_code']) : null,
            'object_type' => isset($args['object_type']) ? sanitize_text_field($args['object_type']) : null,
            'object_id' => isset($args['object_id']) ? absint($args['object_id']) : null,
            'object_name' => isset($args['object_name']) ? sanitize_text_field($args['object_name']) : null,
            'context' => isset($args['context']) ? wp_json_encode($args['context']) : null,
        );

        $inserted = $wpdb->insert(self::$db_table, $data);
        if (!$inserted) {
            return false;
        }

        $log_id = (int) $wpdb->insert_id;
        do_action('wpal_after_log_activity', $log_id, $action, $description, $severity, $args);

        return $log_id;
    }

    /**
     * Determine whether an action should be logged.
     *
     * @param string $action Action key.
     * @param string $severity Severity.
     * @param array  $args Args.
     * @param array  $settings Settings.
     * @return bool
     */
    public static function should_log_action($action, $severity, $args, $settings) {
        $excluded_actions = array_filter(array_map('trim', explode(',', (string) $settings['excluded_actions'])));
        if (in_array($action, $excluded_actions, true)) {
            return false;
        }

        if (!empty($settings['suppressed_severities']) && in_array($severity, (array) $settings['suppressed_severities'], true)) {
            return false;
        }

        $role = isset($args['user_role']) ? $args['user_role'] : '';
        if (!empty($settings['exclude_roles']) && !empty($role) && in_array($role, (array) $settings['exclude_roles'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Format a date for display.
     *
     * @param string $datetime Datetime.
     * @param string $format Optional format.
     * @return string
     */
    public static function format_datetime($datetime, $format = '') {
        if (empty($format)) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }

        return date_i18n($format, strtotime($datetime));
    }

    /**
     * Get client IP.
     *
     * @return string
     */
    public static function get_ip_address() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = wp_unslash($_SERVER['REMOTE_ADDR']);
        }

        $ip = filter_var($ip, FILTER_VALIDATE_IP);

        return $ip ? $ip : '';
    }

    /**
     * Get a condensed browser string.
     *
     * @return string
     */
    public static function get_browser() {
        $agent = !empty($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
        return sanitize_text_field(substr($agent, 0, 255));
    }

    /**
     * Anonymize an IP address.
     *
     * @param string $ip IP address.
     * @return string
     */
    public static function anonymize_ip($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts = array_pad($parts, 8, '0000');
            $parts[6] = '0000';
            $parts[7] = '0000';
            return implode(':', $parts);
        }

        return $ip;
    }

    /**
     * Build badge markup for a severity.
     *
     * @param string $severity Severity.
     * @return string
     */
    public static function get_severity_badge($severity) {
        $map = array(
            'info' => array('label' => __('Info', 'wp-activity-logger-pro'), 'class' => 'info'),
            'warning' => array('label' => __('Warning', 'wp-activity-logger-pro'), 'class' => 'warning'),
            'error' => array('label' => __('Error', 'wp-activity-logger-pro'), 'class' => 'danger'),
            'critical' => array('label' => __('Critical', 'wp-activity-logger-pro'), 'class' => 'danger'),
        );

        $data = isset($map[$severity]) ? $map[$severity] : $map['info'];

        return sprintf(
            '<span class="wpal-badge wpal-badge-%1$s">%2$s</span>',
            esc_attr($data['class']),
            esc_html($data['label'])
        );
    }

    /**
     * Fetch dashboard metrics.
     *
     * @return array
     */
    public static function get_dashboard_metrics() {
        global $wpdb;

        self::init();
        $table_name = self::$db_table;

        $today = gmdate('Y-m-d 00:00:00', current_time('timestamp'));
        $total_logs = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today_logs = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE time >= %s", $today));
        $unique_users = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE user_id > 0");
        $warnings = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE severity IN ('warning','error','critical')");

        $threat_table = $wpdb->prefix . 'wpal_threats';
        $threats = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $threat_table)) ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $threat_table WHERE status = 'new'") : 0;

        return array(
            'total_logs' => $total_logs,
            'today_logs' => $today_logs,
            'unique_users' => $unique_users,
            'warnings' => $warnings,
            'open_threats' => $threats,
        );
    }

    /**
     * Fetch time-series chart data.
     *
     * @param int $days Number of days.
     * @return array
     */
    public static function get_activity_series($days = 14) {
        global $wpdb;

        self::init();
        $table_name = self::$db_table;
        $days = max(1, absint($days));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(time) AS day, COUNT(*) AS total
                FROM $table_name
                WHERE time >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY DATE(time)
                ORDER BY day ASC",
                $days
            )
        );

        $indexed = array();
        foreach ($rows as $row) {
            $indexed[$row->day] = (int) $row->total;
        }

        $labels = array();
        $values = array();

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = gmdate('Y-m-d', strtotime("-{$i} days", current_time('timestamp')));
            $labels[] = $day;
            $values[] = isset($indexed[$day]) ? $indexed[$day] : 0;
        }

        return array('labels' => $labels, 'values' => $values);
    }
}
