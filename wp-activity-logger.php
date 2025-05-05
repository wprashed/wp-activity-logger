<?php
/**
 * Plugin Name: WP Activity Logger Pro
 * Plugin URI: https://example.com/wp-activity-logger-pro
 * Description: Advanced activity logging for WordPress with enhanced features.
 * Version: 1.2.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: wp-activity-logger-pro
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPAL_VERSION', '1.2.0');
define('WPAL_PATH', plugin_dir_path(__FILE__));
define('WPAL_URL', plugin_dir_url(__FILE__));
define('WPAL_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once WPAL_PATH . 'includes/class-wpal-helpers.php';
require_once WPAL_PATH . 'includes/class-wpal-tracker.php';
require_once WPAL_PATH . 'includes/class-wpal-dashboard.php';
require_once WPAL_PATH . 'includes/class-wpal-notifications.php';
require_once WPAL_PATH . 'includes/class-wpal-api.php';
require_once WPAL_PATH . 'includes/class-wpal-export.php';

// Initialize the plugin
function wpal_init() {
    // Initialize classes
    WPAL_Helpers::init();
    WPAL_Tracker::init();
    WPAL_Dashboard::init();
    WPAL_Notifications::init();
    WPAL_API::init();
    WPAL_Export::init();
    
    // Check and fix database on activation
    if (get_option('wpal_db_check_needed', false)) {
        WPAL_Helpers::check_and_fix_database();
        delete_option('wpal_db_check_needed');
    }
    
    // Load translations
    load_plugin_textdomain('wp-activity-logger-pro', false, dirname(WPAL_BASENAME) . '/languages');
}
add_action('plugins_loaded', 'wpal_init');

// Plugin activation
function wpal_activate() {
    // Create database table
    WPAL_Helpers::init();
    WPAL_Helpers::create_db_table();
    
    // Create logs directory
    $log_dir = WPAL_PATH . 'logs/';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Create CSV file
    $csv_file = $log_dir . 'activity.csv';
    if (!file_exists($csv_file)) {
        file_put_contents($csv_file, "Time,User,Action,IP,UserRole,Browser,Severity\n");
    }
    
    // Set default options
    if (!get_option('wpal_retention_days')) {
        update_option('wpal_retention_days', 30);
    }
    
    if (!get_option('wpal_log_storage')) {
        update_option('wpal_log_storage', 'both'); // Options: 'database', 'file', 'both'
    }
    
    if (!get_option('wpal_notification_email')) {
        update_option('wpal_notification_email', get_option('admin_email'));
    }
    
    if (!get_option('wpal_notification_events')) {
        update_option('wpal_notification_events', ['login_failed', 'plugin_activated', 'plugin_deactivated']);
    }
    
    if (!get_option('wpal_daily_report')) {
        update_option('wpal_daily_report', true);
    }
    
    if (!get_option('wpal_webhook_url')) {
        update_option('wpal_webhook_url', '');
    }
    
    if (!get_option('wpal_severity_colors')) {
        update_option('wpal_severity_colors', [
            'info' => '#28a745',
            'warning' => '#ffc107',
            'error' => '#dc3545',
        ]);
    }
    
    // Push notification settings
    if (!get_option('wpal_push_enabled')) {
        update_option('wpal_push_enabled', false);
    }
    
    // Integration settings
    if (!get_option('wpal_slack_webhook')) {
        update_option('wpal_slack_webhook', '');
    }
    
    if (!get_option('wpal_discord_webhook')) {
        update_option('wpal_discord_webhook', '');
    }
    
    if (!get_option('wpal_telegram_bot_token')) {
        update_option('wpal_telegram_bot_token', '');
    }
    
    if (!get_option('wpal_telegram_chat_id')) {
        update_option('wpal_telegram_chat_id', '');
    }
    
    // Flag to check database on next load
    update_option('wpal_db_check_needed', true);
    
    // Log activation
    WPAL_Tracker::log('Plugin activated: WP Activity Logger Pro', get_current_user_id(), 'info');
}
register_activation_hook(__FILE__, 'wpal_activate');

// Plugin deactivation
function wpal_deactivate() {
    // Log deactivation
    WPAL_Tracker::log('Plugin deactivated: WP Activity Logger Pro', get_current_user_id(), 'info');
    
    // Clear scheduled events
    wp_clear_scheduled_hook('wpal_daily_cleanup');
    wp_clear_scheduled_hook('wpal_daily_report');
}
register_deactivation_hook(__FILE__, 'wpal_deactivate');

// Enqueue admin scripts and styles
function wpal_admin_enqueue_scripts($hook) {
    // Only load on plugin pages
    if (strpos($hook, 'wpal-') === false) {
        return;
    }
    
    // Enqueue Chart.js
    wp_enqueue_script('chartjs', WPAL_URL . 'assets/js/chart.min.js', [], '3.7.0', true);
    
    // Enqueue DataTables
    wp_enqueue_style('datatables', WPAL_URL . 'assets/css/datatables.min.css', [], '1.10.25');
    wp_enqueue_script('datatables', WPAL_URL . 'assets/js/datatables.min.js', ['jquery'], '1.10.25', true);
    
    // Enqueue plugin styles and scripts
    wp_enqueue_style('wpal-admin', WPAL_URL . 'assets/css/wpal-admin.css', [], WPAL_VERSION);
    wp_enqueue_script('wpal-admin', WPAL_URL . 'assets/js/wpal-admin.js', ['jquery', 'chartjs', 'datatables'], WPAL_VERSION, true);
    
    // Enqueue push notification script if enabled
    if (get_option('wpal_push_enabled', false)) {
        wp_enqueue_script('wpal-push', WPAL_URL . 'assets/js/wpal-push.js', ['jquery'], WPAL_VERSION, true);
        wp_localize_script('wpal-push', 'WPAL_PUSH', [
            'enabled' => get_option('wpal_push_enabled', false) ? '1' : '0',
            'nonce' => wp_create_nonce('wpal_push'),
            'icon' => WPAL_URL . 'assets/img/notification-icon.png',
            'logs_url' => admin_url('admin.php?page=wpal-logs'),
        ]);
    }
    
    // Pass data to JavaScript
    wp_localize_script('wpal-admin', 'WPAL', [
        'ajax_url' => admin_url('ajax.php'),
        'rest_url' => rest_url('wpal/v1/'),
        'nonce' => wp_create_nonce('wpal_admin'),
        'export_nonce' => wp_create_nonce('wpal_export'),
        'delete_nonce' => wp_create_nonce('wpal_delete'),
        'settings_nonce' => wp_create_nonce('wpal_settings'),
        'confirm_delete' => __('Are you sure you want to delete this log entry?', 'wp-activity-logger-pro'),
        'confirm_delete_all' => __('Are you sure you want to delete all log entries? This cannot be undone.', 'wp-activity-logger-pro'),
        'error_loading' => __('Error loading dashboard data. Please try again.', 'wp-activity-logger-pro'),
    ]);
}
add_action('admin_enqueue_scripts', 'wpal_admin_enqueue_scripts');

// Schedule daily cleanup
function wpal_schedule_events() {
    if (!wp_next_scheduled('wpal_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'wpal_daily_cleanup');
    }
    
    if (get_option('wpal_daily_report', false) && !wp_next_scheduled('wpal_daily_report')) {
        wp_schedule_event(time(), 'daily', 'wpal_daily_report');
    }
}
add_action('wp', 'wpal_schedule_events');

// Daily cleanup action
function wpal_daily_cleanup() {
    $retention_days = get_option('wpal_retention_days', 30);
    WPAL_Helpers::cleanup_old_logs($retention_days);
}
add_action('wpal_daily_cleanup', 'wpal_daily_cleanup');

// Daily report action
function wpal_daily_report() {
    if (get_option('wpal_daily_report', false)) {
        WPAL_Notifications::send_daily_report();
    }
}
add_action('wpal_daily_report', 'wpal_daily_report');

// AJAX handler for deleting logs
add_action('wp_ajax_wpal_delete_log', function() {
    check_ajax_referer('wpal_delete', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
    
    if ($log_id > 0) {
        global $wpdb;
        WPAL_Helpers::init();
        $result = $wpdb->delete(WPAL_Helpers::$db_table, ['id' => $log_id], ['%d']);
        
        if ($result) {
            wp_send_json_success('Log entry deleted successfully.');
        } else {
            wp_send_json_error('Failed to delete log entry.');
        }
    } else {
        wp_send_json_error('Invalid log ID.');
    }
});

// AJAX handler for deleting all logs
add_action('wp_ajax_wpal_delete_all_logs', function() {
    check_ajax_referer('wpal_delete', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    global $wpdb;
    WPAL_Helpers::init();
    $result = $wpdb->query("TRUNCATE TABLE " . WPAL_Helpers::$db_table);
    
    if ($result !== false) {
        // Also clear the CSV file
        $csv_file = WPAL_PATH . 'logs/activity.csv';
        if (file_exists($csv_file)) {
            file_put_contents($csv_file, "Time,User,Action,IP,UserRole,Browser,Severity\n");
        }
        
        wp_send_json_success('All log entries deleted successfully.');
    } else {
        wp_send_json_error('Failed to delete log entries.');
    }
});

// AJAX handler for saving settings
add_action('wp_ajax_wpal_save_settings', function() {
    check_ajax_referer('wpal_settings', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
    
    if (!empty($settings)) {
        foreach ($settings as $key => $value) {
            update_option('wpal_' . $key, $value);
        }
        
        wp_send_json_success('Settings saved successfully.');
    } else {
        wp_send_json_error('No settings to save.');
    }
});

// AJAX handler for repairing database
add_action('wp_ajax_wpal_repair_database', function() {
    check_ajax_referer('wpal_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    WPAL_Helpers::init();
    WPAL_Helpers::create_db_table();
    
    // Add a test entry
    $current_user = wp_get_current_user();
    $entry = [
        'time' => current_time('mysql'),
        'user_id' => $current_user->ID,
        'username' => $current_user->user_login,
        'user_role' => implode(', ', $current_user->roles),
        'action' => 'Database table repaired',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'browser' => WPAL_Helpers::get_browser_name(),
        'severity' => 'info',
        'context' => json_encode(['automatic' => true]),
    ];
    
    global $wpdb;
    $wpdb->insert(
        WPAL_Helpers::$db_table,
        $entry,
        [
            'time' => '%s',
            'user_id' => '%d',
            'username' => '%s',
            'user_role' => '%s',
            'action' => '%s',
            'ip' => '%s',
            'browser' => '%s',
            'severity' => '%s',
            'context' => '%s',
        ]
    );
    
    wp_send_json_success('Database table repaired successfully.');
});

// AJAX handler for repairing files
add_action('wp_ajax_wpal_repair_files', function() {
    check_ajax_referer('wpal_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    WPAL_Helpers::init();
    $log_dir = WPAL_PATH . 'logs/';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        if (!mkdir($log_dir, 0755, true)) {
            wp_send_json_error('Failed to create logs directory. Please check file permissions.');
        }
    }
    
    // Create CSV file if it doesn't exist
    $csv_file = $log_dir . 'activity.csv';
    if (!file_exists($csv_file)) {
        if (file_put_contents($csv_file, "Time,User,Action,IP,UserRole,Browser,Severity\n") === false) {
            wp_send_json_error('Failed to create CSV file. Please check file permissions.');
        }
    }
    
    wp_send_json_success('Log files repaired successfully.');
});

// AJAX handler for resetting settings
add_action('wp_ajax_wpal_reset_settings', function() {
    check_ajax_referer('wpal_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    // Default settings
    $default_options = [
        'retention_days' => 30,
        'log_storage' => 'both',
        'notification_email' => get_option('admin_email'),
        'notification_events' => ['login_failed', 'plugin_activated', 'plugin_deactivated'],
        'daily_report' => true,
        'webhook_url' => '',
        'severity_colors' => [
            'info' => '#28a745',
            'warning' => '#ffc107',
            'error' => '#dc3545',
        ],
        'push_enabled' => false,
        'slack_webhook' => '',
        'discord_webhook' => '',
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',
    ];
    
    // Reset all settings
    foreach ($default_options as $key => $value) {
        update_option('wpal_' . $key, $value);
    }
    
    wp_send_json_success('Settings reset to default values.');
});

// AJAX handler for creating test log
add_action('wp_ajax_wpal_test_log', function() {
    check_ajax_referer('wpal_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    WPAL_Tracker::log('Test log entry from diagnostics page', get_current_user_id(), 'info', [
        'test' => true,
        'timestamp' => time(),
    ]);
    
    wp_send_json_success('Test log entry created successfully. Check the logs page to see it.');
});

// Add plugin action links
function wpal_plugin_action_links($links) {
    $plugin_links = [
        '<a href="' . admin_url('admin.php?page=wpal-logs') . '">' . __('View Logs', 'wp-activity-logger-pro') . '</a>',
        '<a href="' . admin_url('admin.php?page=wpal-settings') . '">' . __('Settings', 'wp-activity-logger-pro') . '</a>',
    ];
    
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . WPAL_BASENAME, 'wpal_plugin_action_links');

// Add plugin meta links
function wpal_plugin_meta_links($links, $file) {
    if ($file === WPAL_BASENAME) {
        $links[] = '<a href="https://example.com/docs/wp-activity-logger-pro/" target="_blank">' . __('Documentation', 'wp-activity-logger-pro') . '</a>';
        $links[] = '<a href="https://example.com/support/" target="_blank">' . __('Support', 'wp-activity-logger-pro') . '</a>';
    }
    
    return $links;
}
add_filter('plugin_row_meta', 'wpal_plugin_meta_links', 10, 2);