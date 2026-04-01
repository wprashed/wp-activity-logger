<?php
/**
 * WP Activity Logger Pro Dashboard Class.
 *
 * @package WP_Activity_Logger_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAL_Dashboard {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Register admin menu.
     */
    public function add_admin_menu() {
        $menu_icon = 'dashicons-visibility';

        add_menu_page(
            __('Activity Logger', 'wp-activity-logger-pro'),
            __('Activity Logger', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro',
            array($this, 'render_dashboard_page'),
            $menu_icon,
            30
        );

        add_submenu_page(
            'wp-activity-logger-pro',
            __('Dashboard', 'wp-activity-logger-pro'),
            __('Dashboard', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'wp-activity-logger-pro',
            __('Activity Logs', 'wp-activity-logger-pro'),
            __('Activity Logs', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-logs',
            array($this, 'render_logs_page')
        );

        add_submenu_page(
            'wp-activity-logger-pro',
            __('Analytics', 'wp-activity-logger-pro'),
            __('Analytics', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-analytics',
            array($this, 'render_analytics_page')
        );

        add_submenu_page(
            'wp-activity-logger-pro',
            __('Threat Detection', 'wp-activity-logger-pro'),
            __('Threat Detection', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-threat-detection',
            array($this, 'render_threat_detection_page')
        );

        add_submenu_page(
            'wp-activity-logger-pro',
            __('Server Recommendations', 'wp-activity-logger-pro'),
            __('Server Recommendations', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-server',
            array($this, 'render_server_recommendations_page')
        );

        add_submenu_page(
            'wp-activity-logger-pro',
            __('Diagnostics', 'wp-activity-logger-pro'),
            __('Diagnostics', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-diagnostics',
            array($this, 'render_diagnostics_page')
        );

        add_submenu_page(
            'wp-activity-logger-pro',
            __('Search Console', 'wp-activity-logger-pro'),
            __('Search Console', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-search-console',
            array($this, 'render_search_console_page')
        );
    }

    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        include WPAL_PLUGIN_DIR . 'templates/dashboard.php';
    }

    /**
     * Render logs page.
     */
    public function render_logs_page() {
        include WPAL_PLUGIN_DIR . 'templates/logs.php';
    }

    /**
     * Render analytics page.
     */
    public function render_analytics_page() {
        include WPAL_PLUGIN_DIR . 'templates/analytics.php';
    }

    /**
     * Render threat detection page.
     */
    public function render_threat_detection_page() {
        include WPAL_PLUGIN_DIR . 'templates/threat-detection.php';
    }

    /**
     * Render server recommendations page.
     */
    public function render_server_recommendations_page() {
        include WPAL_PLUGIN_DIR . 'templates/server-recommendations.php';
    }

    /**
     * Render search console page.
     */
    public function render_search_console_page() {
        include WPAL_PLUGIN_DIR . 'templates/search-console.php';
    }

    /**
     * Render diagnostics page.
     */
    public function render_diagnostics_page() {
        include WPAL_PLUGIN_DIR . 'templates/diagnostics.php';
    }
}
