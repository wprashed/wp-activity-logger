<?php
/**
 * Export template.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
WPAL_Helpers::init();
$table_name = WPAL_Helpers::$db_table;
$settings = WPAL_Helpers::get_settings();
$users = $wpdb->get_col("SELECT DISTINCT username FROM $table_name WHERE username <> '' ORDER BY username ASC");
$actions = $wpdb->get_col("SELECT DISTINCT action FROM $table_name ORDER BY action ASC");
?>

<div class="wrap wpal-wrap">
    <section class="wpal-hero wpal-hero-compact">
        <div>
            <p class="wpal-eyebrow"><?php esc_html_e('Compliance & reporting', 'wp-activity-logger-pro'); ?></p>
            <h1 class="wpal-page-title"><?php esc_html_e('Export Logs', 'wp-activity-logger-pro'); ?></h1>
            <p class="wpal-hero-copy"><?php esc_html_e('Download filtered activity data for audits, troubleshooting, or external analysis.', 'wp-activity-logger-pro'); ?></p>
        </div>
    </section>

    <section class="wpal-grid wpal-grid-2">
        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Export Filters', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Choose the slice of activity you want to download.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" class="wpal-form-stack">
                <input type="hidden" name="action" value="wpal_export_logs">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wpal_nonce')); ?>">
                <label>
                    <span><?php esc_html_e('Format', 'wp-activity-logger-pro'); ?></span>
                    <select class="wpal-input" name="format">
                        <?php foreach (array('csv', 'json', 'xml', 'pdf') as $format) : ?>
                            <option value="<?php echo esc_attr($format); ?>" <?php selected($settings['default_export_format'], $format); ?>><?php echo esc_html(strtoupper($format)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('User', 'wp-activity-logger-pro'); ?></span>
                    <select class="wpal-input" name="user">
                        <option value=""><?php esc_html_e('All users', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user); ?>"><?php echo esc_html($user); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Action', 'wp-activity-logger-pro'); ?></span>
                    <select class="wpal-input" name="action_filter">
                        <option value=""><?php esc_html_e('All actions', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($actions as $action) : ?>
                            <option value="<?php echo esc_attr($action); ?>"><?php echo esc_html($action); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></span>
                    <select class="wpal-input" name="severity">
                        <option value=""><?php esc_html_e('All severities', 'wp-activity-logger-pro'); ?></option>
                        <option value="info"><?php esc_html_e('Info', 'wp-activity-logger-pro'); ?></option>
                        <option value="warning"><?php esc_html_e('Warning', 'wp-activity-logger-pro'); ?></option>
                        <option value="error"><?php esc_html_e('Error', 'wp-activity-logger-pro'); ?></option>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Date from', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input wpal-datepicker" type="text" name="date_from" placeholder="YYYY-MM-DD">
                </label>
                <label>
                    <span><?php esc_html_e('Date to', 'wp-activity-logger-pro'); ?></span>
                    <input class="wpal-input wpal-datepicker" type="text" name="date_to" placeholder="YYYY-MM-DD">
                </label>
                <button type="submit" class="wpal-btn wpal-btn-primary"><?php esc_html_e('Download Export', 'wp-activity-logger-pro'); ?></button>
            </form>
        </article>

        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Included Columns', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Exports include the fields most useful for audit and incident review.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-list">
                <?php foreach (array('Time', 'User', 'Role', 'Action', 'Severity', 'IP', 'Browser', 'Object', 'Description') as $column) : ?>
                    <div class="wpal-list-row">
                        <div><strong><?php echo esc_html($column); ?></strong></div>
                        <div class="wpal-list-value">✓</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</div>
