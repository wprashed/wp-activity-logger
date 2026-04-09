<?php
/**
 * Diagnostics template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$diagnostics = tracepilot_for_wordpress()->diagnostics;
$report = $diagnostics->get_latest_report();
if (empty($report)) {
    $report = $diagnostics->run_diagnostics(false);
}

$history = $diagnostics->get_scan_history(8);
$client_errors = get_option(TracePilot_Diagnostics::CLIENT_ERRORS_OPTION, array());
$client_errors = is_array($client_errors) ? array_slice($client_errors, 0, 5) : array();
$safe_mode = !empty($report['safe_mode']) && is_array($report['safe_mode']) ? $report['safe_mode'] : $diagnostics->get_safe_mode_status();
$issues = !empty($report['issues']) && is_array($report['issues']) ? $report['issues'] : array();
$counts = !empty($report['counts']) && is_array($report['counts']) ? $report['counts'] : array(
    'critical' => 0,
    'warning' => 0,
    'info' => 0,
);
$inventory = !empty($report['inventory']) && is_array($report['inventory']) ? $report['inventory'] : array();
$timeline = !empty($report['timeline']) && is_array($report['timeline']) ? $report['timeline'] : array();
$correlations = !empty($report['correlations']) && is_array($report['correlations']) ? $report['correlations'] : array();
$conflict_plan = !empty($report['conflict_plan']) && is_array($report['conflict_plan']) ? $report['conflict_plan'] : array();
$active_plugins = !empty($inventory['active_plugins']) && is_array($inventory['active_plugins']) ? $inventory['active_plugins'] : array();
$active_theme = !empty($inventory['active_theme']) && is_array($inventory['active_theme']) ? $inventory['active_theme'] : array();
$health_score = isset($report['health_score']) ? (int) $report['health_score'] : 100;

$severity_labels = array(
    'critical' => __('Critical', 'wp-activity-logger-pro'),
    'warning' => __('Warning', 'wp-activity-logger-pro'),
    'info' => __('Info', 'wp-activity-logger-pro'),
);

$confidence_labels = array(
    'high' => __('High confidence', 'wp-activity-logger-pro'),
    'medium' => __('Medium confidence', 'wp-activity-logger-pro'),
    'advanced' => __('Advanced fix', 'wp-activity-logger-pro'),
);

$score_tone = 'good';
if ($health_score < 60) {
    $score_tone = 'danger';
} elseif ($health_score < 80) {
    $score_tone = 'warning';
}

$assistant_hint = !empty($report['assistant_hint']) ? $report['assistant_hint'] : __('Ask things like “Why is my site slow?” or “What should I disable first?”', 'wp-activity-logger-pro');
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('System scanner', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Diagnostics & Conflict Detection', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Scan the site, explain technical problems in plain language, test fixes privately in admin-only safe mode, and track when issues started appearing.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="tracepilot-hero-actions">
            <button type="button" class="tracepilot-btn tracepilot-btn-primary" id="tracepilot-run-diagnostics"><?php esc_html_e('Run Full Scan', 'wp-activity-logger-pro'); ?></button>
            <?php if (!empty($safe_mode['enabled'])) : ?>
                <button type="button" class="tracepilot-btn tracepilot-btn-outline-danger" id="tracepilot-disable-safe-mode"><?php esc_html_e('Disable Safe Mode', 'wp-activity-logger-pro'); ?></button>
            <?php else : ?>
                <button type="button" class="tracepilot-btn tracepilot-btn-secondary" id="tracepilot-enable-safe-mode"><?php esc_html_e('Start Safe Mode', 'wp-activity-logger-pro'); ?></button>
            <?php endif; ?>
        </div>
    </section>

    <section class="tracepilot-stats-grid" id="tracepilot-diagnostics-summary">
        <article class="tracepilot-stat-card tracepilot-score-card tracepilot-score-card-<?php echo esc_attr($score_tone); ?>">
            <span class="tracepilot-stat-label"><?php esc_html_e('Health Score', 'wp-activity-logger-pro'); ?></span>
            <span class="tracepilot-stat-value"><?php echo esc_html($health_score); ?></span>
            <span class="tracepilot-stat-meta"><?php printf(esc_html__('Last scan: %s', 'wp-activity-logger-pro'), !empty($report['generated_at']) ? esc_html(TracePilot_Helpers::format_datetime($report['generated_at'])) : esc_html__('Not saved yet', 'wp-activity-logger-pro')); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Critical Issues', 'wp-activity-logger-pro'); ?></span>
            <span class="tracepilot-stat-value"><?php echo esc_html((int) $counts['critical']); ?></span>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Immediate breakage or severe risk', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Warnings', 'wp-activity-logger-pro'); ?></span>
            <span class="tracepilot-stat-value"><?php echo esc_html((int) $counts['warning']); ?></span>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Likely instability or conflict signals', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Inventory', 'wp-activity-logger-pro'); ?></span>
            <span class="tracepilot-stat-value"><?php echo esc_html(count($active_plugins)); ?></span>
            <span class="tracepilot-stat-meta"><?php printf(esc_html__('Plugins checked, theme: %s', 'wp-activity-logger-pro'), !empty($active_theme['name']) ? esc_html($active_theme['name']) : esc_html__('Unknown', 'wp-activity-logger-pro')); ?></span>
        </article>
    </section>

    <section class="tracepilot-panel tracepilot-diagnostics-shell">
        <div class="tracepilot-panel-tabs" data-tracepilot-tabs>
            <button type="button" class="tracepilot-panel-tab is-active" data-tab-target="overview"><?php esc_html_e('Overview', 'wp-activity-logger-pro'); ?></button>
            <button type="button" class="tracepilot-panel-tab" data-tab-target="timeline"><?php esc_html_e('Timeline', 'wp-activity-logger-pro'); ?></button>
            <button type="button" class="tracepilot-panel-tab" data-tab-target="assistant"><?php esc_html_e('Assistant', 'wp-activity-logger-pro'); ?></button>
            <button type="button" class="tracepilot-panel-tab" data-tab-target="inventory"><?php esc_html_e('Inventory', 'wp-activity-logger-pro'); ?></button>
        </div>

        <div class="tracepilot-tab-panel is-active" data-tab-panel="overview">
            <section class="tracepilot-diagnostics-layout">
                <article class="tracepilot-panel tracepilot-diagnostics-scroller">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Issue List', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Every issue is translated into plain language, then paired with the safest next steps.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-stack" id="tracepilot-diagnostics-issues">
                <?php if (empty($issues)) : ?>
                    <div class="tracepilot-empty-panel">
                        <h3><?php esc_html_e('No active issues in the latest scan', 'wp-activity-logger-pro'); ?></h3>
                        <p><?php esc_html_e('Run a fresh scan after reproducing the problem if the site still feels unstable.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($issues as $issue) : ?>
                        <?php
                        $severity = isset($issue['severity']) ? $issue['severity'] : 'info';
                        $badge_class = 'tracepilot-badge-info';
                        if ('critical' === $severity) {
                            $badge_class = 'tracepilot-badge-danger';
                        } elseif ('warning' === $severity) {
                            $badge_class = 'tracepilot-badge-warning';
                        }
                        ?>
                        <article class="tracepilot-detail-card tracepilot-issue-card">
                            <div class="tracepilot-panel-head">
                                <div>
                                    <h3><?php echo esc_html($issue['message']); ?></h3>
                                    <p><?php echo esc_html($issue['explanation']); ?></p>
                                </div>
                                <span class="tracepilot-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($severity_labels[$severity]); ?></span>
                            </div>

                            <?php if (!empty($issue['page']) || !empty($issue['raw_error'])) : ?>
                                <div class="tracepilot-note">
                                    <?php if (!empty($issue['page'])) : ?>
                                        <strong><?php esc_html_e('Seen on:', 'wp-activity-logger-pro'); ?></strong>
                                        <?php echo ' ' . esc_html($issue['page']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($issue['raw_error'])) : ?>
                                        <div><strong><?php esc_html_e('Raw signal:', 'wp-activity-logger-pro'); ?></strong> <?php echo esc_html($issue['raw_error']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($issue['plugins'])) : ?>
                                <div class="tracepilot-pill-row">
                                    <?php foreach ((array) $issue['plugins'] as $plugin_slug) : ?>
                                        <span class="tracepilot-pill"><?php echo esc_html($plugin_slug); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($issue['suggestions'])) : ?>
                                <div class="tracepilot-suggestion-grid">
                                    <?php foreach ((array) $issue['suggestions'] as $suggestion) : ?>
                                        <div class="tracepilot-suggestion-card">
                                            <div class="tracepilot-suggestion-head">
                                                <strong><?php echo esc_html($suggestion['title']); ?></strong>
                                                <span class="tracepilot-meta-pill"><?php echo esc_html($confidence_labels[$suggestion['confidence']]); ?></span>
                                            </div>
                                            <p><?php echo esc_html($suggestion['description']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
                </article>

                <article class="tracepilot-panel tracepilot-diagnostics-side">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Safe Mode Debugging', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Disable selected plugins only for your own admin session so visitors never see the experiment.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <div class="tracepilot-note" id="tracepilot-safe-mode-status">
                <?php if (!empty($safe_mode['enabled'])) : ?>
                    <?php
                    printf(
                        esc_html__('Safe mode is active for this admin session. %d plugin(s) are hidden only for you.', 'wp-activity-logger-pro'),
                        count((array) $safe_mode['plugins'])
                    );
                    ?>
                <?php else : ?>
                    <?php esc_html_e('Safe mode is currently off. Select one or more plugins below if you want to test the site privately without them.', 'wp-activity-logger-pro'); ?>
                <?php endif; ?>
            </div>

            <div class="tracepilot-safe-mode-card">
                <div class="tracepilot-form-stack">
                <label>
                    <span><?php esc_html_e('Plugins to hide in your admin session', 'wp-activity-logger-pro'); ?></span>
                    <select multiple class="tracepilot-input tracepilot-select-tall tracepilot-safe-mode-select" id="tracepilot-safe-mode-plugins">
                        <?php foreach ($active_plugins as $plugin) : ?>
                            <option value="<?php echo esc_attr($plugin['file']); ?>" <?php selected(in_array($plugin['file'], (array) ($safe_mode['plugins'] ?? array()), true)); ?>>
                                <?php
                                printf(
                                    '%1$s (%2$s)',
                                    esc_html($plugin['name']),
                                    esc_html($plugin['version'] ? $plugin['version'] : $plugin['file'])
                                );
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                </div>

                <div class="tracepilot-inline-actions">
                    <button type="button" class="tracepilot-btn tracepilot-btn-secondary" id="tracepilot-enable-safe-mode-inline"><?php esc_html_e('Enable Safe Mode With Selection', 'wp-activity-logger-pro'); ?></button>
                    <button type="button" class="tracepilot-btn tracepilot-btn-outline-danger" id="tracepilot-disable-safe-mode-inline"><?php esc_html_e('Restore Normal Session', 'wp-activity-logger-pro'); ?></button>
                </div>
            </div>

            <?php if (!empty($conflict_plan['group_a']) || !empty($conflict_plan['group_b'])) : ?>
                <div class="tracepilot-note">
                    <strong><?php esc_html_e('Binary conflict test', 'wp-activity-logger-pro'); ?></strong>
                    <?php echo ' ' . esc_html($conflict_plan['summary']); ?>
                </div>
                <div class="tracepilot-suggestion-grid">
                    <?php if (!empty($conflict_plan['group_a'])) : ?>
                        <div class="tracepilot-suggestion-card">
                            <div class="tracepilot-suggestion-head">
                                <strong><?php esc_html_e('Test Group A Disabled', 'wp-activity-logger-pro'); ?></strong>
                                <span class="tracepilot-meta-pill"><?php esc_html_e('Batch 1', 'wp-activity-logger-pro'); ?></span>
                            </div>
                            <p><?php echo esc_html(implode(', ', wp_list_pluck((array) $conflict_plan['group_a'], 'name'))); ?></p>
                            <button
                                type="button"
                                class="tracepilot-btn tracepilot-btn-outline-primary tracepilot-btn-sm tracepilot-safe-mode-preset"
                                data-plugins="<?php echo esc_attr(wp_json_encode(wp_list_pluck((array) $conflict_plan['group_a'], 'file'))); ?>"
                            ><?php esc_html_e('Disable This Group In Safe Mode', 'wp-activity-logger-pro'); ?></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($conflict_plan['group_b'])) : ?>
                        <div class="tracepilot-suggestion-card">
                            <div class="tracepilot-suggestion-head">
                                <strong><?php esc_html_e('Test Group B Disabled', 'wp-activity-logger-pro'); ?></strong>
                                <span class="tracepilot-meta-pill"><?php esc_html_e('Batch 2', 'wp-activity-logger-pro'); ?></span>
                            </div>
                            <p><?php echo esc_html(implode(', ', wp_list_pluck((array) $conflict_plan['group_b'], 'name'))); ?></p>
                            <button
                                type="button"
                                class="tracepilot-btn tracepilot-btn-outline-primary tracepilot-btn-sm tracepilot-safe-mode-preset"
                                data-plugins="<?php echo esc_attr(wp_json_encode(wp_list_pluck((array) $conflict_plan['group_b'], 'file'))); ?>"
                            ><?php esc_html_e('Disable This Group In Safe Mode', 'wp-activity-logger-pro'); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
                </article>
            </section>
        </div>

        <div class="tracepilot-tab-panel" data-tab-panel="timeline">
            <section class="tracepilot-grid tracepilot-grid-2">
                <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Issue Timeline', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('See when a problem first appeared, how often it has repeated, and whether it lines up with changes on the site.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-list" id="tracepilot-diagnostics-timeline">
                <?php if (empty($timeline)) : ?>
                    <div class="tracepilot-empty-panel">
                        <p><?php esc_html_e('No tracked issue timeline yet. Run a saved scan to start building history.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($timeline as $entry) : ?>
                        <div class="tracepilot-list-row tracepilot-list-row-compact">
                            <div>
                                <strong><?php echo esc_html($entry['message']); ?></strong>
                                <div class="tracepilot-list-subtext">
                                    <?php
                                    printf(
                                        esc_html__('First seen %1$s, last seen %2$s', 'wp-activity-logger-pro'),
                                        esc_html(TracePilot_Helpers::format_datetime($entry['first_seen'])),
                                        esc_html(TracePilot_Helpers::format_datetime($entry['last_seen']))
                                    );
                                    ?>
                                </div>
                            </div>
                            <div class="tracepilot-list-value">
                                <?php
                                printf(
                                    esc_html__('%d scans', 'wp-activity-logger-pro'),
                                    isset($entry['count']) ? (int) $entry['count'] : 1
                                );
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
                </article>

                <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Change Correlation', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('These pair the first appearance of a problem with nearby updates or configuration changes.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-list">
                <?php if (empty($correlations)) : ?>
                    <div class="tracepilot-empty-panel">
                        <p><?php esc_html_e('No strong issue-to-change correlation has been found yet.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($correlations as $correlation) : ?>
                        <div class="tracepilot-list-row tracepilot-list-row-compact">
                            <div>
                                <strong><?php echo esc_html($correlation['issue']); ?></strong>
                                <div class="tracepilot-list-subtext">
                                    <?php
                                    printf(
                                        esc_html__('Started near %1$s at %2$s', 'wp-activity-logger-pro'),
                                        esc_html($correlation['change_label']),
                                        esc_html(TracePilot_Helpers::format_datetime($correlation['change_time']))
                                    );
                                    ?>
                                </div>
                            </div>
                            <span class="tracepilot-meta-pill">
                                <?php
                                printf(
                                    esc_html__('%s hours apart', 'wp-activity-logger-pro'),
                                    esc_html($correlation['delta_hours'])
                                );
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
                </article>
            </section>
        </div>

        <div class="tracepilot-tab-panel" data-tab-panel="assistant">
            <section class="tracepilot-grid tracepilot-grid-2">
                <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('AI Assistant Preview', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('This is a lightweight contextual assistant built from the current scan data. It is meant to guide the next debugging step quickly.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <div class="tracepilot-form-stack">
                <label>
                    <span><?php esc_html_e('Ask a question', 'wp-activity-logger-pro'); ?></span>
                    <textarea class="tracepilot-input" id="tracepilot-diagnostics-question" placeholder="<?php echo esc_attr($assistant_hint); ?>"></textarea>
                </label>
            </div>

            <div class="tracepilot-inline-actions">
                <button type="button" class="tracepilot-btn tracepilot-btn-primary" id="tracepilot-ask-diagnostics-ai"><?php esc_html_e('Get Answer', 'wp-activity-logger-pro'); ?></button>
            </div>

            <div class="tracepilot-note" id="tracepilot-diagnostics-answer">
                <?php esc_html_e('Ask about slowness, conflicts, what to disable first, or what the latest scan means.', 'wp-activity-logger-pro'); ?>
            </div>
                </article>

                <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Recent Browser & Runtime Signals', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('JavaScript errors collected from the browser can reveal conflicts that never make it into PHP logs.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-list">
                <?php if (empty($client_errors)) : ?>
                    <div class="tracepilot-empty-panel">
                        <p><?php esc_html_e('No browser-side runtime errors have been captured recently.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($client_errors as $entry) : ?>
                        <div class="tracepilot-list-row tracepilot-list-row-compact">
                            <div>
                                <strong><?php echo esc_html($entry['message']); ?></strong>
                                <div class="tracepilot-list-subtext">
                                    <?php
                                    printf(
                                        esc_html__('%1$s on %2$s', 'wp-activity-logger-pro'),
                                        esc_html(TracePilot_Helpers::format_datetime($entry['time'])),
                                        esc_html(!empty($entry['page']) ? $entry['page'] : __('Unknown page', 'wp-activity-logger-pro'))
                                    );
                                    ?>
                                </div>
                            </div>
                            <span class="tracepilot-meta-pill"><?php echo esc_html(!empty($entry['source']) ? $entry['source'] : __('Browser', 'wp-activity-logger-pro')); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
                </article>

                <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Recent Scan History', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Compare health score changes over time and see when the issue count started climbing.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="tracepilot-list" id="tracepilot-diagnostics-history">
                <?php if (empty($history)) : ?>
                    <div class="tracepilot-empty-panel">
                        <p><?php esc_html_e('No saved scan history yet. Run the scanner to create the first history point.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($history as $entry) : ?>
                        <div class="tracepilot-list-row tracepilot-list-row-compact">
                            <div>
                                <strong>
                                    <?php
                                    printf(
                                        esc_html__('Health score %d', 'wp-activity-logger-pro'),
                                        isset($entry['health_score']) ? (int) $entry['health_score'] : 0
                                    );
                                    ?>
                                </strong>
                                <div class="tracepilot-list-subtext"><?php echo esc_html(TracePilot_Helpers::format_datetime($entry['generated_at'])); ?></div>
                            </div>
                            <div class="tracepilot-history-inline">
                                <span class="tracepilot-badge tracepilot-badge-danger"><?php echo esc_html((int) ($entry['counts']['critical'] ?? 0)); ?> <?php esc_html_e('critical', 'wp-activity-logger-pro'); ?></span>
                                <span class="tracepilot-badge tracepilot-badge-warning"><?php echo esc_html((int) ($entry['counts']['warning'] ?? 0)); ?> <?php esc_html_e('warnings', 'wp-activity-logger-pro'); ?></span>
                                <span class="tracepilot-badge tracepilot-badge-info"><?php echo esc_html((int) ($entry['counts']['info'] ?? 0)); ?> <?php esc_html_e('info', 'wp-activity-logger-pro'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
                </article>
            </section>
        </div>

        <div class="tracepilot-tab-panel" data-tab-panel="inventory">
            <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2><?php esc_html_e('Scan Inventory', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('The scanner checks the active theme, plugin stack, server limits, WordPress debug status, REST API state, cron backlog, and the activity log database.', 'wp-activity-logger-pro'); ?></p>
            </div>
        </div>
        <div class="tracepilot-inventory-grid">
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('WordPress', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo esc_html($inventory['wordpress_version'] ?? ''); ?></strong>
                <small><?php esc_html_e('Core version scanned', 'wp-activity-logger-pro'); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('PHP', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo esc_html($inventory['php_version'] ?? ''); ?></strong>
                <small><?php esc_html_e('Runtime powering the site', 'wp-activity-logger-pro'); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('REST routes', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo esc_html(isset($inventory['rest_routes']) ? number_format_i18n((int) $inventory['rest_routes']) : 0); ?></strong>
                <small><?php esc_html_e('Registered API endpoints', 'wp-activity-logger-pro'); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('Cron events', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo esc_html(isset($inventory['cron_total']) ? number_format_i18n((int) $inventory['cron_total']) : 0); ?></strong>
                <small><?php esc_html_e('Scheduled jobs discovered', 'wp-activity-logger-pro'); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('WP_DEBUG', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo !empty($inventory['wp_debug']) ? esc_html__('Enabled', 'wp-activity-logger-pro') : esc_html__('Disabled', 'wp-activity-logger-pro'); ?></strong>
                <small><?php esc_html_e('Debug mode state', 'wp-activity-logger-pro'); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('Memory limit', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo esc_html($inventory['memory_limit'] ?? ''); ?></strong>
                <small><?php esc_html_e('Current PHP memory ceiling', 'wp-activity-logger-pro'); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('Max execution time', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo esc_html($inventory['max_execution_time'] ?? ''); ?></strong>
                <small><?php esc_html_e('Maximum PHP request duration', 'wp-activity-logger-pro'); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('Log table', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo !empty($inventory['table_ready']) ? esc_html__('Ready', 'wp-activity-logger-pro') : esc_html__('Missing', 'wp-activity-logger-pro'); ?></strong>
                <small><?php printf(esc_html__('%d stored log rows', 'wp-activity-logger-pro'), isset($inventory['log_total']) ? (int) $inventory['log_total'] : 0); ?></small>
            </div>
            <div class="tracepilot-metric-card">
                <span><?php esc_html_e('Active theme', 'wp-activity-logger-pro'); ?></span>
                <strong><?php echo esc_html(!empty($active_theme['name']) ? $active_theme['name'] : __('Unknown', 'wp-activity-logger-pro')); ?></strong>
                <small><?php esc_html_e('Theme currently rendered publicly', 'wp-activity-logger-pro'); ?></small>
            </div>
        </div>
            </section>
        </div>
    </section>
</div>
