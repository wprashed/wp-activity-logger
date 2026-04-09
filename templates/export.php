<?php
/**
 * Export template.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
TracePilot_Helpers::init();
$table_name = TracePilot_Helpers::$db_table;
$settings = TracePilot_Helpers::get_settings();
$users = $wpdb->get_col("SELECT DISTINCT username FROM $table_name WHERE username <> '' ORDER BY username ASC");
$actions = $wpdb->get_col("SELECT DISTINCT action FROM $table_name ORDER BY action ASC");
$export_columns = array(
    __('Time', 'wp-activity-logger-pro'),
    __('User', 'wp-activity-logger-pro'),
    __('Role', 'wp-activity-logger-pro'),
    __('Action', 'wp-activity-logger-pro'),
    __('Severity', 'wp-activity-logger-pro'),
    __('IP', 'wp-activity-logger-pro'),
    __('Browser', 'wp-activity-logger-pro'),
    __('Object', 'wp-activity-logger-pro'),
    __('Description', 'wp-activity-logger-pro'),
);
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Compliance & reporting', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Export Logs', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Download filtered activity data for audits, troubleshooting, or external analysis.', 'wp-activity-logger-pro'); ?></p>
        </div>
    </section>

    <section class="tracepilot-grid tracepilot-grid-2">
        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Export Filters', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Choose the slice of activity you want to download.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" class="tracepilot-form-stack">
                <input type="hidden" name="action" value="tracepilot_export_logs">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('tracepilot_nonce')); ?>">
                <label>
                    <span><?php esc_html_e('Format', 'wp-activity-logger-pro'); ?></span>
                    <select class="tracepilot-input" name="format">
                        <?php foreach (array('csv', 'json', 'xml', 'pdf') as $format) : ?>
                            <option value="<?php echo esc_attr($format); ?>" <?php selected($settings['default_export_format'], $format); ?>><?php echo esc_html(strtoupper($format)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('User', 'wp-activity-logger-pro'); ?></span>
                    <select class="tracepilot-input" name="user">
                        <option value=""><?php esc_html_e('All users', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user); ?>"><?php echo esc_html($user); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Action', 'wp-activity-logger-pro'); ?></span>
                    <select class="tracepilot-input" name="action_filter">
                        <option value=""><?php esc_html_e('All actions', 'wp-activity-logger-pro'); ?></option>
                        <?php foreach ($actions as $action) : ?>
                            <option value="<?php echo esc_attr($action); ?>"><?php echo esc_html($action); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></span>
                    <select class="tracepilot-input" name="severity">
                        <option value=""><?php esc_html_e('All severities', 'wp-activity-logger-pro'); ?></option>
                        <option value="info"><?php esc_html_e('Info', 'wp-activity-logger-pro'); ?></option>
                        <option value="warning"><?php esc_html_e('Warning', 'wp-activity-logger-pro'); ?></option>
                        <option value="error"><?php esc_html_e('Error', 'wp-activity-logger-pro'); ?></option>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Date from', 'wp-activity-logger-pro'); ?></span>
                    <input class="tracepilot-input tracepilot-datepicker" type="text" name="date_from" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wp-activity-logger-pro'); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Date to', 'wp-activity-logger-pro'); ?></span>
                    <input class="tracepilot-input tracepilot-datepicker" type="text" name="date_to" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wp-activity-logger-pro'); ?>">
                </label>
                <button type="submit" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Download Export', 'wp-activity-logger-pro'); ?></button>
            </form>
        </article>

        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Included Columns', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Exports include the fields most useful for audit and incident review.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-list">
                <?php foreach ($export_columns as $column) : ?>
                    <div class="tracepilot-list-row">
                        <div><strong><?php echo esc_html($column); ?></strong></div>
                        <div class="tracepilot-list-value">✓</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</div>
