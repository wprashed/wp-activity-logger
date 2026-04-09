<?php
/**
 * WP Activity Logger Geolocation
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class TracePilot_Geolocation {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_tracepilot_get_ip_geolocation', array($this, 'ajax_get_ip_geolocation'));
    }

    /**
     * AJAX get IP geolocation
     */
    public function ajax_get_ip_geolocation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Get IP
        $ip = isset($_POST['ip']) ? sanitize_text_field($_POST['ip']) : '';
        
        if (empty($ip)) {
            wp_send_json_error(array('message' => __('IP address is required.', 'wp-activity-logger-pro')));
        }
        
        // Get geolocation data
        $geo_data = $this->get_ip_geolocation($ip);
        
        if (is_wp_error($geo_data)) {
            wp_send_json_error(array('message' => $geo_data->get_error_message()));
        }
        
        wp_send_json_success($geo_data);
    }
    
    /**
     * Get IP geolocation
     */
    public function get_ip_geolocation($ip) {
        // Check if IP is valid
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return array(
                'country' => __('Local', 'wp-activity-logger-pro'),
                'country_code' => 'LO',
                'city' => __('Local', 'wp-activity-logger-pro'),
                'region' => __('Local', 'wp-activity-logger-pro'),
                'continent' => __('Local', 'wp-activity-logger-pro'),
                'latitude' => 0,
                'longitude' => 0,
                'isp' => __('Local', 'wp-activity-logger-pro'),
                'timezone' => __('Local', 'wp-activity-logger-pro')
            );
        }
        
        // Try to get from cache
        $cache_key = 'tracepilot_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Call geolocation API
        $response = wp_remote_get('http://ip-api.com/json/' . $ip . '?fields=status,message,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,isp,org,as,continent,continentCode');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data) || isset($data['status']) && $data['status'] === 'fail') {
            return new WP_Error('geolocation_error', isset($data['message']) ? $data['message'] : __('Failed to get geolocation data.', 'wp-activity-logger-pro'));
        }
        
        // Format data
        $geo_data = array(
            'country' => isset($data['country']) ? $data['country'] : __('Unknown', 'wp-activity-logger-pro'),
            'country_code' => isset($data['countryCode']) ? $data['countryCode'] : 'XX',
            'city' => isset($data['city']) ? $data['city'] : __('Unknown', 'wp-activity-logger-pro'),
            'region' => isset($data['regionName']) ? $data['regionName'] : __('Unknown', 'wp-activity-logger-pro'),
            'continent' => isset($data['continent']) ? $data['continent'] : __('Unknown', 'wp-activity-logger-pro'),
            'latitude' => isset($data['lat']) ? $data['lat'] : 0,
            'longitude' => isset($data['lon']) ? $data['lon'] : 0,
            'isp' => isset($data['isp']) ? $data['isp'] : __('Unknown', 'wp-activity-logger-pro'),
            'timezone' => isset($data['timezone']) ? $data['timezone'] : __('Unknown', 'wp-activity-logger-pro')
        );
        
        // Cache result for 1 week
        set_transient($cache_key, $geo_data, WEEK_IN_SECONDS);
        
        return $geo_data;
    }
}
