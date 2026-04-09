<?php
/**
 * WP Activity Logger Settings
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit();
}

class WPAL_Settings {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'), 40);
        add_action('network_admin_menu', array($this, 'add_settings_page'), 40);
        add_action('wp_ajax_wpal_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_wpal_reset_settings', array($this, 'ajax_reset_settings'));
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'wpal_options_group',
            'wpal_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => WPAL_Helpers::get_default_settings(),
            )
        );
    }

    /**
     * Sanitize options.
     *
     * @param array $options Raw options.
     * @return array
     */
    public function sanitize_options($options) {
        $defaults = WPAL_Helpers::get_default_settings();
        if (!is_array($options)) {
            return $defaults;
        }

        $sanitized = $defaults;
        $checkbox_fields = array(
            'log_user_actions',
            'log_system_actions',
            'enable_notifications',
            'enable_webhook_notifications',
            'daily_summary_enabled',
            'daily_summary_include_threats',
            'enable_threat_detection',
            'enable_threat_notifications',
            'monitor_failed_logins',
            'monitor_unusual_logins',
            'monitor_file_changes',
            'monitor_privilege_escalation',
            'monitor_file_integrity',
            'enable_vulnerability_scanner',
            'vulnerability_auto_scan',
            'vulnerability_scan_plugins',
            'vulnerability_scan_themes',
            'vulnerability_scan_core',
            'vulnerability_include_file_integrity',
            'enable_geolocation',
            'anonymize_ip',
            'plugin_changes_locked',
            'weekly_summary_enabled',
        );

        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = empty($options[$field]) ? 0 : 1;
        }

        $sanitized['log_retention'] = isset($options['log_retention']) ? absint($options['log_retention']) : $defaults['log_retention'];
        $sanitized['timeline_window_hours'] = isset($options['timeline_window_hours']) ? max(1, absint($options['timeline_window_hours'])) : $defaults['timeline_window_hours'];
        $sanitized['retention_info_days'] = isset($options['retention_info_days']) ? absint($options['retention_info_days']) : $defaults['retention_info_days'];
        $sanitized['retention_warning_days'] = isset($options['retention_warning_days']) ? absint($options['retention_warning_days']) : $defaults['retention_warning_days'];
        $sanitized['retention_error_days'] = isset($options['retention_error_days']) ? absint($options['retention_error_days']) : $defaults['retention_error_days'];
        $sanitized['notification_email'] = isset($options['notification_email']) ? sanitize_email($options['notification_email']) : $defaults['notification_email'];
        $sanitized['daily_summary_email'] = isset($options['daily_summary_email']) ? sanitize_email($options['daily_summary_email']) : $defaults['daily_summary_email'];
        $sanitized['weekly_summary_email'] = isset($options['weekly_summary_email']) ? sanitize_email($options['weekly_summary_email']) : $defaults['weekly_summary_email'];
        $sanitized['webhook_url'] = isset($options['webhook_url']) ? esc_url_raw($options['webhook_url']) : '';
        $sanitized['slack_webhook_url'] = isset($options['slack_webhook_url']) ? esc_url_raw($options['slack_webhook_url']) : '';
        $sanitized['discord_webhook_url'] = isset($options['discord_webhook_url']) ? esc_url_raw($options['discord_webhook_url']) : '';
        $sanitized['telegram_bot_token'] = isset($options['telegram_bot_token']) ? sanitize_text_field($options['telegram_bot_token']) : '';
        $sanitized['telegram_chat_id'] = isset($options['telegram_chat_id']) ? sanitize_text_field($options['telegram_chat_id']) : '';
        $sanitized['wordfence_api_key'] = isset($options['wordfence_api_key']) ? sanitize_text_field($options['wordfence_api_key']) : '';
        $sanitized['patchstack_api_key'] = isset($options['patchstack_api_key']) ? sanitize_text_field($options['patchstack_api_key']) : '';
        $sanitized['wpscan_api_token'] = isset($options['wpscan_api_token']) ? sanitize_text_field($options['wpscan_api_token']) : '';
        $sanitized['excluded_actions'] = isset($options['excluded_actions']) ? sanitize_textarea_field($options['excluded_actions']) : '';
        $sanitized['severity_rules'] = isset($options['severity_rules']) ? sanitize_textarea_field($options['severity_rules']) : '';
        $sanitized['redact_context_keys'] = isset($options['redact_context_keys']) ? sanitize_textarea_field($options['redact_context_keys']) : '';
        $sanitized['retention_action_rules'] = isset($options['retention_action_rules']) ? sanitize_textarea_field($options['retention_action_rules']) : '';

        $sanitized['notification_events'] = isset($options['notification_events']) ? array_values(array_filter(array_map('sanitize_key', (array) $options['notification_events']))) : array();
        $sanitized['notification_severities'] = isset($options['notification_severities']) ? array_values(array_filter(array_map('sanitize_key', (array) $options['notification_severities']))) : array();
        $sanitized['suppressed_severities'] = isset($options['suppressed_severities']) ? array_values(array_filter(array_map('sanitize_key', (array) $options['suppressed_severities']))) : array();
        $sanitized['exclude_roles'] = isset($options['exclude_roles']) ? array_values(array_filter(array_map('sanitize_key', (array) $options['exclude_roles']))) : array();
        $sanitized['vulnerability_sources'] = isset($options['vulnerability_sources']) ? array_values(array_filter(array_map('sanitize_key', (array) $options['vulnerability_sources']))) : array();
        $sanitized['blocked_ips'] = isset($options['blocked_ips']) ? array_values(array_filter(array_map('sanitize_text_field', (array) $options['blocked_ips']))) : $defaults['blocked_ips'];

        $normal_severities = array('info', 'warning', 'error');
        if (count(array_intersect($normal_severities, $sanitized['suppressed_severities'])) === count($normal_severities)) {
            $sanitized['suppressed_severities'] = array();
        }

        if (empty($sanitized['log_user_actions']) && empty($sanitized['log_system_actions'])) {
            $sanitized['log_user_actions'] = 1;
            $sanitized['log_system_actions'] = 1;
        }

        $export_format = isset($options['default_export_format']) ? sanitize_key($options['default_export_format']) : '';
        if (!empty($export_format) && in_array($export_format, array('csv', 'json', 'xml', 'pdf'), true)) {
            $sanitized['default_export_format'] = $export_format;
        }

        return wp_parse_args($sanitized, $defaults);
    }

    /**
     * Add submenu page.
     */
    public function add_settings_page() {
        add_submenu_page(
            'wp-activity-logger-pro',
            __('Settings', 'wp-activity-logger-pro'),
            __('Settings', 'wp-activity-logger-pro'),
            WPAL_Helpers::get_admin_capability(),
            'wp-activity-logger-pro-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render page.
     */
    public function render_settings_page() {
        include WPAL_PLUGIN_DIR . 'templates/settings.php';
    }

    /**
     * AJAX save settings.
     */
    public function ajax_save_settings() {
        check_ajax_referer('wpal_nonce', 'nonce');

        if (!WPAL_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }

        $raw = isset($_POST['wpal_options']) ? (array) $_POST['wpal_options'] : array();
        $raw = wp_unslash($raw);
        $current = WPAL_Helpers::get_settings();
        $replace_mode = !empty($_POST['replace_mode']);
        $merged = $replace_mode ? $raw : array_merge($current, $raw);
        $sanitized = $this->sanitize_options($merged);

        if (empty($raw['blocked_ips'])) {
            $sanitized['blocked_ips'] = isset($current['blocked_ips']) ? (array) $current['blocked_ips'] : array();
        }

        update_option('wpal_options', $sanitized);
        update_option('wpal_settings', $sanitized);

        WPAL_Helpers::log_activity(
            'settings_updated',
            __('Activity Logger settings updated', 'wp-activity-logger-pro'),
            'info'
        );

        wp_send_json_success(array('message' => __('Settings saved successfully.', 'wp-activity-logger-pro')));
    }

    /**
     * AJAX reset settings.
     */
    public function ajax_reset_settings() {
        check_ajax_referer('wpal_nonce', 'nonce');

        if (!WPAL_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }

        $defaults = WPAL_Helpers::get_default_settings();
        update_option('wpal_options', $defaults);
        update_option('wpal_settings', $defaults);

        WPAL_Helpers::log_activity(
            'settings_reset',
            __('Activity Logger settings reset to defaults', 'wp-activity-logger-pro'),
            'warning'
        );

        wp_send_json_success(array('message' => __('Settings reset successfully.', 'wp-activity-logger-pro')));
    }
}
