<?php
/**
 * WP Activity Logger Visual Analytics
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class WPAL_Visual_Analytics {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_wpal_get_analytics_data', array($this, 'ajax_get_analytics_data'));
    }

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wp-activity-logger-pro',
            __('Analytics', 'wp-activity-logger-pro'),
            __('Analytics', 'wp-activity-logger-pro'),
            'manage_options',
            'wp-activity-logger-pro-analytics',
            array($this, 'render_page')
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        include WPAL_PLUGIN_DIR . 'templates/analytics.php';
    }
    
    /**
     * AJAX get analytics data
     */
    public function ajax_get_analytics_data() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpal_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Get parameters
        $chart_type = isset($_POST['chart_type']) ? sanitize_text_field($_POST['chart_type']) : 'activity_over_time';
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30d';
        $group_by = isset($_POST['group_by']) ? sanitize_text_field($_POST['group_by']) : 'day';
        
        // Get data based on chart type
        switch ($chart_type) {
            case 'activity_over_time':
                $data = $this->get_activity_over_time($date_range, $group_by);
                break;
                
            case 'activity_by_user':
                $data = $this->get_activity_by_user($date_range);
                break;
                
            case 'activity_by_type':
                $data = $this->get_activity_by_type($date_range);
                break;
                
            case 'activity_heatmap':
                $data = $this->get_activity_heatmap($date_range);
                break;
                
            case 'severity_distribution':
                $data = $this->get_severity_distribution($date_range);
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid chart type.', 'wp-activity-logger-pro')));
                break;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get activity over time
     */
    private function get_activity_over_time($date_range, $group_by) {
        global $wpdb;
        WPAL_Helpers::init();
        $table_name = WPAL_Helpers::$db_table;
        
        // Calculate date range
        $dates = $this->calculate_date_range($date_range);
        $start_date = $dates['start_date'];
        $end_date = $dates['end_date'];
        
        // Determine group by SQL
        switch ($group_by) {
            case 'hour':
                $group_sql = "DATE_FORMAT(time, '%Y-%m-%d %H:00:00')";
                $format = 'Y-m-d H:i:s';
                break;
                
            case 'day':
                $group_sql = "DATE(time)";
                $format = 'Y-m-d';
                break;
                
            case 'week':
                $group_sql = "DATE(DATE_SUB(time, INTERVAL WEEKDAY(time) DAY))";
                $format = 'Y-m-d';
                break;
                
            case 'month':
                $group_sql = "DATE_FORMAT(time, '%Y-%m-01')";
                $format = 'Y-m-d';
                break;
                
            default:
                $group_sql = "DATE(time)";
                $format = 'Y-m-d';
                break;
        }
        
        // Get activity data
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                $group_sql as date,
                COUNT(*) as count
            FROM $table_name
            WHERE time BETWEEN %s AND %s
            GROUP BY date
            ORDER BY date ASC
        ", $start_date, $end_date));
        
        // Format data for chart
        $data = array();
        $labels = array();
        $values = array();
        
        // Create date range
        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            new DateTime($end_date)
        );
        
        // Initialize data with zeros
        foreach ($period as $date) {
            $date_str = $date->format($format);
            $data[$date_str] = 0;
        }
        
        // Fill in actual data
        foreach ($results as $row) {
            $data[$row->date] = (int) $row->count;
        }
        
        // Format for chart.js
        foreach ($data as $date => $count) {
            $labels[] = $date;
            $values[] = $count;
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Activity Count', 'wp-activity-logger-pro'),
                    'data' => $values,
                    'backgroundColor' => 'rgba(34, 113, 177, 0.2)',
                    'borderColor' => 'rgba(34, 113, 177, 1)',
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * Get activity by user
     */
    private function get_activity_by_user($date_range) {
        global $wpdb;
        WPAL_Helpers::init();
        $table_name = WPAL_Helpers::$db_table;
        
        // Calculate date range
        $dates = $this->calculate_date_range($date_range);
        $start_date = $dates['start_date'];
        $end_date = $dates['end_date'];
        
        // Get activity data
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                username,
                COUNT(*) as count
            FROM $table_name
            WHERE time BETWEEN %s AND %s
            GROUP BY username
            ORDER BY count DESC
            LIMIT 10
        ", $start_date, $end_date));
        
        // Format data for chart
        $labels = array();
        $values = array();
        $colors = array();
        
        // Generate colors
        $base_colors = array(
            'rgba(34, 113, 177, 0.7)',
            'rgba(0, 163, 42, 0.7)',
            'rgba(240, 184, 73, 0.7)',
            'rgba(214, 54, 56, 0.7)',
            'rgba(156, 39, 176, 0.7)',
            'rgba(0, 188, 212, 0.7)',
            'rgba(255, 152, 0, 0.7)',
            'rgba(76, 175, 80, 0.7)',
            'rgba(121, 85, 72, 0.7)',
            'rgba(158, 158, 158, 0.7)'
        );
        
        foreach ($results as $index => $row) {
            $labels[] = $row->username;
            $values[] = (int) $row->count;
            $colors[] = $base_colors[$index % count($base_colors)];
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Activity Count', 'wp-activity-logger-pro'),
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => 'rgba(255, 255, 255, 0.5)',
                    'borderWidth' => 1
                )
            ),
            'insights' => $this->generate_user_insights($results)
        );
    }
    
    /**
     * Get activity by type
     */
    private function get_activity_by_type($date_range) {
        global $wpdb;
        WPAL_Helpers::init();
        $table_name = WPAL_Helpers::$db_table;
        
        // Calculate date range
        $dates = $this->calculate_date_range($date_range);
        $start_date = $dates['start_date'];
        $end_date = $dates['end_date'];
        
        // Get activity data
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                action,
                COUNT(*) as count
            FROM $table_name
            WHERE time BETWEEN %s AND %s
            GROUP BY action
            ORDER BY count DESC
            LIMIT 10
        ", $start_date, $end_date));
        
        // Format data for chart
        $labels = array();
        $values = array();
        $colors = array();
        
        // Generate colors
        $base_colors = array(
            'rgba(34, 113, 177, 0.7)',
            'rgba(0, 163, 42, 0.7)',
            'rgba(240, 184, 73, 0.7)',
            'rgba(214, 54, 56, 0.7)',
            'rgba(156, 39, 176, 0.7)',
            'rgba(0, 188, 212, 0.7)',
            'rgba(255, 152, 0, 0.7)',
            'rgba(76, 175, 80, 0.7)',
            'rgba(121, 85, 72, 0.7)',
            'rgba(158, 158, 158, 0.7)'
        );
        
        foreach ($results as $index => $row) {
            $labels[] = $row->action;
            $values[] = (int) $row->count;
            $colors[] = $base_colors[$index % count($base_colors)];
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Activity Count', 'wp-activity-logger-pro'),
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => 'rgba(255, 255, 255, 0.5)',
                    'borderWidth' => 1
                )
            ),
            'insights' => $this->generate_action_insights($results)
        );
    }
    
    /**
     * Get activity heatmap
     */
    private function get_activity_heatmap($date_range) {
        global $wpdb;
        WPAL_Helpers::init();
        $table_name = WPAL_Helpers::$db_table;
        
        // Calculate date range
        $dates = $this->calculate_date_range($date_range);
        $start_date = $dates['start_date'];
        $end_date = $dates['end_date'];
        
        // Get activity data by hour and day of week
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DAYNAME(time) as day_name,
                HOUR(time) as hour,
                COUNT(*) as count
            FROM $table_name
            WHERE time BETWEEN %s AND %s
            GROUP BY day_name, hour
            ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), hour
        ", $start_date, $end_date));
        
        // Format data for heatmap
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $hours = range(0, 23);
        
        $data = array();
        foreach ($days as $day) {
            foreach ($hours as $hour) {
                $data[$day . '-' . sprintf('%02d', $hour)] = 0;
            }
        }
        
        $max_count = 0;
        foreach ($results as $row) {
            $key = $row->day_name . '-' . sprintf('%02d', $row->hour);
            $data[$key] = (int) $row->count;
            if ($row->count > $max_count) {
                $max_count = $row->count;
            }
        }
        
        // Format for chart.js
        $labels = array_keys($data);
        $values = array_values($data);
        
        // Generate color scale
        $colors = array();
        foreach ($values as $value) {
            $intensity = $max_count > 0 ? $value / $max_count : 0;
            $colors[] = 'rgba(34, 113, 177, ' . $intensity . ')';
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Activity Count', 'wp-activity-logger-pro'),
                    'data' => $values,
                    'backgroundColor' => $colors
                )
            ),
            'insights' => $this->generate_heatmap_insights($results)
        );
    }
    
    /**
     * Get severity distribution
     */
    private function get_severity_distribution($date_range) {
        global $wpdb;
        WPAL_Helpers::init();
        $table_name = WPAL_Helpers::$db_table;
        
        // Calculate date range
        $dates = $this->calculate_date_range($date_range);
        $start_date = $dates['start_date'];
        $end_date = $dates['end_date'];
        
        // Get activity data
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                severity,
                COUNT(*) as count
            FROM $table_name
            WHERE time BETWEEN %s AND %s
            GROUP BY severity
            ORDER BY FIELD(severity, 'error', 'warning', 'info')
        ", $start_date, $end_date));
        
        // Format data for chart
        $labels = array();
        $values = array();
        $colors = array(
            'error' => 'rgba(214, 54, 56, 0.7)',
            'warning' => 'rgba(240, 184, 73, 0.7)',
            'info' => 'rgba(34, 113, 177, 0.7)'
        );
        $background_colors = array();
        
        foreach ($results as $row) {
            $severity = $row->severity ? $row->severity : 'info';
            $labels[] = ucfirst($severity);
            $values[] = (int) $row->count;
            $background_colors[] = isset($colors[$severity]) ? $colors[$severity] : $colors['info'];
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Activity Count', 'wp-activity-logger-pro'),
                    'data' => $values,
                    'backgroundColor' => $background_colors,
                    'borderColor' => 'rgba(255, 255, 255, 0.5)',
                    'borderWidth' => 1
                )
            ),
            'insights' => $this->generate_severity_insights($results)
        );
    }
    
    /**
     * Calculate date range
     */
    private function calculate_date_range($date_range) {
        $end_date = date('Y-m-d 23:59:59');
        
        switch ($date_range) {
            case '7d':
                $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
                
            case '30d':
                $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
                
            case '90d':
                $start_date = date('Y-m-d 00:00:00', strtotime('-90 days'));
                break;
                
            case '1y':
                $start_date = date('Y-m-d 00:00:00', strtotime('-1 year'));
                break;
                
            case 'all':
                $start_date = date('Y-m-d 00:00:00', strtotime('-10 years'));
                break;
                
            default:
                $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
        }
        
        return array(
            'start_date' => $start_date,
            'end_date' => $end_date
        );
    }
    
    /**
     * Generate user insights
     */
    private function generate_user_insights($results) {
        if (empty($results)) {
            return __('No user activity data available for the selected period.', 'wp-activity-logger-pro');
        }
        
        $total_activities = 0;
        foreach ($results as $row) {
            $total_activities += $row->count;
        }
        
        $top_user = $results[0]->username;
        $top_user_count = $results[0]->count;
        $top_user_percentage = round(($top_user_count / $total_activities) * 100);
        
        $insights = sprintf(
            __('The most active user is <strong>%s</strong> with %d activities (%d%% of total). ', 'wp-activity-logger-pro'),
            $top_user,
            $top_user_count,
            $top_user_percentage
        );
        
        if (count($results) > 1) {
            $second_user = $results[1]->username;
            $second_user_count = $results[1]->count;
            
            $insights .= sprintf(
                __('Followed by <strong>%s</strong> with %d activities. ', 'wp-activity-logger-pro'),
                $second_user,
                $second_user_count
            );
        }
        
        $insights .= sprintf(
            __('The top 10 users account for %d activities in the selected period.', 'wp-activity-logger-pro'),
            $total_activities
        );
        
        return $insights;
    }
    
    /**
     * Generate action insights
     */
    private function generate_action_insights($results) {
        if (empty($results)) {
            return __('No activity data available for the selected period.', 'wp-activity-logger-pro');
        }
        
        $total_activities = 0;
        foreach ($results as $row) {
            $total_activities += $row->count;
        }
        
        $top_action = $results[0]->action;
        $top_action_count = $results[0]->count;
        $top_action_percentage = round(($top_action_count / $total_activities) * 100);
        
        $insights = sprintf(
            __('The most common activity is <strong>%s</strong> with %d occurrences (%d%% of total). ', 'wp-activity-logger-pro'),
            $top_action,
            $top_action_count,
            $top_action_percentage
        );
        
        if (count($results) > 1) {
            $second_action = $results[1]->action;
            $second_action_count = $results[1]->count;
            
            $insights .= sprintf(
                __('Followed by <strong>%s</strong> with %d occurrences. ', 'wp-activity-logger-pro'),
                $second_action,
                $second_action_count
            );
        }
        
        $insights .= sprintf(
            __('The top 10 activity types account for %d activities in the selected period.', 'wp-activity-logger-pro'),
            $total_activities
        );
        
        return $insights;
    }
    
    /**
     * Generate heatmap insights
     */
    private function generate_heatmap_insights($results) {
        if (empty($results)) {
            return __('No activity data available for the selected period.', 'wp-activity-logger-pro');
        }
        
        // Find peak day and hour
        $peak_day = '';
        $peak_hour = 0;
        $peak_count = 0;
        
        $day_totals = array();
        $hour_totals = array();
        
        foreach ($results as $row) {
            // Update peak
            if ($row->count > $peak_count) {
                $peak_day = $row->day_name;
                $peak_hour = $row->hour;
                $peak_count = $row->count;
            }
            
            // Update day totals
            if (!isset($day_totals[$row->day_name])) {
                $day_totals[$row->day_name] = 0;
            }
            $day_totals[$row->day_name] += $row->count;
            
            // Update hour totals
            if (!isset($hour_totals[$row->hour])) {
                $hour_totals[$row->hour] = 0;
            }
            $hour_totals[$row->hour] += $row->count;
        }
        
        // Find busiest day
        $busiest_day = '';
        $busiest_day_count = 0;
        foreach ($day_totals as $day => $count) {
            if ($count > $busiest_day_count) {
                $busiest_day = $day;
                $busiest_day_count = $count;
            }
        }
        
        // Find busiest hour
        $busiest_hour = 0;
        $busiest_hour_count = 0;
        foreach ($hour_totals as $hour => $count) {
            if ($count > $busiest_hour_count) {
                $busiest_hour = $hour;
                $busiest_hour_count = $count;
            }
        }
        
        $insights = sprintf(
            __('Peak activity occurs on <strong>%s</strong> at <strong>%02d:00</strong> with %d activities. ', 'wp-activity-logger-pro'),
            $peak_day,
            $peak_hour,
            $peak_count
        );
        
        $insights .= sprintf(
            __('The busiest day is <strong>%s</strong> with %d total activities. ', 'wp-activity-logger-pro'),
            $busiest_day,
            $busiest_day_count
        );
        
        $insights .= sprintf(
            __('The busiest hour is <strong>%02d:00</strong> with %d total activities across all days.', 'wp-activity-logger-pro'),
            $busiest_hour,
            $busiest_hour_count
        );
        
        return $insights;
    }
    
    /**
     * Generate severity insights
     */
    private function generate_severity_insights($results) {
        if (empty($results)) {
            return __('No activity data available for the selected period.', 'wp-activity-logger-pro');
        }
        
        $total = 0;
        $severity_counts = array(
            'error' => 0,
            'warning' => 0,
            'info' => 0
        );
        
        foreach ($results as $row) {
            $severity = $row->severity ? $row->severity : 'info';
            $severity_counts[$severity] = $row->count;
            $total += $row->count;
        }
        
        $error_percentage = $total > 0 ? round(($severity_counts['error'] / $total) * 100) : 0;
        $warning_percentage = $total > 0 ? round(($severity_counts['warning'] / $total) * 100) : 0;
        $info_percentage = $total > 0 ? round(($severity_counts['info'] / $total) * 100) : 0;
        
        $insights = sprintf(
            __('Of the total %d activities, <strong>%d%%</strong> are informational, <strong>%d%%</strong> are warnings, and <strong>%d%%</strong> are errors. ', 'wp-activity-logger-pro'),
            $total,
            $info_percentage,
            $warning_percentage,
            $error_percentage
        );
        
        if ($error_percentage > 10) {
            $insights .= __('The high percentage of errors may indicate issues that need attention.', 'wp-activity-logger-pro');
        } else if ($warning_percentage > 20) {
            $insights .= __('The moderate level of warnings suggests some areas may need monitoring.', 'wp-activity-logger-pro');
        } else {
            $insights .= __('The low percentage of errors and warnings indicates a healthy system.', 'wp-activity-logger-pro');
        }
        
        return $insights;
    }
}
