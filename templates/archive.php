<?php
/**
 * Archive template.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
WPAL_Archive::init();
$archive_table = WPAL_Archive::$archive_table;
$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $archive_table));
$logs = array();

if ($exists) {
    $logs = $wpdb->get_results("SELECT * FROM $archive_table ORDER BY archived_at DESC LIMIT 200");
}
?>

<div class="wrap wpal-wrap">
    <section class="wpal-hero wpal-hero-compact">
        <div>
            <p class="wpal-eyebrow"><?php esc_html_e('Storage', 'wp-activity-logger-pro'); ?></p>
            <h1 class="wpal-page-title"><?php esc_html_e('Archive', 'wp-activity-logger-pro'); ?></h1>
            <p class="wpal-hero-copy"><?php esc_html_e('Review archived logs, restore important entries, or permanently remove stale records.', 'wp-activity-logger-pro'); ?></p>
        </div>
    </section>

    <section class="wpal-panel">
        <div class="wpal-panel-head">
            <div>
                <h2><?php esc_html_e('Archived Logs', 'wp-activity-logger-pro'); ?></h2>
                <p><?php echo esc_html(sprintf(_n('%d archived log', '%d archived logs', count($logs), 'wp-activity-logger-pro'), count($logs))); ?></p>
            </div>
        </div>

        <?php if (!$exists || empty($logs)) : ?>
            <p><?php esc_html_e('No archived logs found.', 'wp-activity-logger-pro'); ?></p>
        <?php else : ?>
            <div class="wpal-table-wrap">
                <table class="wpal-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Archived', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('User', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('Action', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></th>
                            <th><?php esc_html_e('Actions', 'wp-activity-logger-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html(WPAL_Helpers::format_datetime($log->archived_at)); ?></td>
                                <td><?php echo esc_html($log->username); ?></td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td><?php echo WPAL_Helpers::get_severity_badge($log->severity); ?></td>
                                <td class="wpal-table-actions">
                                    <button type="button" class="wpal-btn wpal-btn-secondary wpal-restore-log" data-log-id="<?php echo esc_attr($log->id); ?>"><?php esc_html_e('Restore', 'wp-activity-logger-pro'); ?></button>
                                    <button type="button" class="wpal-btn wpal-btn-danger wpal-delete-archived-log" data-log-id="<?php echo esc_attr($log->id); ?>"><?php esc_html_e('Delete', 'wp-activity-logger-pro'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
