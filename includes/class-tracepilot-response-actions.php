<?php
/**
 * Response actions for TracePilot for WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TracePilot_Response_Actions {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_block_ip'));
        add_filter('map_meta_cap', array($this, 'lock_plugin_changes'), 20, 4);
        add_action('wp_ajax_tracepilot_block_ip', array($this, 'ajax_block_ip'));
        add_action('wp_ajax_tracepilot_force_logout_user', array($this, 'ajax_force_logout_user'));
        add_action('wp_ajax_tracepilot_reset_user_password', array($this, 'ajax_reset_user_password'));
        add_action('wp_ajax_tracepilot_toggle_plugin_changes_lock', array($this, 'ajax_toggle_plugin_changes_lock'));
        add_action('wp_ajax_tracepilot_export_user_logs', array($this, 'ajax_export_user_logs'));
        add_action('wp_ajax_tracepilot_delete_user_logs', array($this, 'ajax_delete_user_logs'));
    }

    /**
     * Block requests from blocked IPs.
     */
    public function maybe_block_ip() {
        if (is_admin() && TracePilot_Helpers::current_user_can_manage()) {
            return;
        }

        $settings = TracePilot_Helpers::get_settings();
        $ip = TracePilot_Helpers::get_ip_address();

        if (!empty($ip) && in_array($ip, (array) $settings['blocked_ips'], true)) {
            wp_die(esc_html__('Access denied by WP Activity Logger security policy.', 'wp-activity-logger-pro'), 403);
        }
    }

    /**
     * Prevent plugin-changing capabilities when locked.
     *
     * @param array  $caps Caps.
     * @param string $cap Capability.
     * @return array
     */
    public function lock_plugin_changes($caps, $cap) {
        $settings = TracePilot_Helpers::get_settings();
        if (empty($settings['plugin_changes_locked'])) {
            return $caps;
        }

        if ('activate_plugins' === $cap) {
            if (!is_admin()) {
                return array('do_not_allow');
            }

            $current_page = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';
            $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '';
            $action2 = isset($_REQUEST['action2']) ? sanitize_key(wp_unslash($_REQUEST['action2'])) : '';
            $mutating_actions = array(
                'activate',
                'activate-selected',
                'deactivate',
                'deactivate-selected',
                'enable',
                'disable',
                'resume',
                'update',
                'update-selected',
                'delete-selected',
            );

            if ('plugins.php' === $current_page && !in_array($action, $mutating_actions, true) && !in_array($action2, $mutating_actions, true)) {
                return $caps;
            }

            return array('do_not_allow');
        }

        if (in_array($cap, array('install_plugins', 'update_plugins', 'delete_plugins', 'edit_plugins'), true)) {
            return array('do_not_allow');
        }

        return $caps;
    }

    /**
     * Save settings helper.
     *
     * @param array $settings Settings.
     */
    private function persist_settings($settings) {
        update_option('wpal_options', $settings);
        update_option('wpal_settings', $settings);
    }

    /**
     * AJAX block IP.
     */
    public function ajax_block_ip() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $ip = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
        if (!$ip) {
            wp_send_json_error(array('message' => __('IP address is required.', 'wp-activity-logger-pro')));
        }

        $settings = TracePilot_Helpers::get_settings();
        $blocked = (array) $settings['blocked_ips'];
        if (!in_array($ip, $blocked, true)) {
            $blocked[] = $ip;
        }
        $settings['blocked_ips'] = array_values(array_unique($blocked));
        $this->persist_settings($settings);

        TracePilot_Helpers::log_activity('ip_blocked', sprintf(__('Blocked IP address %s', 'wp-activity-logger-pro'), $ip), 'warning', array('ip' => $ip));
        wp_send_json_success(array('message' => __('IP blocked successfully.', 'wp-activity-logger-pro')));
    }

    /**
     * AJAX force logout.
     */
    public function ajax_force_logout_user() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User ID is required.', 'wp-activity-logger-pro')));
        }

        $tokens = WP_Session_Tokens::get_instance($user_id);
        $tokens->destroy_all();

        $user = get_userdata($user_id);
        TracePilot_Helpers::log_activity('user_forced_logout', sprintf(__('Forced logout for user %s', 'wp-activity-logger-pro'), $user ? $user->user_login : $user_id), 'warning', array('user_id' => $user_id));
        wp_send_json_success(array('message' => __('User sessions cleared.', 'wp-activity-logger-pro')));
    }

    /**
     * AJAX password reset.
     */
    public function ajax_reset_user_password() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $user = $user_id ? get_userdata($user_id) : false;
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'wp-activity-logger-pro')));
        }

        retrieve_password($user->user_login);
        TracePilot_Helpers::log_activity('password_reset_requested', sprintf(__('Triggered password reset for user %s', 'wp-activity-logger-pro'), $user->user_login), 'warning', array('user_id' => $user_id));
        wp_send_json_success(array('message' => __('Password reset email sent.', 'wp-activity-logger-pro')));
    }

    /**
     * AJAX toggle plugin lock.
     */
    public function ajax_toggle_plugin_changes_lock() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $enabled = !empty($_POST['enabled']) ? 1 : 0;
        $settings = TracePilot_Helpers::get_settings();
        $settings['plugin_changes_locked'] = $enabled;
        $this->persist_settings($settings);

        TracePilot_Helpers::log_activity('plugin_change_lock_toggled', $enabled ? __('Plugin changes locked', 'wp-activity-logger-pro') : __('Plugin changes unlocked', 'wp-activity-logger-pro'), 'warning');
        wp_send_json_success(array('message' => __('Plugin change policy updated.', 'wp-activity-logger-pro')));
    }

    /**
     * Export user logs.
     */
    public function ajax_export_user_logs() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $rows = TracePilot_Helpers::export_user_logs($user_id);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=user-logs-' . $user_id . '.json');
        echo wp_json_encode($rows);
        exit;
    }

    /**
     * Delete user logs.
     */
    public function ajax_delete_user_logs() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-activity-logger-pro')));
        }

        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User ID is required.', 'wp-activity-logger-pro')));
        }

        $deleted = TracePilot_Helpers::delete_user_logs($user_id);
        TracePilot_Helpers::log_activity('user_logs_deleted', sprintf(__('Deleted logs for user ID %d', 'wp-activity-logger-pro'), $user_id), 'warning');
        wp_send_json_success(array('message' => sprintf(__('Deleted %d logs.', 'wp-activity-logger-pro'), (int) $deleted)));
    }
}
