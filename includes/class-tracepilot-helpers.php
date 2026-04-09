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

class TracePilot_Helpers {
    /**
     * Database table name.
     *
     * @var string
     */
    public static $db_table;

    /**
     * Cached log table columns.
     *
     * @var array|null
     */
    private static $log_table_columns = null;

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
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'daily_summary_enabled' => 0,
            'daily_summary_email' => get_option('admin_email'),
            'daily_summary_include_threats' => 1,
            'weekly_summary_enabled' => 0,
            'weekly_summary_email' => get_option('admin_email'),
            'enable_threat_detection' => 1,
            'enable_threat_notifications' => 1,
            'monitor_failed_logins' => 1,
            'monitor_unusual_logins' => 1,
            'monitor_file_changes' => 1,
            'monitor_privilege_escalation' => 1,
            'monitor_file_integrity' => 1,
            'enable_vulnerability_scanner' => 1,
            'vulnerability_auto_scan' => 0,
            'vulnerability_sources' => array('wordfence', 'patchstack', 'wpscan'),
            'vulnerability_scan_plugins' => 1,
            'vulnerability_scan_themes' => 1,
            'vulnerability_scan_core' => 1,
            'vulnerability_include_file_integrity' => 1,
            'wordfence_api_key' => '',
            'patchstack_api_key' => '',
            'wpscan_api_token' => '',
            'enable_geolocation' => 1,
            'anonymize_ip' => 0,
            'gdpr_mode' => 0,
            'mask_ip_in_ui' => 0,
            'exclude_roles' => array(),
            'excluded_actions' => '',
            'suppressed_severities' => array(),
            'severity_rules' => '',
            'redact_context_keys' => '',
            'retention_info_days' => 30,
            'retention_warning_days' => 60,
            'retention_error_days' => 90,
            'retention_action_rules' => '',
            'blocked_ips' => array(),
            'plugin_changes_locked' => 0,
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

        $settings = wp_parse_args($settings, $defaults);

        if (!empty($settings['gdpr_mode'])) {
            $settings = self::apply_gdpr_guardrails($settings);
        }

        return $settings;
    }

    /**
     * Enforce GDPR-oriented privacy defaults when GDPR mode is enabled.
     *
     * @param array $settings Current settings.
     * @return array
     */
    private static function apply_gdpr_guardrails($settings) {
        $settings['anonymize_ip'] = 1;
        $settings['enable_geolocation'] = 0;
        $settings['mask_ip_in_ui'] = 1;

        $settings['log_retention'] = min(max(7, absint($settings['log_retention'])), 90);
        $settings['retention_info_days'] = min(max(7, absint($settings['retention_info_days'])), 90);
        $settings['retention_warning_days'] = min(max(14, absint($settings['retention_warning_days'])), 180);
        $settings['retention_error_days'] = min(max(30, absint($settings['retention_error_days'])), 365);

        $required_keys = array(
            'password',
            'pass',
            'pwd',
            'token',
            'secret',
            'authorization',
            'cookie',
            'email',
            'phone',
            'ip',
            'first_name',
            'last_name',
            'address',
        );

        $current_keys = array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $settings['redact_context_keys'])));
        $merged_keys = array_values(array_unique(array_filter(array_map('sanitize_key', array_merge($current_keys, $required_keys)))));
        $settings['redact_context_keys'] = implode(',', $merged_keys);

        return $settings;
    }

    /**
     * Determine whether sensitive IP data should be masked in admin UI.
     *
     * @return bool
     */
    public static function should_mask_ip_in_ui() {
        $settings = self::get_settings();
        return !empty($settings['mask_ip_in_ui']) || !empty($settings['gdpr_mode']) || !empty($settings['anonymize_ip']);
    }

    /**
     * Format an IP address for UI display based on privacy settings.
     *
     * @param string $ip Raw IP address.
     * @return string
     */
    public static function format_ip_for_display($ip) {
        $ip = (string) $ip;
        if ('' === $ip) {
            return '';
        }

        if (!self::should_mask_ip_in_ui()) {
            return $ip;
        }

        $masked = self::anonymize_ip($ip);
        if ($masked !== $ip) {
            return $masked;
        }

        return substr($ip, 0, 3) . '***';
    }

    /**
     * Get the capability used for plugin admin menus on the current screen.
     *
     * @return string
     */
    public static function get_admin_capability() {
        if (is_multisite() && is_network_admin()) {
            return 'manage_network_options';
        }

        return 'manage_options';
    }

    /**
     * Check whether the current user can manage the plugin.
     *
     * @return bool
     */
    public static function current_user_can_manage() {
        return current_user_can('manage_options') || (is_multisite() && is_super_admin());
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
        self::$log_table_columns = null;
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

        $severity = self::apply_severity_rules($action, $severity, $settings);

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

        $context = isset($args['context']) ? self::redact_context($args['context'], $settings) : null;

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
            'context' => null !== $context ? wp_json_encode($context) : null,
        );

        $data = self::filter_log_data_for_existing_columns($data);
        $inserted = $wpdb->insert(self::$db_table, $data);
        if (!$inserted && false !== strpos((string) $wpdb->last_error, 'doesn\'t exist')) {
            self::create_tables();
            $data = self::filter_log_data_for_existing_columns($data);
            $inserted = $wpdb->insert(self::$db_table, $data);
        }
        if (!$inserted) {
            return false;
        }

        $log_id = (int) $wpdb->insert_id;
        do_action('tracepilot_after_log_activity', $log_id, $action, $description, $severity, $args);
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

        if (!empty($settings['suppressed_severities'])) {
            $suppressed = array_values(array_unique(array_map('sanitize_key', (array) $settings['suppressed_severities'])));
            $all_levels = array('info', 'warning', 'error', 'critical');
            $normal_levels = array('info', 'warning', 'error');

            // Failsafe: never let the settings accidentally suppress every severity,
            // otherwise the logger appears "broken" and no new activity is stored.
            if (
                count(array_intersect($all_levels, $suppressed)) < count($all_levels) &&
                count(array_intersect($normal_levels, $suppressed)) < count($normal_levels) &&
                in_array($severity, $suppressed, true)
            ) {
                return false;
            }
        }

        $role = isset($args['user_role']) ? $args['user_role'] : '';
        if (!empty($settings['exclude_roles']) && !empty($role) && in_array($role, (array) $settings['exclude_roles'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Filter log payload by actual table columns.
     *
     * @param array $data Log data.
     * @return array
     */
    private static function filter_log_data_for_existing_columns($data) {
        $columns = self::get_log_table_columns();
        if (empty($columns)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($columns));
    }

    /**
     * Get existing log table columns.
     *
     * @return array
     */
    private static function get_log_table_columns() {
        global $wpdb;

        if (null !== self::$log_table_columns) {
            return self::$log_table_columns;
        }

        self::init();
        $table = self::$db_table;
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        self::$log_table_columns = is_array($columns) ? $columns : array();

        return self::$log_table_columns;
    }

    /**
     * Apply action-based severity overrides.
     *
     * @param string $action Action.
     * @param string $severity Severity.
     * @param array  $settings Settings.
     * @return string
     */
    public static function apply_severity_rules($action, $severity, $settings) {
        if (empty($settings['severity_rules'])) {
            return $severity;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $settings['severity_rules']);
        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line || false === strpos($line, '=')) {
                continue;
            }

            list($rule_action, $rule_severity) = array_map('trim', explode('=', $line, 2));
            if ($rule_action === $action && in_array($rule_severity, array('info', 'warning', 'error', 'critical'), true)) {
                return $rule_severity;
            }
        }

        return $severity;
    }

    /**
     * Redact configured keys from context payloads.
     *
     * @param mixed $context Context.
     * @param array $settings Settings.
     * @return mixed
     */
    public static function redact_context($context, $settings) {
        if (empty($settings['redact_context_keys'])) {
            return $context;
        }

        $keys = array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $settings['redact_context_keys'])));
        if (empty($keys) || !is_array($context)) {
            return $context;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $context)) {
                $context[$key] = '[redacted]';
            }
        }

        return $context;
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
     * Get normalized client IP diagnostics.
     *
     * @return array
     */
    public static function get_ip_details() {
        $details = array(
            'ip' => '',
            'source' => '',
            'is_public' => false,
            'raw_headers' => array(),
            'candidates' => array(),
        );

        $headers = array(
            'HTTP_CF_CONNECTING_IP' => 'Cloudflare',
            'HTTP_TRUE_CLIENT_IP' => 'True-Client-IP',
            'HTTP_X_REAL_IP' => 'X-Real-IP',
            'HTTP_X_FORWARDED_FOR' => 'X-Forwarded-For',
            'HTTP_CLIENT_IP' => 'Client-IP',
            'REMOTE_ADDR' => 'Remote Address',
        );

        foreach ($headers as $server_key => $label) {
            if (empty($_SERVER[$server_key])) {
                continue;
            }

            $raw_value = trim((string) wp_unslash($_SERVER[$server_key]));
            if ('' === $raw_value) {
                continue;
            }

            $details['raw_headers'][$server_key] = $raw_value;
            $parts = array_map('trim', explode(',', $raw_value));

            foreach ($parts as $part) {
                $ip = self::normalize_ip_candidate($part);
                if (!$ip) {
                    continue;
                }

                $candidate = array(
                    'ip' => $ip,
                    'source' => $label,
                    'is_public' => self::is_public_ip($ip),
                );

                $details['candidates'][] = $candidate;
            }
        }

        if (empty($details['candidates'])) {
            return $details;
        }

        $selected = null;
        foreach ($details['candidates'] as $candidate) {
            if ($candidate['is_public']) {
                $selected = $candidate;
                break;
            }
        }

        if (!$selected) {
            $selected = $details['candidates'][0];
        }

        $details['ip'] = $selected['ip'];
        $details['source'] = $selected['source'];
        $details['is_public'] = $selected['is_public'];

        return $details;
    }

    /**
     * Get client IP.
     *
     * @return string
     */
    public static function get_ip_address() {
        $details = self::get_ip_details();
        return $details['ip'];
    }

    /**
     * Normalize a possible IP value from a proxy header.
     *
     * @param string $value Header value.
     * @return string
     */
    private static function normalize_ip_candidate($value) {
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ('' === $value || 'unknown' === strtolower($value)) {
            return '';
        }

        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }

        if (preg_match('/^\[(.*)\]:(\d+)$/', $value, $matches) && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $matches[1];
        }

        if (preg_match('/^(.+):(\d+)$/', $value, $matches) && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Determine whether an IP is public.
     *
     * @param string $ip IP address.
     * @return bool
     */
    private static function is_public_ip($ip) {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
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
            '<span class="tracepilot-badge tracepilot-badge-%1$s">%2$s</span>',
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

        if (is_multisite() && is_network_admin()) {
            $logs = self::get_logs(array(), 2000);
            $today_start = strtotime(gmdate('Y-m-d 00:00:00', current_time('timestamp')));
            $unique_users = array();
            $warnings = 0;
            $today_logs = 0;
            foreach ($logs as $log) {
                if (!empty($log->user_id)) {
                    $unique_users[(int) $log->user_id] = true;
                }
                if (in_array($log->severity, array('warning', 'error', 'critical'), true)) {
                    $warnings++;
                }
                if (strtotime($log->time) >= $today_start) {
                    $today_logs++;
                }
            }

            return array(
                'total_logs' => count($logs),
                'today_logs' => $today_logs,
                'unique_users' => count($unique_users),
                'warnings' => $warnings,
                'open_threats' => 0,
            );
        }

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

        if (is_multisite() && is_network_admin()) {
            $logs = self::get_logs(array(), 5000);
            $indexed = array();
            for ($i = $days - 1; $i >= 0; $i--) {
                $day = gmdate('Y-m-d', strtotime("-{$i} days", current_time('timestamp')));
                $indexed[$day] = 0;
            }
            foreach ($logs as $log) {
                $day = gmdate('Y-m-d', strtotime($log->time));
                if (isset($indexed[$day])) {
                    $indexed[$day]++;
                }
            }
            return array('labels' => array_keys($indexed), 'values' => array_values($indexed));
        }

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

    /**
     * Get site label for a blog ID.
     *
     * @param int $site_id Site/blog ID.
     * @return string
     */
    public static function get_site_label($site_id) {
        if (!is_multisite()) {
            return get_bloginfo('name');
        }

        $details = get_blog_details((int) $site_id);
        return $details ? $details->blogname : sprintf(__('Site #%d', 'wp-activity-logger-pro'), (int) $site_id);
    }

    /**
     * Aggregate logs across multisite installations when in network admin.
     *
     * @param array $filters Filters.
     * @param int   $limit Limit.
     * @return array
     */
    public static function get_logs($filters = array(), $limit = 500) {
        global $wpdb;

        self::init();
        $limit = max(1, absint($limit));

        if (!is_multisite() || !is_network_admin()) {
            return self::query_local_logs(self::$db_table, $filters, $limit);
        }

        $all_logs = array();
        $site_ids = !empty($filters['site_id']) ? array((int) $filters['site_id']) : wp_list_pluck(get_sites(array('number' => 1000)), 'blog_id');
        foreach ($site_ids as $site_id) {
            switch_to_blog((int) $site_id);
            self::init();
            $rows = self::query_local_logs(self::$db_table, $filters, $limit, false);
            foreach ($rows as $row) {
                $row->site_id = (int) $site_id;
                $row->site_label = self::get_site_label($site_id);
                $all_logs[] = $row;
            }
            restore_current_blog();
        }
        self::init();

        usort(
            $all_logs,
            function($a, $b) {
                return strcmp($b->time, $a->time);
            }
        );

        return array_slice($all_logs, 0, $limit);
    }

    /**
     * Query the local logs table.
     *
     * @param string $table_name Table name.
     * @param array  $filters Filters.
     * @param int    $limit Limit.
     * @param bool   $prepare_limit Whether to include limit in SQL.
     * @return array
     */
    public static function query_local_logs($table_name, $filters = array(), $limit = 500, $prepare_limit = true) {
        global $wpdb;

        $where = array('1=1');
        $args = array();

        $map = array(
            'role_filter' => 'user_role = %s',
            'severity_filter' => 'severity = %s',
            'action_filter' => 'action = %s',
            'site_id' => 'site_id = %d',
        );

        foreach ($map as $filter_key => $sql) {
            if (!empty($filters[$filter_key])) {
                $where[] = $sql;
                $args[] = ('site_id' === $filter_key) ? (int) $filters[$filter_key] : $filters[$filter_key];
            }
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'time >= %s';
            $args[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'time <= %s';
            $args[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = '(username LIKE %s OR action LIKE %s OR description LIKE %s)';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        $query = "SELECT * FROM $table_name WHERE " . implode(' AND ', $where) . ' ORDER BY time DESC';
        if ($prepare_limit) {
            $query .= $wpdb->prepare(' LIMIT %d', $limit);
        } else {
            $query .= ' LIMIT ' . (int) $limit;
        }

        if (!empty($args)) {
            $query = $wpdb->prepare($query, $args);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Export logs for a specific user.
     *
     * @param int $user_id User ID.
     * @return array
     */
    public static function export_user_logs($user_id) {
        global $wpdb;
        self::init();

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . self::$db_table . ' WHERE user_id = %d ORDER BY time DESC',
                (int) $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Delete logs for a specific user.
     *
     * @param int $user_id User ID.
     * @return int|false
     */
    public static function delete_user_logs($user_id) {
        global $wpdb;
        self::init();

        return $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . self::$db_table . ' WHERE user_id = %d',
                (int) $user_id
            )
        );
    }
}
