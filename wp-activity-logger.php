<?php
/**
 * Plugin Name: WP Activity Logger Pro
 * Plugin URI: https://example.com/wp-activity-logger-pro
 * Description: Advanced activity logging for WordPress with real-time notifications, analytics, threat detection, and modern reporting tools.
 * Version: 1.2.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: wp-activity-logger-pro
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPAL_VERSION', '1.2.0');
define('WPAL_PLUGIN_FILE', __FILE__);
define('WPAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPAL_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-helpers.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-dashboard.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-api.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-export.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-notifications.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-tracker.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-visual-analytics.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-threat-detection.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-server-recommendations.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-google-search-console.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-diagnostics.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-geolocation.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-settings.php';
require_once WPAL_PLUGIN_DIR . 'includes/class-wpal-archive.php';

class WP_Activity_Logger_Pro {
    /**
     * Singleton instance.
     *
     * @var WP_Activity_Logger_Pro|null
     */
    private static $instance = null;

    /**
     * Module instances.
     */
    public $helpers;
    public $dashboard;
    public $api;
    public $export;
    public $notifications;
    public $tracker;
    public $visual_analytics;
    public $threat_detection;
    public $server_recommendations;
    public $google_search_console;
    public $diagnostics;
    public $geolocation;
    public $settings;
    public $archive;

    /**
     * Get singleton.
     *
     * @return WP_Activity_Logger_Pro
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->helpers = new WPAL_Helpers();
        $this->dashboard = new WPAL_Dashboard();
        $this->api = new WPAL_API();
        $this->export = new WPAL_Export();
        $this->notifications = new WPAL_Notifications();
        $this->geolocation = new WPAL_Geolocation();
        $this->settings = new WPAL_Settings();
        $this->tracker = new WPAL_Tracker();
        $this->visual_analytics = new WPAL_Visual_Analytics();
        $this->threat_detection = new WPAL_Threat_Detection();
        $this->server_recommendations = new WPAL_Server_Recommendations();
        $this->google_search_console = new WPAL_Google_Search_Console();
        $this->diagnostics = new WPAL_Diagnostics();
        $this->archive = new WPAL_Archive();

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'maybe_upgrade_schema'));
        add_action('wp', array($this, 'schedule_cron_jobs'));
        add_action('wpal_daily_cron', array($this, 'run_daily_tasks'));
    }

    /**
     * Activation.
     */
    public function activate() {
        WPAL_Helpers::create_tables();
        WPAL_Archive::create_table();
        $this->create_threats_table();

        $defaults = WPAL_Helpers::get_default_settings();
        if (!get_option('wpal_options')) {
            add_option('wpal_options', $defaults);
        }
        if (!get_option('wpal_settings')) {
            add_option('wpal_settings', $defaults);
        }

        if (!wp_next_scheduled('wpal_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'wpal_daily_cron');
        }
    }

    /**
     * Deactivation.
     */
    public function deactivate() {
        wp_clear_scheduled_hook('wpal_daily_cron');
    }

    /**
     * Load translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain('wp-activity-logger-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Ensure schema upgrades are applied.
     */
    public function maybe_upgrade_schema() {
        WPAL_Helpers::create_tables();
        WPAL_Archive::create_table();
        $this->create_threats_table();
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin hook.
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wp-activity-logger-pro') === false) {
            return;
        }

        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_style('wpal-admin', WPAL_PLUGIN_URL . 'assets/css/wpal-admin.css', array(), WPAL_VERSION);
        wp_enqueue_style('wpal-datatables', 'https://cdn.datatables.net/1.13.8/css/dataTables.dataTables.min.css', array(), '1.13.8');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('wpal-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', array(), '4.4.2', true);
        wp_enqueue_script('wpal-datatables', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', array('jquery'), '1.13.8', true);
        wp_enqueue_script('wpal-admin', WPAL_PLUGIN_URL . 'assets/js/wpal-admin.js', array('jquery', 'jquery-ui-datepicker', 'wpal-chartjs', 'wpal-datatables'), WPAL_VERSION, true);

        wp_localize_script(
            'wpal-admin',
            'wpal_admin_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpal_nonce'),
                'confirm_delete' => __('Are you sure you want to delete this log entry?', 'wp-activity-logger-pro'),
                'confirm_delete_all' => __('Are you sure you want to delete all log entries? This action cannot be undone.', 'wp-activity-logger-pro'),
                'export_url' => admin_url('admin-ajax.php'),
            )
        );
    }

    /**
     * Create threats table.
     */
    private function create_threats_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpal_threats';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            time datetime NOT NULL,
            type varchar(50) NOT NULL,
            severity varchar(20) NOT NULL,
            description text NOT NULL,
            context longtext,
            status varchar(20) NOT NULL DEFAULT 'new',
            PRIMARY KEY  (id),
            KEY time (time),
            KEY type (type),
            KEY severity (severity),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Schedule cron jobs.
     */
    public function schedule_cron_jobs() {
        if (!wp_next_scheduled('wpal_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'wpal_daily_cron');
        }
    }

    /**
     * Run daily maintenance.
     */
    public function run_daily_tasks() {
        $this->cleanup_old_logs();

        if (method_exists($this->threat_detection, 'scheduled_threat_analysis')) {
            $this->threat_detection->scheduled_threat_analysis();
        }
    }

    /**
     * Clean up expired logs.
     */
    private function cleanup_old_logs() {
        $settings = WPAL_Helpers::get_settings();
        $retention_days = isset($settings['log_retention']) ? absint($settings['log_retention']) : 30;

        if ($retention_days < 1) {
            return;
        }

        global $wpdb;
        WPAL_Helpers::init();

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . WPAL_Helpers::$db_table . " WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
    }
}

function wp_activity_logger_pro() {
    return WP_Activity_Logger_Pro::get_instance();
}

wp_activity_logger_pro();
