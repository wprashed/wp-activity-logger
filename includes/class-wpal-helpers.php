<?php
/**
 * Helper functions for WP Activity Logger Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WPAL_Helpers {
    public static $db_table;
    
    /**
     * Initialize the class
     */
    public static function init() {
        global $wpdb;
        self::$db_table = $wpdb->prefix . 'wpal_logs';
    }
    
    /**
     * Create the database table
     */
    public static function create_db_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE " . self::$db_table . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            time datetime NOT NULL,
            user_id bigint(20) NOT NULL,
            username varchar(60) NOT NULL,
            user_role varchar(255) NOT NULL,
            action text NOT NULL,
            ip varchar(45) NOT NULL,
            browser varchar(255) NOT NULL,
            severity varchar(20) NOT NULL DEFAULT 'info',
            context text,
            PRIMARY KEY  (id),
            KEY time (time),
            KEY user_id (user_id),
            KEY severity (severity)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check and fix database if needed
     */
    public static function check_and_fix_database() {
        self::init();
        
        global $wpdb;
        $table_name = self::$db_table;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            self::create_db_table();
            
            // Add some initial data so dashboard doesn't show empty
            $current_user = wp_get_current_user();
            $entry = [
                'time' => current_time('mysql'),
                'user_id' => $current_user->ID,
                'username' => $current_user->user_login,
                'user_role' => implode(', ', $current_user->roles),
                'action' => 'Database table created',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'browser' => self::get_browser_name(),
                'severity' => 'info',
                'context' => json_encode(['automatic' => true]),
            ];
            
            $wpdb->insert(
                self::$db_table,
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
        }
        
        return $table_exists;
    }
    
    /**
     * Clean up old logs
     */
    public static function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        // Delete from database
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $wpdb->query($wpdb->prepare("DELETE FROM " . self::$db_table . " WHERE time < %s", $date));
        
        // Delete from CSV file
        $csv_file = WPAL_PATH . 'logs/activity.csv';
        if (file_exists($csv_file)) {
            // Read the CSV file
            $lines = file($csv_file);
            
            if ($lines) {
                // Keep the header
                $header = $lines[0];
                $new_lines = [$header];
                
                // Filter out old entries
                $cutoff_timestamp = strtotime("-$days days");
                
                for ($i = 1; $i < count($lines); $i++) {
                    $line = $lines[$i];
                    $fields = str_getcsv($line);
                    
                    if (count($fields) > 0) {
                        $time = strtotime($fields[0]);
                        
                        if ($time > $cutoff_timestamp) {
                            $new_lines[] = $line;
                        }
                    }
                }
                
                // Write the filtered lines back to the file
                file_put_contents($csv_file, implode('', $new_lines));
            }
        }
    }
    
    /**
     * Get browser name from user agent
     */
    public static function get_browser_name() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        
        if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) {
            return 'Opera';
        } elseif (strpos($user_agent, 'Edge')) {
            return 'Edge';
        } elseif (strpos($user_agent, 'Chrome')) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'Safari')) {
            return 'Safari';
        } elseif (strpos($user_agent, 'Firefox')) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
            return 'Internet Explorer';
        }
        
        return 'Unknown';
    }
    
    /**
     * Get user's IP address
     */
    public static function get_ip_address() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Get user role(s)
     */
    public static function get_user_role($user_id) {
        $user = get_userdata($user_id);
        
        if ($user && !empty($user->roles)) {
            return implode(', ', $user->roles);
        }
        
        return 'Guest';
    }
    
    /**
     * Format date and time
     */
    public static function format_datetime($datetime, $format = 'Y-m-d H:i:s') {
        return date_i18n($format, strtotime($datetime));
    }
    
    /**
     * Get severity badge HTML
     */
    public static function get_severity_badge($severity) {
        $class = 'bg-success';
        
        if ($severity === 'warning') {
            $class = 'bg-warning';
        } elseif ($severity === 'error') {
            $class = 'bg-danger';
        }
        
        return '<span class="badge ' . $class . '">' . strtoupper($severity) . '</span>';
    }
    
    /**
     * Get pagination HTML
     */
    public static function get_pagination($total_items, $per_page, $current_page, $base_url) {
        $total_pages = ceil($total_items / $per_page);
        
        if ($total_pages <= 1) {
            return '';
        }
        
        $html = '<div class="pagination">';
        
        // Previous button
        if ($current_page > 1) {
            $html .= '<a href="' . add_query_arg('paged', ($current_page - 1), $base_url) . '" class="button">&laquo; ' . __('Previous', 'wp-activity-logger-pro') . '</a>';
        } else {
            $html .= '<span class="button disabled">&laquo; ' . __('Previous', 'wp-activity-logger-pro') . '</span>';
        }
        
        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            $html .= '<a href="' . add_query_arg('paged', 1, $base_url) . '" class="button">1</a>';
            
            if ($start_page > 2) {
                $html .= '<span class="button disabled">...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                $html .= '<span class="button button-primary">' . $i . '</span>';
            } else {
                $html .= '<a href="' . add_query_arg('paged', $i, $base_url) . '" class="button">' . $i . '</a>';
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $html .= '<span class="button disabled">...</span>';
            }
            
            $html .= '<a href="' . add_query_arg('paged', $total_pages, $base_url) . '" class="button">' . $total_pages . '</a>';
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $html .= '<a href="' . add_query_arg('paged', ($current_page + 1), $base_url) . '" class="button">' . __('Next', 'wp-activity-logger-pro') . ' &raquo;</a>';
        } else {
            $html .= '<span class="button disabled">' . __('Next', 'wp-activity-logger-pro') . ' &raquo;</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Sanitize and validate data
     */
    public static function sanitize_data($data, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            
            case 'url':
                return esc_url_raw($data);
            
            case 'int':
                return intval($data);
            
            case 'float':
                return floatval($data);
            
            case 'bool':
                return (bool) $data;
            
            case 'array':
                return is_array($data) ? array_map('sanitize_text_field', $data) : [];
            
            case 'html':
                return wp_kses_post($data);
            
            case 'json':
                return json_encode(json_decode($data));
            
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }
}