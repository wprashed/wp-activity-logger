<?php
/**
 * Dashboard template.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
TracePilot_Helpers::init();
$table_name = TracePilot_Helpers::$db_table;

$metrics = TracePilot_Helpers::get_dashboard_metrics();
$series = TracePilot_Helpers::get_activity_series(14);

$top_actions = $wpdb->get_results(
    "SELECT action, COUNT(*) AS total
    FROM $table_name
    GROUP BY action
    ORDER BY total DESC
    LIMIT 6"
);

$recent_logs = TracePilot_Helpers::get_logs(array(), 8);
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Security, audit, and reporting', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Activity Logger Dashboard', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Monitor user activity, review suspicious behavior, and export the events that matter from one clean workspace.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="tracepilot-hero-actions">
            <a class="tracepilot-btn tracepilot-btn-primary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-logs')); ?>"><?php esc_html_e('Open Logs', 'wp-activity-logger-pro'); ?></a>
            <a class="tracepilot-btn tracepilot-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-analytics')); ?>"><?php esc_html_e('View Analytics', 'wp-activity-logger-pro'); ?></a>
            <a class="tracepilot-btn tracepilot-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-export')); ?>"><?php esc_html_e('Export Data', 'wp-activity-logger-pro'); ?></a>
        </div>
    </section>

    <section class="tracepilot-stats-grid">
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Total Logs', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html(number_format_i18n($metrics['total_logs'])); ?></strong>
            <span class="tracepilot-stat-meta"><?php esc_html_e('All recorded events', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Today', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html(number_format_i18n($metrics['today_logs'])); ?></strong>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Events in the last 24 hours', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Active Users', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html(number_format_i18n($metrics['unique_users'])); ?></strong>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Unique users recorded', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Warnings', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html(number_format_i18n($metrics['warnings'])); ?></strong>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Warning and error-level logs', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Open Threats', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html(number_format_i18n($metrics['open_threats'])); ?></strong>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Threats still marked new', 'wp-activity-logger-pro'); ?></span>
        </article>
    </section>

    <section class="tracepilot-grid tracepilot-grid-2">
        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Activity Trend', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Last 14 days of recorded activity.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-chart-shell">
                <canvas id="tracepilot-dashboard-trend"></canvas>
            </div>
        </article>

        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Quick Links', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Jump straight into the workflow you need.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-quick-links">
                <a class="tracepilot-quick-link" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-threat-detection')); ?>">
                    <strong><?php esc_html_e('Threat Detection', 'wp-activity-logger-pro'); ?></strong>
                    <span><?php esc_html_e('Review brute-force, unusual location, and privilege alerts.', 'wp-activity-logger-pro'); ?></span>
                </a>
                <a class="tracepilot-quick-link" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-settings')); ?>">
                    <strong><?php esc_html_e('Notification Rules', 'wp-activity-logger-pro'); ?></strong>
                    <span><?php esc_html_e('Tune email, webhook, Slack, and Discord routing.', 'wp-activity-logger-pro'); ?></span>
                </a>
                <a class="tracepilot-quick-link" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-export')); ?>">
                    <strong><?php esc_html_e('Compliance Export', 'wp-activity-logger-pro'); ?></strong>
                    <span><?php esc_html_e('Download filtered logs for audits or troubleshooting.', 'wp-activity-logger-pro'); ?></span>
                </a>
            </div>
        </article>
    </section>

    <section class="tracepilot-grid tracepilot-grid-2">
        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Top Actions', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Most common activity types right now.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-list">
                <?php if (empty($top_actions)) : ?>
                    <p><?php esc_html_e('No actions recorded yet.', 'wp-activity-logger-pro'); ?></p>
                <?php else : ?>
                    <?php foreach ($top_actions as $action) : ?>
                        <div class="tracepilot-list-row">
                            <div>
                                <strong><?php echo esc_html($action->action); ?></strong>
                            </div>
                            <div class="tracepilot-list-value"><?php echo esc_html(number_format_i18n($action->total)); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Recent Activity', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Latest events across the site.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-list">
                <?php if (empty($recent_logs)) : ?>
                    <p><?php esc_html_e('No logs found yet.', 'wp-activity-logger-pro'); ?></p>
                <?php else : ?>
                    <?php foreach ($recent_logs as $log) : ?>
                        <button type="button" class="tracepilot-list-row tracepilot-list-row-button tracepilot-view-log" data-log-id="<?php echo esc_attr($log->id); ?>">
                            <div>
                                <strong><?php echo esc_html($log->action); ?></strong>
                                <div class="tracepilot-list-subtext"><?php echo esc_html($log->username); ?> • <?php echo esc_html(TracePilot_Helpers::format_datetime($log->time)); ?><?php echo !empty($log->site_label) ? ' • ' . esc_html($log->site_label) : ''; ?></div>
                            </div>
                            <div><?php echo TracePilot_Helpers::get_severity_badge($log->severity); ?></div>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>
</div>

<div id="tracepilot-log-details-modal" class="tracepilot-modal">
    <div class="tracepilot-modal-dialog">
        <button type="button" class="tracepilot-modal-close" aria-label="<?php esc_attr_e('Close', 'wp-activity-logger-pro'); ?>">×</button>
        <div class="tracepilot-modal-body"></div>
    </div>
</div>

<script>
window.tracepilotDashboardTrend = <?php echo wp_json_encode($series); ?>;
</script>
