<?php
/**
 * WP Activity Logger Archive
 *
 * @package WP Activity Logger
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class TracePilot_Archive {
    /**
     * Archive table name
     */
    public static $archive_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_submenu_page'), 15);
        add_action('network_admin_menu', array($this, 'add_submenu_page'), 15);
        add_action('wp_ajax_tracepilot_archive_log', array($this, 'ajax_archive_log'));
        add_action('wp_ajax_tracepilot_archive_all_logs', array($this, 'ajax_archive_all_logs'));
        add_action('wp_ajax_tracepilot_restore_log', array($this, 'ajax_restore_log'));
        add_action('wp_ajax_tracepilot_delete_archived_log', array($this, 'ajax_delete_archived_log'));
        
        // Initialize archive table name
        self::init();
    }
    
    /**
     * Initialize archive table name
     */
    public static function init() {
        global $wpdb;
        self::$archive_table = $wpdb->prefix . 'wpal_archive';
    }
    
    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wp-activity-logger-pro',
            __('Archive', 'wp-activity-logger-pro'),
            __('Archive', 'wp-activity-logger-pro'),
            TracePilot_Helpers::get_admin_capability(),
            'wp-activity-logger-pro-archive',
            array($this, 'render_page')
        );
    }
    
    /**
     * Render page
     */
    public function render_page() {
        include TracePilot_PLUGIN_DIR . 'templates/archive.php';
    }
    
    /**
     * Create archive table
     */
    public static function create_table() {
        global $wpdb;
        self::init();
        $table_name = self::$archive_table;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            original_id bigint(20) unsigned NOT NULL,
            time datetime NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            username varchar(60) DEFAULT NULL,
            user_role varchar(60) DEFAULT NULL,
            action varchar(255) NOT NULL,
            description text NOT NULL,
            severity varchar(20) DEFAULT 'info',
            ip varchar(45) DEFAULT NULL,
            browser varchar(255) DEFAULT NULL,
            object_type varchar(255) DEFAULT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            object_name varchar(255) DEFAULT NULL,
            context longtext DEFAULT NULL,
            archived_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY time (time),
            KEY user_id (user_id),
            KEY action (action),
            KEY severity (severity),
            KEY ip (ip),
            KEY object_type (object_type),
            KEY object_id (object_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * AJAX archive log
     */
    public function ajax_archive_log() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Get log ID
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        
        if (!$log_id) {
            wp_send_json_error(array('message' => __('Invalid log ID.', 'wp-activity-logger-pro')));
        }
        
        if (is_multisite() && $site_id) {
            switch_to_blog($site_id);
        }

        // Archive log
        $result = $this->archive_log($log_id);

        if (is_multisite() && $site_id) {
            restore_current_blog();
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Log entry archived successfully.', 'wp-activity-logger-pro')));
    }
    
    /**
     * Archive log
     */
    public function archive_log($log_id) {
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        self::init();
        $archive_table = self::$archive_table;
        
        // Get log
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id));
        
        if (!$log) {
            return new WP_Error('log_not_found', __('Log entry not found.', 'wp-activity-logger-pro'));
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        // Insert into archive table
        $result = $wpdb->insert(
            $archive_table,
            array(
                'original_id' => $log->id,
                'time' => $log->time,
                'user_id' => $log->user_id,
                'username' => $log->username,
                'user_role' => $log->user_role,
                'action' => $log->action,
                'description' => $log->description,
                'severity' => $log->severity,
                'ip' => $log->ip,
                'browser' => $log->browser,
                'object_type' => $log->object_type,
                'object_id' => $log->object_id,
                'object_name' => $log->object_name,
                'context' => $log->context,
                'archived_at' => current_time('mysql')
            ),
            array(
                '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'
            )
        );
        
        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('archive_failed', __('Failed to archive log entry.', 'wp-activity-logger-pro'));
        }
        
        // Delete from logs table
        $result = $wpdb->delete(
            $table_name,
            array('id' => $log_id),
            array('%d')
        );
        
        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('delete_failed', __('Failed to remove log entry from active logs.', 'wp-activity-logger-pro'));
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Log the archival
        TracePilot_Helpers::log_activity(
            'log_archived',
            sprintf(__('Log entry #%d was archived', 'wp-activity-logger-pro'), $log_id),
            'info'
        );
        
        return true;
    }
    
    /**
     * AJAX archive all logs
     */
    public function ajax_archive_all_logs() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Get filters
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        // Archive logs
        $result = $this->archive_all_logs($filters);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d log entries archived successfully.', 'wp-activity-logger-pro'), $result)
        ));
    }
    
    /**
     * Archive all logs
     */
    public function archive_all_logs($filters = array()) {
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        self::init();
        $archive_table = self::$archive_table;
        
        // Build where clause
        $where_clauses = array();
        $where_values = array();
        
        // Apply role filter
        if (!empty($filters['role'])) {
            $where_clauses[] = "user_role = %s";
            $where_values[] = $filters['role'];
        }
        
        // Apply severity filter
        if (!empty($filters['severity'])) {
            $where_clauses[] = "severity = %s";
            $where_values[] = $filters['severity'];
        }
        
        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $where_clauses[] = "time >= %s";
            $where_values[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = "time <= %s";
            $where_values[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Build query
        $query = "SELECT * FROM $table_name";
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        // Prepare query if we have values
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        // Get logs
        $logs = $wpdb->get_results($query);
        
        if (empty($logs)) {
            return 0;
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        $archived_count = 0;
        
        foreach ($logs as $log) {
            // Insert into archive table
            $result = $wpdb->insert(
                $archive_table,
                array(
                    'original_id' => $log->id,
                    'time' => $log->time,
                    'user_id' => $log->user_id,
                    'username' => $log->username,
                    'user_role' => $log->user_role,
                    'action' => $log->action,
                    'description' => $log->description,
                    'severity' => $log->severity,
                    'ip' => $log->ip,
                    'browser' => $log->browser,
                    'object_type' => $log->object_type,
                    'object_id' => $log->object_id,
                    'object_name' => $log->object_name,
                    'context' => $log->context,
                    'archived_at' => current_time('mysql')
                ),
                array(
                    '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'
                )
            );
            
            if ($result === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('archive_failed', __('Failed to archive log entries.', 'wp-activity-logger-pro'));
            }
            
            $archived_count++;
        }
        
        // Delete from logs table
        $delete_query = "DELETE FROM $table_name";
        if (!empty($where_clauses)) {
            $delete_query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        // Prepare delete query if we have values
        if (!empty($where_values)) {
            $delete_query = $wpdb->prepare($delete_query, $where_values);
        }
        
        $result = $wpdb->query($delete_query);
        
        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('delete_failed', __('Failed to remove log entries from active logs.', 'wp-activity-logger-pro'));
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Log the archival
        TracePilot_Helpers::log_activity(
            'logs_archived',
            sprintf(__('%d log entries were archived', 'wp-activity-logger-pro'), $archived_count),
            'info'
        );
        
        return $archived_count;
    }
    
    /**
     * AJAX restore log
     */
    public function ajax_restore_log() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Get log ID
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        
        if (!$log_id) {
            wp_send_json_error(array('message' => __('Invalid log ID.', 'wp-activity-logger-pro')));
        }
        
        // Restore log
        $result = $this->restore_log($log_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Log entry restored successfully.', 'wp-activity-logger-pro')));
    }
    
    /**
     * Restore log
     */
    public function restore_log($log_id) {
        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;
        self::init();
        $archive_table = self::$archive_table;
        
        // Get archived log
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $archive_table WHERE id = %d", $log_id));
        
        if (!$log) {
            return new WP_Error('log_not_found', __('Archived log entry not found.', 'wp-activity-logger-pro'));
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        // Insert into logs table
        $result = $wpdb->insert(
            $table_name,
            array(
                'time' => $log->time,
                'user_id' => $log->user_id,
                'username' => $log->username,
                'user_role' => $log->user_role,
                'action' => $log->action,
                'description' => $log->description,
                'severity' => $log->severity,
                'ip' => $log->ip,
                'browser' => $log->browser,
                'object_type' => $log->object_type,
                'object_id' => $log->object_id,
                'object_name' => $log->object_name,
                'context' => $log->context
            ),
            array(
                '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'
            )
        );
        
        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('restore_failed', __('Failed to restore log entry.', 'wp-activity-logger-pro'));
        }
        
        // Delete from archive table
        $result = $wpdb->delete(
            $archive_table,
            array('id' => $log_id),
            array('%d')
        );
        
        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('delete_failed', __('Failed to remove log entry from archive.', 'wp-activity-logger-pro'));
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Log the restoration
        TracePilot_Helpers::log_activity(
            'log_restored',
            sprintf(__('Log entry #%d was restored from archive', 'wp-activity-logger-pro'), $log->original_id),
            'info'
        );
        
        return true;
    }
    
    /**
     * AJAX delete archived log
     */
    public function ajax_delete_archived_log() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracepilot_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'wp-activity-logger-pro')));
        }
        
        // Check permissions
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }
        
        // Get log ID
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        
        if (!$log_id) {
            wp_send_json_error(array('message' => __('Invalid log ID.', 'wp-activity-logger-pro')));
        }
        
        // Delete log
        $result = $this->delete_archived_log($log_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Archived log entry deleted successfully.', 'wp-activity-logger-pro')));
    }
    
    /**
     * Delete archived log
     */
    public function delete_archived_log($log_id) {
        global $wpdb;
        self::init();
        $archive_table = self::$archive_table;
        
        // Delete from archive table
        $result = $wpdb->delete(
            $archive_table,
            array('id' => $log_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', __('Failed to delete archived log entry.', 'wp-activity-logger-pro'));
        }
        
        // Log the deletion
        TracePilot_Helpers::log_activity(
            'archived_log_deleted',
            sprintf(__('Archived log entry #%d was deleted', 'wp-activity-logger-pro'), $log_id),
            'info'
        );
        
        return true;
    }
}
