<?php
/**
 * Threat detection template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = TracePilot_Helpers::get_settings();
$integrity = tracepilot_for_wordpress()->file_integrity->get_baseline_status();
$vulnerability_report = array();
$active_threat_rules = (int) $options['monitor_failed_logins'] + (int) $options['monitor_unusual_logins'] + (int) $options['monitor_file_changes'] + (int) $options['monitor_privilege_escalation'];
$enabled_sources = array_values(array_intersect(array('wordfence', 'patchstack', 'wpscan'), (array) $options['vulnerability_sources']));
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Security monitoring', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Threat Detection', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Analyze your audit trail for brute-force activity, unusual login patterns, suspicious file changes, and privilege escalation events.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="tracepilot-hero-actions">
            <button id="tracepilot-analyze-threats" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Analyze Threats', 'wp-activity-logger-pro'); ?></button>
        </div>
    </section>

    <section class="tracepilot-stats-grid">
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Detection Rules', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html($active_threat_rules); ?></strong>
            <span class="tracepilot-stat-meta"><?php esc_html_e('Checks currently watching new activity', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Threat Engine', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo !empty($options['enable_threat_detection']) ? esc_html__('On', 'wp-activity-logger-pro') : esc_html__('Off', 'wp-activity-logger-pro'); ?></strong>
            <span class="tracepilot-stat-meta"><?php echo !empty($options['enable_threat_notifications']) ? esc_html__('Notifications are active', 'wp-activity-logger-pro') : esc_html__('Notifications are paused', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Intel Providers', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo esc_html(count($enabled_sources)); ?></strong>
            <span class="tracepilot-stat-meta"><?php echo !empty($enabled_sources) ? esc_html(implode(', ', array_map('ucfirst', $enabled_sources))) : esc_html__('No external feed enabled', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="tracepilot-stat-card">
            <span class="tracepilot-stat-label"><?php esc_html_e('Integrity Baseline', 'wp-activity-logger-pro'); ?></span>
            <strong class="tracepilot-stat-value"><?php echo !empty($integrity['exists']) ? esc_html((int) $integrity['count']) : '0'; ?></strong>
            <span class="tracepilot-stat-meta"><?php echo !empty($integrity['exists']) ? esc_html__('Tracked files in the current baseline', 'wp-activity-logger-pro') : esc_html__('Build a baseline before scanning', 'wp-activity-logger-pro'); ?></span>
        </article>
    </section>

    <section class="tracepilot-grid tracepilot-grid-2">
        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Detection Rules', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Choose which automated checks stay active as new logs are recorded.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <form id="tracepilot-threat-settings-form" class="tracepilot-form-stack">
                <div class="tracepilot-check-grid tracepilot-check-grid-wide">
                    <label class="tracepilot-check-card tracepilot-check-card-feature">
                        <input type="checkbox" name="wpal_options[enable_threat_detection]" value="1" <?php checked($options['enable_threat_detection'], 1); ?>>
                        <span>
                            <strong><?php esc_html_e('Enable threat detection', 'wp-activity-logger-pro'); ?></strong>
                            <small><?php esc_html_e('Analyze new log entries as they are written.', 'wp-activity-logger-pro'); ?></small>
                        </span>
                    </label>

                    <label class="tracepilot-check-card tracepilot-check-card-feature">
                        <input type="checkbox" name="wpal_options[enable_threat_notifications]" value="1" <?php checked($options['enable_threat_notifications'], 1); ?>>
                        <span>
                            <strong><?php esc_html_e('Send threat notifications', 'wp-activity-logger-pro'); ?></strong>
                            <small><?php esc_html_e('Use your configured notification channels for serious detections.', 'wp-activity-logger-pro'); ?></small>
                        </span>
                    </label>
                </div>

                <div>
                    <span class="tracepilot-section-label"><?php esc_html_e('Threat types to monitor', 'wp-activity-logger-pro'); ?></span>
                    <div class="tracepilot-check-grid tracepilot-check-grid-wide">
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_failed_logins]" value="1" <?php checked($options['monitor_failed_logins'], 1); ?>>
                            <span><strong><?php esc_html_e('Failed login attacks', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_unusual_logins]" value="1" <?php checked($options['monitor_unusual_logins'], 1); ?>>
                            <span><strong><?php esc_html_e('Unusual login patterns', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_file_changes]" value="1" <?php checked($options['monitor_file_changes'], 1); ?>>
                            <span><strong><?php esc_html_e('Suspicious file changes', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="tracepilot-check-card tracepilot-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_privilege_escalation]" value="1" <?php checked($options['monitor_privilege_escalation'], 1); ?>>
                            <span><strong><?php esc_html_e('Privilege escalation', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                    </div>
                </div>

                <div class="tracepilot-inline-actions">
                    <button type="button" id="tracepilot-save-threat-settings" class="tracepilot-btn tracepilot-btn-secondary"><?php esc_html_e('Save Detection Settings', 'wp-activity-logger-pro'); ?></button>
                    <span id="tracepilot-threat-settings-feedback" class="tracepilot-form-feedback"></span>
                </div>
            </form>
        </article>

        <article class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Threat Summary', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Run an on-demand scan to populate the live results panel.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <div class="tracepilot-toolbar-pills">
                <span class="tracepilot-pill"><?php echo !empty($options['enable_threat_detection']) ? esc_html__('Live analysis enabled', 'wp-activity-logger-pro') : esc_html__('Live analysis disabled', 'wp-activity-logger-pro'); ?></span>
                <span class="tracepilot-pill"><?php printf(esc_html__('%d rule(s) active', 'wp-activity-logger-pro'), $active_threat_rules); ?></span>
                <span class="tracepilot-pill"><?php echo !empty($options['enable_threat_notifications']) ? esc_html__('Alert routing ready', 'wp-activity-logger-pro') : esc_html__('Alerts not routed', 'wp-activity-logger-pro'); ?></span>
            </div>

            <div id="tracepilot-threat-summary" class="tracepilot-stats-grid" style="display:none; margin-bottom:14px;">
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('Total', 'wp-activity-logger-pro'); ?></span>
                    <strong id="tracepilot-total-threats" class="tracepilot-stat-value">0</strong>
                </article>
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('High', 'wp-activity-logger-pro'); ?></span>
                    <strong id="tracepilot-high-threats" class="tracepilot-stat-value">0</strong>
                </article>
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('Medium', 'wp-activity-logger-pro'); ?></span>
                    <strong id="tracepilot-medium-threats" class="tracepilot-stat-value">0</strong>
                </article>
                <article class="tracepilot-stat-card">
                    <span class="tracepilot-stat-label"><?php esc_html_e('Low', 'wp-activity-logger-pro'); ?></span>
                    <strong id="tracepilot-low-threats" class="tracepilot-stat-value">0</strong>
                </article>
            </div>

            <div id="tracepilot-threat-loading" class="tracepilot-note" style="display:none;">
                <?php esc_html_e('Analyzing activity logs for potential threats...', 'wp-activity-logger-pro'); ?>
            </div>

            <div id="tracepilot-no-threats" class="tracepilot-empty-panel" style="display:none;">
                <strong><?php esc_html_e('No threats detected', 'wp-activity-logger-pro'); ?></strong>
                <p><?php esc_html_e('The current log sample does not match any active detection rules.', 'wp-activity-logger-pro'); ?></p>
            </div>

            <div id="tracepilot-threat-results" style="display:none;">
                <div class="tracepilot-table-wrap">
                    <table class="tracepilot-table tracepilot-responsive-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></th>
                                <th><?php esc_html_e('Type', 'wp-activity-logger-pro'); ?></th>
                                <th><?php esc_html_e('Description', 'wp-activity-logger-pro'); ?></th>
                                <th><?php esc_html_e('IP', 'wp-activity-logger-pro'); ?></th>
                                <th><?php esc_html_e('Time', 'wp-activity-logger-pro'); ?></th>
                                <th><?php esc_html_e('Actions', 'wp-activity-logger-pro'); ?></th>
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
                <h2><?php esc_html_e('Software Vulnerability Report', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('Check installed plugins, themes, and WordPress core against Wordfence, Patchstack, and WPScan, then combine that with local file-integrity signals.', 'wp-activity-logger-pro'); ?></p>
            </div>
            <div class="tracepilot-hero-actions">
                <button type="button" id="tracepilot-scan-vulnerabilities" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Scan Software', 'wp-activity-logger-pro'); ?></button>
            </div>
        </div>

        <div class="tracepilot-toolbar-pills">
            <span class="tracepilot-pill"><?php echo !empty($enabled_sources) ? esc_html(implode(', ', array_map('ucfirst', $enabled_sources))) : esc_html__('No provider selected', 'wp-activity-logger-pro'); ?></span>
            <span class="tracepilot-pill"><?php echo !empty($options['vulnerability_include_file_integrity']) ? esc_html__('Integrity signals included', 'wp-activity-logger-pro') : esc_html__('Software feed only', 'wp-activity-logger-pro'); ?></span>
        </div>

        <div id="tracepilot-vulnerability-status" class="tracepilot-note">
            <?php
            esc_html_e('Run a manual scan to generate the latest software security report.', 'wp-activity-logger-pro');
            ?>
        </div>

            <div id="tracepilot-vulnerability-summary" class="tracepilot-stats-grid" style="<?php echo empty($vulnerability_report['summary']) ? 'display:none; margin-top:16px;' : 'margin-top:16px;'; ?>">
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('Affected', 'wp-activity-logger-pro'); ?></span>
                <strong id="tracepilot-vuln-affected" class="tracepilot-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['affected']) : 0; ?></strong>
            </article>
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('Critical', 'wp-activity-logger-pro'); ?></span>
                <strong id="tracepilot-vuln-critical" class="tracepilot-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['critical']) : 0; ?></strong>
            </article>
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('High', 'wp-activity-logger-pro'); ?></span>
                <strong id="tracepilot-vuln-high" class="tracepilot-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['high']) : 0; ?></strong>
            </article>
            <article class="tracepilot-stat-card">
                <span class="tracepilot-stat-label"><?php esc_html_e('Clean', 'wp-activity-logger-pro'); ?></span>
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
                        <th><?php esc_html_e('Component', 'wp-activity-logger-pro'); ?></th>
                        <th><?php esc_html_e('Type', 'wp-activity-logger-pro'); ?></th>
                        <th><?php esc_html_e('Version', 'wp-activity-logger-pro'); ?></th>
                        <th><?php esc_html_e('Severity', 'wp-activity-logger-pro'); ?></th>
                        <th><?php esc_html_e('Findings', 'wp-activity-logger-pro'); ?></th>
                        <th><?php esc_html_e('Recommended fix', 'wp-activity-logger-pro'); ?></th>
                    </tr>
                </thead>
                <tbody id="tracepilot-vulnerability-table">
                    <?php if (!empty($vulnerability_report['items'])) : ?>
                        <?php foreach ($vulnerability_report['items'] as $item) : ?>
                            <?php
                            $badge_class = in_array($item['severity'], array('critical', 'high'), true) ? 'danger' : ('medium' === $item['severity'] ? 'warning' : 'info');
                            $recommendation = __('No action needed right now.', 'wp-activity-logger-pro');
                            if (!empty($item['findings'][0]['fixed_in'])) {
                                $recommendation = sprintf(__('Update to %s or newer.', 'wp-activity-logger-pro'), $item['findings'][0]['fixed_in']);
                            } elseif (!empty($item['local_changes'])) {
                                $recommendation = __('Review recent file changes against the integrity baseline.', 'wp-activity-logger-pro');
                            } elseif (!empty($item['findings'])) {
                                $recommendation = __('Review the linked advisory and update or replace this component.', 'wp-activity-logger-pro');
                            }
                            ?>
                            <tr>
                                <td data-label="<?php esc_attr_e('Component', 'wp-activity-logger-pro'); ?>">
                                    <strong><?php echo esc_html($item['name']); ?></strong>
                                    <div class="tracepilot-list-subtext"><?php echo esc_html($item['slug']); ?></div>
                                </td>
                                <td data-label="<?php esc_attr_e('Type', 'wp-activity-logger-pro'); ?>"><?php echo esc_html(ucfirst($item['type'])); ?></td>
                                <td data-label="<?php esc_attr_e('Version', 'wp-activity-logger-pro'); ?>"><?php echo esc_html($item['version']); ?></td>
                                <td data-label="<?php esc_attr_e('Severity', 'wp-activity-logger-pro'); ?>"><span class="tracepilot-badge tracepilot-badge-<?php echo esc_attr($badge_class); ?>"><?php echo esc_html(ucfirst($item['severity'])); ?></span></td>
                                <td data-label="<?php esc_attr_e('Findings', 'wp-activity-logger-pro'); ?>">
                                    <?php echo esc_html((int) $item['finding_count']); ?>
                                    <?php if (!empty($item['local_change_count'])) : ?>
                                        <span class="tracepilot-meta-pill"><?php echo esc_html(sprintf(__('%d local file changes', 'wp-activity-logger-pro'), (int) $item['local_change_count'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Recommended fix', 'wp-activity-logger-pro'); ?>"><?php echo esc_html($recommendation); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td data-label="<?php esc_attr_e('Status', 'wp-activity-logger-pro'); ?>" colspan="6"><?php esc_html_e('Run a scan to generate a software security report.', 'wp-activity-logger-pro'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="tracepilot-panel">
        <div class="tracepilot-panel-head">
            <div>
                <h2><?php esc_html_e('File Integrity', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('Create a baseline of core, plugin, and theme files, then scan for new, deleted, or modified files.', 'wp-activity-logger-pro'); ?></p>
            </div>
            <div class="tracepilot-hero-actions">
                <button type="button" id="tracepilot-build-baseline" class="tracepilot-btn tracepilot-btn-secondary"><?php esc_html_e('Build Baseline', 'wp-activity-logger-pro'); ?></button>
                <button type="button" id="tracepilot-scan-integrity" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Scan Integrity', 'wp-activity-logger-pro'); ?></button>
            </div>
        </div>
        <div class="tracepilot-toolbar-pills">
            <span class="tracepilot-pill"><?php echo !empty($integrity['exists']) ? esc_html__('Baseline ready', 'wp-activity-logger-pro') : esc_html__('Baseline missing', 'wp-activity-logger-pro'); ?></span>
            <?php if (!empty($integrity['exists'])) : ?>
                <span class="tracepilot-pill"><?php printf(esc_html__('%d files tracked', 'wp-activity-logger-pro'), (int) $integrity['count']); ?></span>
            <?php endif; ?>
        </div>
        <div class="tracepilot-note" id="tracepilot-integrity-status">
            <?php
            echo $integrity['exists']
                ? esc_html(sprintf(__('Baseline created %1$s with %2$d files.', 'wp-activity-logger-pro'), $integrity['created_at'], $integrity['count']))
                : esc_html__('No baseline exists yet.', 'wp-activity-logger-pro');
            ?>
        </div>
        <div id="tracepilot-integrity-results" class="tracepilot-list" style="margin-top:16px;"></div>
    </section>
</div>

<script>
jQuery(function($) {
    $('#tracepilot-save-threat-settings').on('click', function() {
        const feedback = $('#tracepilot-threat-settings-feedback');
        feedback.text('Saving...');

        const options = {
            enable_threat_detection: 0,
            enable_threat_notifications: 0,
            monitor_failed_logins: 0,
            monitor_unusual_logins: 0,
            monitor_file_changes: 0,
            monitor_privilege_escalation: 0
        };
        new window.FormData(document.getElementById('tracepilot-threat-settings-form')).forEach(function(value, key) {
            const match = key.match(/^wpal_options\[([^\]]+)\]$/);
            if (match) {
                options[match[1]] = value;
            }
        });

        $.post(ajaxurl, {
            action: 'tracepilot_save_settings',
            nonce: '<?php echo esc_js(wp_create_nonce('tracepilot_nonce')); ?>',
            wpal_options: options
        }).done(function(response) {
            feedback.text(response.success ? response.data.message : 'Unable to save settings.');
        });
    });

    $('#tracepilot-analyze-threats').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('<?php echo esc_js(__('Analyzing...', 'wp-activity-logger-pro')); ?>');
        $('#tracepilot-threat-loading').show();
        $('#tracepilot-threat-results, #tracepilot-no-threats, #tracepilot-threat-summary').hide();

        $.post(ajaxurl, {
            action: 'tracepilot_analyze_threats',
            nonce: '<?php echo esc_js(wp_create_nonce('tracepilot_nonce')); ?>'
        }).done(function(response) {
            if (!response.success) {
                window.alert(response.data.message || 'Unable to analyze threats.');
                return;
            }

            const data = response.data;
            $('#tracepilot-total-threats').text(data.summary.total);
            $('#tracepilot-high-threats').text(data.summary.high);
            $('#tracepilot-medium-threats').text(data.summary.medium);
            $('#tracepilot-low-threats').text(data.summary.low);
            $('#tracepilot-threat-summary').show();

            if (!data.threats.length) {
                $('#tracepilot-no-threats').show();
                return;
            }

            const rows = data.threats.map(function(threat) {
                const badgeClass = threat.severity === 'high' ? 'danger' : (threat.severity === 'medium' ? 'warning' : 'info');
                const badge = '<span class="tracepilot-badge tracepilot-badge-' + badgeClass + '">' + threat.severity.charAt(0).toUpperCase() + threat.severity.slice(1) + '</span>';
                const label = threat.type.replace(/_/g, ' ');
                const userAction = threat.user_id ? '<button type="button" class="tracepilot-btn tracepilot-btn-secondary tracepilot-force-logout" data-user-id="' + threat.user_id + '"><?php echo esc_js(__('Force Logout', 'wp-activity-logger-pro')); ?></button>' : '';
                const ipAction = threat.ip ? '<button type="button" class="tracepilot-btn tracepilot-btn-secondary tracepilot-block-ip" data-ip="' + threat.ip + '"><?php echo esc_js(__('Block IP', 'wp-activity-logger-pro')); ?></button>' : '';
                return '<tr>' +
                    '<td data-label="<?php echo esc_js(__('Severity', 'wp-activity-logger-pro')); ?>">' + badge + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Type', 'wp-activity-logger-pro')); ?>">' + label + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Description', 'wp-activity-logger-pro')); ?>">' + threat.description + '</td>' +
                    '<td data-label="<?php echo esc_js(__('IP', 'wp-activity-logger-pro')); ?>">' + (threat.ip || '—') + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Time', 'wp-activity-logger-pro')); ?>">' + (threat.last_attempt || threat.login_time || threat.time || '—') + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Actions', 'wp-activity-logger-pro')); ?>" class="tracepilot-table-actions">' + ipAction + userAction + '</td>' +
                '</tr>';
            });

            $('#tracepilot-threats-table').html(rows.join(''));
            $('#tracepilot-threat-results').show();
        }).always(function() {
            $('#tracepilot-threat-loading').hide();
            button.prop('disabled', false).text('<?php echo esc_js(__('Analyze Threats', 'wp-activity-logger-pro')); ?>');
        });
    });

    function severityBadge(severity) {
        const badgeClass = (severity === 'critical' || severity === 'high') ? 'danger' : (severity === 'medium' ? 'warning' : 'info');
        return '<span class="tracepilot-badge tracepilot-badge-' + badgeClass + '">' + severity.charAt(0).toUpperCase() + severity.slice(1) + '</span>';
    }

    function renderVulnerabilityReport(data) {
        $('#tracepilot-vulnerability-status').text('Latest report generated ' + data.generated_at + '.');
        $('#tracepilot-vulnerability-summary').show();
        $('#tracepilot-vuln-affected').text(data.summary.affected);
        $('#tracepilot-vuln-critical').text(data.summary.critical);
        $('#tracepilot-vuln-high').text(data.summary.high);
        $('#tracepilot-vuln-clean').text(data.summary.clean);

        const notesBox = $('#tracepilot-vulnerability-notes').empty();
        if (data.notes && data.notes.length) {
            data.notes.forEach(function(note) {
                notesBox.append('<div class="tracepilot-list-row"><div>' + $('<div>').text(note).html() + '</div></div>');
            });
            notesBox.show();
        } else {
            notesBox.hide();
        }

        const rows = (data.items || []).map(function(item) {
            let recommendation = '<?php echo esc_js(__('No action needed right now.', 'wp-activity-logger-pro')); ?>';
            if (item.findings && item.findings.length && item.findings[0].fixed_in) {
                recommendation = '<?php echo esc_js(__('Update to', 'wp-activity-logger-pro')); ?> ' + item.findings[0].fixed_in + ' <?php echo esc_js(__('or newer.', 'wp-activity-logger-pro')); ?>';
            } else if (item.local_change_count) {
                recommendation = '<?php echo esc_js(__('Review recent file changes against the integrity baseline.', 'wp-activity-logger-pro')); ?>';
            } else if (item.findings && item.findings.length) {
                recommendation = '<?php echo esc_js(__('Review the linked advisory and update or replace this component.', 'wp-activity-logger-pro')); ?>';
            }

            const findings = [];
            (item.findings || []).slice(0, 2).forEach(function(finding) {
                findings.push('<div class="tracepilot-list-subtext"><strong>' + $('<div>').text(finding.provider).html() + ':</strong> ' + $('<div>').text(finding.title).html() + '</div>');
            });

            const localPill = item.local_change_count ? '<span class="tracepilot-meta-pill">' + item.local_change_count + ' <?php echo esc_js(__('local file changes', 'wp-activity-logger-pro')); ?></span>' : '';
            return '<tr>' +
                '<td data-label="<?php echo esc_js(__('Component', 'wp-activity-logger-pro')); ?>"><strong>' + $('<div>').text(item.name).html() + '</strong><div class="tracepilot-list-subtext">' + $('<div>').text(item.slug).html() + '</div></td>' +
                '<td data-label="<?php echo esc_js(__('Type', 'wp-activity-logger-pro')); ?>">' + $('<div>').text(item.type.charAt(0).toUpperCase() + item.type.slice(1)).html() + '</td>' +
                '<td data-label="<?php echo esc_js(__('Version', 'wp-activity-logger-pro')); ?>">' + $('<div>').text(item.version).html() + '</td>' +
                '<td data-label="<?php echo esc_js(__('Severity', 'wp-activity-logger-pro')); ?>">' + severityBadge(item.severity) + '</td>' +
                '<td data-label="<?php echo esc_js(__('Findings', 'wp-activity-logger-pro')); ?>">' + (item.finding_count || 0) + localPill + findings.join('') + '</td>' +
                '<td data-label="<?php echo esc_js(__('Recommended fix', 'wp-activity-logger-pro')); ?>">' + $('<div>').text(recommendation).html() + '</td>' +
            '</tr>';
        });

        $('#tracepilot-vulnerability-table').html(rows.length ? rows.join('') : '<tr><td data-label="<?php echo esc_js(__('Status', 'wp-activity-logger-pro')); ?>" colspan="6"><?php echo esc_js(__('No installed components were found in the current scan scope.', 'wp-activity-logger-pro')); ?></td></tr>');
    }

    $('#tracepilot-scan-vulnerabilities').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('<?php echo esc_js(__('Scanning...', 'wp-activity-logger-pro')); ?>');
        $('#tracepilot-vulnerability-status').text('<?php echo esc_js(__('Checking installed software against vulnerability intelligence providers...', 'wp-activity-logger-pro')); ?>');

        $.post(ajaxurl, {
            action: 'tracepilot_scan_vulnerabilities',
            nonce: '<?php echo esc_js(wp_create_nonce('tracepilot_nonce')); ?>'
        }).done(function(response) {
            if (!response.success) {
                window.alert(response.data.message || 'Unable to scan software.');
                return;
            }

            renderVulnerabilityReport(response.data);
        }).always(function() {
            button.prop('disabled', false).text('<?php echo esc_js(__('Scan Software', 'wp-activity-logger-pro')); ?>');
        });
    });

    function renderIntegrity(response) {
        const list = $('#tracepilot-integrity-results').empty();
        if (!response.data.changes.length) {
            list.append('<div class="tracepilot-list-row"><div>' + response.data.message + '</div></div>');
            return;
        }

        response.data.changes.forEach(function(change) {
            list.append('<div class="tracepilot-list-row"><div><strong>' + change.type + '</strong><div class="tracepilot-list-subtext">' + change.path + '</div></div><div class="tracepilot-meta-pill">' + change.group + '</div></div>');
        });
    }

    $('#tracepilot-build-baseline').on('click', function() {
        $.post(ajaxurl, {
            action: 'tracepilot_build_file_baseline',
            nonce: '<?php echo esc_js(wp_create_nonce('tracepilot_nonce')); ?>'
        }).done(function(response) {
            if (response.success) {
                $('#tracepilot-integrity-status').text('Baseline created with ' + response.data.count + ' files.');
            }
        });
    });

    $('#tracepilot-scan-integrity').on('click', function() {
        $.post(ajaxurl, {
            action: 'tracepilot_scan_file_integrity',
            nonce: '<?php echo esc_js(wp_create_nonce('tracepilot_nonce')); ?>'
        }).done(function(response) {
            if (response.success) {
                renderIntegrity(response);
            }
        });
    });
});
</script>
