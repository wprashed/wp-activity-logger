<?php
/**
 * WP Activity Logger Server Recommendations
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class TracePilot_Server_Recommendations {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_tracepilot_analyze_server_needs', array($this, 'ajax_analyze_server_needs'));
    }

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wp-activity-logger-pro',
            __('Server Recommendations', 'wp-activity-logger-pro'),
            __('Server Recommendations', 'wp-activity-logger-pro'),
            TracePilot_Helpers::get_admin_capability(),
            'wp-activity-logger-pro-server-recommendations',
            array($this, 'render_page')
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        include TracePilot_PLUGIN_DIR . 'templates/server-recommendations.php';
    }

    /**
     * Analyze server needs based on logs
     */
    public function ajax_analyze_server_needs() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }

        // Get log data for analysis
        $analysis_data = $this->analyze_server_needs();
        
        wp_send_json_success($analysis_data);
    }

    /**
     * Analyze server needs based on logs
     */
    public function analyze_server_needs() {
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        
        // Get total logs count
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Get logs per day (average)
        $logs_per_day = $wpdb->get_var("
            SELECT COUNT(*) / COUNT(DISTINCT DATE(time)) 
            FROM $table_name 
            WHERE time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Get peak logs per hour
        $peak_logs_per_hour = $wpdb->get_var("
            SELECT COUNT(*) as count
            FROM $table_name
            WHERE time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(time), HOUR(time)
            ORDER BY count DESC
            LIMIT 1
        ");
        
        // Get database size
        $db_size = $wpdb->get_var("
            SELECT SUM(data_length + index_length) 
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE() 
            AND table_name = '$table_name'
        ");
        
        // Get average log size
        $avg_log_size = $total_logs > 0 ? $db_size / $total_logs : 0;
        
        // Get site stats
        $total_posts = wp_count_posts()->publish;
        $total_pages = wp_count_posts('page')->publish;
        $total_users = count_users();
        $total_comments = wp_count_comments()->approved;
        
        // Calculate recommendations
        $recommendations = $this->calculate_recommendations(
            $total_logs,
            $logs_per_day,
            $peak_logs_per_hour,
            $db_size,
            $avg_log_size,
            $total_posts,
            $total_pages,
            $total_users['total_users'],
            $total_comments
        );
        
        return array(
            'stats' => array(
                'total_logs' => $total_logs,
                'logs_per_day' => round($logs_per_day),
                'peak_logs_per_hour' => $peak_logs_per_hour,
                'db_size' => size_format($db_size),
                'avg_log_size' => size_format($avg_log_size),
                'total_posts' => $total_posts,
                'total_pages' => $total_pages,
                'total_users' => $total_users['total_users'],
                'total_comments' => $total_comments,
            ),
            'recommendations' => $recommendations,
            'current_server' => $this->get_current_server_info()
        );
    }
    
    /**
     * Calculate server recommendations
     */
    private function calculate_recommendations($total_logs, $logs_per_day, $peak_logs_per_hour, $db_size, $avg_log_size, $total_posts, $total_pages, $total_users, $total_comments) {
        // Base calculations on activity volume and site size
        
        // Storage recommendation (in GB)
        $projected_monthly_db_growth = ($avg_log_size * $logs_per_day * 30) / (1024 * 1024 * 1024);
        $storage_recommendation = max(5, ceil($db_size / (1024 * 1024 * 1024) * 3 + $projected_monthly_db_growth * 6));
        
        // RAM recommendation (in GB)
        $base_ram = 2;
        $ram_factor = 0;
        
        if ($total_users > 1000) $ram_factor += 2;
        else if ($total_users > 100) $ram_factor += 1;
        
        if ($peak_logs_per_hour > 1000) $ram_factor += 4;
        else if ($peak_logs_per_hour > 100) $ram_factor += 2;
        else if ($peak_logs_per_hour > 10) $ram_factor += 1;
        
        $ram_recommendation = $base_ram + $ram_factor;
        
        // CPU cores recommendation
        $base_cpu = 2;
        $cpu_factor = 0;
        
        if ($peak_logs_per_hour > 1000) $cpu_factor += 6;
        else if ($peak_logs_per_hour > 100) $cpu_factor += 2;
        else if ($peak_logs_per_hour > 10) $cpu_factor += 1;
        
        $cpu_recommendation = $base_cpu + $cpu_factor;
        
        // Bandwidth recommendation (in GB/month)
        $avg_page_size = 2; // MB
        $estimated_monthly_pagetemplates = $logs_per_day * 30 * 2; // Assuming each logged action represents ~0.5 pagetemplates
        $bandwidth_recommendation = ceil(($estimated_monthly_pagetemplates * $avg_page_size) / 1024);
        
        // Hosting type recommendation
        $hosting_type = 'Shared Hosting';
        
        if ($total_users > 10000 || $peak_logs_per_hour > 1000 || $total_posts > 10000) {
            $hosting_type = 'Dedicated Server';
        } else if ($total_users > 1000 || $peak_logs_per_hour > 100 || $total_posts > 1000) {
            $hosting_type = 'VPS/Cloud Hosting';
        } else if ($total_users > 100 || $peak_logs_per_hour > 10 || $total_posts > 100) {
            $hosting_type = 'Managed WordPress Hosting';
        }
        
        return array(
            'storage' => $storage_recommendation,
            'ram' => $ram_recommendation,
            'cpu' => $cpu_recommendation,
            'bandwidth' => $bandwidth_recommendation,
            'hosting_type' => $hosting_type,
            'explanation' => $this->generate_recommendation_explanation(
                $storage_recommendation,
                $ram_recommendation,
                $cpu_recommendation,
                $bandwidth_recommendation,
                $hosting_type,
                $logs_per_day,
                $peak_logs_per_hour,
                $total_users,
                $total_posts
            )
        );
    }
    
    /**
     * Generate recommendation explanation
     */
    private function generate_recommendation_explanation($storage, $ram, $cpu, $bandwidth, $hosting_type, $logs_per_day, $peak_logs_per_hour, $total_users, $total_posts) {
        $explanation = sprintf(
            __('Based on your site activity of approximately %d logs per day (with peaks of %d logs per hour), %d users, and %d posts, we recommend the following server configuration:', 'wp-activity-logger-pro'),
            $logs_per_day,
            $peak_logs_per_hour,
            $total_users,
            $total_posts
        );
        
        $explanation .= "\n\n";
        $explanation .= sprintf(__('Storage: %d GB', 'wp-activity-logger-pro'), $storage) . "\n";
        $explanation .= sprintf(__('RAM: %d GB', 'wp-activity-logger-pro'), $ram) . "\n";
        $explanation .= sprintf(__('CPU: %d cores', 'wp-activity-logger-pro'), $cpu) . "\n";
        $explanation .= sprintf(__('Bandwidth: %d GB/month', 'wp-activity-logger-pro'), $bandwidth) . "\n";
        $explanation .= sprintf(__('Recommended Hosting Type: %s', 'wp-activity-logger-pro'), $hosting_type) . "\n\n";
        
        // Add specific explanations based on hosting type
        switch ($hosting_type) {
            case 'Dedicated Server':
                $explanation .= __('Your site shows high activity levels that would benefit from a dedicated server. This ensures consistent performance and the ability to handle traffic spikes without degradation.', 'wp-activity-logger-pro');
                break;
                
            case 'VPS/Cloud Hosting':
                $explanation .= __('Your site has moderate to high activity that would be best served by a VPS or cloud hosting solution. This provides better resource allocation than shared hosting while maintaining cost efficiency.', 'wp-activity-logger-pro');
                break;
                
            case 'Managed WordPress Hosting':
                $explanation .= __('Your site would benefit from specialized WordPress hosting that optimizes performance and provides WordPress-specific security and caching features.', 'wp-activity-logger-pro');
                break;
                
            default:
                $explanation .= __('Your site has relatively low activity levels and could be adequately served by quality shared hosting. However, as your site grows, consider upgrading to managed WordPress hosting.', 'wp-activity-logger-pro');
                break;
        }
        
        return $explanation;
    }
    
    /**
     * Get current server info
     */
    private function get_current_server_info() {
        // Get PHP memory limit
        $memory_limit = ini_get('memory_limit');
        
        // Get server software
        $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
        
        // Get PHP version
        $php_version = phpversion();
        
        // Get MySQL version
        global $wpdb;
        $mysql_version = $wpdb->db_version();
        
        // Get max execution time
        $max_execution_time = ini_get('max_execution_time');
        
        // Get post max size
        $post_max_size = ini_get('post_max_size');
        
        // Get upload max filesize
        $upload_max_filesize = ini_get('upload_max_filesize');
        
        return array(
            'memory_limit' => $memory_limit,
            'server_software' => $server_software,
            'php_version' => $php_version,
            'mysql_version' => $mysql_version,
            'max_execution_time' => $max_execution_time,
            'post_max_size' => $post_max_size,
            'upload_max_filesize' => $upload_max_filesize
        );
    }
}
