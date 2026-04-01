<?php
/**
 * Settings template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = WPAL_Helpers::get_settings();
$roles = wp_roles();
?>

<div class="wrap wpal-wrap">
    <section class="wpal-hero wpal-hero-compact">
        <div>
            <p class="wpal-eyebrow"><?php esc_html_e('Controls', 'wp-activity-logger-pro'); ?></p>
            <h1 class="wpal-page-title"><?php esc_html_e('Settings', 'wp-activity-logger-pro'); ?></h1>
            <p class="wpal-hero-copy"><?php esc_html_e('Configure alert routing, privacy rules, suppression filters, export defaults, and timeline behavior from one place.', 'wp-activity-logger-pro'); ?></p>
        </div>
    </section>

    <form id="wpal-settings-form" class="wpal-grid wpal-grid-2">
        <section class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Retention & Privacy', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Decide how long data lives and how much personal data is stored.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-form-stack">
                <label>
                    <span><?php esc_html_e('Log retention (days)', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="number" min="0" name="wpal_options[log_retention]" value="<?php echo esc_attr($settings['log_retention']); ?>">
                </label>
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[anonymize_ip]" value="1" <?php checked($settings['anonymize_ip'], 1); ?>>
                    <span><?php esc_html_e('Anonymize stored IP addresses', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label>
                    <span><?php esc_html_e('Redact context keys', 'wp-activity-logger-pro'); ?></span>
                    <textarea class="wpal-input" rows="3" name="wpal_options[redact_context_keys]" placeholder="password,token,email"><?php echo esc_textarea($settings['redact_context_keys']); ?></textarea>
                </label>
                <label>
                    <span><?php esc_html_e('Timeline window (hours)', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="number" min="1" name="wpal_options[timeline_window_hours]" value="<?php echo esc_attr($settings['timeline_window_hours']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Exclude roles from logging', 'wp-activity-logger-pro'); ?></span>
                    <div class="wpal-check-grid">
                        <?php foreach ($roles->roles as $role_key => $role) : ?>
                            <label class="wpal-check-card">
                                <input type="checkbox" name="wpal_options[exclude_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, (array) $settings['exclude_roles'], true)); ?>>
                                <span>
                                    <strong><?php echo esc_html($role['name']); ?></strong>
                                    <small><?php echo esc_html($role_key); ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </label>
            </div>
        </section>

        <section class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Notification Routing', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Send alerts to email, generic webhooks, Slack, and Discord.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-form-stack">
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[enable_notifications]" value="1" <?php checked($settings['enable_notifications'], 1); ?>>
                    <span><?php esc_html_e('Enable notifications', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label>
                    <span><?php esc_html_e('Alert email', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="email" name="wpal_options[notification_email]" value="<?php echo esc_attr($settings['notification_email']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Generic webhook URL', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="url" name="wpal_options[webhook_url]" value="<?php echo esc_attr($settings['webhook_url']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Slack webhook URL', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="url" name="wpal_options[slack_webhook_url]" value="<?php echo esc_attr($settings['slack_webhook_url']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Discord webhook URL', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="url" name="wpal_options[discord_webhook_url]" value="<?php echo esc_attr($settings['discord_webhook_url']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Telegram bot token', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="text" name="wpal_options[telegram_bot_token]" value="<?php echo esc_attr($settings['telegram_bot_token']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Telegram chat ID', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="text" name="wpal_options[telegram_chat_id]" value="<?php echo esc_attr($settings['telegram_chat_id']); ?>">
                </label>
            </div>
        </section>

        <section class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Vulnerability Intelligence', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Connect Wordfence, Patchstack, and WPScan so installed plugins, themes, and WordPress core can be checked against known vulnerabilities.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-form-stack">
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[enable_vulnerability_scanner]" value="1" <?php checked($settings['enable_vulnerability_scanner'], 1); ?>>
                    <span><?php esc_html_e('Enable software vulnerability scanning', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[vulnerability_auto_scan]" value="1" <?php checked($settings['vulnerability_auto_scan'], 1); ?>>
                    <span><?php esc_html_e('Run lightweight weekly scheduled scans', 'wp-activity-logger-pro'); ?></span>
                </label>
                <div>
                    <span><?php esc_html_e('Providers', 'wp-activity-logger-pro'); ?></span>
                    <div class="wpal-check-grid" style="margin-top:8px;">
                        <?php foreach (array('wordfence' => 'Wordfence', 'patchstack' => 'Patchstack', 'wpscan' => 'WPScan') as $provider_key => $provider_label) : ?>
                            <label class="wpal-check-card">
                                <input type="checkbox" name="wpal_options[vulnerability_sources][]" value="<?php echo esc_attr($provider_key); ?>" <?php checked(in_array($provider_key, (array) $settings['vulnerability_sources'], true)); ?>>
                                <span><strong><?php echo esc_html($provider_label); ?></strong></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <span><?php esc_html_e('Software scope', 'wp-activity-logger-pro'); ?></span>
                    <div class="wpal-check-grid" style="margin-top:8px;">
                        <label class="wpal-check-card">
                            <input type="checkbox" name="wpal_options[vulnerability_scan_plugins]" value="1" <?php checked($settings['vulnerability_scan_plugins'], 1); ?>>
                            <span><strong><?php esc_html_e('Plugins', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="wpal-check-card">
                            <input type="checkbox" name="wpal_options[vulnerability_scan_themes]" value="1" <?php checked($settings['vulnerability_scan_themes'], 1); ?>>
                            <span><strong><?php esc_html_e('Themes', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="wpal-check-card">
                            <input type="checkbox" name="wpal_options[vulnerability_scan_core]" value="1" <?php checked($settings['vulnerability_scan_core'], 1); ?>>
                            <span><strong><?php esc_html_e('WordPress core', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                    </div>
                </div>
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[vulnerability_include_file_integrity]" value="1" <?php checked($settings['vulnerability_include_file_integrity'], 1); ?>>
                    <span><?php esc_html_e('Blend file-integrity changes into the software report', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label>
                    <span><?php esc_html_e('Wordfence API key', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="text" name="wpal_options[wordfence_api_key]" value="<?php echo esc_attr($settings['wordfence_api_key']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Patchstack API key', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="text" name="wpal_options[patchstack_api_key]" value="<?php echo esc_attr($settings['patchstack_api_key']); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('WPScan API token', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="text" name="wpal_options[wpscan_api_token]" value="<?php echo esc_attr($settings['wpscan_api_token']); ?>">
                </label>
            </div>
        </section>

        <section class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Alert Filters', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Choose which actions and severities trigger alerts.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-form-stack">
                <div>
                    <span><?php esc_html_e('Alert severities', 'wp-activity-logger-pro'); ?></span>
                    <div class="wpal-check-grid" style="margin-top:8px;">
                        <?php foreach (array('info', 'warning', 'error') as $severity) : ?>
                            <label class="wpal-check-card">
                                <input type="checkbox" name="wpal_options[notification_severities][]" value="<?php echo esc_attr($severity); ?>" <?php checked(in_array($severity, (array) $settings['notification_severities'], true)); ?>>
                                <span><strong><?php echo esc_html(ucfirst($severity)); ?></strong></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <span><?php esc_html_e('Alert event keys', 'wp-activity-logger-pro'); ?></span>
                    <div class="wpal-check-grid" style="margin-top:8px;">
                        <?php foreach (array('login_failed', 'plugin_activated', 'plugin_deactivated', 'theme_switched', 'user_role_changed', 'settings_updated') as $event_key) : ?>
                            <label class="wpal-check-card">
                                <input type="checkbox" name="wpal_options[notification_events][]" value="<?php echo esc_attr($event_key); ?>" <?php checked(in_array($event_key, (array) $settings['notification_events'], true)); ?>>
                                <span><strong><?php echo esc_html($event_key); ?></strong></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[enable_webhook_notifications]" value="1" <?php checked($settings['enable_webhook_notifications'], 1); ?>>
                    <span><?php esc_html_e('Enable generic webhook delivery', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label>
                    <span><?php esc_html_e('Severity override rules', 'wp-activity-logger-pro'); ?></span>
                    <textarea class="wpal-input" rows="4" name="wpal_options[severity_rules]" placeholder="login_failed=error&#10;settings_updated=warning"><?php echo esc_textarea($settings['severity_rules']); ?></textarea>
                </label>
            </div>
        </section>

        <section class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Suppression & Summaries', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Reduce noise, tune what gets recorded, and enable scheduled summaries.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-form-stack">
                <label>
                    <span><?php esc_html_e('Excluded actions', 'wp-activity-logger-pro'); ?></span>
                    <textarea class="wpal-input" rows="4" name="wpal_options[excluded_actions]" placeholder="heartbeat_received, autosave"><?php echo esc_textarea($settings['excluded_actions']); ?></textarea>
                </label>
                <div>
                    <span><?php esc_html_e('Suppressed severities', 'wp-activity-logger-pro'); ?></span>
                    <div class="wpal-check-grid" style="margin-top:8px;">
                        <?php foreach (array('info', 'warning', 'error') as $severity) : ?>
                            <label class="wpal-check-card">
                                <input type="checkbox" name="wpal_options[suppressed_severities][]" value="<?php echo esc_attr($severity); ?>" <?php checked(in_array($severity, (array) $settings['suppressed_severities'], true)); ?>>
                                <span><strong><?php echo esc_html(ucfirst($severity)); ?></strong></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[daily_summary_enabled]" value="1" <?php checked($settings['daily_summary_enabled'], 1); ?>>
                    <span><?php esc_html_e('Send daily summary report', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label>
                    <span><?php esc_html_e('Summary email', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="email" name="wpal_options[daily_summary_email]" value="<?php echo esc_attr($settings['daily_summary_email']); ?>">
                </label>
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[weekly_summary_enabled]" value="1" <?php checked($settings['weekly_summary_enabled'], 1); ?>>
                    <span><?php esc_html_e('Send weekly summary report', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label>
                    <span><?php esc_html_e('Weekly summary email', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input" type="email" name="wpal_options[weekly_summary_email]" value="<?php echo esc_attr($settings['weekly_summary_email']); ?>">
                </label>
                <div class="wpal-grid" style="grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px;">
                    <label>
                        <span><?php esc_html_e('Info retention days', 'wp-activity-logger-pro'); ?></span>
                        <input class="wpal-input" type="number" min="0" name="wpal_options[retention_info_days]" value="<?php echo esc_attr($settings['retention_info_days']); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Warning retention days', 'wp-activity-logger-pro'); ?></span>
                        <input class="wpal-input" type="number" min="0" name="wpal_options[retention_warning_days]" value="<?php echo esc_attr($settings['retention_warning_days']); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Error retention days', 'wp-activity-logger-pro'); ?></span>
                        <input class="wpal-input" type="number" min="0" name="wpal_options[retention_error_days]" value="<?php echo esc_attr($settings['retention_error_days']); ?>">
                    </label>
                </div>
                <label>
                    <span><?php esc_html_e('Action retention rules', 'wp-activity-logger-pro'); ?></span>
                    <textarea class="wpal-input" rows="4" name="wpal_options[retention_action_rules]" placeholder="login_failed=7&#10;plugin_activated=180"><?php echo esc_textarea($settings['retention_action_rules']); ?></textarea>
                </label>
                <label class="wpal-check">
                    <input type="checkbox" name="wpal_options[plugin_changes_locked]" value="1" <?php checked($settings['plugin_changes_locked'], 1); ?>>
                    <span><?php esc_html_e('Disable plugin change capability', 'wp-activity-logger-pro'); ?></span>
                </label>
                <label>
                    <span><?php esc_html_e('Default export format', 'wp-activity-logger-pro'); ?></span>
                    <select class="wpal-input" name="wpal_options[default_export_format]">
                        <?php foreach (array('csv', 'json', 'xml', 'pdf') as $format) : ?>
                            <option value="<?php echo esc_attr($format); ?>" <?php selected($settings['default_export_format'], $format); ?>><?php echo esc_html(strtoupper($format)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="wpal-panel wpal-panel-full">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Privacy Tools', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Export or delete a specific user’s log history when handling privacy requests.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-inline-actions" style="margin-bottom:16px;">
                <input id="wpal-privacy-user-id" class="wpal-input" type="number" min="1" placeholder="<?php esc_attr_e('User ID', 'wp-activity-logger-pro'); ?>" style="max-width:140px;">
                <button type="button" id="wpal-export-user-logs" class="wpal-btn wpal-btn-secondary"><?php esc_html_e('Export User Logs', 'wp-activity-logger-pro'); ?></button>
                <button type="button" id="wpal-delete-user-logs-btn" class="wpal-btn wpal-btn-danger"><?php esc_html_e('Delete User Logs', 'wp-activity-logger-pro'); ?></button>
            </div>
            <div class="wpal-inline-actions">
                <button type="submit" class="wpal-btn wpal-btn-primary"><?php esc_html_e('Save Settings', 'wp-activity-logger-pro'); ?></button>
                <button type="button" id="wpal-reset-settings" class="wpal-btn wpal-btn-danger"><?php esc_html_e('Reset to Defaults', 'wp-activity-logger-pro'); ?></button>
                <span id="wpal-settings-feedback" class="wpal-form-feedback" aria-live="polite"></span>
            </div>
        </section>
    </form>
</div>
