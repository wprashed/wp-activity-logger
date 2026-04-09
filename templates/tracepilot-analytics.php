<?php
/**
 * Analytics template.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Insights', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Analytics', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Compare activity volume, user behavior, action mix, and severity distribution over time.', 'wp-activity-logger-pro'); ?></p>
        </div>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2><?php esc_html_e('Build a chart', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('Choose a view and generate a fresh dataset from the current logs table.', 'wp-activity-logger-pro'); ?></p>
            </div>
        </div>
        <form id="tracepilot-analytics-form" class="tracepilot-filter-grid">
            <label>
                <span><?php esc_html_e('Chart type', 'wp-activity-logger-pro'); ?></span>
                <select id="tracepilot-chart-type" class="tracepilot-input">
                    <option value="activity_over_time"><?php esc_html_e('Activity over time', 'wp-activity-logger-pro'); ?></option>
                    <option value="activity_by_user"><?php esc_html_e('Activity by user', 'wp-activity-logger-pro'); ?></option>
                    <option value="activity_by_type"><?php esc_html_e('Activity by type', 'wp-activity-logger-pro'); ?></option>
                    <option value="severity_distribution"><?php esc_html_e('Severity distribution', 'wp-activity-logger-pro'); ?></option>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Date range', 'wp-activity-logger-pro'); ?></span>
                <select id="tracepilot-date-range" class="tracepilot-input">
                    <option value="7d"><?php esc_html_e('Last 7 days', 'wp-activity-logger-pro'); ?></option>
                    <option value="30d" selected><?php esc_html_e('Last 30 days', 'wp-activity-logger-pro'); ?></option>
                    <option value="90d"><?php esc_html_e('Last 90 days', 'wp-activity-logger-pro'); ?></option>
                    <option value="1y"><?php esc_html_e('Last year', 'wp-activity-logger-pro'); ?></option>
                </select>
            </label>
            <label id="tracepilot-group-by-wrap">
                <span><?php esc_html_e('Group by', 'wp-activity-logger-pro'); ?></span>
                <select id="tracepilot-group-by" class="tracepilot-input">
                    <option value="day" selected><?php esc_html_e('Day', 'wp-activity-logger-pro'); ?></option>
                    <option value="week"><?php esc_html_e('Week', 'wp-activity-logger-pro'); ?></option>
                    <option value="month"><?php esc_html_e('Month', 'wp-activity-logger-pro'); ?></option>
                </select>
            </label>
            <div class="tracepilot-filter-actions">
                <button type="submit" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Generate', 'wp-activity-logger-pro'); ?></button>
            </div>
        </form>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2 id="tracepilot-analytics-title"><?php esc_html_e('Activity over time', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('Charts update live from current plugin data.', 'wp-activity-logger-pro'); ?></p>
            </div>
        </div>
        <div class="tracepilot-chart-shell">
            <canvas id="tracepilot-analytics-chart"></canvas>
        </div>
        <div id="tracepilot-analytics-insights" class="tracepilot-list" style="margin-top:16px;"></div>
    </section>
</div>

<script>
jQuery(function($) {
    let chart;

    function renderAnalytics(config, title, insights) {
        const canvas = document.getElementById('tracepilot-analytics-chart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(canvas, config);
        $('#tracepilot-analytics-title').text(title);

        const box = $('#tracepilot-analytics-insights').empty();
        if (Array.isArray(insights) && insights.length) {
            insights.forEach(function(line) {
                box.append('<div class="tracepilot-list-row"><div>' + $('<div>').text(line).html() + '</div></div>');
            });
        }
    }

    function loadAnalytics() {
        const chartType = $('#tracepilot-chart-type').val();
        const dateRange = $('#tracepilot-date-range').val();
        const groupBy = $('#tracepilot-group-by').val();

        $.post(ajaxurl, {
            action: 'tracepilot_get_analytics_data',
            nonce: '<?php echo esc_js(wp_create_nonce('tracepilot_nonce')); ?>',
            chart_type: chartType,
            date_range: dateRange,
            group_by: groupBy
        }).done(function(response) {
            if (!response.success) {
                return;
            }

            let type = 'bar';
            let options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: chartType === 'severity_distribution',
                        position: 'bottom'
                    }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            };

            if (chartType === 'activity_over_time') {
                type = 'line';
                options.plugins.legend.display = false;
            } else if (chartType === 'severity_distribution') {
                type = 'doughnut';
                delete options.scales;
            }

            renderAnalytics(
                {
                    type: type,
                    data: response.data,
                    options: options
                },
                $('#tracepilot-chart-type option:selected').text(),
                response.data.insights || []
            );
        });
    }

    $('#tracepilot-chart-type').on('change', function() {
        $('#tracepilot-group-by-wrap').toggle($(this).val() === 'activity_over_time');
    });

    $('#tracepilot-analytics-form').on('submit', function(event) {
        event.preventDefault();
        loadAnalytics();
    });

    loadAnalytics();
});
</script>
