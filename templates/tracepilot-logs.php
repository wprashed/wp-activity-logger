<?php
/**
 * Logs template.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
TracePilot_Helpers::init();
$table_name = TracePilot_Helpers::$db_table;

$role_filter = isset($_GET['role_filter']) ? sanitize_text_field(wp_unslash($_GET['role_filter'])) : '';
$severity_filter = isset($_GET['severity_filter']) ? sanitize_text_field(wp_unslash($_GET['severity_filter'])) : '';
$action_filter = isset($_GET['action_filter']) ? sanitize_text_field(wp_unslash($_GET['action_filter'])) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$site_filter = isset($_GET['site_id']) ? absint($_GET['site_id']) : 0;
$severity_display = array(
    'info' => __('Info', 'wp-activity-logger-pro'),
    'warning' => __('Warning', 'wp-activity-logger-pro'),
    'error' => __('Error', 'wp-activity-logger-pro'),
);
$logs = TracePilot_Helpers::get_logs(
    array(
        'role_filter' => $role_filter,
        'severity_filter' => $severity_filter,
        'action_filter' => $action_filter,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'search' => $search,
        'site_id' => $site_filter,
    ),
    500
);
$roles = $wpdb->get_col("SELECT DISTINCT user_role FROM $table_name WHERE user_role <> '' ORDER BY user_role ASC");
$actions = $wpdb->get_col("SELECT DISTINCT action FROM $table_name WHERE action <> '' ORDER BY action ASC");
$sites = is_multisite() ? get_sites(array('number' => 200)) : array();

$severity_rows = $wpdb->get_results("SELECT severity, COUNT(*) AS total FROM $table_name GROUP BY severity");
$severity_labels = array();
$severity_values = array();
foreach ($severity_rows as $row) {
    $severity_labels[] = ucfirst($row->severity);
    $severity_values[] = (int) $row->total;
}
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Audit trail', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Activity Logs', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Filter recent events, inspect full context, archive old entries, and remove noisy records when needed.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="tracepilot-hero-actions">
            <a class="tracepilot-btn tracepilot-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-export')); ?>"><?php esc_html_e('Export', 'wp-activity-logger-pro'); ?></a>
            <a class="tracepilot-btn tracepilot-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-archive')); ?>"><?php esc_html_e('Archive', 'wp-activity-logger-pro'); ?></a>
            <button type="button" id="tracepilot-delete-all-logs" class="tracepilot-btn tracepilot-btn-danger"><?php esc_html_e('Delete All', 'wp-activity-logger-pro'); ?></button>
        </div>
    </section>

    <section class="tracepilot-grid tracepilot-grid-2">
        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Filters', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Narrow down the event stream quickly.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <form method="get" class="tracepilot-filter-grid">
                <input type="hidden" name="page" value="wp-activity-logger-pro-logs">
                <label>
                    <span><?php esc_html_e('Search', 'wp-activity-logger-pro'); ?></span>
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" class="tracepilot-input" placeholder="<?php esc_attr_e('Username, action, description', 'wp-activity-logger-pro'); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Role', 'wp-activity-logger-pro'); ?></span>
                    <select name="role_filter" class="tracepilot-input">
                        <option value=""><?php esc_html_e('All roles', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($roles as $role) : ?>
                            <option value="<?php echo esc_attr($role); ?>" <?php selected($role_filter, $role); ?>><?php echo esc_html($role); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></span>
                    <select name="severity_filter" class="tracepilot-input">
                        <option value=""><?php esc_html_e('All severities', 'wp-activity-logger-pro'); ?></option>
                        <option value="info" <?php selected($severity_filter, 'info'); ?>><?php esc_html_e('Info', 'wp-activity-logger-pro'); ?></option>
                        <option value="warning" <?php selected($severity_filter, 'warning'); ?>><?php esc_html_e('Warning', 'wp-activity-logger-pro'); ?></option>
                        <option value="error" <?php selected($severity_filter, 'error'); ?>><?php esc_html_e('Error', 'wp-activity-logger-pro'); ?></option>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Action', 'wp-activity-logger-pro'); ?></span>
                    <select name="action_filter" class="tracepilot-input">
                        <option value=""><?php esc_html_e('All actions', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($actions as $action) : ?>
                            <option value="<?php echo esc_attr($action); ?>" <?php selected($action_filter, $action); ?>><?php echo esc_html($action); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if (is_multisite() && is_network_admin()) : ?>
                    <label>
                        <span><?php esc_html_e('Site', 'wp-activity-logger-pro'); ?></span>
                        <select name="site_id" class="tracepilot-input">
                            <option value="0"><?php esc_html_e('All sites', 'wp-activity-logger-pro'); ?></option>
                            <?php foreach ($sites as $site) : ?>
                                <option value="<?php echo esc_attr($site->blog_id); ?>" <?php selected($site_filter, (int) $site->blog_id); ?>><?php echo esc_html($site->blogname ? $site->blogname : $site->domain); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <label>
                    <span><?php esc_html_e('From', 'wp-activity-logger-pro'); ?></span>
                    <input type="text" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="tracepilot-input tracepilot-datepicker" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wp-activity-logger-pro'); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('To', 'wp-activity-logger-pro'); ?></span>
                    <input type="text" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="tracepilot-input tracepilot-datepicker" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wp-activity-logger-pro'); ?>">
                </label>
                <div class="tracepilot-filter-actions">
                    <button type="submit" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Apply Filters', 'wp-activity-logger-pro'); ?></button>
                    <a class="tracepilot-btn tracepilot-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-logs')); ?>"><?php esc_html_e('Reset', 'wp-activity-logger-pro'); ?></a>
                </div>
            </form>
        </article>

        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Severity Mix', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('A quick view of how noisy the event stream is.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-chart-shell tracepilot-chart-shell-sm">
                <canvas id="tracepilot-logs-severity"></canvas>
            </div>
        </article>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2><?php esc_html_e('Log Stream', 'wp-activity-logger-pro'); ?></h2>
                <p><?php echo esc_html(sprintf(_n('%d log shown', '%d logs shown', count($logs), 'wp-activity-logger-pro'), count($logs))); ?></p>
            </div>
        </div>

        <form method="get" class="tracepilot-log-filter-bar">
            <input type="hidden" name="page" value="wp-activity-logger-pro-logs">
            <label class="tracepilot-log-filter-search">
                <span><?php esc_html_e('Search', 'wp-activity-logger-pro'); ?></span>
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" class="tracepilot-input" placeholder="<?php esc_attr_e('Search usernames, actions, descriptions', 'wp-activity-logger-pro'); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></span>
                <select name="severity_filter" class="tracepilot-input">
                    <option value=""><?php esc_html_e('All severities', 'wp-activity-logger-pro'); ?></option>
                    <option value="info" <?php selected($severity_filter, 'info'); ?>><?php esc_html_e('Info', 'wp-activity-logger-pro'); ?></option>
                    <option value="warning" <?php selected($severity_filter, 'warning'); ?>><?php esc_html_e('Warning', 'wp-activity-logger-pro'); ?></option>
                    <option value="error" <?php selected($severity_filter, 'error'); ?>><?php esc_html_e('Error', 'wp-activity-logger-pro'); ?></option>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Action', 'wp-activity-logger-pro'); ?></span>
                <select name="action_filter" class="tracepilot-input">
                    <option value=""><?php esc_html_e('All actions', 'wp-activity-logger-pro'); ?></option>
                    <?php foreach ($actions as $action) : ?>
                        <option value="<?php echo esc_attr($action); ?>" <?php selected($action_filter, $action); ?>><?php echo esc_html($action); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Role', 'wp-activity-logger-pro'); ?></span>
                <select name="role_filter" class="tracepilot-input">
                    <option value=""><?php esc_html_e('All roles', 'wp-activity-logger-pro'); ?></option>
                    <?php foreach ($roles as $role) : ?>
                        <option value="<?php echo esc_attr($role); ?>" <?php selected($role_filter, $role); ?>><?php echo esc_html($role); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('From', 'wp-activity-logger-pro'); ?></span>
                <input type="text" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="tracepilot-input tracepilot-datepicker" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wp-activity-logger-pro'); ?>">
            </label>
            <label>
                <span><?php esc_html_e('To', 'wp-activity-logger-pro'); ?></span>
                <input type="text" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="tracepilot-input tracepilot-datepicker" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wp-activity-logger-pro'); ?>">
            </label>
            <?php if (is_multisite() && is_network_admin()) : ?>
                <label>
                    <span><?php esc_html_e('Site', 'wp-activity-logger-pro'); ?></span>
                    <select name="site_id" class="tracepilot-input">
                        <option value="0"><?php esc_html_e('All sites', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($sites as $site) : ?>
                            <option value="<?php echo esc_attr($site->blog_id); ?>" <?php selected($site_filter, (int) $site->blog_id); ?>><?php echo esc_html($site->blogname ? $site->blogname : $site->domain); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <div class="tracepilot-filter-actions">
                <button type="submit" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Filter Logs', 'wp-activity-logger-pro'); ?></button>
                <a class="tracepilot-btn tracepilot-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-logger-pro-logs')); ?>"><?php esc_html_e('Clear', 'wp-activity-logger-pro'); ?></a>
            </div>
        </form>

        <?php if ($search || $severity_filter || $action_filter || $role_filter || $date_from || $date_to || $site_filter) : ?>
            <div class="tracepilot-toolbar-pills">
                <?php if ($search) : ?><span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('Search: %s', 'wp-activity-logger-pro'), $search)); ?></span><?php endif; ?>
                <?php if ($severity_filter) : ?><span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('Severity: %s', 'wp-activity-logger-pro'), isset($severity_display[ $severity_filter ]) ? $severity_display[ $severity_filter ] : $severity_filter)); ?></span><?php endif; ?>
                <?php if ($action_filter) : ?><span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('Action: %s', 'wp-activity-logger-pro'), $action_filter)); ?></span><?php endif; ?>
                <?php if ($role_filter) : ?><span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('Role: %s', 'wp-activity-logger-pro'), $role_filter)); ?></span><?php endif; ?>
                <?php if ($date_from) : ?><span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('From: %s', 'wp-activity-logger-pro'), $date_from)); ?></span><?php endif; ?>
                <?php if ($date_to) : ?><span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('To: %s', 'wp-activity-logger-pro'), $date_to)); ?></span><?php endif; ?>
                <?php if ($site_filter && is_multisite() && is_network_admin()) : ?><span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('Site ID: %d', 'wp-activity-logger-pro'), $site_filter)); ?></span><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($logs)) : ?>
            <p><?php esc_html_e('No logs match the current filters.', 'wp-activity-logger-pro'); ?></p>
        <?php else : ?>
            <div class="tracepilot-stream">
                <?php foreach ($logs as $log) : ?>
                    <article class="tracepilot-stream-card">
                        <div class="tracepilot-stream-head">
                            <div class="tracepilot-stream-time">
                                <span class="tracepilot-stream-kicker"><?php esc_html_e('Recorded', 'wp-activity-logger-pro'); ?></span>
                                <strong><?php echo esc_html(TracePilot_Helpers::format_datetime($log->time)); ?></strong>
                            </div>
                            <div class="tracepilot-stream-head-meta">
                                <?php echo TracePilot_Helpers::get_severity_badge($log->severity); ?>
                                <?php if (!empty($log->site_label)) : ?>
                                    <span class="tracepilot-meta-pill"><?php echo esc_html($log->site_label); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tracepilot-stream-body">
                            <div class="tracepilot-stream-primary">
                                <h3><?php echo esc_html($log->description); ?></h3>
                                <p class="tracepilot-stream-action"><?php echo esc_html($log->action); ?></p>
                            </div>

                            <aside class="tracepilot-stream-sidebar">
                                <div class="tracepilot-stream-meta-grid">
                                    <div class="tracepilot-stream-meta-item">
                                        <span><?php esc_html_e('User', 'wp-activity-logger-pro'); ?></span>
                                        <strong><?php echo esc_html($log->username ? $log->username : __('Guest', 'wp-activity-logger-pro')); ?></strong>
                                        <?php if (!empty($log->user_role)) : ?>
                                            <em><?php echo esc_html($log->user_role); ?></em>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tracepilot-stream-meta-item">
                                        <span><?php esc_html_e('IP Address', 'wp-activity-logger-pro'); ?></span>
                                        <strong><?php echo esc_html($log->ip ? $log->ip : '—'); ?></strong>
                                    </div>
                                    <div class="tracepilot-stream-meta-item">
                                        <span><?php esc_html_e('Event ID', 'wp-activity-logger-pro'); ?></span>
                                        <strong>#<?php echo esc_html($log->id); ?></strong>
                                    </div>
                                </div>

                                <div class="tracepilot-stream-actions">
                                    <button type="button" class="tracepilot-btn tracepilot-btn-secondary tracepilot-view-log" data-log-id="<?php echo esc_attr($log->id); ?>" data-site-id="<?php echo esc_attr(isset($log->site_id) ? $log->site_id : 0); ?>"><?php esc_html_e('View Details', 'wp-activity-logger-pro'); ?></button>
                                    <button type="button" class="tracepilot-btn tracepilot-btn-secondary tracepilot-archive-log" data-log-id="<?php echo esc_attr($log->id); ?>" data-site-id="<?php echo esc_attr(isset($log->site_id) ? $log->site_id : 0); ?>"><?php esc_html_e('Archive', 'wp-activity-logger-pro'); ?></button>
                                    <button type="button" class="tracepilot-btn tracepilot-btn-danger tracepilot-delete-log" data-log-id="<?php echo esc_attr($log->id); ?>" data-site-id="<?php echo esc_attr(isset($log->site_id) ? $log->site_id : 0); ?>"><?php esc_html_e('Delete', 'wp-activity-logger-pro'); ?></button>
                                </div>
                            </aside>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div id="tracepilot-log-details-modal" class="tracepilot-modal">
    <div class="tracepilot-modal-dialog">
        <button type="button" class="tracepilot-modal-close" aria-label="<?php esc_attr_e('Close', 'wp-activity-logger-pro'); ?>">×</button>
        <div class="tracepilot-modal-body"></div>
    </div>
</div>

<script>
window.tracepilotLogsSeverity = {
    labels: <?php echo wp_json_encode($severity_labels); ?>,
    values: <?php echo wp_json_encode($severity_values); ?>
};
</script>
