<?php
/*
Plugin Name: WP Activity Logger Pro
Description: Enhanced activity tracking with advanced filtering, user roles tracking, data export, custom notifications, and real-time monitoring dashboard.
Version: 2.0
Author: Rashed Hossain
*/

// Define constants
define('WPAL_PATH', plugin_dir_path(__FILE__));
define('WPAL_URL', plugin_dir_url(__FILE__));
define('WPAL_VERSION', '2.0');

// Include helper classes
include_once WPAL_PATH . 'includes/class-wpal-helpers.php';
include_once WPAL_PATH . 'includes/class-wpal-tracker.php';
include_once WPAL_PATH . 'includes/class-wpal-dashboard.php';
include_once WPAL_PATH . 'includes/class-wpal-settings.php';
include_once WPAL_PATH . 'includes/class-wpal-export.php';
include_once WPAL_PATH . 'includes/class-wpal-notifications.php';

// Hooks
register_activation_hook(__FILE__, ['WPAL_Helpers', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['WPAL_Helpers', 'plugin_deactivation']);
add_action('init', ['WPAL_Tracker', 'init']);
add_action('admin_menu', ['WPAL_Dashboard', 'init']);
add_action('admin_init', ['WPAL_Settings', 'init']);

// Enqueue admin scripts/styles
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'wpal') === false) return;
    
    wp_enqueue_style('wpal-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
    wp_enqueue_style('wpal-datepicker', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_style('wpal-css', WPAL_URL . 'assets/css/wpal-admin.css', [], WPAL_VERSION);
    
    wp_enqueue_script('wpal-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', [], false, true);
    wp_enqueue_script('wpal-chart', 'https://cdn.jsdelivr.net/npm/chart.js', [], false, true);
    wp_enqueue_script('wpal-datepicker', 'https://cdn.jsdelivr.net/npm/flatpickr', [], false, true);
    wp_enqueue_script('wpal-push', WPAL_URL . 'assets/js/wpal-push.js', ['jquery'], WPAL_VERSION, true);
    wp_enqueue_script('wpal-admin', WPAL_URL . 'assets/js/wpal-admin.js', ['jquery', 'wpal-chart', 'wpal-datepicker'], WPAL_VERSION, true);
    
    wp_localize_script('wpal-admin', 'WPAL', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'rest_url' => rest_url('wpal/v1/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'is_pro' => true,
    ]);
});

// REST API
add_action('rest_api_init', function () {
    register_rest_route('wpal/v1', '/logs', [
        'methods' => 'GET',
        'callback' => 'wpal_get_logs',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'args' => [
            'from' => [
                'type' => 'string',
                'required' => false,
            ],
            'to' => [
                'type' => 'string',
                'required' => false,
            ],
            'user' => [
                'type' => 'string',
                'required' => false,
            ],
            'action_type' => [
                'type' => 'string',
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'required' => false,
                'default' => 100,
            ],
        ],
    ]);
    
    register_rest_route('wpal/v1', '/stats', [
        'methods' => 'GET',
        'callback' => 'wpal_get_stats',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ]);
    
    register_rest_route('wpal/v1', '/export', [
        'methods' => 'GET',
        'callback' => ['WPAL_Export', 'rest_export'],
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ]);
});

function wpal_get_logs($request) {
    $params = $request->get_params();
    $logs = WPAL_Helpers::get_filtered_logs($params);
    return rest_ensure_response($logs);
}

function wpal_get_stats($request) {
    $stats = [
        'total_logs' => WPAL_Helpers::count_logs(),
        'user_activity' => WPAL_Helpers::get_user_activity_stats(),
        'action_types' => WPAL_Helpers::get_action_type_stats(),
        'daily_activity' => WPAL_Helpers::get_daily_activity_stats(),
    ];
    return rest_ensure_response($stats);
}

// AJAX handlers
add_action('wp_ajax_wpal_live_feed', ['WPAL_Dashboard', 'ajax_live_feed']);
add_action('wp_ajax_wpal_clear_logs', ['WPAL_Helpers', 'ajax_clear_logs']);
add_action('wp_ajax_wpal_test_notification', ['WPAL_Notifications', 'ajax_test_notification']);

// Cron for log maintenance and notifications
add_action('wpal_daily_maintenance', ['WPAL_Helpers', 'perform_maintenance']);
add_action('wpal_send_daily_report', ['WPAL_Notifications', 'send_daily_report']);