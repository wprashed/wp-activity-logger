<?php
/**
 * WP Activity Logger Google Search Console Integration
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class TracePilot_Google_Search_Console {
    /**
     * Google API client
     */
    private $client;
    
    /**
     * Search Console service
     */
    private $service;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_oauth_callback'));
        add_action('wp_ajax_tracepilot_gsc_fetch_data', array($this, 'ajax_fetch_data'));
        add_action('wp_ajax_tracepilot_gsc_disconnect', array($this, 'ajax_disconnect'));
    }

    /**
     * Register Search Console settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'tracepilot_gsc_options',
            'tracepilot_gsc_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => array(),
            )
        );
    }

    /**
     * Sanitize Search Console options.
     *
     * @param array $options Raw options.
     * @return array
     */
    public function sanitize_options($options) {
        $current = get_option('tracepilot_gsc_options', array());
        $options = is_array($options) ? $options : array();

        $sanitized = array(
            'client_id' => isset($options['client_id']) ? sanitize_text_field(wp_unslash($options['client_id'])) : '',
            'client_secret' => isset($options['client_secret']) ? sanitize_text_field(wp_unslash($options['client_secret'])) : '',
        );

        if (!empty($current['access_token']) && is_array($current['access_token'])) {
            $sanitized['access_token'] = $current['access_token'];
        }

        return $sanitized;
    }

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wp-activity-logger-pro',
            __('Search Console', 'wp-activity-logger-pro'),
            __('Search Console', 'wp-activity-logger-pro'),
            TracePilot_Helpers::get_admin_capability(),
            'wp-activity-logger-pro-search-console',
            array($this, 'render_page')
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        include TracePilot_PLUGIN_DIR . 'templates/tracepilot-search-console.php';
    }
    
    /**
     * Initialize Google API client
     */
    private function initialize_client() {
        if (!class_exists('Google_Client')) {
            require_once TracePilot_PLUGIN_DIR . 'vendor/autoload.php';
        }
        
        $this->client = new Google_Client();
        $this->client->setApplicationName('TracePilot for WordPress');
        $this->client->setScopes(array('https://www.googleapis.com/auth/webmasters.readonly'));
        $this->client->setRedirectUri(admin_url('admin.php?page=wp-activity-logger-pro-search-console&oauth=callback'));
        
        // Get client ID and secret from options
        $options = get_option('tracepilot_gsc_options', array());
        
        if (!empty($options['client_id']) && !empty($options['client_secret'])) {
            $this->client->setClientId($options['client_id']);
            $this->client->setClientSecret($options['client_secret']);
            
            // Set access token if available
            if (!empty($options['access_token'])) {
                $this->client->setAccessToken($options['access_token']);
                
                // Refresh token if expired
                if ($this->client->isAccessTokenExpired()) {
                    if ($this->client->getRefreshToken()) {
                        $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                        $options['access_token'] = $this->client->getAccessToken();
                        update_option('tracepilot_gsc_options', $options);
                    }
                }
            }
        }
        
        return $this->client;
    }
    
    /**
     * Get authorization URL
     */
    public function get_auth_url() {
        $client = $this->initialize_client();
        return $client->createAuthUrl();
    }
    
    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback() {
        $page  = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $oauth = isset($_GET['oauth']) ? sanitize_key(wp_unslash($_GET['oauth'])) : '';
        $code  = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';

        if ('wp-activity-logger-pro-search-console' === $page && 'callback' === $oauth && !empty($code)) {
            if (!TracePilot_Helpers::current_user_can_manage()) {
                return;
            }

            $client = $this->initialize_client();

            try {
                $token = $client->fetchAccessTokenWithAuthCode($code);

                if (isset($token['error'])) {
                    add_settings_error(
                        'tracepilot_gsc',
                        'oauth_error',
                        sprintf(
                            /* translators: %s: OAuth error message. */
                            esc_html__('OAuth error: %s', 'wp-activity-logger-pro'),
                            esc_html($token['error'])
                        ),
                        'error'
                    );
                } else {
                    $options = get_option('tracepilot_gsc_options', array());
                    $options['access_token'] = $token;
                    update_option('tracepilot_gsc_options', $options);

                    wp_safe_redirect(admin_url('admin.php?page=wp-activity-logger-pro-search-console&connected=1'));
                    exit;
                }
            } catch (Exception $e) {
                add_settings_error(
                    'tracepilot_gsc',
                    'oauth_exception',
                    sprintf(
                        /* translators: %s: Exception message returned during OAuth flow. */
                        esc_html__('OAuth exception: %s', 'wp-activity-logger-pro'),
                        esc_html($e->getMessage())
                    ),
                    'error'
                );
            }
        }
    }
    
    /**
     * Check if connected to Google Search Console
     */
    public function is_connected() {
        $options = get_option('tracepilot_gsc_options', array());
        
        if (empty($options['client_id']) || empty($options['client_secret']) || empty($options['access_token'])) {
            return false;
        }
        
        $client = $this->initialize_client();
        return !$client->isAccessTokenExpired();
    }
    
    /**
     * Get Search Console sites
     */
    public function get_sites() {
        if (!$this->is_connected()) {
            return array();
        }
        
        try {
            $client = $this->initialize_client();
            $service = new Google_Service_Webmasters($client);
            
            $sites = $service->sites->listSites();
            return $sites->getSiteEntry();
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get Search Console data
     */
    public function get_search_data($site_url, $start_date, $end_date, $dimensions = array('query'), $row_limit = 1000) {
        if (!$this->is_connected()) {
            return array();
        }
        
        try {
            $client = $this->initialize_client();
            $service = new Google_Service_Webmasters($client);
            
            $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
            $request->setStartDate($start_date);
            $request->setEndDate($end_date);
            $request->setDimensions($dimensions);
            $request->setRowLimit($row_limit);
            
            $response = $service->searchanalytics->query($site_url, $request);
            return $response->getRows();
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * AJAX fetch data
     */
    public function ajax_fetch_data() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Check if connected
        if (!$this->is_connected()) {
            wp_send_json_error(array('message' => __('Not connected to Google Search Console.', 'wp-activity-logger-pro')));
        }
        
        // Get parameters
        $site_url   = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : gmdate('Y-m-d', strtotime('-30 days'));
        $end_date   = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : gmdate('Y-m-d');
        $dimensions = isset($_POST['dimensions']) ? array_values(array_filter(array_map('sanitize_key', (array) wp_unslash($_POST['dimensions'])))) : array('query');
        
        if (empty($site_url)) {
            wp_send_json_error(array('message' => __('Site URL is required.', 'wp-activity-logger-pro')));
        }
        
        // Get data
        $data = $this->get_search_data($site_url, $start_date, $end_date, $dimensions);
        
        if (isset($data['error'])) {
            wp_send_json_error(array('message' => sanitize_text_field($data['error'])));
        }
        
        // Get log data for correlation
        $log_data = $this->get_log_data_for_correlation($start_date, $end_date);
        
        wp_send_json_success(array(
            'search_data' => $data,
            'log_data' => $log_data,
            'correlation' => $this->correlate_data($data, $log_data)
        ));
    }
    
    /**
     * Get log data for correlation
     */
    private function get_log_data_for_correlation($start_date, $end_date) {
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        
        $logs_by_date = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(time) as log_date, 
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users
            FROM $table_name
            WHERE time BETWEEN %s AND %s
            GROUP BY DATE(time)
            ORDER BY log_date ASC
        ", $start_date . ' 00:00:00', $end_date . ' 23:59:59'), ARRAY_A);
        
        $result = array();
        foreach ($logs_by_date as $log) {
            $result[$log['log_date']] = array(
                'count' => (int) $log['count'],
                'unique_users' => (int) $log['unique_users']
            );
        }
        
        return $result;
    }
    
    /**
     * Correlate Search Console data with log data
     */
    private function correlate_data($search_data, $log_data) {
        // Group search data by date
        $search_by_date = array();
        foreach ($search_data as $row) {
            $date = isset($row->keys[0]) ? $row->keys[0] : '';
            if (!empty($date) && strtotime($date)) {
                if (!isset($search_by_date[$date])) {
                    $search_by_date[$date] = array(
                        'clicks' => 0,
                        'impressions' => 0,
                        'ctr' => 0,
                        'position' => 0
                    );
                }
                
                $search_by_date[$date]['clicks'] += $row->clicks;
                $search_by_date[$date]['impressions'] += $row->impressions;
                $search_by_date[$date]['ctr'] = ($search_by_date[$date]['impressions'] > 0) ? 
                    ($search_by_date[$date]['clicks'] / $search_by_date[$date]['impressions']) * 100 : 0;
                $search_by_date[$date]['position'] += $row->position;
            }
        }
        
        // Combine search data with log data
        $correlation = array();
        $dates = array_unique(array_merge(array_keys($search_by_date), array_keys($log_data)));
        sort($dates);
        
        foreach ($dates as $date) {
            $correlation[$date] = array(
                'search' => isset($search_by_date[$date]) ? $search_by_date[$date] : array(
                    'clicks' => 0,
                    'impressions' => 0,
                    'ctr' => 0,
                    'position' => 0
                ),
                'logs' => isset($log_data[$date]) ? $log_data[$date] : array(
                    'count' => 0,
                    'unique_users' => 0
                )
            );
        }
        
        return $correlation;
    }
    
    /**
     * AJAX disconnect
     */
    public function ajax_disconnect() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Delete access token
        $options = get_option('tracepilot_gsc_options', array());
        unset($options['access_token']);
        update_option('tracepilot_gsc_options', $options);
        
        wp_send_json_success(array('message' => __('Successfully disconnected from Google Search Console.', 'wp-activity-logger-pro')));
    }
}
