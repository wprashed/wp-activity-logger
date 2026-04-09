<?php
/**
 * Notifications class for TracePilot for WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TracePilot_Notifications {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('tracepilot_after_log_activity', array($this, 'maybe_send_notifications'), 20, 5);
        add_action('tracepilot_daily_cron', array($this, 'send_daily_summary'));
        add_action('tracepilot_weekly_cron', array($this, 'send_weekly_summary'));
    }

    /**
     * Send notifications for qualifying logs.
     *
     * @param int    $log_id Log ID.
     * @param string $action Action.
     * @param string $message Description.
     * @param string $severity Severity.
     * @param array  $context Context.
     */
    public function maybe_send_notifications($log_id, $action, $message, $severity, $context) {
        $settings = TracePilot_Helpers::get_settings();
        if (empty($settings['enable_notifications'])) {
            return;
        }

        $matches_event = in_array($action, (array) $settings['notification_events'], true);
        $matches_severity = in_array($severity, (array) $settings['notification_severities'], true);

        if (!$matches_event && !$matches_severity) {
            return;
        }

        $payload = $this->build_payload($log_id, $action, $message, $severity, $context);
        $this->dispatch_payload($payload, $settings);
    }

    /**
     * Send a custom alert through configured channels.
     *
     * @param string $action Action.
     * @param string $message Message.
     * @param string $severity Severity.
     * @param array  $context Context.
     */
    public function send_custom_notification($action, $message, $severity = 'warning', $context = array()) {
        $settings = TracePilot_Helpers::get_settings();
        if (empty($settings['enable_notifications']) && empty($settings['enable_threat_notifications'])) {
            return;
        }

        $payload = $this->build_payload(0, $action, $message, $severity, $context);
        $this->dispatch_payload($payload, $settings);
    }

    /**
     * Dispatch a payload.
     *
     * @param array $payload Payload.
     * @param array $settings Settings.
     */
    private function dispatch_payload($payload, $settings) {
        if (!empty($settings['notification_email'])) {
            $this->send_email_notification($settings['notification_email'], $payload);
        }

        if (!empty($settings['enable_webhook_notifications']) && !empty($settings['webhook_url'])) {
            $this->send_webhook($settings['webhook_url'], $payload);
        }

        if (!empty($settings['slack_webhook_url'])) {
            $this->send_slack_notification($settings['slack_webhook_url'], $payload);
        }

        if (!empty($settings['discord_webhook_url'])) {
            $this->send_discord_notification($settings['discord_webhook_url'], $payload);
        }

        if (!empty($settings['telegram_bot_token']) && !empty($settings['telegram_chat_id'])) {
            $this->send_telegram_notification($settings['telegram_bot_token'], $settings['telegram_chat_id'], $payload);
        }
    }

    /**
     * Build notification payload.
     *
     * @param int    $log_id Log ID.
     * @param string $action Action.
     * @param string $message Description.
     * @param string $severity Severity.
     * @param array  $context Context.
     * @return array
     */
    private function build_payload($log_id, $action, $message, $severity, $context) {
        return array(
            'log_id' => (int) $log_id,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url('/'),
            'action' => $action,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
            'time' => current_time('mysql'),
            'admin_url' => admin_url('admin.php?page=wp-activity-logger-pro-logs'),
        );
    }

    /**
     * Send email notification.
     *
     * @param string $email Recipient.
     * @param array  $payload Payload.
     */
    private function send_email_notification($email, $payload) {
        $subject = sprintf(
            __('[%1$s] %2$s alert: %3$s', 'wp-activity-logger-pro'),
            $payload['site_name'],
            strtoupper($payload['severity']),
            $payload['action']
        );

        $body = sprintf(__('A new activity alert was recorded on %s.', 'wp-activity-logger-pro'), $payload['site_name']) . "\n\n";
        $body .= sprintf(__('Action: %s', 'wp-activity-logger-pro'), $payload['action']) . "\n";
        $body .= sprintf(__('Severity: %s', 'wp-activity-logger-pro'), ucfirst($payload['severity'])) . "\n";
        $body .= sprintf(__('Message: %s', 'wp-activity-logger-pro'), $payload['message']) . "\n";
        $body .= sprintf(__('Time: %s', 'wp-activity-logger-pro'), $payload['time']) . "\n";
        $body .= sprintf(__('Dashboard: %s', 'wp-activity-logger-pro'), $payload['admin_url']) . "\n";

        wp_mail($email, $subject, $body, array('Content-Type: text/plain; charset=UTF-8'));
    }

    /**
     * Send generic webhook payload.
     *
     * @param string $url Webhook URL.
     * @param array  $payload Payload.
     */
    private function send_webhook($url, $payload) {
        wp_remote_post(
            $url,
            array(
                'timeout' => 8,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode($payload),
            )
        );
    }

    /**
     * Send Slack webhook.
     *
     * @param string $url Webhook URL.
     * @param array  $payload Payload.
     */
    private function send_slack_notification($url, $payload) {
        $body = array(
            'text' => sprintf('[%s] %s: %s', strtoupper($payload['severity']), $payload['action'], $payload['message']),
            'attachments' => array(
                array(
                    'color' => $payload['severity'] === 'error' ? '#d63638' : '#2271b1',
                    'fields' => array(
                        array('title' => 'Site', 'value' => $payload['site_name'], 'short' => true),
                        array('title' => 'Time', 'value' => $payload['time'], 'short' => true),
                    ),
                ),
            ),
        );

        $this->send_webhook($url, $body);
    }

    /**
     * Send Discord webhook.
     *
     * @param string $url Webhook URL.
     * @param array  $payload Payload.
     */
    private function send_discord_notification($url, $payload) {
        $body = array(
            'content' => sprintf('**%s** `%s` %s', strtoupper($payload['severity']), $payload['action'], $payload['message']),
        );

        $this->send_webhook($url, $body);
    }

    /**
     * Send Telegram notification.
     *
     * @param string $token Bot token.
     * @param string $chat_id Chat ID.
     * @param array  $payload Payload.
     */
    private function send_telegram_notification($token, $chat_id, $payload) {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', rawurlencode($token));
        $body = array(
            'chat_id' => $chat_id,
            'text' => sprintf(
                "[%s] %s\n%s\n%s\n%s",
                strtoupper($payload['severity']),
                $payload['action'],
                $payload['message'],
                $payload['time'],
                $payload['admin_url']
            ),
        );

        wp_remote_post(
            $url,
            array(
                'timeout' => 8,
                'body' => $body,
            )
        );
    }

    /**
     * Send scheduled summary.
     */
    public function send_daily_summary() {
        $settings = TracePilot_Helpers::get_settings();
        if (empty($settings['daily_summary_enabled']) || empty($settings['daily_summary_email'])) {
            return;
        }

        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;

        $rows = $wpdb->get_results(
            "SELECT severity, COUNT(*) AS total
            FROM $table_name
            WHERE time >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY severity"
        );

        $actions = $wpdb->get_results(
            "SELECT action, COUNT(*) AS total
            FROM $table_name
            WHERE time >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY action
            ORDER BY total DESC
            LIMIT 5"
        );

        $body = sprintf(__('Daily activity summary for %s', 'wp-activity-logger-pro'), get_bloginfo('name')) . "\n\n";
        foreach ($rows as $row) {
            $body .= sprintf('%s: %d', ucfirst($row->severity), (int) $row->total) . "\n";
        }

        if (!empty($actions)) {
            $body .= "\n" . __('Top actions:', 'wp-activity-logger-pro') . "\n";
            foreach ($actions as $action) {
                $body .= sprintf('- %s: %d', $action->action, (int) $action->total) . "\n";
            }
        }

        if (!empty($settings['daily_summary_include_threats'])) {
            $threat_table = $wpdb->prefix . 'wpal_threats';
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $threat_table))) {
                $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $threat_table WHERE time >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
                $body .= "\n" . sprintf(__('Threats detected in the last 24 hours: %d', 'wp-activity-logger-pro'), $count) . "\n";
            }
        }

        wp_mail(
            $settings['daily_summary_email'],
            sprintf(__('[%s] Daily activity summary', 'wp-activity-logger-pro'), get_bloginfo('name')),
            $body,
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }

    /**
     * Send weekly summary.
     */
    public function send_weekly_summary() {
        $settings = TracePilot_Helpers::get_settings();
        if (empty($settings['weekly_summary_enabled']) || empty($settings['weekly_summary_email'])) {
            return;
        }

        global $wpdb;
        TracePilot_Helpers::init();
        $table_name = TracePilot_Helpers::$db_table;

        $sections = array(
            'failed_logins' => "SELECT COUNT(*) FROM $table_name WHERE action = 'login_failed' AND time >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'role_changes' => "SELECT COUNT(*) FROM $table_name WHERE action = 'user_role_changed' AND time >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'plugin_changes' => "SELECT COUNT(*) FROM $table_name WHERE action IN ('plugin_activated','plugin_deactivated') AND time >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'theme_changes' => "SELECT COUNT(*) FROM $table_name WHERE action = 'theme_switched' AND time >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'high_severity' => "SELECT COUNT(*) FROM $table_name WHERE severity IN ('error','critical') AND time >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        );

        $body = sprintf(__('Weekly activity summary for %s', 'wp-activity-logger-pro'), get_bloginfo('name')) . "\n\n";
        foreach ($sections as $label => $sql) {
            $body .= sprintf("%s: %d\n", ucwords(str_replace('_', ' ', $label)), (int) $wpdb->get_var($sql));
        }

        wp_mail(
            $settings['weekly_summary_email'],
            sprintf(__('[%s] Weekly activity summary', 'wp-activity-logger-pro'), get_bloginfo('name')),
            $body,
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }
}
