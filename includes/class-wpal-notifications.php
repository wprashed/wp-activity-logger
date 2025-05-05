<?php
class WPAL_Notifications {
    public static function send_notification($log_entry) {
        // Email notification
        $notification_email = get_option('wpal_notification_email');
        if (!empty($notification_email)) {
            self::send_email_notification($notification_email, $log_entry);
        }
        
        // Webhook notification
        $webhook_url = get_option('wpal_webhook_url');
        if (!empty($webhook_url)) {
            self::send_webhook_notification($webhook_url, $log_entry);
        }
        
        // Slack notification
        $slack_webhook = get_option('wpal_slack_webhook');
        if (!empty($slack_webhook)) {
            self::send_slack_notification($slack_webhook, $log_entry);
        }
        
        // Discord notification
        $discord_webhook = get_option('wpal_discord_webhook');
        if (!empty($discord_webhook)) {
            self::send_discord_notification($discord_webhook, $log_entry);
        }
        
        // Telegram notification
        $telegram_bot_token = get_option('wpal_telegram_bot_token');
        $telegram_chat_id = get_option('wpal_telegram_chat_id');
        if (!empty($telegram_bot_token) && !empty($telegram_chat_id)) {
            self::send_telegram_notification($telegram_bot_token, $telegram_chat_id, $log_entry);
        }
    }
    
    public static function send_email_notification($email, $log_entry) {
        $subject = '[WPAL] ' . $log_entry['severity'] . ' - ' . $log_entry['action'];
        
        $message = '<h2>Activity Log Alert</h2>';
        $message .= '<p><strong>Time:</strong> ' . $log_entry['time'] . '</p>';
        $message .= '<p><strong>User:</strong> ' . $log_entry['username'] . ' (' . $log_entry['user_role'] . ')</p>';
        $message .= '<p><strong>Action:</strong> ' . $log_entry['action'] . '</p>';
        $message .= '<p><strong>IP:</strong> ' . $log_entry['ip'] . '</p>';
        $message .= '<p><strong>Browser:</strong> ' . $log_entry['browser'] . '</p>';
        $message .= '<p><strong>Severity:</strong> ' . $log_entry['severity'] . '</p>';
        
        if (!empty($log_entry['context'])) {
            $message .= '<h3>Additional Details:</h3>';
            $message .= '<pre>' . print_r($log_entry['context'], true) . '</pre>';
        }
        
        $message .= '<p><a href="' . admin_url('admin.php?page=wpal-logs') . '">View All Logs</a></p>';
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    public static function send_webhook_notification($webhook_url, $log_entry) {
        wp_remote_post($webhook_url, [
            'body' => json_encode([
                'event' => 'new_log',
                'data' => $log_entry,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }
    
    public static function send_slack_notification($webhook_url, $log_entry) {
        // Set color based on severity
        $color = '#28a745'; // Default green for info
        if ($log_entry['severity'] === 'warning') {
            $color = '#ffc107'; // Yellow for warning
        } elseif ($log_entry['severity'] === 'error') {
            $color = '#dc3545'; // Red for error
        }
        
        $payload = [
            'attachments' => [
                [
                    'fallback' => $log_entry['action'],
                    'color' => $color,
                    'title' => 'Activity Log: ' . $log_entry['action'],
                    'fields' => [
                        [
                            'title' => 'Time',
                            'value' => $log_entry['time'],
                            'short' => true,
                        ],
                        [
                            'title' => 'User',
                            'value' => $log_entry['username'] . ' (' . $log_entry['user_role'] . ')',
                            'short' => true,
                        ],
                        [
                            'title' => 'IP',
                            'value' => $log_entry['ip'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Browser',
                            'value' => $log_entry['browser'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => strtoupper($log_entry['severity']),
                            'short' => true,
                        ],
                    ],
                    'footer' => 'WP Activity Logger Pro',
                    'ts' => strtotime($log_entry['time']),
                ],
            ],
        ];
        
        wp_remote_post($webhook_url, [
            'body' => json_encode($payload),
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }
    
    public static function send_discord_notification($webhook_url, $log_entry) {
        // Set color based on severity
        $color = 3066993; // Green for info
        if ($log_entry['severity'] === 'warning') {
            $color = 16776960; // Yellow for warning
        } elseif ($log_entry['severity'] === 'error') {
            $color = 15158332; // Red for error
        }
        
        $payload = [
            'embeds' => [
                [
                    'title' => 'Activity Log: ' . $log_entry['action'],
                    'color' => $color,
                    'fields' => [
                        [
                            'name' => 'Time',
                            'value' => $log_entry['time'],
                            'inline' => true,
                        ],
                        [
                            'name' => 'User',
                            'value' => $log_entry['username'] . ' (' . $log_entry['user_role'] . ')',
                            'inline' => true,
                        ],
                        [
                            'name' => 'IP',
                            'value' => $log_entry['ip'],
                            'inline' => true,
                        ],
                        [
                            'name' => 'Browser',
                            'value' => $log_entry['browser'],
                            'inline' => true,
                        ],
                        [
                            'name' => 'Severity',
                            'value' => strtoupper($log_entry['severity']),
                            'inline' => true,
                        ],
                    ],
                    'footer' => [
                        'text' => 'WP Activity Logger Pro',
                    ],
                    'timestamp' => date('c', strtotime($log_entry['time'])),
                ],
            ],
        ];
        
        wp_remote_post($webhook_url, [
            'body' => json_encode($payload),
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }
    
    public static function send_telegram_notification($bot_token, $chat_id, $log_entry) {
        $message = "🔔 *Activity Log Alert*\n\n";
        $message .= "⏰ *Time:* " . $log_entry['time'] . "\n";
        $message .= "👤 *User:* " . $log_entry['username'] . " (" . $log_entry['user_role'] . ")\n";
        $message .= "🔄 *Action:* " . $log_entry['action'] . "\n";
        $message .= "🌐 *IP:* " . $log_entry['ip'] . "\n";
        $message .= "🌍 *Browser:* " . $log_entry['browser'] . "\n";
        
        // Add emoji based on severity
        $severity_emoji = '✅'; // Info
        if ($log_entry['severity'] === 'warning') {
            $severity_emoji = '⚠️'; // Warning
        } elseif ($log_entry['severity'] === 'error') {
            $severity_emoji = '❌'; // Error
        }
        
        $message .= "$severity_emoji *Severity:* " . strtoupper($log_entry['severity']) . "\n";
        
        $url = "https://api.telegram.org/bot$bot_token/sendMessage";
        $payload = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];
        
        wp_remote_post($url, [
            'body' => $payload,
        ]);
    }
    
    public static function send_daily_report() {
        $notification_email = get_option('wpal_notification_email');
        $daily_report = get_option('wpal_daily_report', true);
        
        if (empty($notification_email) || !$daily_report) {
            return;
        }
        
        // Get logs from the last 24 hours
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $logs = WPAL_Helpers::get_filtered_logs([
            'from' => $yesterday,
            'limit' => 1000,
        ]);
        
        if (empty($logs)) {
            return; // No logs to report
        }
        
        // Count by severity
        $severity_counts = [
            'info' => 0,
            'warning' => 0,
            'error' => 0,
        ];
        
        // Count by user
        $user_counts = [];
        
        // Count by action type
        $action_counts = [];
        
        foreach ($logs as $log) {
            // Count by severity
            $severity = isset($log['severity']) ? $log['severity'] : 'info';
            if (isset($severity_counts[$severity])) {
                $severity_counts[$severity]++;
            }
            
            // Count by user
            $username = $log['username'] ?? 'Unknown';
            if (!isset($user_counts[$username])) {
                $user_counts[$username] = 0;
            }
            $user_counts[$username]++;
            
            // Count by action type (first word of action)
            $action = $log['action'] ?? '';
            $action_type = explode(' ', $action)[0];
            if (!isset($action_counts[$action_type])) {
                $action_counts[$action_type] = 0;
            }
            $action_counts[$action_type]++;
        }
        
        // Sort counts
        arsort($user_counts);
        arsort($action_counts);
        
        // Build email
        $subject = '[WPAL] Daily Activity Summary - ' . date('Y-m-d');
        
        $message = '<h2>Daily Activity Summary</h2>';
        $message .= '<p>This is a summary of activity on your website for the last 24 hours.</p>';
        
        // Summary stats
        $message .= '<h3>Summary</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Total Logs:</strong> ' . count($logs) . '</li>';
        $message .= '<li><strong>Info:</strong> ' . $severity_counts['info'] . '</li>';
        $message .= '<li><strong>Warnings:</strong> ' . $severity_counts['warning'] . '</li>';
        $message .= '<li><strong>Errors:</strong> ' . $severity_counts['error'] . '</li>';
        $message .= '</ul>';
        
        // Top users
        $message .= '<h3>Top Users</h3>';
        $message .= '<ul>';
        $count = 0;
        foreach ($user_counts as $user => $count) {
            $message .= '<li><strong>' . $user . ':</strong> ' . $count . ' activities</li>';
            $count++;
            if ($count >= 5) break; // Show top 5
        }
        $message .= '</ul>';
        
        // Top actions
        $message .= '<h3>Top Actions</h3>';
        $message .= '<ul>';
        $count = 0;
        foreach ($action_counts as $action => $count) {
            $message .= '<li><strong>' . $action . ':</strong> ' . $count . ' occurrences</li>';
            $count++;
            if ($count >= 5) break; // Show top 5
        }
        $message .= '</ul>';
        
        // Recent important logs
        $message .= '<h3>Recent Important Logs</h3>';
        $message .= '<table style="width: 100%; border-collapse: collapse;">';
        $message .= '<tr style="background-color: #f2f2f2;">';
        $message .= '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Time</th>';
        $message .= '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">User</th>';
        $message .= '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Action</th>';
        $message .= '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Severity</th>';
        $message .= '</tr>';
        
        // Filter for important logs (warnings and errors)
        $important_logs = array_filter($logs, function($log) {
            return isset($log['severity']) && ($log['severity'] === 'warning' || $log['severity'] === 'error');
        });
        
        // Show the 10 most recent important logs
        $important_logs = array_slice($important_logs, 0, 10);
        
        if (empty($important_logs)) {
            $message .= '<tr><td colspan="4" style="padding: 8px; text-align: center; border: 1px solid #ddd;">No important logs found</td></tr>';
        } else {
            foreach ($important_logs as $log) {
                $bg_color = $log['severity'] === 'warning' ? '#fff3cd' : '#f8d7da';
                $message .= '<tr style="background-color: ' . $bg_color . ';">';
                $message .= '<td style="padding: 8px; text-align: left; border: 1px solid #ddd;">' . $log['time'] . '</td>';
                $message .= '<td style="padding: 8px; text-align: left; border: 1px solid #ddd;">' . $log['username'] . '</td>';
                $message .= '<td style="padding: 8px; text-align: left; border: 1px solid #ddd;">' . $log['action'] . '</td>';
                $message .= '<td style="padding: 8px; text-align: left; border: 1px solid #ddd;">' . strtoupper($log['severity']) . '</td>';
                $message .= '</tr>';
            }
        }
        
        $message .= '</table>';
        
        // Link to dashboard
        $message .= '<p><a href="' . admin_url('admin.php?page=wpal-logs') . '">View All Logs</a></p>';
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($notification_email, $subject, $message, $headers);
    }
    
    public static function ajax_test_notification() {
        check_ajax_referer('wpal_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $test_entry = [
            'time' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'username' => wp_get_current_user()->user_login,
            'user_role' => implode(', ', wp_get_current_user()->roles),
            'action' => 'Test notification',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'browser' => WPAL_Helpers::get_browser_name(),
            'severity' => 'info',
            'context' => ['test' => true, 'timestamp' => time()],
        ];
        
        self::send_notification($test_entry);
        
        wp_send_json_success('Test notification sent successfully');
    }
}