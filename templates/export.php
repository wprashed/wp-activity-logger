<?php
/**
 * Template for the export page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get unique users and actions for filters
global $wpdb;
WPAL_Helpers::init();
$table_name = WPAL_Helpers::$db_table;

$users = $wpdb->get_col("SELECT DISTINCT username FROM $table_name ORDER BY username ASC");
$actions = $wpdb->get_col("SELECT DISTINCT action FROM $table_name ORDER BY action ASC");
?>

<div class="wrap wpal-wrap">
    <div class="wpal-dashboard-header">
        <h1 class="wpal-dashboard-title"><?php _e('Export Logs', 'wp-activity-logger-pro'); ?></h1>
        <div class="wpal-dashboard-actions">
            <a href="<?php echo admin_url('admin.php?page=wp-activity-logger-pro-dashboard'); ?>" class="wpal-btn wpal-btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart-2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <?php _e('Dashboard', 'wp-activity-logger-pro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wp-activity-logger-pro'); ?>" class="wpal-btn wpal-btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-list"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                <?php _e('View Logs', 'wp-activity-logger-pro'); ?>
            </a>
        </div>
    </div>
    
    <div class="wpal-widget">
        <div class="wpal-widget-header">
            <h3 class="wpal-widget-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-filter"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                <?php _e('Export Options', 'wp-activity-logger-pro'); ?>
            </h3>
        </div>
        <div class="wpal-widget-body">
            <form id="wpal-export-form" method="post">
                <div class="wpal-d-flex wpal-flex-wrap">
                    <div class="wpal-form-group" style="margin-right: 1rem;">
                        <label for="format" class="wpal-form-label"><?php _e('Export Format', 'wp-activity-logger-pro'); ?></label>
                        <select name="format" id="format" class="wpal-form-control">
                            <option value="csv"><?php _e('CSV', 'wp-activity-logger-pro'); ?></option>
                            <option value="json"><?php _e('JSON', 'wp-activity-logger-pro'); ?></option>
                            <option value="xml"><?php _e('XML', 'wp-activity-logger-pro'); ?></option>
                            <option value="pdf"><?php _e('PDF', 'wp-activity-logger-pro'); ?></option>
                        </select>
                    </div>
                    
                    <div class="wpal-form-group" style="margin-right: 1rem;">
                        <label for="user" class="wpal-form-label"><?php _e('User', 'wp-activity-logger-pro'); ?></label>
                        <select name="user" id="user" class="wpal-form-control">
                            <option value=""><?php _e('All Users', 'wp-activity-logger-pro'); ?></option>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo esc_attr($user); ?>"><?php echo esc_html($user); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="wpal-form-group" style="margin-right: 1rem;">
                        <label for="action" class="wpal-form-label"><?php _e('Action', 'wp-activity-logger-pro'); ?></label>
                        <select name="action" id="action" class="wpal-form-control">
                            <option value=""><?php _e('All Actions', 'wp-activity-logger-pro'); ?></option>
                            <?php foreach ($actions as $action) : ?>
                                <option value="<?php echo esc_attr($action); ?>"><?php echo esc_html($action); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="wpal-form-group" style="margin-right: 1rem;">
                        <label for="severity" class="wpal-form-label"><?php _e('Severity', 'wp-activity-logger-pro'); ?></label>
                        <select name="severity" id="severity" class="wpal-form-control">
                            <option value=""><?php _e('All Severities', 'wp-activity-logger-pro'); ?></option>
                            <option value="info"><?php _e('Info', 'wp-activity-logger-pro'); ?></option>
                            <option value="warning"><?php _e('Warning', 'wp-activity-logger-pro'); ?></option>
                            <option value="error"><?php _e('Error', 'wp-activity-logger-pro'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="wpal-d-flex wpal-flex-wrap">
                    <div class="wpal-form-group" style="margin-right: 1rem;">
                        <label for="date_from" class="wpal-form-label"><?php _e('Date From', 'wp-activity-logger-pro'); ?></label>
                        <input type="text" name="date_from" id="date_from" class="wpal-form-control wpal-datepicker" placeholder="YYYY-MM-DD">
                    </div>
                    
                    <div class="wpal-form-group" style="margin-right: 1rem;">
                        <label for="date_to" class="wpal-form-label"><?php _e('Date To', 'wp-activity-logger-pro'); ?></label>
                        <input type="text" name="date_to" id="date_to" class="wpal-form-control wpal-datepicker" placeholder="YYYY-MM-DD">
                    </div>
                </div>
                
                <div class="wpal-form-group">
                    <button type="submit" class="wpal-btn wpal-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <?php _e('Export Logs', 'wp-activity-logger-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="wpal-widget">
        <div class="wpal-widget-header">
            <h3 class="wpal-widget-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <?php _e('Export Information', 'wp-activity-logger-pro'); ?>
            </h3>
        </div>
        <div class="wpal-widget-body">
            <div class="wpal-alert wpal-alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <p><?php _e('Export your activity logs in various formats for backup or analysis purposes.', 'wp-activity-logger-pro'); ?></p>
                    <ul>
                        <li><?php _e('<strong>CSV</strong>: Comma-separated values format, ideal for spreadsheet applications like Excel.', 'wp-activity-logger-pro'); ?></li>
                        <li><?php _e('<strong>JSON</strong>: JavaScript Object Notation format, ideal for programmatic processing.', 'wp-activity-logger-pro'); ?></li>
                        <li><?php _e('<strong>XML</strong>: Extensible Markup Language format, ideal for data interchange.', 'wp-activity-logger-pro'); ?></li>
                        <li><?php _e('<strong>PDF</strong>: Portable Document Format, ideal for printing and sharing.', 'wp-activity-logger-pro'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize datepickers
    if ($.fn.datepicker) {
        $('.wpal-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '0'
        });
    }
    
    // Handle export form submission
    $('#wpal-export-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpal_export_logs',
                nonce: wpal_admin_vars.nonce,
                ...formData
            },
            success: function(response) {
                // Create a blob and download
                const blob = new Blob([response], { type: 'application/octet-stream' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'activity-logs-' + new Date().toISOString().split('T')[0] + '.' + $('#format').val();
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            },
            error: function() {
                alert('An error occurred during export. Please try again.');
            }
        });
    });
});
</script>