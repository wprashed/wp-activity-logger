<?php
/**
 * WP Activity Logger Settings
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class WPAL_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'), 20);
        add_action('wp_ajax_wpal_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register the wpal_options setting
        register_setting(
            'wpal_options_group',
            'wpal_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => array(
                    'log_user_actions' => 1,
                    'log_system_actions' => 1,
                    'log_retention' => 30,
                    'enable_notifications' => 0,
                    'notification_email' => get_option('admin_email'),
                    'notification_events' => array('login_failed', 'plugin_activated', 'plugin_deactivated', 'theme_switched'),
                    'enable_threat_detection' => 1,
                    'enable_threat_notifications' => 1,
                    'monitor_failed_logins' => 1,
                    'monitor_unusual_logins' => 1,
                    'monitor_file_changes' => 1,
                    'monitor_privilege_escalation' => 1,
                    'enable_geolocation' => 1,
                )
            )
        );
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($options) {
        // Ensure we have an array
        if (!is_array($options)) {
            return array();
        }

        $sanitized = array();

        // Sanitize boolean options
        $boolean_options = array(
            'log_user_actions',
            'log_system_actions',
            'enable_notifications',
            'enable_threat_detection',
            'enable_threat_notifications',
            'monitor_failed_logins',
            'monitor_unusual_logins',
            'monitor_file_changes',
            'monitor_privilege_escalation',
            'enable_geolocation',
        );

        foreach ($boolean_options as $option) {
            $sanitized[$option] = isset($options[$option]) ? 1 : 0;
        }

        // Sanitize numeric options
        if (isset($options['log_retention'])) {
            $sanitized['log_retention'] = absint($options['log_retention']);
        }

        // Sanitize email
        if (isset($options['notification_email'])) {
            $sanitized['notification_email'] = sanitize_email($options['notification_email']);
        }

        // Sanitize notification events
        if (isset($options['notification_events']) && is_array($options['notification_events'])) {
            $sanitized['notification_events'] = array_map('sanitize_text_field', $options['notification_events']);
        }

        return $sanitized;
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'wp-activity-logger-pro-dashboard',
            __('Settings', 'wp-activity-logger-pro'),
            __('Settings', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include WPAL_PLUGIN_DIR . 'templates/settings.php';
    }

    /**
     * AJAX save settings
     */
    public function ajax_save_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpal_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Get settings
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        // Parse settings
        parse_str($settings, $parsed_settings);
        
        // Get current options
        $current_options = get_option('wpal_options', array());
        
        // Update options
        $updated_options = array_merge($current_options, isset($parsed_settings['wpal_options']) ? $parsed_settings['wpal_options'] : array());
        
        // Save options
        update_option('wpal_options', $updated_options);
        
        // Log activity
        WPAL_Helpers::log_activity(
            'settings_updated',
            __('Activity Logger settings updated', 'wp-activity-logger-pro'),
            'info'
        );
        
        wp_send_json_success(array('message' => __('Settings saved successfully.', 'wp-activity-logger-pro')));
    }
}
