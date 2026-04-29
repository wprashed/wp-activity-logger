<?php
/**
 * Threat detection template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = TracePilot_Helpers::get_settings();
$integrity = tracepilot()->file_integrity->get_baseline_status();
$vulnerability_report = array();
$active_threat_rules = (int) $options['monitor_failed_logins'] + (int) $options['monitor_unusual_logins'] + (int) $options['monitor_file_changes'] + (int) $options['monitor_privilege_escalation'];
$enabled_sources = array_values(array_intersect(array('wordfence', 'patchstack', 'wpscan'), (array) $options['vulnerability_sources']));
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Security monitoring', 'tracepilot'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Threat Detection', 'tracepilot'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Analyze your audit trail for brute-force activity, unusual login patterns, suspicious file changes, and privilege escalation events.', 'tracepilot'); ?></p>
        </div>
        <div class="tracepilot-hero-actions">
            <button id="tracepilot-analyze-threats" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Analyze Threats', 'tracepilot'); ?></button>
        </div>
    </section>

    <section class="tracepilot-stats-grid">
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Detection Rules', 'tracepilot'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html($active_threat_rules); ?></strong>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Checks currently watching new activity', 'tracepilot'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Threat Engine', 'tracepilot'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo !empty($options['enable_threat_detection']) ? esc_html__('On', 'tracepilot') : esc_html__('Off', 'tracepilot'); ?></strong>
            <span class="tracepilot-stat-meta"><?php echo !empty($options['enable_threat_notifications']) ? esc_html__('Notifications are active', 'tracepilot') : esc_html__('Notifications are paused', 'tracepilot'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Intel Providers', 'tracepilot'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html(count($enabled_sources)); ?></strong>
            <span class="tracepilot-stat-meta"><?php echo !empty($enabled_sources) ? esc_html(implode(', ', array_map('ucfirst', $enabled_sources))) : esc_html__('No external feed enabled', 'tracepilot'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Integrity Baseline', 'tracepilot'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo !empty($integrity['exists']) ? esc_html((int) $integrity['count']) : '0'; ?></strong>
            <span class="tracepilot-stat-meta"><?php echo !empty($integrity['exists']) ? esc_html__('Tracked files in the current baseline', 'tracepilot') : esc_html__('Build a baseline before scanning', 'tracepilot'); ?></span>
        </article>
    </section>

    <section class="tracepilot-grid tracepilot-grid-2">
        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Detection Rules', 'tracepilot'); ?></h2>
                    <p><?php esc_html_e('Choose which automated checks stay active as new logs are recorded.', 'tracepilot'); ?></p>
                </div>
            </div>

            <form id="tracepilot-threat-settings-form" class="tracepilot-form-stack">
                <div class="tracepilot-check-grid tracepilot-check-grid-wide">
                    <label class="tracepilot-check-card tracepilot-check-card-feature">
                        <input type="checkbox" name="wpal_options[enable_threat_detection]" value="1" <?php checked($options['enable_threat_detection'], 1); ?>>
                        <span>
                            <strong><?php esc_html_e('Enable threat detection', 'tracepilot'); ?></strong>
                            <small><?php esc_html_e('Analyze new log entries as they are written.', 'tracepilot'); ?></small>
                        </span>
                    </label>

                    <label class="tracepilot-check-card tracepilot-check-card-feature">
                        <input type="checkbox" name="wpal_options[enable_threat_notifications]" value="1" <?php checked($options['enable_threat_notifications'], 1); ?>>
                        <span>
                            <strong><?php esc_html_e('Send threat notifications', 'tracepilot'); ?></strong>
                            <small><?php esc_html_e('Use your configured notification channels for serious detections.', 'tracepilot'); ?></small>
                        </span>
                    </label>
                </div>

                <div>
                    <span class="tracepilot-section-label"><?php esc_html_e('Threat types to monitor', 'tracepilot'); ?></span>
                    <div class="tracepilot-check-grid tracepilot-check-grid-wide">
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_failed_logins]" value="1" <?php checked($options['monitor_failed_logins'], 1); ?>>
                            <span><strong><?php esc_html_e('Failed login attacks', 'tracepilot'); ?></strong></span>
                        </label>
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_unusual_logins]" value="1" <?php checked($options['monitor_unusual_logins'], 1); ?>>
                            <span><strong><?php esc_html_e('Unusual login patterns', 'tracepilot'); ?></strong></span>
                        </label>
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_file_changes]" value="1" <?php checked($options['monitor_file_changes'], 1); ?>>
                            <span><strong><?php esc_html_e('Suspicious file changes', 'tracepilot'); ?></strong></span>
                        </label>
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_privilege_escalation]" value="1" <?php checked($options['monitor_privilege_escalation'], 1); ?>>
                            <span><strong><?php esc_html_e('Privilege escalation', 'tracepilot'); ?></strong></span>
                        </label>
                    </div>
                </div>

                <div class="tracepilot-inline-actions">
                    <button type="button" id="tracepilot-save-threat-settings" class="tracepilot-btn tracepilot-btn-secondary"><?php esc_html_e('Save Detection Settings', 'tracepilot'); ?></button>
                    <span id="tracepilot-threat-settings-feedback" class="tracepilot-form-feedback"></span>
                </div>
            </form>
        </article>

        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Threat Summary', 'tracepilot'); ?></h2>
                    <p><?php esc_html_e('Run an on-demand scan to populate the live results panel.', 'tracepilot'); ?></p>
                </div>
            </div>

            <div class="tracepilot-toolbar-pills">
                <span class="tracepilot-pill"><?php echo !empty($options['enable_threat_detection']) ? esc_html__('Live analysis enabled', 'tracepilot') : esc_html__('Live analysis disabled', 'tracepilot'); ?></span>
                <span class="tracepilot-pill"><?php printf(esc_html__('%d rule(s) active', 'tracepilot'), $active_threat_rules); ?></span>
                <span class="tracepilot-pill"><?php echo !empty($options['enable_threat_notifications']) ? esc_html__('Alert routing ready', 'tracepilot') : esc_html__('Alerts not routed', 'tracepilot'); ?></span>
            </div>

            <div id="tracepilot-threat-summary" class="tracepilot-stats-grid" style="display:none; margin-bottom:14px;">
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('Total', 'tracepilot'); ?></span>
                    <strong id="tracepilot-total-threats" class="tracepilot-stat-value">0</strong>
                </article>
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('High', 'tracepilot'); ?></span>
                    <strong id="tracepilot-high-threats" class="tracepilot-stat-value">0</strong>
                </article>
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('Medium', 'tracepilot'); ?></span>
                    <strong id="tracepilot-medium-threats" class="tracepilot-stat-value">0</strong>
                </article>
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('Low', 'tracepilot'); ?></span>
                    <strong id="tracepilot-low-threats" class="tracepilot-stat-value">0</strong>
                </article>
            </div>

            <div id="tracepilot-threat-loading" class="tracepilot-note" style="display:none;">
                <?php esc_html_e('Analyzing activity logs for potential threats...', 'tracepilot'); ?>
            </div>

            <div id="tracepilot-no-threats" class="tracepilot-empty-panel" style="display:none;">
                <strong><?php esc_html_e('No threats detected', 'tracepilot'); ?></strong>
                <p><?php esc_html_e('The current log sample does not match any active detection rules.', 'tracepilot'); ?></p>
            </div>

            <div id="tracepilot-threat-results" style="display:none;">
                <div class="tracepilot-table-wrap">
                    <table class="tracepilot-table tracepilot-responsive-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Severity', 'tracepilot'); ?></th>
                                <th><?php esc_html_e('Type', 'tracepilot'); ?></th>
                                <th><?php esc_html_e('Description', 'tracepilot'); ?></th>
                                <th><?php esc_html_e('IP', 'tracepilot'); ?></th>
                                <th><?php esc_html_e('Time', 'tracepilot'); ?></th>
                                <th><?php esc_html_e('Actions', 'tracepilot'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="tracepilot-threats-table"></tbody>
                    </table>
                </div>
            </div>
        </article>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2><?php esc_html_e('Software Vulnerability Report', 'tracepilot'); ?></h2>
                <p><?php esc_html_e('Check installed plugins, themes, and WordPress core against Wordfence, Patchstack, and WPScan, then combine that with local file-integrity signals.', 'tracepilot'); ?></p>
            </div>
            <div class="tracepilot-hero-actions">
                <button type="button" id="tracepilot-scan-vulnerabilities" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Scan Software', 'tracepilot'); ?></button>
            </div>
        </div>

        <div class="tracepilot-toolbar-pills">
            <span class="tracepilot-pill"><?php echo !empty($enabled_sources) ? esc_html(implode(', ', array_map('ucfirst', $enabled_sources))) : esc_html__('No provider selected', 'tracepilot'); ?></span>
            <span class="tracepilot-pill"><?php echo !empty($options['vulnerability_include_file_integrity']) ? esc_html__('Integrity signals included', 'tracepilot') : esc_html__('Software feed only', 'tracepilot'); ?></span>
        </div>

        <div id="tracepilot-vulnerability-status" class="tracepilot-note">
            <?php
            esc_html_e('Run a manual scan to generate the latest software security report.', 'tracepilot');
            ?>
        </div>

            <div id="tracepilot-vulnerability-summary" class="tracepilot-stats-grid" style="<?php echo empty($vulnerability_report['summary']) ? 'display:none; margin-top:16px;' : 'margin-top:16px;'; ?>">
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('Affected', 'tracepilot'); ?></span>
                <strong id="tracepilot-vuln-affected" class="tracepilot-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['affected']) : 0; ?></strong>
            </article>
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('Critical', 'tracepilot'); ?></span>
                <strong id="tracepilot-vuln-critical" class="tracepilot-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['critical']) : 0; ?></strong>
            </article>
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('High', 'tracepilot'); ?></span>
                <strong id="tracepilot-vuln-high" class="tracepilot-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['high']) : 0; ?></strong>
            </article>
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('Clean', 'tracepilot'); ?></span>
                <strong id="tracepilot-vuln-clean" class="tracepilot-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['clean']) : 0; ?></strong>
            </article>
        </div>

        <div id="tracepilot-vulnerability-notes" class="tracepilot-list" style="margin-top:16px;<?php echo empty($vulnerability_report['notes']) ? 'display:none;' : ''; ?>">
            <?php foreach ((array) ($vulnerability_report['notes'] ?? array()) as $note) : ?>
                <div class="tracepilot-list-row"><div><?php echo esc_html($note); ?></div></div>
            <?php endforeach; ?>
        </div>

        <div class="tracepilot-table-wrap" style="margin-top:16px;">
            <table class="tracepilot-table tracepilot-responsive-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Component', 'tracepilot'); ?></th>
                        <th><?php esc_html_e('Type', 'tracepilot'); ?></th>
                        <th><?php esc_html_e('Version', 'tracepilot'); ?></th>
                        <th><?php esc_html_e('Severity', 'tracepilot'); ?></th>
                        <th><?php esc_html_e('Findings', 'tracepilot'); ?></th>
                        <th><?php esc_html_e('Recommended fix', 'tracepilot'); ?></th>
                    </tr>
                </thead>
                <tbody id="tracepilot-vulnerability-table">
                    <?php if (!empty($vulnerability_report['items'])) : ?>
                        <?php foreach ($vulnerability_report['items'] as $item) : ?>
                            <?php
                            $badge_class = in_array($item['severity'], array('critical', 'high'), true) ? 'danger' : ('medium' === $item['severity'] ? 'warning' : 'info');
                            $recommendation = __('No action needed right now.', 'tracepilot');
                            if (!empty($item['findings'][0]['fixed_in'])) {
                                $recommendation = sprintf(__('Update to %s or newer.', 'tracepilot'), $item['findings'][0]['fixed_in']);
                            } elseif (!empty($item['local_changes'])) {
                                $recommendation = __('Review recent file changes against the integrity baseline.', 'tracepilot');
                            } elseif (!empty($item['findings'])) {
                                $recommendation = __('Review the linked advisory and update or replace this component.', 'tracepilot');
                            }
                            ?>
                            <tr>
                                <td data-label="<?php esc_attr_e('Component', 'tracepilot'); ?>">
                                    <strong><?php echo esc_html($item['name']); ?></strong>
                                    <div class="tracepilot-list-subtext"><?php echo esc_html($item['slug']); ?></div>
                                </td>
                                <td data-label="<?php esc_attr_e('Type', 'tracepilot'); ?>"><?php echo esc_html(ucfirst($item['type'])); ?></td>
                                <td data-label="<?php esc_attr_e('Version', 'tracepilot'); ?>"><?php echo esc_html($item['version']); ?></td>
                                <td data-label="<?php esc_attr_e('Severity', 'tracepilot'); ?>"><span class="tracepilot-badge tracepilot-badge-<?php echo esc_attr($badge_class); ?>"><?php echo esc_html(ucfirst($item['severity'])); ?></span></td>
                                <td data-label="<?php esc_attr_e('Findings', 'tracepilot'); ?>">
                                    <?php echo esc_html((int) $item['finding_count']); ?>
                                    <?php if (!empty($item['local_change_count'])) : ?>
                                        <span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('%d local file changes', 'tracepilot'), (int) $item['local_change_count'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Recommended fix', 'tracepilot'); ?>"><?php echo esc_html($recommendation); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td data-label="<?php esc_attr_e('Status', 'tracepilot'); ?>" colspan="6"><?php esc_html_e('Run a scan to generate a software security report.', 'tracepilot'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2><?php esc_html_e('File Integrity', 'tracepilot'); ?></h2>
                <p><?php esc_html_e('Create a baseline of core, plugin, and theme files, then scan for new, deleted, or modified files.', 'tracepilot'); ?></p>
            </div>
            <div class="tracepilot-hero-actions">
                <button type="button" id="tracepilot-build-baseline" class="tracepilot-btn tracepilot-btn-secondary"><?php esc_html_e('Build Baseline', 'tracepilot'); ?></button>
                <button type="button" id="tracepilot-scan-integrity" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Scan Integrity', 'tracepilot'); ?></button>
            </div>
        </div>
        <div class="tracepilot-toolbar-pills">
            <span class="tracepilot-pill"><?php echo !empty($integrity['exists']) ? esc_html__('Baseline ready', 'tracepilot') : esc_html__('Baseline missing', 'tracepilot'); ?></span>
            <?php if (!empty($integrity['exists'])) : ?>
                <span class="tracepilot-pill"><?php printf(esc_html__('%d files tracked', 'tracepilot'), (int) $integrity['count']); ?></span>
            <?php endif; ?>
        </div>
        <div class="tracepilot-note" id="tracepilot-integrity-status">
            <?php
            echo $integrity['exists']
                ? esc_html(sprintf(__('Baseline created %1$s with %2$d files.', 'tracepilot'), $integrity['created_at'], $integrity['count']))
                : esc_html__('No baseline exists yet.', 'tracepilot');
            ?>
        </div>
        <div id="tracepilot-integrity-results" class="tracepilot-list" style="margin-top:16px;"></div>
    </section>
</div>
