<?php
/**
 * Template for the server recommendations page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current server info
$server_recommendations = new WPAL_Server_Recommendations();
$server_info = $server_recommendations->analyze_server_needs();
?>

<div class="wrap wpal-wrap">
    <div class="wpal-dashboard-header">
        <h1 class="wpal-dashboard-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-server"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            <?php _e('Server Recommendations', 'wp-activity-logger-pro'); ?>
        </h1>
        <div class="wpal-dashboard-actions">
            <button id="wpal-analyze-server" class="wpal-btn wpal-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-cw"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                <?php _e('Analyze Server Needs', 'wp-activity-logger-pro'); ?>
            </button>
        </div>
    </div>
    
    <div class="wpal-alert wpal-alert-info">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <div>
            <?php _e('This tool analyzes your site activity logs to provide recommendations for optimal server configuration. These recommendations are based on your site\'s activity patterns, user count, and content volume.', 'wp-activity-logger-pro'); ?>
        </div>
    </div>
    
    <div class="wpal-widgets-grid">
        <div class="wpal-widget">
            <div class="wpal-widget-header">
                <h3 class="wpal-widget-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    <?php _e('Activity Statistics', 'wp-activity-logger-pro'); ?>
                </h3>
            </div>
            <div class="wpal-widget-body">
                <div class="wpal-stats-grid">
                    <div class="wpal-stat-card">
                        <div class="wpal-stat-card-header">
                            <div class="wpal-stat-card-icon info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-list"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            </div>
                            <div class="wpal-stat-card-title"><?php _e('Total Logs', 'wp-activity-logger-pro'); ?></div>
                        </div>
                        <p class="wpal-stat-card-value" id="wpal-stat-total-logs"><?php echo number_format($server_info['stats']['total_logs']); ?></p>
                    </div>
                    
                    <div class="wpal-stat-card">
                        <div class="wpal-stat-card-header">
                            <div class="wpal-stat-card-icon info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </div>
                            <div class="wpal-stat-card-title"><?php _e('Logs Per Day (Avg)', 'wp-activity-logger-pro'); ?></div>
                        </div>
                        <p class="wpal-stat-card-value" id="wpal-stat-logs-per-day"><?php echo number_format($server_info['stats']['logs_per_day']); ?></p>
                    </div>
                    
                    <div class="wpal-stat-card">
                        <div class="wpal-stat-card-header">
                            <div class="wpal-stat-card-icon warning">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div class="wpal-stat-card-title"><?php _e('Peak Logs Per Hour', 'wp-activity-logger-pro'); ?></div>
                        </div>
                        <p class="wpal-stat-card-value" id="wpal-stat-peak-logs"><?php echo number_format($server_info['stats']['peak_logs_per_hour']); ?></p>
                    </div>
                    
                    <div class="wpal-stat-card">
                        <div class="wpal-stat-card-header">
                            <div class="wpal-stat-card-icon info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-database"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                            </div>
                            <div class="wpal-stat-card-title"><?php _e('Database Size', 'wp-activity-logger-pro'); ?></div>
                        </div>
                        <p class="wpal-stat-card-value" id="wpal-stat-db-size"><?php echo $server_info['stats']['db_size']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="wpal-widget">
            <div class="wpal-widget-header">
                <h3 class="wpal-widget-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-server"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                    <?php _e('Current Server Configuration', 'wp-activity-logger-pro'); ?>
                </h3>
            </div>
            <div class="wpal-widget-body">
                <table class="wpal-table">
                    <tbody>
                        <tr>
                            <th><?php _e('Server Software', 'wp-activity-logger-pro'); ?></th>
                            <td id="wpal-server-software"><?php echo esc_html($server_info['current_server']['server_software']); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('PHP Version', 'wp-activity-logger-pro'); ?></th>
                            <td id="wpal-php-version"><?php echo esc_html($server_info['current_server']['php_version']); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('MySQL Version', 'wp-activity-logger-pro'); ?></th>
                            <td id="wpal-mysql-version"><?php echo esc_html($server_info['current_server']['mysql_version']); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('PHP Memory Limit', 'wp-activity-logger-pro'); ?></th>
                            <td id="wpal-memory-limit"><?php echo esc_html($server_info['current_server']['memory_limit']); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Max Execution Time', 'wp-activity-logger-pro'); ?></th>
                            <td id="wpal-max-execution-time"><?php echo esc_html($server_info['current_server']['max_execution_time']); ?> <?php _e('seconds', 'wp-activity-logger-pro'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Post Max Size', 'wp-activity-logger-pro'); ?></th>
                            <td id="wpal-post-max-size"><?php echo esc_html($server_info['current_server']['post_max_size']); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Upload Max Filesize', 'wp-activity-logger-pro'); ?></th>
                            <td id="wpal-upload-max-filesize"><?php echo esc_html($server_info['current_server']['upload_max_filesize']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="wpal-widget">
        <div class="wpal-widget-header">
            <h3 class="wpal-widget-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-cpu"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                <?php _e('Recommended Server Configuration', 'wp-activity-logger-pro'); ?>
            </h3>
        </div>
        <div class="wpal-widget-body">
            <div class="wpal-stats-grid">
                <div class="wpal-stat-card">
                    <div class="wpal-stat-card-header">
                        <div class="wpal-stat-card-icon info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-hard-drive"><line x1="22" y1="12" x2="2" y2="12"></line><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" y1="16" x2="6.01" y2="16"></line><line x1="10" y1="16" x2="10.01" y2="16"></line></svg>
                        </div>
                        <div class="wpal-stat-card-title"><?php _e('Storage', 'wp-activity-logger-pro'); ?></div>
                    </div>
                    <p class="wpal-stat-card-value" id="wpal-rec-storage"><?php echo $server_info['recommendations']['storage']; ?> GB</p>
                </div>
                
                <div class="wpal-stat-card">
                    <div class="wpal-stat-card-header">
                        <div class="wpal-stat-card-icon info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-cpu"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                        </div>
                        <div class="wpal-stat-card-title"><?php _e('CPU Cores', 'wp-activity-logger-pro'); ?></div>
                    </div>
                    <p class="wpal-stat-card-value" id="wpal-rec-cpu"><?php echo $server_info['recommendations']['cpu']; ?></p>
                </div>
                
                <div class="wpal-stat-card">
                    <div class="wpal-stat-card-header">
                        <div class="wpal-stat-card-icon info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        </div>
                        <div class="wpal-stat-card-title"><?php _e('RAM', 'wp-activity-logger-pro'); ?></div>
                    </div>
                    <p class="wpal-stat-card-value" id="wpal-rec-ram"><?php echo $server_info['recommendations']['ram']; ?> GB</p>
                </div>
                
                <div class="wpal-stat-card">
                    <div class="wpal-stat-card-header">
                        <div class="wpal-stat-card-icon info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-wifi"><path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>
                        </div>
                        <div class="wpal-stat-card-title"><?php _e('Bandwidth', 'wp-activity-logger-pro'); ?></div>
                    </div>
                    <p class="wpal-stat-card-value" id="wpal-rec-bandwidth"><?php echo $server_info['recommendations']['bandwidth']; ?> GB/month</p>
                </div>
            </div>
            
            <div class="wpal-mt-4">
                <div class="wpal-form-group">
                    <label class="wpal-form-label"><?php _e('Recommended Hosting Type', 'wp-activity-logger-pro'); ?></label>
                    <div class="wpal-badge wpal-badge-info" style="font-size: 16px; padding: 8px 12px;" id="wpal-rec-hosting-type">
                        <?php echo $server_info['recommendations']['hosting_type']; ?>
                    </div>
                </div>
                
                <div class="wpal-form-group wpal-mt-4">
                    <label class="wpal-form-label"><?php _e('Explanation', 'wp-activity-logger-pro'); ?></label>
                    <div class="wpal-log-details-content" id="wpal-rec-explanation">
                        <?php echo nl2br(esc_html($server_info['recommendations']['explanation'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wpal-analyze-server').on('click', function() {
        const $button = $(this);
        
        // Disable button and show loading
        $button.prop('disabled', true).html('<div class="wpal-spinner"></div> <?php _e('Analyzing...', 'wp-activity-logger-pro'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpal_analyze_server_needs',
                nonce: '<?php echo wp_create_nonce('wpal_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update stats
                    $('#wpal-stat-total-logs').text(data.stats.total_logs.toLocaleString());
                    $('#wpal-stat-logs-per-day').text(data.stats.logs_per_day.toLocaleString());
                    $('#wpal-stat-peak-logs').text(data.stats.peak_logs_per_hour.toLocaleString());
                    $('#wpal-stat-db-size').text(data.stats.db_size);
                    
                    // Update server info
                    $('#wpal-server-software').text(data.current_server.server_software);
                    $('#wpal-php-version').text(data.current_server.php_version);
                    $('#wpal-mysql-version').text(data.current_server.mysql_version);
                    $('#wpal-memory-limit').text(data.current_server.memory_limit);
                    $('#wpal-max-execution-time').text(data.current_server.max_execution_time + ' <?php _e('seconds', 'wp-activity-logger-pro'); ?>');
                    $('#wpal-post-max-size').text(data.current_server.post_max_size);
                    $('#wpal-upload-max-filesize').text(data.current_server.upload_max_filesize);
                    
                    // Update recommendations
                    $('#wpal-rec-storage').text(data.recommendations.storage + ' GB');
                    $('#wpal-rec-cpu').text(data.recommendations.cpu);
                    $('#wpal-rec-ram').text(data.recommendations.ram + ' GB');
                    $('#wpal-rec-bandwidth').text(data.recommendations.bandwidth + ' GB/month');
                    $('#wpal-rec-hosting-type').text(data.recommendations.hosting_type);
                    $('#wpal-rec-explanation').html(data.recommendations.explanation.replace(/\n/g, '<br>'));
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p><?php _e('Server analysis completed successfully.', 'wp-activity-logger-pro'); ?></p></div>')
                        .insertAfter('.wpal-dashboard-header')
                        .delay(3000)
                        .fadeOut(function() {
                            $(this).remove();
                        });
                } else {
                    // Show error message
                    $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.wpal-dashboard-header')
                        .delay(3000)
                        .fadeOut(function() {
                            $(this).remove();
                        });
                }
                
                // Re-enable button
                $button.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-cw"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> <?php _e('Analyze Server Needs', 'wp-activity-logger-pro'); ?>');
            },
            error: function(xhr, status, error) {
                // Show error message
                $('<div class="notice notice-error is-dismissible"><p><?php _e('An error occurred while analyzing server needs.', 'wp-activity-logger-pro'); ?></p></div>')
                    .insertAfter('.wpal-dashboard-header')
                    .delay(3000)
                    .fadeOut(function() {
                        $(this).remove();
                    });
                
                // Re-enable button
                $button.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-cw"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> <?php _e('Analyze Server Needs', 'wp-activity-logger-pro'); ?>');
            }
        });
    });
});
</script>
