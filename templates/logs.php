<?php
/**
 * Logs template.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
WPAL_Helpers::init();
$table_name = WPAL_Helpers::$db_table;

$role_filter = isset($_GET['role_filter']) ? sanitize_text_field(wp_unslash($_GET['role_filter'])) : '';
$severity_filter = isset($_GET['severity_filter']) ? sanitize_text_field(wp_unslash($_GET['severity_filter'])) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$site_filter = isset($_GET['site_id']) ? absint($_GET['site_id']) : 0;
$logs = WPAL_Helpers::get_logs(
    array(
        'role_filter' => $role_filter,
        'severity_filter' => $severity_filter,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'search' => $search,
        'site_id' => $site_filter,
    ),
    500
);
$roles = $wpdb->get_col("SELECT DISTINCT user_role FROM $table_name WHERE user_role <> '' ORDER BY user_role ASC");
$sites = is_multisite() ? get_sites(array('number' => 200)) : array();

$severity_rows = $wpdb->get_results("SELECT severity, COUNT(*) AS total FROM $table_name GROUP BY severity");
$severity_labels = array();
$severity_values = array();
foreach ($severity_rows as $row) {
    $severity_labels[] = ucfirst($row->severity);
    $severity_values[] = (int) $row->total;
}
?>

<div class="wrap wpal-wrap">
    <section class="wpal-hero wpal-hero-compact">
        <div>
            <p class="wpal-eyebrow"><?php esc_html_e('Audit trail', 'wp-activity-logger-pro'); ?></p>
            <h1 class="wpal-page-title"><?php esc_html_e('Activity Logs', 'wp-activity-logger-pro'); ?></h1>
            <p class="wpal-hero-copy"><?php esc_html_e('Filter recent events, inspect full context, archive old entries, and remove noisy records when needed.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="wpal-hero-actions">
            <a class="wpal-btn wpal-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-export')); ?>"><?php esc_html_e('Export', 'wp-activity-logger-pro'); ?></a>
            <a class="wpal-btn wpal-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-archive')); ?>"><?php esc_html_e('Archive', 'wp-activity-logger-pro'); ?></a>
            <button type="button" id="wpal-delete-all-logs" class="wpal-btn wpal-btn-danger"><?php esc_html_e('Delete All', 'wp-activity-logger-pro'); ?></button>
        </div>
    </section>

    <section class="wpal-grid wpal-grid-2">
        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Filters', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Narrow down the event stream quickly.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <form method="get" class="wpal-filter-grid">
                <input type="hidden" name="page" value="wp-activity-logger-pro-logs">
                <label>
                    <span><?php esc_html_e('Search', 'wp-activity-logger-pro'); ?></span>
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" class="wpal-input" placeholder="<?php esc_attr_e('Username, action, description', 'wp-activity-logger-pro'); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Role', 'wp-activity-logger-pro'); ?></span>
                    <select name="role_filter" class="wpal-input">
                        <option value=""><?php esc_html_e('All roles', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($roles as $role) : ?>
                            <option value="<?php echo esc_attr($role); ?>" <?php selected($role_filter, $role); ?>><?php echo esc_html($role); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></span>
                    <select name="severity_filter" class="wpal-input">
                        <option value=""><?php esc_html_e('All severities', 'wp-activity-logger-pro'); ?></option>
                        <option value="info" <?php selected($severity_filter, 'info'); ?>><?php esc_html_e('Info', 'wp-activity-logger-pro'); ?></option>
                        <option value="warning" <?php selected($severity_filter, 'warning'); ?>><?php esc_html_e('Warning', 'wp-activity-logger-pro'); ?></option>
                        <option value="error" <?php selected($severity_filter, 'error'); ?>><?php esc_html_e('Error', 'wp-activity-logger-pro'); ?></option>
                    </select>
                </label>
                <?php if (is_multisite() && is_network_admin()) : ?>
                    <label>
                        <span><?php esc_html_e('Site', 'wp-activity-logger-pro'); ?></span>
                        <select name="site_id" class="wpal-input">
                            <option value="0"><?php esc_html_e('All sites', 'wp-activity-logger-pro'); ?></option>
                            <?php foreach ($sites as $site) : ?>
                                <option value="<?php echo esc_attr($site->blog_id); ?>" <?php selected($site_filter, (int) $site->blog_id); ?>><?php echo esc_html($site->blogname ? $site->blogname : $site->domain); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <label>
                    <span><?php esc_html_e('From', 'wp-activity-logger-pro'); ?></span>
                    <input type="text" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="wpal-input wpal-datepicker" placeholder="YYYY-MM-DD">
                </label>
                <label>
                    <span><?php esc_html_e('To', 'wp-activity-logger-pro'); ?></span>
                    <input type="text" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="wpal-input wpal-datepicker" placeholder="YYYY-MM-DD">
                </label>
                <div class="wpal-filter-actions">
                    <button type="submit" class="wpal-btn wpal-btn-primary"><?php esc_html_e('Apply Filters', 'wp-activity-logger-pro'); ?></button>
                    <a class="wpal-btn wpal-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-logs')); ?>"><?php esc_html_e('Reset', 'wp-activity-logger-pro'); ?></a>
                </div>
            </form>
        </article>

        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Severity Mix', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('A quick view of how noisy the event stream is.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-chart-shell wpal-chart-shell-sm">
                <canvas id="wpal-logs-severity"></canvas>
            </div>
        </article>
    </section>

    <section class="wpal-panel">
        <div class="wpal-panel-head">
            <div>
                <h2><?php esc_html_e('Log Stream', 'wp-activity-logger-pro'); ?></h2>
                <p><?php echo esc_html(sprintf(_n('%d log shown', '%d logs shown', count($logs), 'wp-activity-logger-pro'), count($logs))); ?></p>
            </div>
        </div>

        <?php if (empty($logs)) : ?>
            <p><?php esc_html_e('No logs match the current filters.', 'wp-activity-logger-pro'); ?></p>
        <?php else : ?>
            <div class="wpal-stream">
                <?php foreach ($logs as $log) : ?>
                    <article class="wpal-stream-card">
                        <div class="wpal-stream-head">
                            <div class="wpal-stream-time">
                                <span class="wpal-stream-kicker"><?php esc_html_e('Recorded', 'wp-activity-logger-pro'); ?></span>
                                <strong><?php echo esc_html(WPAL_Helpers::format_datetime($log->time)); ?></strong>
                            </div>
                            <div class="wpal-stream-head-meta">
                                <?php echo WPAL_Helpers::get_severity_badge($log->severity); ?>
                                <?php if (!empty($log->site_label)) : ?>
                                    <span class="wpal-meta-pill"><?php echo esc_html($log->site_label); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="wpal-stream-body">
                            <div class="wpal-stream-primary">
                                <h3><?php echo esc_html($log->description); ?></h3>
                                <p class="wpal-stream-action"><?php echo esc_html($log->action); ?></p>
                            </div>

                            <aside class="wpal-stream-sidebar">
                                <div class="wpal-stream-meta-grid">
                                    <div class="wpal-stream-meta-item">
                                        <span><?php esc_html_e('User', 'wp-activity-logger-pro'); ?></span>
                                        <strong><?php echo esc_html($log->username ? $log->username : __('Guest', 'wp-activity-logger-pro')); ?></strong>
                                        <?php if (!empty($log->user_role)) : ?>
                                            <em><?php echo esc_html($log->user_role); ?></em>
                                        <?php endif; ?>
                                    </div>
                                    <div class="wpal-stream-meta-item">
                                        <span><?php esc_html_e('IP Address', 'wp-activity-logger-pro'); ?></span>
                                        <strong><?php echo esc_html($log->ip ? $log->ip : '—'); ?></strong>
                                    </div>
                                    <div class="wpal-stream-meta-item">
                                        <span><?php esc_html_e('Event ID', 'wp-activity-logger-pro'); ?></span>
                                        <strong>#<?php echo esc_html($log->id); ?></strong>
                                    </div>
                                </div>

                                <div class="wpal-stream-actions">
                                    <button type="button" class="wpal-btn wpal-btn-secondary wpal-view-log" data-log-id="<?php echo esc_attr($log->id); ?>" data-site-id="<?php echo esc_attr(isset($log->site_id) ? $log->site_id : 0); ?>"><?php esc_html_e('View Details', 'wp-activity-logger-pro'); ?></button>
                                    <button type="button" class="wpal-btn wpal-btn-secondary wpal-archive-log" data-log-id="<?php echo esc_attr($log->id); ?>" data-site-id="<?php echo esc_attr(isset($log->site_id) ? $log->site_id : 0); ?>"><?php esc_html_e('Archive', 'wp-activity-logger-pro'); ?></button>
                                    <button type="button" class="wpal-btn wpal-btn-danger wpal-delete-log" data-log-id="<?php echo esc_attr($log->id); ?>" data-site-id="<?php echo esc_attr(isset($log->site_id) ? $log->site_id : 0); ?>"><?php esc_html_e('Delete', 'wp-activity-logger-pro'); ?></button>
                                </div>
                            </aside>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div id="wpal-log-details-modal" class="wpal-modal">
    <div class="wpal-modal-dialog">
        <button type="button" class="wpal-modal-close" aria-label="<?php esc_attr_e('Close', 'wp-activity-logger-pro'); ?>">×</button>
        <div class="wpal-modal-body"></div>
    </div>
</div>

<script>
window.wpalLogsSeverity = {
    labels: <?php echo wp_json_encode($severity_labels); ?>,
    values: <?php echo wp_json_encode($severity_values); ?>
};
</script>
