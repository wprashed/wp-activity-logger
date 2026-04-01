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

$where = array('1=1');
$args = array();

if ($role_filter) {
    $where[] = 'user_role = %s';
    $args[] = $role_filter;
}

if ($severity_filter) {
    $where[] = 'severity = %s';
    $args[] = $severity_filter;
}

if ($date_from) {
    $where[] = 'time >= %s';
    $args[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where[] = 'time <= %s';
    $args[] = $date_to . ' 23:59:59';
}

if ($search) {
    $where[] = '(username LIKE %s OR action LIKE %s OR description LIKE %s)';
    $like = '%' . $wpdb->esc_like($search) . '%';
    $args[] = $like;
    $args[] = $like;
    $args[] = $like;
}

$query = "SELECT * FROM $table_name WHERE " . implode(' AND ', $where) . ' ORDER BY time DESC LIMIT 500';
if (!empty($args)) {
    $query = $wpdb->prepare($query, $args);
}

$logs = $wpdb->get_results($query);
$roles = $wpdb->get_col("SELECT DISTINCT user_role FROM $table_name WHERE user_role <> '' ORDER BY user_role ASC");

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
            <div class="wpal-table-wrap">
                <table id="wpal-logs-table" class="wpal-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Time', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('User', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('Action', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('Description', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('IP', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('Actions', 'wp-activity-logger-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html(WPAL_Helpers::format_datetime($log->time)); ?></td>
                                <td>
                                    <strong><?php echo esc_html($log->username); ?></strong>
                                    <?php if (!empty($log->user_role)) : ?>
                                        <span class="wpal-meta-pill"><?php echo esc_html($log->user_role); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td><?php echo esc_html(wp_trim_words($log->description, 14)); ?></td>
                                <td><?php echo WPAL_Helpers::get_severity_badge($log->severity); ?></td>
                                <td><?php echo esc_html($log->ip ? $log->ip : '—'); ?></td>
                                <td class="wpal-table-actions">
                                    <button type="button" class="wpal-btn wpal-btn-secondary wpal-view-log" data-log-id="<?php echo esc_attr($log->id); ?>"><?php esc_html_e('View', 'wp-activity-logger-pro'); ?></button>
                                    <button type="button" class="wpal-btn wpal-btn-secondary wpal-archive-log" data-log-id="<?php echo esc_attr($log->id); ?>"><?php esc_html_e('Archive', 'wp-activity-logger-pro'); ?></button>
                                    <button type="button" class="wpal-btn wpal-btn-danger wpal-delete-log" data-log-id="<?php echo esc_attr($log->id); ?>"><?php esc_html_e('Delete', 'wp-activity-logger-pro'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
