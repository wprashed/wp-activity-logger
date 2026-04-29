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
            <p class="tracepilot-eyebrow"><?php esc_html_e('Insights', 'tracepilot'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Analytics', 'tracepilot'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Compare activity volume, user behavior, action mix, and severity distribution over time.', 'tracepilot'); ?></p>
        </div>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2><?php esc_html_e('Build a chart', 'tracepilot'); ?></h2>
                <p><?php esc_html_e('Choose a view and generate a fresh dataset from the current logs table.', 'tracepilot'); ?></p>
            </div>
        </div>
        <form id="tracepilot-analytics-form" class="tracepilot-filter-grid">
            <label>
                <span><?php esc_html_e('Chart type', 'tracepilot'); ?></span>
                <select id="tracepilot-chart-type" class="tracepilot-input">
                    <option value="activity_over_time"><?php esc_html_e('Activity over time', 'tracepilot'); ?></option>
                    <option value="activity_by_user"><?php esc_html_e('Activity by user', 'tracepilot'); ?></option>
                    <option value="activity_by_type"><?php esc_html_e('Activity by type', 'tracepilot'); ?></option>
                    <option value="severity_distribution"><?php esc_html_e('Severity distribution', 'tracepilot'); ?></option>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Date range', 'tracepilot'); ?></span>
                <select id="tracepilot-date-range" class="tracepilot-input">
                    <option value="7d"><?php esc_html_e('Last 7 days', 'tracepilot'); ?></option>
                    <option value="30d" selected><?php esc_html_e('Last 30 days', 'tracepilot'); ?></option>
                    <option value="90d"><?php esc_html_e('Last 90 days', 'tracepilot'); ?></option>
                    <option value="1y"><?php esc_html_e('Last year', 'tracepilot'); ?></option>
                </select>
            </label>
            <label id="tracepilot-group-by-wrap">
                <span><?php esc_html_e('Group by', 'tracepilot'); ?></span>
                <select id="tracepilot-group-by" class="tracepilot-input">
                    <option value="day" selected><?php esc_html_e('Day', 'tracepilot'); ?></option>
                    <option value="week"><?php esc_html_e('Week', 'tracepilot'); ?></option>
                    <option value="month"><?php esc_html_e('Month', 'tracepilot'); ?></option>
                </select>
            </label>
            <div class="tracepilot-filter-actions">
                <button type="submit" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Generate', 'tracepilot'); ?></button>
            </div>
        </form>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2 id="tracepilot-analytics-title"><?php esc_html_e('Activity over time', 'tracepilot'); ?></h2>
                <p><?php esc_html_e('Charts update live from current plugin data.', 'tracepilot'); ?></p>
            </div>
        </div>
        <div class="tracepilot-chart-shell">
            <canvas id="tracepilot-analytics-chart"></canvas>
        </div>
        <div id="tracepilot-analytics-insights" class="tracepilot-list" style="margin-top:16px;"></div>
    </section>
</div>
