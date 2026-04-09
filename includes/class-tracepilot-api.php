<?php
/**
 * API class for TracePilot for WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TracePilot_API {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('wp_ajax_tracepilot_get_activity_chart', array($this, 'get_activity_chart'));
        add_action('wp_ajax_tracepilot_get_top_users', array($this, 'get_top_users'));
        add_action('wp_ajax_tracepilot_get_severity_breakdown', array($this, 'get_severity_breakdown'));
        add_action('wp_ajax_tracepilot_get_recent_logs', array($this, 'get_recent_logs'));
        add_action('wp_ajax_tracepilot_get_log_details', array($this, 'get_log_details'));
        add_action('wp_ajax_tracepilot_delete_log', array($this, 'delete_log'));
        add_action('wp_ajax_tracepilot_delete_all_logs', array($this, 'delete_all_logs'));
    }

    /**
     * Verify request.
     */
    private function guard_request() {
        check_ajax_referer('tracepilot_nonce', 'nonce');

        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_die(__('You do not have permission to access this page.', 'wp-activity-logger-pro'));
        }
    }

    /**
     * Output activity chart widget.
     */
    public function get_activity_chart() {
        $this->guard_request();
        $series = TracePilot_Helpers::get_activity_series(14);
        ?>
        <canvas id="tracepilot-widget-activity-chart" height="220"></canvas>
        <script>
        if (window.tracepilotRenderLineChart) {
            window.tracepilotRenderLineChart('tracepilot-widget-activity-chart', <?php echo wp_json_encode($series['labels']); ?>, <?php echo wp_json_encode($series['values']); ?>);
        }
        </script>
        <?php
        wp_die();
    }

    /**
     * Output top users widget.
     */
    public function get_top_users() {
        $this->guard_request();
        global $wpdb;
        TracePilot_Helpers::init();

        $users = $wpdb->get_results(
            "SELECT username, user_role, COUNT(*) AS total
            FROM " . TracePilot_Helpers::$db_table . "
            WHERE username <> ''
            GROUP BY username, user_role
            ORDER BY total DESC
            LIMIT 6"
        );

        if (empty($users)) {
            echo '<p>' . esc_html__('No user activity found yet.', 'wp-activity-logger-pro') . '</p>';
            wp_die();
        }

        echo '<div class="tracepilot-list">';
        foreach ($users as $user) {
            echo '<div class="tracepilot-list-row">';
            echo '<div><strong>' . esc_html($user->username) . '</strong><span class="tracepilot-meta-pill">' . esc_html($user->user_role) . '</span></div>';
            echo '<div class="tracepilot-list-value">' . esc_html(number_format_i18n($user->total)) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        wp_die();
    }

    /**
     * Output severity breakdown widget.
     */
    public function get_severity_breakdown() {
        $this->guard_request();
        global $wpdb;
        TracePilot_Helpers::init();

        $rows = $wpdb->get_results(
            "SELECT severity, COUNT(*) AS total
            FROM " . TracePilot_Helpers::$db_table . "
            GROUP BY severity
            ORDER BY total DESC"
        );

        $labels = array();
        $values = array();
        foreach ($rows as $row) {
            $labels[] = ucfirst($row->severity);
            $values[] = (int) $row->total;
        }
        ?>
        <canvas id="tracepilot-widget-severity-chart" height="220"></canvas>
        <script>
        if (window.tracepilotRenderDoughnutChart) {
            window.tracepilotRenderDoughnutChart('tracepilot-widget-severity-chart', <?php echo wp_json_encode($labels); ?>, <?php echo wp_json_encode($values); ?>);
        }
        </script>
        <?php
        wp_die();
    }

    /**
     * Output recent logs widget.
     */
    public function get_recent_logs() {
        $this->guard_request();
        global $wpdb;
        TracePilot_Helpers::init();

        $logs = $wpdb->get_results(
            "SELECT * FROM " . TracePilot_Helpers::$db_table . "
            ORDER BY time DESC
            LIMIT 8"
        );

        if (empty($logs)) {
            echo '<p>' . esc_html__('No logs found.', 'wp-activity-logger-pro') . '</p>';
            wp_die();
        }

        echo '<div class="tracepilot-list">';
        foreach ($logs as $log) {
            echo '<button type="button" class="tracepilot-list-row tracepilot-list-row-button tracepilot-view-log" data-log-id="' . esc_attr($log->id) . '">';
            echo '<div><strong>' . esc_html($log->action) . '</strong><div class="tracepilot-list-subtext">' . esc_html($log->username) . ' • ' . esc_html(TracePilot_Helpers::format_datetime($log->time)) . '</div></div>';
            echo '<div>' . TracePilot_Helpers::get_severity_badge($log->severity) . '</div>';
            echo '</button>';
        }
        echo '</div>';
        wp_die();
    }

    /**
     * Output log detail modal body.
     */
    public function get_log_details() {
        $this->guard_request();
        if (is_multisite() && !empty($_POST['site_id'])) {
            switch_to_blog(absint($_POST['site_id']));
            TracePilot_Helpers::init();
        }
        include TracePilot_PLUGIN_DIR . 'templates/tracepilot-log-details.php';
        if (is_multisite() && !empty($_POST['site_id'])) {
            restore_current_blog();
            TracePilot_Helpers::init();
        }
        wp_die();
    }

    /**
     * Delete a log.
     */
    public function delete_log() {
        check_ajax_referer('tracepilot_nonce', 'nonce');

        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to delete logs.', 'wp-activity-logger-pro')));
        }

        $log_id = isset($_POST['log_id']) ? absint($_POST['log_id']) : 0;
        $site_id = isset($_POST['site_id']) ? absint($_POST['site_id']) : 0;
        if (!$log_id) {
            wp_send_json_error(array('message' => __('Invalid log ID.', 'wp-activity-logger-pro')));
        }

        if (is_multisite() && $site_id) {
            switch_to_blog($site_id);
            TracePilot_Helpers::init();
        }

        global $wpdb;
        TracePilot_Helpers::init();
        $result = $wpdb->delete(TracePilot_Helpers::$db_table, array('id' => $log_id), array('%d'));

        if (is_multisite() && $site_id) {
            restore_current_blog();
            TracePilot_Helpers::init();
        }

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete log.', 'wp-activity-logger-pro')));
        }

        wp_send_json_success(array('message' => __('Log deleted successfully.', 'wp-activity-logger-pro')));
    }

    /**
     * Delete all logs.
     */
    public function delete_all_logs() {
        check_ajax_referer('tracepilot_nonce', 'nonce');

        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to delete logs.', 'wp-activity-logger-pro')));
        }

        global $wpdb;
        TracePilot_Helpers::init();
        $result = $wpdb->query("TRUNCATE TABLE " . TracePilot_Helpers::$db_table);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete logs.', 'wp-activity-logger-pro')));
        }

        wp_send_json_success(array('message' => __('All logs deleted successfully.', 'wp-activity-logger-pro')));
    }
}
