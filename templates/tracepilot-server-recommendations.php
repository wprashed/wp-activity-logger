<?php
/**
 * Server recommendations template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$server_recommendations = new TracePilot_Server_Recommendations();
$server_info = $server_recommendations->analyze_server_needs();
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Capacity planning', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Server Recommendations', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Estimate a reasonable hosting profile from current activity volume, user count, and content footprint.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="tracepilot-hero-actions">
            <button id="tracepilot-analyze-server" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Analyze Server Needs', 'wp-activity-logger-pro'); ?></button>
        </div>
    </section>

    <section class="tracepilot-stats-grid">
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Total Logs', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value" id="tracepilot-stat-total-logs"><?php echo esc_html(number_format_i18n($server_info['stats']['total_logs'])); ?></strong>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Avg Logs / Day', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value" id="tracepilot-stat-logs-per-day"><?php echo esc_html(number_format_i18n($server_info['stats']['logs_per_day'])); ?></strong>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Peak Logs / Hour', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value" id="tracepilot-stat-peak-logs"><?php echo esc_html(number_format_i18n($server_info['stats']['peak_logs_per_hour'])); ?></strong>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Database Size', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value" id="tracepilot-stat-db-size"><?php echo esc_html($server_info['stats']['db_size']); ?></strong>
        </article>
    </section>

    <section class="tracepilot-grid tracepilot-grid-2">
        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Current Server Configuration', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Values detected from the current hosting environment.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-table-wrap">
                <table class="tracepilot-table tracepilot-kv-table">
                    <tbody>
                        <tr><th><?php esc_html_e('Server software', 'wp-activity-logger-pro'); ?></th><td id="tracepilot-server-software"><?php echo esc_html($server_info['current_server']['server_software']); ?></td></tr>
                        <tr><th><?php esc_html_e('PHP version', 'wp-activity-logger-pro'); ?></th><td id="tracepilot-php-version"><?php echo esc_html($server_info['current_server']['php_version']); ?></td></tr>
                        <tr><th><?php esc_html_e('MySQL version', 'wp-activity-logger-pro'); ?></th><td id="tracepilot-mysql-version"><?php echo esc_html($server_info['current_server']['mysql_version']); ?></td></tr>
                        <tr><th><?php esc_html_e('Memory limit', 'wp-activity-logger-pro'); ?></th><td id="tracepilot-memory-limit"><?php echo esc_html($server_info['current_server']['memory_limit']); ?></td></tr>
                        <tr><th><?php esc_html_e('Max execution time', 'wp-activity-logger-pro'); ?></th><td id="tracepilot-max-execution-time"><?php echo esc_html($server_info['current_server']['max_execution_time']); ?>s</td></tr>
                        <tr><th><?php esc_html_e('Post max size', 'wp-activity-logger-pro'); ?></th><td id="tracepilot-post-max-size"><?php echo esc_html($server_info['current_server']['post_max_size']); ?></td></tr>
                        <tr><th><?php esc_html_e('Upload max filesize', 'wp-activity-logger-pro'); ?></th><td id="tracepilot-upload-max-filesize"><?php echo esc_html($server_info['current_server']['upload_max_filesize']); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Recommended Profile', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('A practical starting point based on current plugin telemetry.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-pill-row" style="margin-bottom:16px;">
                <span class="tracepilot-pill" id="tracepilot-rec-storage"><?php echo esc_html($server_info['recommendations']['storage']); ?> GB <?php esc_html_e('storage', 'wp-activity-logger-pro'); ?></span>
                <span class="tracepilot-pill" id="tracepilot-rec-cpu"><?php echo esc_html($server_info['recommendations']['cpu']); ?> <?php esc_html_e('CPU cores', 'wp-activity-logger-pro'); ?></span>
                <span class="tracepilot-pill" id="tracepilot-rec-ram"><?php echo esc_html($server_info['recommendations']['ram']); ?> GB RAM</span>
                <span class="tracepilot-pill" id="tracepilot-rec-bandwidth"><?php echo esc_html($server_info['recommendations']['bandwidth']); ?> GB/month</span>
            </div>
            <div class="tracepilot-note" id="tracepilot-rec-hosting-type" style="margin-bottom:16px;">
                <?php echo esc_html(sprintf(__('Recommended hosting type: %s', 'wp-activity-logger-pro'), $server_info['recommendations']['hosting_type'])); ?>
            </div>
            <div class="tracepilot-detail-card" id="tracepilot-rec-explanation">
                <?php echo nl2br(esc_html($server_info['recommendations']['explanation'])); ?>
            </div>
        </article>
    </section>
</div>

<script>
jQuery(function($) {
    $('#tracepilot-analyze-server').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('<?php echo esc_js(__('Analyzing...', 'wp-activity-logger-pro')); ?>');

        $.post(ajaxurl, {
            action: 'tracepilot_analyze_server_needs',
            nonce: '<?php echo esc_js(wp_create_nonce('tracepilot_nonce')); ?>'
        }).done(function(response) {
            if (!response.success) {
                window.alert(response.data.message || 'Unable to analyze server needs.');
                return;
            }

            const data = response.data;
            $('#tracepilot-stat-total-logs').text(data.stats.total_logs.toLocaleString());
            $('#tracepilot-stat-logs-per-day').text(data.stats.logs_per_day.toLocaleString());
            $('#tracepilot-stat-peak-logs').text(data.stats.peak_logs_per_hour.toLocaleString());
            $('#tracepilot-stat-db-size').text(data.stats.db_size);

            $('#tracepilot-server-software').text(data.current_server.server_software);
            $('#tracepilot-php-version').text(data.current_server.php_version);
            $('#tracepilot-mysql-version').text(data.current_server.mysql_version);
            $('#tracepilot-memory-limit').text(data.current_server.memory_limit);
            $('#tracepilot-max-execution-time').text(data.current_server.max_execution_time + 's');
            $('#tracepilot-post-max-size').text(data.current_server.post_max_size);
            $('#tracepilot-upload-max-filesize').text(data.current_server.upload_max_filesize);

            $('#tracepilot-rec-storage').text(data.recommendations.storage + ' GB storage');
            $('#tracepilot-rec-cpu').text(data.recommendations.cpu + ' CPU cores');
            $('#tracepilot-rec-ram').text(data.recommendations.ram + ' GB RAM');
            $('#tracepilot-rec-bandwidth').text(data.recommendations.bandwidth + ' GB/month');
            $('#tracepilot-rec-hosting-type').text('Recommended hosting type: ' + data.recommendations.hosting_type);
            $('#tracepilot-rec-explanation').html(data.recommendations.explanation.replace(/\n/g, '<br>'));
        }).always(function() {
            button.prop('disabled', false).text('<?php echo esc_js(__('Analyze Server Needs', 'wp-activity-logger-pro')); ?>');
        });
    });
});
</script>
