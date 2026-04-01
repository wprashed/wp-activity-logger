<?php
/**
 * Diagnostics template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$diagnostics = wp_activity_logger_pro()->diagnostics;
$report = $diagnostics->get_latest_report();
if (empty($report)) {
    $report = $diagnostics->run_diagnostics(false);
}

$history = $diagnostics->get_scan_history(8);
$client_errors = get_option(WPAL_Diagnostics::CLIENT_ERRORS_OPTION, array());
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

<div class="wrap wpal-wrap">
    <section class="wpal-hero wpal-hero-compact">
        <div>
            <p class="wpal-eyebrow"><?php esc_html_e('System scanner', 'wp-activity-logger-pro'); ?></p>
            <h1 class="wpal-page-title"><?php esc_html_e('Diagnostics & Conflict Detection', 'wp-activity-logger-pro'); ?></h1>
            <p class="wpal-hero-copy"><?php esc_html_e('Scan the site, explain technical problems in plain language, test fixes privately in admin-only safe mode, and track when issues started appearing.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="wpal-hero-actions">
            <button type="button" class="wpal-btn wpal-btn-primary" id="wpal-run-diagnostics"><?php esc_html_e('Run Full Scan', 'wp-activity-logger-pro'); ?></button>
            <?php if (!empty($safe_mode['enabled'])) : ?>
                <button type="button" class="wpal-btn wpal-btn-outline-danger" id="wpal-disable-safe-mode"><?php esc_html_e('Disable Safe Mode', 'wp-activity-logger-pro'); ?></button>
            <?php else : ?>
                <button type="button" class="wpal-btn wpal-btn-secondary" id="wpal-enable-safe-mode"><?php esc_html_e('Start Safe Mode', 'wp-activity-logger-pro'); ?></button>
            <?php endif; ?>
        </div>
    </section>

    <section class="wpal-stats-grid" id="wpal-diagnostics-summary">
        <article class="wpal-stat-card wpal-score-card wpal-score-card-<?php echo esc_attr($score_tone); ?>">
            <span class="wpal-stat-label"><?php esc_html_e('Health Score', 'wp-activity-logger-pro'); ?></span>
            <span class="wpal-stat-value"><?php echo esc_html($health_score); ?></span>
            <span class="wpal-stat-meta"><?php printf(esc_html__('Last scan: %s', 'wp-activity-logger-pro'), !empty($report['generated_at']) ? esc_html(WPAL_Helpers::format_datetime($report['generated_at'])) : esc_html__('Not saved yet', 'wp-activity-logger-pro')); ?></span>
        </article>
        <article class="wpal-stat-card">
            <span class="wpal-stat-label"><?php esc_html_e('Critical Issues', 'wp-activity-logger-pro'); ?></span>
            <span class="wpal-stat-value"><?php echo esc_html((int) $counts['critical']); ?></span>
            <span class="wpal-stat-meta"><?php esc_html_e('Immediate breakage or severe risk', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="wpal-stat-card">
            <span class="wpal-stat-label"><?php esc_html_e('Warnings', 'wp-activity-logger-pro'); ?></span>
            <span class="wpal-stat-value"><?php echo esc_html((int) $counts['warning']); ?></span>
            <span class="wpal-stat-meta"><?php esc_html_e('Likely instability or conflict signals', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="wpal-stat-card">
            <span class="wpal-stat-label"><?php esc_html_e('Inventory', 'wp-activity-logger-pro'); ?></span>
            <span class="wpal-stat-value"><?php echo esc_html(count($active_plugins)); ?></span>
            <span class="wpal-stat-meta"><?php printf(esc_html__('Plugins checked, theme: %s', 'wp-activity-logger-pro'), !empty($active_theme['name']) ? esc_html($active_theme['name']) : esc_html__('Unknown', 'wp-activity-logger-pro')); ?></span>
        </article>
    </section>

    <section class="wpal-grid wpal-grid-2">
        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Issue List', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Every issue is translated into plain language, then paired with the safest next steps.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-stack" id="wpal-diagnostics-issues">
                <?php if (empty($issues)) : ?>
                    <div class="wpal-empty-panel">
                        <h3><?php esc_html_e('No active issues in the latest scan', 'wp-activity-logger-pro'); ?></h3>
                        <p><?php esc_html_e('Run a fresh scan after reproducing the problem if the site still feels unstable.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($issues as $issue) : ?>
                        <?php
                        $severity = isset($issue['severity']) ? $issue['severity'] : 'info';
                        $badge_class = 'wpal-badge-info';
                        if ('critical' === $severity) {
                            $badge_class = 'wpal-badge-danger';
                        } elseif ('warning' === $severity) {
                            $badge_class = 'wpal-badge-warning';
                        }
                        ?>
                        <article class="wpal-detail-card wpal-issue-card">
                            <div class="wpal-panel-head">
                                <div>
                                    <h3><?php echo esc_html($issue['message']); ?></h3>
                                    <p><?php echo esc_html($issue['explanation']); ?></p>
                                </div>
                                <span class="wpal-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($severity_labels[$severity]); ?></span>
                            </div>

                            <?php if (!empty($issue['page']) || !empty($issue['raw_error'])) : ?>
                                <div class="wpal-note">
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
                                <div class="wpal-pill-row">
                                    <?php foreach ((array) $issue['plugins'] as $plugin_slug) : ?>
                                        <span class="wpal-pill"><?php echo esc_html($plugin_slug); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($issue['suggestions'])) : ?>
                                <div class="wpal-suggestion-grid">
                                    <?php foreach ((array) $issue['suggestions'] as $suggestion) : ?>
                                        <div class="wpal-suggestion-card">
                                            <div class="wpal-suggestion-head">
                                                <strong><?php echo esc_html($suggestion['title']); ?></strong>
                                                <span class="wpal-meta-pill"><?php echo esc_html($confidence_labels[$suggestion['confidence']]); ?></span>
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

        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Safe Mode Debugging', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Disable selected plugins only for your own admin session so visitors never see the experiment.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <div class="wpal-note" id="wpal-safe-mode-status">
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

            <div class="wpal-form-stack">
                <label>
                    <span><?php esc_html_e('Plugins to hide in your admin session', 'wp-activity-logger-pro'); ?></span>
                    <select multiple class="wpal-input" id="wpal-safe-mode-plugins">
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

            <div class="wpal-inline-actions">
                <button type="button" class="wpal-btn wpal-btn-secondary" id="wpal-enable-safe-mode-inline"><?php esc_html_e('Enable Safe Mode With Selection', 'wp-activity-logger-pro'); ?></button>
                <button type="button" class="wpal-btn wpal-btn-outline-danger" id="wpal-disable-safe-mode-inline"><?php esc_html_e('Restore Normal Session', 'wp-activity-logger-pro'); ?></button>
            </div>

            <?php if (!empty($conflict_plan['group_a']) || !empty($conflict_plan['group_b'])) : ?>
                <div class="wpal-note">
                    <strong><?php esc_html_e('Binary conflict test', 'wp-activity-logger-pro'); ?></strong>
                    <?php echo ' ' . esc_html($conflict_plan['summary']); ?>
                </div>
                <div class="wpal-suggestion-grid">
                    <?php if (!empty($conflict_plan['group_a'])) : ?>
                        <div class="wpal-suggestion-card">
                            <div class="wpal-suggestion-head">
                                <strong><?php esc_html_e('Test Group A Disabled', 'wp-activity-logger-pro'); ?></strong>
                                <span class="wpal-meta-pill"><?php esc_html_e('Batch 1', 'wp-activity-logger-pro'); ?></span>
                            </div>
                            <p><?php echo esc_html(implode(', ', wp_list_pluck((array) $conflict_plan['group_a'], 'name'))); ?></p>
                            <button
                                type="button"
                                class="wpal-btn wpal-btn-outline-primary wpal-btn-sm wpal-safe-mode-preset"
                                data-plugins="<?php echo esc_attr(wp_json_encode(wp_list_pluck((array) $conflict_plan['group_a'], 'file'))); ?>"
                            ><?php esc_html_e('Disable This Group In Safe Mode', 'wp-activity-logger-pro'); ?></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($conflict_plan['group_b'])) : ?>
                        <div class="wpal-suggestion-card">
                            <div class="wpal-suggestion-head">
                                <strong><?php esc_html_e('Test Group B Disabled', 'wp-activity-logger-pro'); ?></strong>
                                <span class="wpal-meta-pill"><?php esc_html_e('Batch 2', 'wp-activity-logger-pro'); ?></span>
                            </div>
                            <p><?php echo esc_html(implode(', ', wp_list_pluck((array) $conflict_plan['group_b'], 'name'))); ?></p>
                            <button
                                type="button"
                                class="wpal-btn wpal-btn-outline-primary wpal-btn-sm wpal-safe-mode-preset"
                                data-plugins="<?php echo esc_attr(wp_json_encode(wp_list_pluck((array) $conflict_plan['group_b'], 'file'))); ?>"
                            ><?php esc_html_e('Disable This Group In Safe Mode', 'wp-activity-logger-pro'); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="wpal-grid wpal-grid-2">
        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Issue Timeline', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('See when a problem first appeared, how often it has repeated, and whether it lines up with changes on the site.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-list" id="wpal-diagnostics-timeline">
                <?php if (empty($timeline)) : ?>
                    <div class="wpal-empty-panel">
                        <p><?php esc_html_e('No tracked issue timeline yet. Run a saved scan to start building history.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($timeline as $entry) : ?>
                        <div class="wpal-list-row">
                            <div>
                                <strong><?php echo esc_html($entry['message']); ?></strong>
                                <div class="wpal-list-subtext">
                                    <?php
                                    printf(
                                        esc_html__('First seen %1$s, last seen %2$s', 'wp-activity-logger-pro'),
                                        esc_html(WPAL_Helpers::format_datetime($entry['first_seen'])),
                                        esc_html(WPAL_Helpers::format_datetime($entry['last_seen']))
                                    );
                                    ?>
                                </div>
                            </div>
                            <div class="wpal-list-value">
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

        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Change Correlation', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('These pair the first appearance of a problem with nearby updates or configuration changes.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-list">
                <?php if (empty($correlations)) : ?>
                    <div class="wpal-empty-panel">
                        <p><?php esc_html_e('No strong issue-to-change correlation has been found yet.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($correlations as $correlation) : ?>
                        <div class="wpal-list-row">
                            <div>
                                <strong><?php echo esc_html($correlation['issue']); ?></strong>
                                <div class="wpal-list-subtext">
                                    <?php
                                    printf(
                                        esc_html__('Started near %1$s at %2$s', 'wp-activity-logger-pro'),
                                        esc_html($correlation['change_label']),
                                        esc_html(WPAL_Helpers::format_datetime($correlation['change_time']))
                                    );
                                    ?>
                                </div>
                            </div>
                            <span class="wpal-meta-pill">
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

        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('AI Assistant Preview', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('This is a lightweight contextual assistant built from the current scan data. It is meant to guide the next debugging step quickly.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <div class="wpal-form-stack">
                <label>
                    <span><?php esc_html_e('Ask a question', 'wp-activity-logger-pro'); ?></span>
                    <textarea class="wpal-input" id="wpal-diagnostics-question" placeholder="<?php echo esc_attr($assistant_hint); ?>"></textarea>
                </label>
            </div>

            <div class="wpal-inline-actions">
                <button type="button" class="wpal-btn wpal-btn-primary" id="wpal-ask-diagnostics-ai"><?php esc_html_e('Get Answer', 'wp-activity-logger-pro'); ?></button>
            </div>

            <div class="wpal-note" id="wpal-diagnostics-answer">
                <?php esc_html_e('Ask about slowness, conflicts, what to disable first, or what the latest scan means.', 'wp-activity-logger-pro'); ?>
            </div>
        </article>
    </section>

    <section class="wpal-grid wpal-grid-2">
        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Recent Browser & Runtime Signals', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('JavaScript errors collected from the browser can reveal conflicts that never make it into PHP logs.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-list">
                <?php if (empty($client_errors)) : ?>
                    <div class="wpal-empty-panel">
                        <p><?php esc_html_e('No browser-side runtime errors have been captured recently.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($client_errors as $entry) : ?>
                        <div class="wpal-list-row">
                            <div>
                                <strong><?php echo esc_html($entry['message']); ?></strong>
                                <div class="wpal-list-subtext">
                                    <?php
                                    printf(
                                        esc_html__('%1$s on %2$s', 'wp-activity-logger-pro'),
                                        esc_html(WPAL_Helpers::format_datetime($entry['time'])),
                                        esc_html(!empty($entry['page']) ? $entry['page'] : __('Unknown page', 'wp-activity-logger-pro'))
                                    );
                                    ?>
                                </div>
                            </div>
                            <span class="wpal-meta-pill"><?php echo esc_html(!empty($entry['source']) ? $entry['source'] : __('Browser', 'wp-activity-logger-pro')); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Recent Scan History', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Compare health score changes over time and see when the issue count started climbing.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <div class="wpal-list" id="wpal-diagnostics-history">
                <?php if (empty($history)) : ?>
                    <div class="wpal-empty-panel">
                        <p><?php esc_html_e('No saved scan history yet. Run the scanner to create the first history point.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($history as $entry) : ?>
                        <div class="wpal-list-row">
                            <div>
                                <strong>
                                    <?php
                                    printf(
                                        esc_html__('Health score %d', 'wp-activity-logger-pro'),
                                        isset($entry['health_score']) ? (int) $entry['health_score'] : 0
                                    );
                                    ?>
                                </strong>
                                <div class="wpal-list-subtext"><?php echo esc_html(WPAL_Helpers::format_datetime($entry['generated_at'])); ?></div>
                            </div>
                            <div class="wpal-history-inline">
                                <span class="wpal-badge wpal-badge-danger"><?php echo esc_html((int) ($entry['counts']['critical'] ?? 0)); ?> <?php esc_html_e('critical', 'wp-activity-logger-pro'); ?></span>
                                <span class="wpal-badge wpal-badge-warning"><?php echo esc_html((int) ($entry['counts']['warning'] ?? 0)); ?> <?php esc_html_e('warnings', 'wp-activity-logger-pro'); ?></span>
                                <span class="wpal-badge wpal-badge-info"><?php echo esc_html((int) ($entry['counts']['info'] ?? 0)); ?> <?php esc_html_e('info', 'wp-activity-logger-pro'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <section class="wpal-panel">
        <div class="wpal-panel-head">
            <div>
                <h2><?php esc_html_e('Scan Inventory', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('The scanner checks the active theme, plugin stack, server limits, WordPress debug status, REST API state, cron backlog, and the activity log database.', 'wp-activity-logger-pro'); ?></p>
            </div>
        </div>
        <div class="wpal-grid wpal-grid-2">
            <div class="wpal-detail-card">
                <dl>
                    <dt><?php esc_html_e('WordPress', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html($inventory['wordpress_version'] ?? ''); ?></dd>
                    <dt><?php esc_html_e('PHP', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html($inventory['php_version'] ?? ''); ?></dd>
                    <dt><?php esc_html_e('REST routes', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html(isset($inventory['rest_routes']) ? number_format_i18n((int) $inventory['rest_routes']) : 0); ?></dd>
                    <dt><?php esc_html_e('Cron events', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html(isset($inventory['cron_total']) ? number_format_i18n((int) $inventory['cron_total']) : 0); ?></dd>
                    <dt><?php esc_html_e('WP_DEBUG', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo !empty($inventory['wp_debug']) ? esc_html__('Enabled', 'wp-activity-logger-pro') : esc_html__('Disabled', 'wp-activity-logger-pro'); ?></dd>
                </dl>
            </div>
            <div class="wpal-detail-card">
                <dl>
                    <dt><?php esc_html_e('Memory limit', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html($inventory['memory_limit'] ?? ''); ?></dd>
                    <dt><?php esc_html_e('Max execution time', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html($inventory['max_execution_time'] ?? ''); ?></dd>
                    <dt><?php esc_html_e('Log table', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo !empty($inventory['table_ready']) ? esc_html__('Ready', 'wp-activity-logger-pro') : esc_html__('Missing', 'wp-activity-logger-pro'); ?></dd>
                    <dt><?php esc_html_e('Stored log rows', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html(isset($inventory['log_total']) ? number_format_i18n((int) $inventory['log_total']) : 0); ?></dd>
                    <dt><?php esc_html_e('Active theme', 'wp-activity-logger-pro'); ?></dt>
                    <dd><?php echo esc_html(!empty($active_theme['name']) ? $active_theme['name'] : __('Unknown', 'wp-activity-logger-pro')); ?></dd>
                </dl>
            </div>
        </div>
    </section>
</div>
