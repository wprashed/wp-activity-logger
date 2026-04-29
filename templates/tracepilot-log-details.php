<?php
/**
 * Log details template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$log_id = isset($_POST['log_id']) ? absint(wp_unslash($_POST['log_id'])) : 0;
if (!$log_id) {
    echo '<p>' . esc_html__('Invalid log ID.', 'tracepilot') . '</p>';
    return;
}

global $wpdb;
TracePilot_Helpers::init();
$table_name = TracePilot_Helpers::$db_table;
$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id));

if (!$log) {
    echo '<p>' . esc_html__('Log not found.', 'tracepilot') . '</p>';
    return;
}

$context = !empty($log->context) ? json_decode($log->context, true) : array();
$settings = TracePilot_Helpers::get_settings();
$display_ip = !empty($log->ip) ? TracePilot_Helpers::format_ip_for_display($log->ip) : '';
$allow_ip_actions = !TracePilot_Helpers::should_mask_ip_in_ui();
$window_hours = max(1, absint($settings['timeline_window_hours']));

$timeline = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, time, action, severity, description
        FROM $table_name
        WHERE id != %d
        AND (
            (user_id > 0 AND user_id = %d)
            OR
            (ip <> '' AND ip = %s)
        )
        AND time BETWEEN DATE_SUB(%s, INTERVAL %d HOUR) AND DATE_ADD(%s, INTERVAL %d HOUR)
        ORDER BY time ASC
        LIMIT 12",
        $log->id,
        absint($log->user_id),
        $log->ip,
        $log->time,
        $window_hours,
        $log->time,
        $window_hours
    )
);
?>

<div class="tracepilot-detail">
    <div class="tracepilot-detail-header">
        <div>
            <p class="tracepilot-eyebrow"><?php echo esc_html($log->action); ?></p>
            <h2><?php echo esc_html($log->description); ?></h2>
        </div>
        <div><?php echo TracePilot_Helpers::get_severity_badge($log->severity); ?></div>
    </div>

    <div class="tracepilot-detail-grid">
        <div class="tracepilot-detail-card">
            <h3><?php esc_html_e('Event', 'tracepilot'); ?></h3>
            <dl>
                <dt><?php esc_html_e('Time', 'tracepilot'); ?></dt>
                <dd><?php echo esc_html(TracePilot_Helpers::format_datetime($log->time)); ?></dd>
                <dt><?php esc_html_e('Action', 'tracepilot'); ?></dt>
                <dd><?php echo esc_html($log->action); ?></dd>
                <dt><?php esc_html_e('Object', 'tracepilot'); ?></dt>
                <dd><?php echo esc_html($log->object_name ? $log->object_name : '—'); ?></dd>
            </dl>
        </div>

        <div class="tracepilot-detail-card">
            <h3><?php esc_html_e('Actor', 'tracepilot'); ?></h3>
            <dl>
                <dt><?php esc_html_e('Username', 'tracepilot'); ?></dt>
                <dd><?php echo esc_html($log->username); ?></dd>
                <dt><?php esc_html_e('Role', 'tracepilot'); ?></dt>
                <dd><?php echo esc_html($log->user_role ? $log->user_role : '—'); ?></dd>
                <dt><?php esc_html_e('IP', 'tracepilot'); ?></dt>
                <dd><?php echo esc_html($display_ip ? $display_ip : '—'); ?></dd>
                <dt><?php esc_html_e('Browser', 'tracepilot'); ?></dt>
                <dd><?php echo esc_html($log->browser ? $log->browser : '—'); ?></dd>
            </dl>
            <div class="tracepilot-inline-actions" style="margin-top:14px;">
                <?php if (!empty($log->ip) && $allow_ip_actions) : ?>
                    <button type="button" class="tracepilot-btn tracepilot-btn-secondary tracepilot-block-ip" data-ip="<?php echo esc_attr($log->ip); ?>"><?php esc_html_e('Block IP', 'tracepilot'); ?></button>
                <?php endif; ?>
                <?php if (!empty($log->user_id)) : ?>
                    <button type="button" class="tracepilot-btn tracepilot-btn-secondary tracepilot-force-logout" data-user-id="<?php echo esc_attr($log->user_id); ?>"><?php esc_html_e('Force Logout', 'tracepilot'); ?></button>
                    <button type="button" class="tracepilot-btn tracepilot-btn-secondary tracepilot-reset-password" data-user-id="<?php echo esc_attr($log->user_id); ?>"><?php esc_html_e('Reset Password', 'tracepilot'); ?></button>
                    <button type="button" class="tracepilot-btn tracepilot-btn-danger tracepilot-delete-user-logs" data-user-id="<?php echo esc_attr($log->user_id); ?>"><?php esc_html_e('Delete User Logs', 'tracepilot'); ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($context) && is_array($context)) : ?>
        <div class="tracepilot-detail-card">
            <h3><?php esc_html_e('Context', 'tracepilot'); ?></h3>
            <pre class="tracepilot-code-block"><?php echo esc_html(wp_json_encode($context, JSON_PRETTY_PRINT)); ?></pre>
        </div>
    <?php endif; ?>

    <div class="tracepilot-detail-card">
        <h3><?php echo esc_html(sprintf(__('Session timeline (%d hour window)', 'tracepilot'), $window_hours)); ?></h3>
        <?php if (empty($timeline)) : ?>
            <p><?php esc_html_e('No nearby events were found for this user or IP.', 'tracepilot'); ?></p>
        <?php else : ?>
            <div class="tracepilot-list">
                <?php foreach ($timeline as $entry) : ?>
                    <div class="tracepilot-list-row">
                        <div>
                            <strong><?php echo esc_html($entry->action); ?></strong>
                            <div class="tracepilot-list-subtext"><?php echo esc_html(TracePilot_Helpers::format_datetime($entry->time)); ?></div>
                            <div class="tracepilot-list-subtext"><?php echo esc_html($entry->description); ?></div>
                        </div>
                        <div><?php echo TracePilot_Helpers::get_severity_badge($entry->severity); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
