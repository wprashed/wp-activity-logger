<?php
/**
* Template for log details
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get log ID
$log_id = isset($_GET['log_id']) ? intval($_GET['log_id']) : 0;

if (!$log_id) {
    ?>
    <div class="wpal-alert wpal-alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-circle"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <?php _e('Invalid log ID.', 'wp-activity-logger-pro'); ?>
    </div>
    <?php
    return;
}

// Get log details
global $wpdb;
WPAL_Helpers::init();
$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WPAL_Helpers::$db_table . " WHERE id = %d", $log_id));

if (!$log) {
    ?>
    <div class="wpal-alert wpal-alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-circle"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <?php _e('Log entry not found.', 'wp-activity-logger-pro'); ?>
    </div>
    <?php
    return;
}

// Get severity icon and class
$severity_icon = '';
$severity_class = '';
switch ($log->severity) {
    case 'info':
        $severity_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        $severity_class = 'info';
        break;
    case 'warning':
        $severity_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
        $severity_class = 'warning';
        break;
    case 'error':
        $severity_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-octagon"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        $severity_class = 'danger';
        break;
    default:
        $severity_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>  class="feather feather-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>';
        $severity_class = 'info';
}

// Decode context
$context = json_decode($log->context, true);
?>

<div class="wpal-log-details">
    <div class="wpal-log-details-header">
        <div class="wpal-log-details-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <h2 class="wpal-log-details-title"><?php _e('Log Details', 'wp-activity-logger-pro'); ?></h2>
    </div>
    
    <div class="wpal-log-details-meta">
        <div class="wpal-log-details-meta-item">
            <div class="wpal-log-details-meta-label"><?php _e('ID', 'wp-activity-logger-pro'); ?></div>
            <div class="wpal-log-details-meta-value"><?php echo esc_html($log->id); ?></div>
        </div>
        
        <div class="wpal-log-details-meta-item">
            <div class="wpal-log-details-meta-label"><?php _e('Time', 'wp-activity-logger-pro'); ?></div>
            <div class="wpal-log-details-meta-value"><?php echo WPAL_Helpers::format_datetime($log->time); ?></div>
        </div>
        
        <div class="wpal-log-details-meta-item">
            <div class="wpal-log-details-meta-label"><?php _e('User', 'wp-activity-logger-pro'); ?></div>
            <div class="wpal-log-details-meta-value"><?php echo esc_html($log->username); ?> (<?php echo esc_html($log->user_role); ?>)</div>
        </div>
        
        <div class="wpal-log-details-meta-item">
            <div class="wpal-log-details-meta-label"><?php _e('Action', 'wp-activity-logger-pro'); ?></div>
            <div class="wpal-log-details-meta-value"><?php echo esc_html($log->action); ?></div>
        </div>
        
        <div class="wpal-log-details-meta-item">
            <div class="wpal-log-details-meta-label"><?php _e('Severity', 'wp-activity-logger-pro'); ?></div>
            <div class="wpal-log-details-meta-value">
                <span class="wpal-badge wpal-badge-<?php echo $severity_class; ?>">
                    <?php echo $severity_icon; ?>
                    <?php echo esc_html(ucfirst($log->severity)); ?>
                </span>
            </div>
        </div>
        
        <div class="wpal-log-details-meta-item">
            <div class="wpal-log-details-meta-label"><?php _e('IP Address', 'wp-activity-logger-pro'); ?></div>
            <div class="wpal-log-details-meta-value"><?php echo esc_html($log->ip); ?></div>
        </div>
        
        <div class="wpal-log-details-meta-item">
            <div class="wpal-log-details-meta-label"><?php _e('Browser', 'wp-activity-logger-pro'); ?></div>
            <div class="wpal-log-details-meta-value"><?php echo esc_html($log->browser); ?></div>
        </div>
    </div>
    
    <?php if ($context && !empty($context)) : ?>
        <h3><?php _e('Context', 'wp-activity-logger-pro'); ?></h3>
        <div class="wpal-log-details-content">
            <?php foreach ($context as $key => $value) : ?>
                <strong><?php echo esc_html($key); ?>:</strong> 
                <?php 
                if (is_array($value) || is_object($value)) {
                    echo esc_html(json_encode($value, JSON_PRETTY_PRINT));
                } else {
                    echo esc_html($value);
                }
                ?>
                <br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="wpal-modal-footer">
    <button type="button" class="wpal-btn wpal-btn-secondary wpal-modal-close">
        <?php _e('Close', 'wp-activity-logger-pro'); ?>
    </button>
</div>