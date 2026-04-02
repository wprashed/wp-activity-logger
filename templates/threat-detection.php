<?php
/**
 * Threat detection template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = WPAL_Helpers::get_settings();
$integrity = wp_activity_logger_pro()->file_integrity->get_baseline_status();
$vulnerability_report = array();
$active_threat_rules = (int) $options['monitor_failed_logins'] + (int) $options['monitor_unusual_logins'] + (int) $options['monitor_file_changes'] + (int) $options['monitor_privilege_escalation'];
$enabled_sources = array_values(array_intersect(array('wordfence', 'patchstack', 'wpscan'), (array) $options['vulnerability_sources']));
?>

<div class="wrap wpal-wrap">
    <section class="wpal-hero wpal-hero-compact">
        <div>
            <p class="wpal-eyebrow"><?php esc_html_e('Security monitoring', 'wp-activity-logger-pro'); ?></p>
            <h1 class="wpal-page-title"><?php esc_html_e('Threat Detection', 'wp-activity-logger-pro'); ?></h1>
            <p class="wpal-hero-copy"><?php esc_html_e('Analyze your audit trail for brute-force activity, unusual login patterns, suspicious file changes, and privilege escalation events.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <div class="wpal-hero-actions">
            <button id="wpal-analyze-threats" class="wpal-btn wpal-btn-primary"><?php esc_html_e('Analyze Threats', 'wp-activity-logger-pro'); ?></button>
        </div>
    </section>

    <section class="wpal-stats-grid">
        <article class="wpal-stat-card">
            <span class="wpal-stat-label"><?php esc_html_e('Detection Rules', 'wp-activity-logger-pro'); ?></span>
            <strong class="wpal-stat-value"><?php echo esc_html($active_threat_rules); ?></strong>
            <span class="wpal-stat-meta"><?php esc_html_e('Checks currently watching new activity', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="wpal-stat-card">
            <span class="wpal-stat-label"><?php esc_html_e('Threat Engine', 'wp-activity-logger-pro'); ?></span>
            <strong class="wpal-stat-value"><?php echo !empty($options['enable_threat_detection']) ? esc_html__('On', 'wp-activity-logger-pro') : esc_html__('Off', 'wp-activity-logger-pro'); ?></strong>
            <span class="wpal-stat-meta"><?php echo !empty($options['enable_threat_notifications']) ? esc_html__('Notifications are active', 'wp-activity-logger-pro') : esc_html__('Notifications are paused', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="wpal-stat-card">
            <span class="wpal-stat-label"><?php esc_html_e('Intel Providers', 'wp-activity-logger-pro'); ?></span>
            <strong class="wpal-stat-value"><?php echo esc_html(count($enabled_sources)); ?></strong>
            <span class="wpal-stat-meta"><?php echo !empty($enabled_sources) ? esc_html(implode(', ', array_map('ucfirst', $enabled_sources))) : esc_html__('No external feed enabled', 'wp-activity-logger-pro'); ?></span>
        </article>
        <article class="wpal-stat-card">
            <span class="wpal-stat-label"><?php esc_html_e('Integrity Baseline', 'wp-activity-logger-pro'); ?></span>
            <strong class="wpal-stat-value"><?php echo !empty($integrity['exists']) ? esc_html((int) $integrity['count']) : '0'; ?></strong>
            <span class="wpal-stat-meta"><?php echo !empty($integrity['exists']) ? esc_html__('Tracked files in the current baseline', 'wp-activity-logger-pro') : esc_html__('Build a baseline before scanning', 'wp-activity-logger-pro'); ?></span>
        </article>
    </section>

    <section class="wpal-grid wpal-grid-2">
        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Detection Rules', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Choose which automated checks stay active as new logs are recorded.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <form id="wpal-threat-settings-form" class="wpal-form-stack">
                <div class="wpal-check-grid wpal-check-grid-wide">
                    <label class="wpal-check-card wpal-check-card-feature">
                        <input type="checkbox" name="wpal_options[enable_threat_detection]" value="1" <?php checked($options['enable_threat_detection'], 1); ?>>
                        <span>
                            <strong><?php esc_html_e('Enable threat detection', 'wp-activity-logger-pro'); ?></strong>
                            <small><?php esc_html_e('Analyze new log entries as they are written.', 'wp-activity-logger-pro'); ?></small>
                        </span>
                    </label>

                    <label class="wpal-check-card wpal-check-card-feature">
                        <input type="checkbox" name="wpal_options[enable_threat_notifications]" value="1" <?php checked($options['enable_threat_notifications'], 1); ?>>
                        <span>
                            <strong><?php esc_html_e('Send threat notifications', 'wp-activity-logger-pro'); ?></strong>
                            <small><?php esc_html_e('Use your configured notification channels for serious detections.', 'wp-activity-logger-pro'); ?></small>
                        </span>
                    </label>
                </div>

                <div>
                    <span class="wpal-section-label"><?php esc_html_e('Threat types to monitor', 'wp-activity-logger-pro'); ?></span>
                    <div class="wpal-check-grid wpal-check-grid-wide">
                        <label class="wpal-check-card wpal-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_failed_logins]" value="1" <?php checked($options['monitor_failed_logins'], 1); ?>>
                            <span><strong><?php esc_html_e('Failed login attacks', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="wpal-check-card wpal-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_unusual_logins]" value="1" <?php checked($options['monitor_unusual_logins'], 1); ?>>
                            <span><strong><?php esc_html_e('Unusual login patterns', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="wpal-check-card wpal-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_file_changes]" value="1" <?php checked($options['monitor_file_changes'], 1); ?>>
                            <span><strong><?php esc_html_e('Suspicious file changes', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                        <label class="wpal-check-card wpal-check-card-compact">
                            <input type="checkbox" name="wpal_options[monitor_privilege_escalation]" value="1" <?php checked($options['monitor_privilege_escalation'], 1); ?>>
                            <span><strong><?php esc_html_e('Privilege escalation', 'wp-activity-logger-pro'); ?></strong></span>
                        </label>
                    </div>
                </div>

                <div class="wpal-inline-actions">
                    <button type="button" id="wpal-save-threat-settings" class="wpal-btn wpal-btn-secondary"><?php esc_html_e('Save Detection Settings', 'wp-activity-logger-pro'); ?></button>
                    <span id="wpal-threat-settings-feedback" class="wpal-form-feedback"></span>
                </div>
            </form>
        </article>

        <article class="wpal-panel">
            <div class="wpal-panel-head">
                <div>
                    <h2><?php esc_html_e('Threat Summary', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Run an on-demand scan to populate the live results panel.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>

            <div class="wpal-toolbar-pills">
                <span class="wpal-pill"><?php echo !empty($options['enable_threat_detection']) ? esc_html__('Live analysis enabled', 'wp-activity-logger-pro') : esc_html__('Live analysis disabled', 'wp-activity-logger-pro'); ?></span>
                <span class="wpal-pill"><?php printf(esc_html__('%d rule(s) active', 'wp-activity-logger-pro'), $active_threat_rules); ?></span>
                <span class="wpal-pill"><?php echo !empty($options['enable_threat_notifications']) ? esc_html__('Alert routing ready', 'wp-activity-logger-pro') : esc_html__('Alerts not routed', 'wp-activity-logger-pro'); ?></span>
            </div>

            <div id="wpal-threat-summary" class="wpal-stats-grid" style="display:none; margin-bottom:14px;">
                <article class="wpal-stat-card">
                    <span class="wpal-stat-label"><?php esc_html_e('Total', 'wp-activity-logger-pro'); ?></span>
                    <strong id="wpal-total-threats" class="wpal-stat-value">0</strong>
                </article>
                <article class="wpal-stat-card">
                    <span class="wpal-stat-label"><?php esc_html_e('High', 'wp-activity-logger-pro'); ?></span>
                    <strong id="wpal-high-threats" class="wpal-stat-value">0</strong>
                </article>
                <article class="wpal-stat-card">
                    <span class="wpal-stat-label"><?php esc_html_e('Medium', 'wp-activity-logger-pro'); ?></span>
                    <strong id="wpal-medium-threats" class="wpal-stat-value">0</strong>
                </article>
                <article class="wpal-stat-card">
                    <span class="wpal-stat-label"><?php esc_html_e('Low', 'wp-activity-logger-pro'); ?></span>
                    <strong id="wpal-low-threats" class="wpal-stat-value">0</strong>
                </article>
            </div>

            <div id="wpal-threat-loading" class="wpal-note" style="display:none;">
                <?php esc_html_e('Analyzing activity logs for potential threats...', 'wp-activity-logger-pro'); ?>
            </div>

            <div id="wpal-no-threats" class="wpal-empty-panel" style="display:none;">
                <strong><?php esc_html_e('No threats detected', 'wp-activity-logger-pro'); ?></strong>
                <p><?php esc_html_e('The current log sample does not match any active detection rules.', 'wp-activity-logger-pro'); ?></p>
            </div>

            <div id="wpal-threat-results" style="display:none;">
                <div class="wpal-table-wrap">
                    <table class="wpal-table wpal-responsive-table">
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
                        <tbody id="wpal-threats-table"></tbody>
                    </table>
                </div>
            </div>
        </article>
    </section>

    <section class="wpal-panel">
        <div class="wpal-panel-head">
            <div>
                <h2><?php esc_html_e('Software Vulnerability Report', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('Check installed plugins, themes, and WordPress core against Wordfence, Patchstack, and WPScan, then combine that with local file-integrity signals.', 'wp-activity-logger-pro'); ?></p>
            </div>
            <div class="wpal-hero-actions">
                <button type="button" id="wpal-scan-vulnerabilities" class="wpal-btn wpal-btn-primary"><?php esc_html_e('Scan Software', 'wp-activity-logger-pro'); ?></button>
            </div>
        </div>

        <div class="wpal-toolbar-pills">
            <span class="wpal-pill"><?php echo !empty($enabled_sources) ? esc_html(implode(', ', array_map('ucfirst', $enabled_sources))) : esc_html__('No provider selected', 'wp-activity-logger-pro'); ?></span>
            <span class="wpal-pill"><?php echo !empty($options['vulnerability_include_file_integrity']) ? esc_html__('Integrity signals included', 'wp-activity-logger-pro') : esc_html__('Software feed only', 'wp-activity-logger-pro'); ?></span>
        </div>

        <div id="wpal-vulnerability-status" class="wpal-note">
            <?php
            esc_html_e('Run a manual scan to generate the latest software security report.', 'wp-activity-logger-pro');
            ?>
        </div>

            <div id="wpal-vulnerability-summary" class="wpal-stats-grid" style="<?php echo empty($vulnerability_report['summary']) ? 'display:none; margin-top:16px;' : 'margin-top:16px;'; ?>">
            <article class="wpal-stat-card">
                <span class="wpal-stat-label"><?php esc_html_e('Affected', 'wp-activity-logger-pro'); ?></span>
                <strong id="wpal-vuln-affected" class="wpal-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['affected']) : 0; ?></strong>
            </article>
            <article class="wpal-stat-card">
                <span class="wpal-stat-label"><?php esc_html_e('Critical', 'wp-activity-logger-pro'); ?></span>
                <strong id="wpal-vuln-critical" class="wpal-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['critical']) : 0; ?></strong>
            </article>
            <article class="wpal-stat-card">
                <span class="wpal-stat-label"><?php esc_html_e('High', 'wp-activity-logger-pro'); ?></span>
                <strong id="wpal-vuln-high" class="wpal-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['high']) : 0; ?></strong>
            </article>
            <article class="wpal-stat-card">
                <span class="wpal-stat-label"><?php esc_html_e('Clean', 'wp-activity-logger-pro'); ?></span>
                <strong id="wpal-vuln-clean" class="wpal-stat-value"><?php echo !empty($vulnerability_report['summary']) ? esc_html((int) $vulnerability_report['summary']['clean']) : 0; ?></strong>
            </article>
        </div>

        <div id="wpal-vulnerability-notes" class="wpal-list" style="margin-top:16px;<?php echo empty($vulnerability_report['notes']) ? 'display:none;' : ''; ?>">
            <?php foreach ((array) ($vulnerability_report['notes'] ?? array()) as $note) : ?>
                <div class="wpal-list-row"><div><?php echo esc_html($note); ?></div></div>
            <?php endforeach; ?>
        </div>

        <div class="wpal-table-wrap" style="margin-top:16px;">
            <table class="wpal-table wpal-responsive-table">
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
                <tbody id="wpal-vulnerability-table">
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
                                    <div class="wpal-list-subtext"><?php echo esc_html($item['slug']); ?></div>
                                </td>
                                <td data-label="<?php esc_attr_e('Type', 'wp-activity-logger-pro'); ?>"><?php echo esc_html(ucfirst($item['type'])); ?></td>
                                <td data-label="<?php esc_attr_e('Version', 'wp-activity-logger-pro'); ?>"><?php echo esc_html($item['version']); ?></td>
                                <td data-label="<?php esc_attr_e('Severity', 'wp-activity-logger-pro'); ?>"><span class="wpal-badge wpal-badge-<?php echo esc_attr($badge_class); ?>"><?php echo esc_html(ucfirst($item['severity'])); ?></span></td>
                                <td data-label="<?php esc_attr_e('Findings', 'wp-activity-logger-pro'); ?>">
                                    <?php echo esc_html((int) $item['finding_count']); ?>
                                    <?php if (!empty($item['local_change_count'])) : ?>
                                        <span class="wpal-meta-pill"><?php echo esc_html(sprintf(__('%d local file changes', 'wp-activity-logger-pro'), (int) $item['local_change_count'])); ?></span>
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

    <section class="wpal-panel">
        <div class="wpal-panel-head">
            <div>
                <h2><?php esc_html_e('File Integrity', 'wp-activity-logger-pro'); ?></h2>
                <p><?php esc_html_e('Create a baseline of core, plugin, and theme files, then scan for new, deleted, or modified files.', 'wp-activity-logger-pro'); ?></p>
            </div>
            <div class="wpal-hero-actions">
                <button type="button" id="wpal-build-baseline" class="wpal-btn wpal-btn-secondary"><?php esc_html_e('Build Baseline', 'wp-activity-logger-pro'); ?></button>
                <button type="button" id="wpal-scan-integrity" class="wpal-btn wpal-btn-primary"><?php esc_html_e('Scan Integrity', 'wp-activity-logger-pro'); ?></button>
            </div>
        </div>
        <div class="wpal-toolbar-pills">
            <span class="wpal-pill"><?php echo !empty($integrity['exists']) ? esc_html__('Baseline ready', 'wp-activity-logger-pro') : esc_html__('Baseline missing', 'wp-activity-logger-pro'); ?></span>
            <?php if (!empty($integrity['exists'])) : ?>
                <span class="wpal-pill"><?php printf(esc_html__('%d files tracked', 'wp-activity-logger-pro'), (int) $integrity['count']); ?></span>
            <?php endif; ?>
        </div>
        <div class="wpal-note" id="wpal-integrity-status">
            <?php
            echo $integrity['exists']
                ? esc_html(sprintf(__('Baseline created %1$s with %2$d files.', 'wp-activity-logger-pro'), $integrity['created_at'], $integrity['count']))
                : esc_html__('No baseline exists yet.', 'wp-activity-logger-pro');
            ?>
        </div>
        <div id="wpal-integrity-results" class="wpal-list" style="margin-top:16px;"></div>
    </section>
</div>

<script>
jQuery(function($) {
    $('#wpal-save-threat-settings').on('click', function() {
        const feedback = $('#wpal-threat-settings-feedback');
        feedback.text('Saving...');

        const options = {
            enable_threat_detection: 0,
            enable_threat_notifications: 0,
            monitor_failed_logins: 0,
            monitor_unusual_logins: 0,
            monitor_file_changes: 0,
            monitor_privilege_escalation: 0
        };
        new window.FormData(document.getElementById('wpal-threat-settings-form')).forEach(function(value, key) {
            const match = key.match(/^wpal_options\[([^\]]+)\]$/);
            if (match) {
                options[match[1]] = value;
            }
        });

        $.post(ajaxurl, {
            action: 'wpal_save_settings',
            nonce: '<?php echo esc_js(wp_create_nonce('wpal_nonce')); ?>',
            wpal_options: options
        }).done(function(response) {
            feedback.text(response.success ? response.data.message : 'Unable to save settings.');
        });
    });

    $('#wpal-analyze-threats').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('<?php echo esc_js(__('Analyzing...', 'wp-activity-logger-pro')); ?>');
        $('#wpal-threat-loading').show();
        $('#wpal-threat-results, #wpal-no-threats, #wpal-threat-summary').hide();

        $.post(ajaxurl, {
            action: 'wpal_analyze_threats',
            nonce: '<?php echo esc_js(wp_create_nonce('wpal_nonce')); ?>'
        }).done(function(response) {
            if (!response.success) {
                window.alert(response.data.message || 'Unable to analyze threats.');
                return;
            }

            const data = response.data;
            $('#wpal-total-threats').text(data.summary.total);
            $('#wpal-high-threats').text(data.summary.high);
            $('#wpal-medium-threats').text(data.summary.medium);
            $('#wpal-low-threats').text(data.summary.low);
            $('#wpal-threat-summary').show();

            if (!data.threats.length) {
                $('#wpal-no-threats').show();
                return;
            }

            const rows = data.threats.map(function(threat) {
                const badgeClass = threat.severity === 'high' ? 'danger' : (threat.severity === 'medium' ? 'warning' : 'info');
                const badge = '<span class="wpal-badge wpal-badge-' + badgeClass + '">' + threat.severity.charAt(0).toUpperCase() + threat.severity.slice(1) + '</span>';
                const label = threat.type.replace(/_/g, ' ');
                const userAction = threat.user_id ? '<button type="button" class="wpal-btn wpal-btn-secondary wpal-force-logout" data-user-id="' + threat.user_id + '"><?php echo esc_js(__('Force Logout', 'wp-activity-logger-pro')); ?></button>' : '';
                const ipAction = threat.ip ? '<button type="button" class="wpal-btn wpal-btn-secondary wpal-block-ip" data-ip="' + threat.ip + '"><?php echo esc_js(__('Block IP', 'wp-activity-logger-pro')); ?></button>' : '';
                return '<tr>' +
                    '<td data-label="<?php echo esc_js(__('Severity', 'wp-activity-logger-pro')); ?>">' + badge + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Type', 'wp-activity-logger-pro')); ?>">' + label + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Description', 'wp-activity-logger-pro')); ?>">' + threat.description + '</td>' +
                    '<td data-label="<?php echo esc_js(__('IP', 'wp-activity-logger-pro')); ?>">' + (threat.ip || '—') + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Time', 'wp-activity-logger-pro')); ?>">' + (threat.last_attempt || threat.login_time || threat.time || '—') + '</td>' +
                    '<td data-label="<?php echo esc_js(__('Actions', 'wp-activity-logger-pro')); ?>" class="wpal-table-actions">' + ipAction + userAction + '</td>' +
                '</tr>';
            });

            $('#wpal-threats-table').html(rows.join(''));
            $('#wpal-threat-results').show();
        }).always(function() {
            $('#wpal-threat-loading').hide();
            button.prop('disabled', false).text('<?php echo esc_js(__('Analyze Threats', 'wp-activity-logger-pro')); ?>');
        });
    });

    function severityBadge(severity) {
        const badgeClass = (severity === 'critical' || severity === 'high') ? 'danger' : (severity === 'medium' ? 'warning' : 'info');
        return '<span class="wpal-badge wpal-badge-' + badgeClass + '">' + severity.charAt(0).toUpperCase() + severity.slice(1) + '</span>';
    }

    function renderVulnerabilityReport(data) {
        $('#wpal-vulnerability-status').text('Latest report generated ' + data.generated_at + '.');
        $('#wpal-vulnerability-summary').show();
        $('#wpal-vuln-affected').text(data.summary.affected);
        $('#wpal-vuln-critical').text(data.summary.critical);
        $('#wpal-vuln-high').text(data.summary.high);
        $('#wpal-vuln-clean').text(data.summary.clean);

        const notesBox = $('#wpal-vulnerability-notes').empty();
        if (data.notes && data.notes.length) {
            data.notes.forEach(function(note) {
                notesBox.append('<div class="wpal-list-row"><div>' + $('<div>').text(note).html() + '</div></div>');
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
                findings.push('<div class="wpal-list-subtext"><strong>' + $('<div>').text(finding.provider).html() + ':</strong> ' + $('<div>').text(finding.title).html() + '</div>');
            });

            const localPill = item.local_change_count ? '<span class="wpal-meta-pill">' + item.local_change_count + ' <?php echo esc_js(__('local file changes', 'wp-activity-logger-pro')); ?></span>' : '';
            return '<tr>' +
                '<td data-label="<?php echo esc_js(__('Component', 'wp-activity-logger-pro')); ?>"><strong>' + $('<div>').text(item.name).html() + '</strong><div class="wpal-list-subtext">' + $('<div>').text(item.slug).html() + '</div></td>' +
                '<td data-label="<?php echo esc_js(__('Type', 'wp-activity-logger-pro')); ?>">' + $('<div>').text(item.type.charAt(0).toUpperCase() + item.type.slice(1)).html() + '</td>' +
                '<td data-label="<?php echo esc_js(__('Version', 'wp-activity-logger-pro')); ?>">' + $('<div>').text(item.version).html() + '</td>' +
                '<td data-label="<?php echo esc_js(__('Severity', 'wp-activity-logger-pro')); ?>">' + severityBadge(item.severity) + '</td>' +
                '<td data-label="<?php echo esc_js(__('Findings', 'wp-activity-logger-pro')); ?>">' + (item.finding_count || 0) + localPill + findings.join('') + '</td>' +
                '<td data-label="<?php echo esc_js(__('Recommended fix', 'wp-activity-logger-pro')); ?>">' + $('<div>').text(recommendation).html() + '</td>' +
            '</tr>';
        });

        $('#wpal-vulnerability-table').html(rows.length ? rows.join('') : '<tr><td data-label="<?php echo esc_js(__('Status', 'wp-activity-logger-pro')); ?>" colspan="6"><?php echo esc_js(__('No installed components were found in the current scan scope.', 'wp-activity-logger-pro')); ?></td></tr>');
    }

    $('#wpal-scan-vulnerabilities').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('<?php echo esc_js(__('Scanning...', 'wp-activity-logger-pro')); ?>');
        $('#wpal-vulnerability-status').text('<?php echo esc_js(__('Checking installed software against vulnerability intelligence providers...', 'wp-activity-logger-pro')); ?>');

        $.post(ajaxurl, {
            action: 'wpal_scan_vulnerabilities',
            nonce: '<?php echo esc_js(wp_create_nonce('wpal_nonce')); ?>'
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
        const list = $('#wpal-integrity-results').empty();
        if (!response.data.changes.length) {
            list.append('<div class="wpal-list-row"><div>' + response.data.message + '</div></div>');
            return;
        }

        response.data.changes.forEach(function(change) {
            list.append('<div class="wpal-list-row"><div><strong>' + change.type + '</strong><div class="wpal-list-subtext">' + change.path + '</div></div><div class="wpal-meta-pill">' + change.group + '</div></div>');
        });
    }

    $('#wpal-build-baseline').on('click', function() {
        $.post(ajaxurl, {
            action: 'wpal_build_file_baseline',
            nonce: '<?php echo esc_js(wp_create_nonce('wpal_nonce')); ?>'
        }).done(function(response) {
            if (response.success) {
                $('#wpal-integrity-status').text('Baseline created with ' + response.data.count + ' files.');
            }
        });
    });

    $('#wpal-scan-integrity').on('click', function() {
        $.post(ajaxurl, {
            action: 'wpal_scan_file_integrity',
            nonce: '<?php echo esc_js(wp_create_nonce('wpal_nonce')); ?>'
        }).done(function(response) {
            if (response.success) {
                renderIntegrity(response);
            }
        });
    });
});
</script>
