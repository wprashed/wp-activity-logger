<?php
/**
 * WP Activity Logger Threat Detection
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class TracePilot_Threat_Detection {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_tracepilot_analyze_threats', array($this, 'ajax_analyze_threats'));
        add_action('tracepilot_after_log_activity', array($this, 'analyze_log_for_threats'), 10, 5);
        add_action('tracepilot_daily_cron', array($this, 'scheduled_threat_analysis'));
    }

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wp-activity-logger-pro',
            __('Threat Detection', 'wp-activity-logger-pro'),
            __('Threat Detection', 'wp-activity-logger-pro'),
            TracePilot_Helpers::get_admin_capability(),
            'wp-activity-logger-pro-threat-detection',
            array($this, 'render_page')
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        include TracePilot_PLUGIN_DIR . 'templates/tracepilot-threat-detection.php';
    }
    
    /**
     * AJAX analyze threats
     */
    public function ajax_analyze_threats() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Run threat analysis
        $threats = $this->run_threat_analysis();
        
        wp_send_json_success(array(
            'threats' => $threats,
            'summary' => $this->get_threat_summary($threats)
        ));
    }
    
    /**
     * Run threat analysis
     */
    public function run_threat_analysis() {
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        
        $threats = array();
        
        // Detect multiple failed login attempts
        $failed_logins = $wpdb->get_results("
            SELECT 
                ip, 
                COUNT(*) as count, 
                MIN(time) as first_attempt,
                MAX(time) as last_attempt
            FROM $table_name
            WHERE action = 'login_failed'
            AND time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY ip
            HAVING count >= 5
            ORDER BY count DESC
        ");
        
        foreach ($failed_logins as $login) {
            $threats[] = array(
                'type' => 'failed_login',
                'severity' => ($login->count >= 10) ? 'high' : 'medium',
                'ip' => $login->ip,
                'count' => $login->count,
                'first_attempt' => $login->first_attempt,
                'last_attempt' => $login->last_attempt,
                'description' => sprintf(
                    __('Multiple failed login attempts (%d) from IP %s', 'wp-activity-logger-pro'),
                    $login->count,
                    $login->ip
                )
            );
        }
        
        // Detect unusual login times
        $user_login_times = $wpdb->get_results("
            SELECT 
                user_id,
                username,
                TIME(time) as login_time
            FROM $table_name
            WHERE action = 'user_login'
            AND user_id > 0
            AND time > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY user_id, time
        ");
        
        $user_login_patterns = array();
        foreach ($user_login_times as $login) {
            if (!isset($user_login_patterns[$login->user_id])) {
                $user_login_patterns[$login->user_id] = array(
                    'username' => $login->username,
                    'times' => array()
                );
            }
            
            $hour = date('H', strtotime($login->login_time));
            $user_login_patterns[$login->user_id]['times'][] = $hour;
        }
        
        // Check for unusual login times
        foreach ($user_login_patterns as $user_id => $pattern) {
            if (count($pattern['times']) < 5) {
                continue; // Not enough data
            }
            
            // Calculate average login hour and standard deviation
            $hours = array_map('intval', $pattern['times']);
            $avg_hour = array_sum($hours) / count($hours);
            
            $variance = 0;
            foreach ($hours as $hour) {
                $variance += pow($hour - $avg_hour, 2);
            }
            $std_dev = sqrt($variance / count($hours));
            
            // Get recent logins
            $recent_logins = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    time,
                    ip
                FROM $table_name
                WHERE action = 'user_login'
                AND user_id = %d
                AND time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY time DESC
            ", $user_id));
            
            foreach ($recent_logins as $login) {
                $login_hour = intval(date('H', strtotime($login->time)));
                
                // If login hour is more than 2 standard deviations from average, flag it
                if (abs($login_hour - $avg_hour) > ($std_dev * 2) && $std_dev > 1) {
                    $threats[] = array(
                        'type' => 'unusual_login_time',
                        'severity' => 'medium',
                        'user_id' => $user_id,
                        'username' => $pattern['username'],
                        'login_time' => $login->time,
                        'ip' => $login->ip,
                        'avg_hour' => round($avg_hour),
                        'login_hour' => $login_hour,
                        'description' => sprintf(
                            __('Unusual login time for user %s (User typically logs in around %02d:00, but logged in at %02d:00)', 'wp-activity-logger-pro'),
                            $pattern['username'],
                            round($avg_hour),
                            $login_hour
                        )
                    );
                }
            }
        }
        
        // Detect unusual geographic locations
        $user_locations = $wpdb->get_results("
            SELECT 
                user_id,
                username,
                ip
            FROM $table_name
            WHERE action = 'user_login'
            AND user_id > 0
            AND time > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY user_id, ip
            ORDER BY user_id, ip
        ");
        
        $user_ips = array();
        foreach ($user_locations as $location) {
            if (!isset($user_ips[$location->user_id])) {
                $user_ips[$location->user_id] = array(
                    'username' => $location->username,
                    'ips' => array()
                );
            }
            
            $user_ips[$location->user_id]['ips'][] = $location->ip;
        }
        
        // Get recent logins
        $recent_logins = $wpdb->get_results("
            SELECT 
                user_id,
                username,
                time,
                ip
            FROM $table_name
            WHERE action = 'user_login'
            AND user_id > 0
            AND time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY time DESC
        ");
        
        foreach ($recent_logins as $login) {
            if (isset($user_ips[$login->user_id]) && count($user_ips[$login->user_id]['ips']) >= 3) {
                $common_ips = $user_ips[$login->user_id]['ips'];
                
                // If this IP is not in the user's common IPs, flag it
                if (!in_array($login->ip, $common_ips)) {
                    // Get geolocation data
                    $geo_data = $this->get_ip_geolocation($login->ip);
                    $location = isset($geo_data['country']) ? $geo_data['country'] : __('Unknown', 'wp-activity-logger-pro');
                    
                    $threats[] = array(
                        'type' => 'unusual_location',
                        'severity' => 'high',
                        'user_id' => $login->user_id,
                        'username' => $login->username,
                        'login_time' => $login->time,
                        'ip' => $login->ip,
                        'location' => $location,
                        'description' => sprintf(
                            __('Login from unusual location for user %s (IP: %s, Location: %s)', 'wp-activity-logger-pro'),
                            $login->username,
                            $login->ip,
                            $location
                        )
                    );
                }
            }
        }
        
        // Detect suspicious file modifications
        $file_modifications = $wpdb->get_results("
            SELECT 
                id,
                user_id,
                username,
                time,
                ip,
                action,
                description,
                object_type,
                object_name
            FROM $table_name
            WHERE (
                action LIKE '%file_edit%' OR
                action LIKE '%plugin_edit%' OR
                action LIKE '%theme_edit%'
            )
            AND time > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY time DESC
        ");
        
        foreach ($file_modifications as $mod) {
            // Check if file is in core directories
            $suspicious = false;
            $file_path = '';
            
            if (!empty($mod->object_name)) {
                $file_path = $mod->object_name;
                
                // Check for suspicious file types or locations
                if (
                    preg_match('/\.(php|js)$/i', $file_path) &&
                    (
                        strpos($file_path, 'wp-includes') !== false ||
                        strpos($file_path, 'wp-admin') !== false ||
                        strpos($file_path, 'functions.php') !== false
                    )
                ) {
                    $suspicious = true;
                }
            }
            
            if ($suspicious) {
                $threats[] = array(
                    'type' => 'suspicious_file_modification',
                    'severity' => 'high',
                    'log_id' => $mod->id,
                    'user_id' => $mod->user_id,
                    'username' => $mod->username,
                    'time' => $mod->time,
                    'ip' => $mod->ip,
                    'file_path' => $file_path,
                    'description' => sprintf(
                        __('Suspicious file modification by user %s (File: %s)', 'wp-activity-logger-pro'),
                        $mod->username,
                        $file_path
                    )
                );
            }
        }

        $integrity_alerts = $wpdb->get_results("
            SELECT id, time, username, ip, object_name, action, description
            FROM $table_name
            WHERE action IN ('file_integrity_modified', 'file_integrity_deleted', 'file_integrity_new')
            AND time > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY time DESC
        ");

        foreach ($integrity_alerts as $alert) {
            $threats[] = array(
                'type' => 'file_integrity_alert',
                'severity' => 'high',
                'log_id' => $alert->id,
                'username' => $alert->username,
                'time' => $alert->time,
                'ip' => $alert->ip,
                'file_path' => $alert->object_name,
                'description' => $alert->description,
            );
        }
        
        // Detect privilege escalation
        $role_changes = $wpdb->get_results("
            SELECT 
                id,
                user_id,
                username,
                time,
                ip,
                action,
                description,
                object_type,
                object_id,
                object_name,
                context
            FROM $table_name
            WHERE action = 'user_role_changed'
            AND time > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY time DESC
        ");
        
        foreach ($role_changes as $change) {
            $context = !empty($change->context) ? json_decode($change->context, true) : array();
            
            if (
                isset($context['new_role']) && 
                (
                    $context['new_role'] === 'administrator' ||
                    $context['new_role'] === 'editor'
                )
            ) {
                $threats[] = array(
                    'type' => 'privilege_escalation',
                    'severity' => 'high',
                    'log_id' => $change->id,
                    'user_id' => $change->user_id,
                    'username' => $change->username,
                    'time' => $change->time,
                    'ip' => $change->ip,
                    'target_user' => $change->object_name,
                    'new_role' => $context['new_role'],
                    'description' => sprintf(
                        __('User %s changed role of %s to %s', 'wp-activity-logger-pro'),
                        $change->username,
                        $change->object_name,
                        $context['new_role']
                    )
                );
            }
        }
        
        return $threats;
    }
    
    /**
     * Get threat summary
     */
    private function get_threat_summary($threats) {
        $total = count($threats);
        $high = 0;
        $medium = 0;
        $low = 0;
        
        $types = array();
        
        foreach ($threats as $threat) {
            if ($threat['severity'] === 'high') {
                $high++;
            } else if ($threat['severity'] === 'medium') {
                $medium++;
            } else {
                $low++;
            }
            
            if (!isset($types[$threat['type']])) {
                $types[$threat['type']] = 0;
            }
            
            $types[$threat['type']]++;
        }
        
        return array(
            'total' => $total,
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
            'types' => $types
        );
    }
    
    /**
     * Analyze log for threats
     */
    public function analyze_log_for_threats($log_id, $action, $description, $severity, $args) {
        // Check if threat detection is enabled
        $options = get_option('wpal_options', array());
        if (empty($options['enable_threat_detection'])) {
            return;
        }
        
        // Analyze specific actions
        switch ($action) {
            case 'login_failed':
                $this->check_brute_force_attempts($args);
                break;
                
            case 'user_login':
                $this->check_unusual_login($log_id, $args);
                break;
                
            case 'file_edited':
            case 'plugin_edited':
            case 'theme_edited':
                $this->check_suspicious_file_modification($log_id, $action, $args);
                break;
                
            case 'user_role_changed':
                $this->check_privilege_escalation($log_id, $args);
                break;
        }
    }
    
    /**
     * Check for brute force attempts
     */
    private function check_brute_force_attempts($args) {
        if (empty($args['ip'])) {
            return;
        }
        
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        
        // Count failed login attempts from this IP in the last hour
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM $table_name
            WHERE action = 'login_failed'
            AND ip = %s
            AND time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ", $args['ip']));
        
        if ($count >= 10) {
            // Log threat
            $this->log_threat(
                'brute_force',
                'high',
                sprintf(__('Possible brute force attack detected from IP %s (%d failed login attempts in the last hour)', 'wp-activity-logger-pro'), $args['ip'], $count),
                array(
                    'ip' => $args['ip'],
                    'count' => $count
                )
            );
            
            // Send notification
            $this->send_threat_notification(
                'brute_force',
                sprintf(__('Possible brute force attack detected from IP %s', 'wp-activity-logger-pro'), $args['ip']),
                sprintf(__('%d failed login attempts in the last hour from IP %s', 'wp-activity-logger-pro'), $count, $args['ip'])
            );
        }
    }
    
    /**
     * Check for unusual login
     */
    private function check_unusual_login($log_id, $args) {
        if (empty($args['user_id']) || empty($args['ip'])) {
            return;
        }
        
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        
        // Get user's previous login IPs
        $previous_ips = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT ip
            FROM $table_name
            WHERE action = 'user_login'
            AND user_id = %d
            AND id != %d
            ORDER BY time DESC
            LIMIT 10
        ", $args['user_id'], $log_id));
        
        // If user has previous logins and this IP is not in the list
        if (!empty($previous_ips) && !in_array($args['ip'], $previous_ips)) {
            // Get geolocation data
            $geo_data = $this->get_ip_geolocation($args['ip']);
            $location = isset($geo_data['country']) ? $geo_data['country'] : __('Unknown', 'wp-activity-logger-pro');
            
            // Log threat
            $this->log_threat(
                'unusual_login_location',
                'medium',
                sprintf(__('Login from unusual location for user %s (IP: %s, Location: %s)', 'wp-activity-logger-pro'), $args['username'], $args['ip'], $location),
                array(
                    'user_id' => $args['user_id'],
                    'username' => $args['username'],
                    'ip' => $args['ip'],
                    'location' => $location
                )
            );
            
            // Send notification
            $this->send_threat_notification(
                'unusual_login_location',
                sprintf(__('Login from unusual location for user %s', 'wp-activity-logger-pro'), $args['username']),
                sprintf(__('User %s logged in from IP %s (Location: %s)', 'wp-activity-logger-pro'), $args['username'], $args['ip'], $location)
            );
        }
    }
    
    /**
     * Check for suspicious file modification
     */
    private function check_suspicious_file_modification($log_id, $action, $args) {
        if (empty($args['object_name'])) {
            return;
        }
        
        $file_path = $args['object_name'];
        
        // Check for suspicious file types or locations
        if (
            preg_match('/\.(php|js)$/i', $file_path) &&
            (
                strpos($file_path, 'wp-includes') !== false ||
                strpos($file_path, 'wp-admin') !== false ||
                strpos($file_path, 'functions.php') !== false
            )
        ) {
            // Log threat
            $this->log_threat(
                'suspicious_file_modification',
                'high',
                sprintf(__('Suspicious file modification by user %s (File: %s)', 'wp-activity-logger-pro'), $args['username'], $file_path),
                array(
                    'user_id' => $args['user_id'],
                    'username' => $args['username'],
                    'ip' => $args['ip'],
                    'file_path' => $file_path
                )
            );
            
            // Send notification
            $this->send_threat_notification(
                'suspicious_file_modification',
                sprintf(__('Suspicious file modification detected', 'wp-activity-logger-pro')),
                sprintf(__('User %s modified file %s', 'wp-activity-logger-pro'), $args['username'], $file_path)
            );
        }
    }
    
    /**
     * Check for privilege escalation
     */
    private function check_privilege_escalation($log_id, $args) {
        if (empty($args['context'])) {
            return;
        }
        
        $context = is_array($args['context']) ? $args['context'] : json_decode($args['context'], true);
        
        if (
            isset($context['new_role']) && 
            (
                $context['new_role'] === 'administrator' ||
                $context['new_role'] === 'editor'
            )
        ) {
            // Log threat
            $this->log_threat(
                'privilege_escalation',
                'high',
                sprintf(__('User %s changed role of %s to %s', 'wp-activity-logger-pro'), $args['username'], $args['object_name'], $context['new_role']),
                array(
                    'user_id' => $args['user_id'],
                    'username' => $args['username'],
                    'ip' => $args['ip'],
                    'target_user' => $args['object_name'],
                    'new_role' => $context['new_role']
                )
            );
            
            // Send notification
            $this->send_threat_notification(
                'privilege_escalation',
                sprintf(__('Privilege escalation detected', 'wp-activity-logger-pro')),
                sprintf(__('User %s changed role of %s to %s', 'wp-activity-logger-pro'), $args['username'], $args['object_name'], $context['new_role'])
            );
        }
    }
    
    /**
     * Log threat
     */
    private function log_threat($type, $severity, $description, $context = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpal_threats';
        
        $wpdb->insert(
            $table_name,
            array(
                'time' => current_time('mysql'),
                'type' => $type,
                'severity' => $severity,
                'description' => $description,
                'context' => json_encode($context),
                'status' => 'new'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Send threat notification
     */
    private function send_threat_notification($type, $subject, $message) {
        // Check if notifications are enabled
        $options = get_option('wpal_options', array());
        if (empty($options['enable_threat_notifications'])) {
            return;
        }

        if (function_exists('tracepilot_for_wordpress') && isset(tracepilot_for_wordpress()->notifications)) {
            tracepilot_for_wordpress()->notifications->send_custom_notification($type, $message, 'error', array('subject' => $subject));
        } else {
            wp_mail(get_option('admin_email'), $subject, $message);
        }
    }
    
    /**
     * Get IP geolocation
     */
    private function get_ip_geolocation($ip) {
        // Check if IP is valid
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return array('country' => __('Local', 'wp-activity-logger-pro'));
        }
        
        // Try to get from cache
        $cache_key = 'tracepilot_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Call geolocation API
        $response = wp_remote_get('http://ip-api.com/json/' . $ip);
        
        if (is_wp_error($response)) {
            return array('country' => __('Unknown', 'wp-activity-logger-pro'));
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data) || !isset($data['country'])) {
            return array('country' => __('Unknown', 'wp-activity-logger-pro'));
        }
        
        // Cache result for 1 week
        set_transient($cache_key, $data, WEEK_IN_SECONDS);
        
        return $data;
    }
    
    /**
     * Scheduled threat analysis
     */
    public function scheduled_threat_analysis() {
        // Check if threat detection is enabled
        $options = get_option('wpal_options', array());
        if (empty($options['enable_threat_detection'])) {
            return;
        }
        
        // Run threat analysis
        $threats = $this->run_threat_analysis();
        
        // Send summary notification if there are high severity threats
        $high_threats = array_filter($threats, function($threat) {
            return $threat['severity'] === 'high';
        });
        
        if (!empty($high_threats)) {
            $this->send_threat_summary_notification($threats);
        }
    }
    
    /**
     * Send threat summary notification
     */
    private function send_threat_summary_notification($threats) {
        // Check if notifications are enabled
        $options = get_option('wpal_options', array());
        if (empty($options['enable_threat_notifications'])) {
            return;
        }
        
        // Get notification email
        $email = !empty($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
        
        // Get threat summary
        $summary = $this->get_threat_summary($threats);
        
        // Send email
        $subject = sprintf(__('[%s] Security Alert: %d security threats detected', 'wp-activity-logger-pro'), get_bloginfo('name'), $summary['total']);
        
        $body = sprintf(__('Security threats have been detected on your WordPress site (%s).', 'wp-activity-logger-pro'), get_bloginfo('url')) . "\n\n";
        $body .= sprintf(__('Total Threats: %d', 'wp-activity-logger-pro'), $summary['total']) . "\n";
        $body .= sprintf(__('High Severity: %d', 'wp-activity-logger-pro'), $summary['high']) . "\n";
        $body .= sprintf(__('Medium Severity: %d', 'wp-activity-logger-pro'), $summary['medium']) . "\n";
        $body .= sprintf(__('Low Severity: %d', 'wp-activity-logger-pro'), $summary['low']) . "\n\n";
        
        $body .= __('Top Threats:', 'wp-activity-logger-pro') . "\n";
        
        // Add top 5 high severity threats
        $high_threats = array_filter($threats, function($threat) {
            return $threat['severity'] === 'high';
        });
        
        $count = 0;
        foreach ($high_threats as $threat) {
            $body .= '- ' . $threat['description'] . "\n";
            $count++;
            
            if ($count >= 5) {
                break;
            }
        }
        
        $body .= "\n" . sprintf(__('Please log in to your WordPress admin panel to investigate: %s', 'wp-activity-logger-pro'), admin_url('admin.php?page=wp-activity-logger-pro-threat-detection'));
        
        wp_mail($email, $subject, $body);
    }
}
