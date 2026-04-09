<?php
/**
 * Advanced diagnostics, scanner, conflict detector, and safe mode manager.
 */

if (!defined('ABSPATH')) {
    exit();
}

class TracePilot_Diagnostics {
    const REPORT_OPTION = 'tracepilot_system_scan_report';
    const HISTORY_OPTION = 'tracepilot_system_scan_history';
    const TIMELINE_OPTION = 'tracepilot_issue_timeline';
    const CLIENT_ERRORS_OPTION = 'tracepilot_client_errors';
    const SAFE_MODE_COOKIE = 'tracepilot_safe_mode';
    const SAFE_MODE_TTL = 7200;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('wp_ajax_tracepilot_run_diagnostics', array($this, 'ajax_run_diagnostics'));
        add_action('wp_ajax_tracepilot_enable_safe_mode', array($this, 'ajax_enable_safe_mode'));
        add_action('wp_ajax_tracepilot_disable_safe_mode', array($this, 'ajax_disable_safe_mode'));
        add_action('wp_ajax_tracepilot_ask_diagnostics_ai', array($this, 'ajax_ask_diagnostics_ai'));
        add_action('wp_ajax_tracepilot_capture_client_error', array($this, 'ajax_capture_client_error'));
        add_action('wp_ajax_nopriv_tracepilot_capture_client_error', array($this, 'ajax_capture_client_error'));

        add_filter('option_active_plugins', array($this, 'filter_active_plugins_for_safe_mode'));
        add_filter('site_option_active_sitewide_plugins', array($this, 'filter_network_active_plugins_for_safe_mode'));

        add_action('wp_footer', array($this, 'inject_client_error_snippet'), 99);
        add_action('admin_footer', array($this, 'inject_client_error_snippet'), 99);
        add_action('admin_notices', array($this, 'render_admin_alert_notice'));
    }

    /**
     * AJAX scan runner.
     */
    public function ajax_run_diagnostics() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }

        $report = $this->run_diagnostics(true);
        wp_send_json_success($report);
    }

    /**
     * AJAX safe mode enable.
     */
    public function ajax_enable_safe_mode() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }

        $plugins = isset($_POST['plugins']) ? array_values(array_filter(array_map('sanitize_text_field', (array) wp_unslash($_POST['plugins'])))) : array();
        $token = wp_generate_password(24, false, false);
        $payload = array(
            'user_id' => get_current_user_id(),
            'plugins' => $plugins,
            'enabled_at' => time(),
        );

        set_transient($this->get_safe_mode_transient_key($token), $payload, self::SAFE_MODE_TTL);
        setcookie(self::SAFE_MODE_COOKIE, $token, time() + self::SAFE_MODE_TTL, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE[self::SAFE_MODE_COOKIE] = $token;

        TracePilot_Helpers::log_activity(
            'safe_mode_enabled',
            __('Safe mode enabled for current admin session', 'wp-activity-logger-pro'),
            'warning',
            array('context' => array('plugins' => $plugins))
        );

        wp_send_json_success(array('message' => __('Safe mode enabled for this admin session.', 'wp-activity-logger-pro')));
    }

    /**
     * AJAX safe mode disable.
     */
    public function ajax_disable_safe_mode() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }

        $token = isset($_COOKIE[self::SAFE_MODE_COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::SAFE_MODE_COOKIE])) : '';
        if ($token) {
            delete_transient($this->get_safe_mode_transient_key($token));
        }

        setcookie(self::SAFE_MODE_COOKIE, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
        unset($_COOKIE[self::SAFE_MODE_COOKIE]);

        TracePilot_Helpers::log_activity(
            'safe_mode_disabled',
            __('Safe mode disabled for current admin session', 'wp-activity-logger-pro'),
            'info'
        );

        wp_send_json_success(array('message' => __('Safe mode disabled.', 'wp-activity-logger-pro')));
    }

    /**
     * AJAX assistant answer.
     */
    public function ajax_ask_diagnostics_ai() {
        check_ajax_referer('tracepilot_nonce', 'nonce');
        if (!TracePilot_Helpers::current_user_can_manage()) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-activity-logger-pro')));
        }

        $question = isset($_POST['question']) ? sanitize_textarea_field(wp_unslash($_POST['question'])) : '';
        if ('' === $question) {
            wp_send_json_error(array('message' => __('Ask a question first.', 'wp-activity-logger-pro')));
        }

        wp_send_json_success(array('answer' => $this->answer_contextual_question($question)));
    }

    /**
     * Capture JS/runtime errors.
     */
    public function ajax_capture_client_error() {
        $entry = array(
            'time' => current_time('mysql'),
            'page' => isset($_POST['page']) ? esc_url_raw(wp_unslash($_POST['page'])) : '',
            'message' => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
            'source' => isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : '',
            'stack' => isset($_POST['stack']) ? sanitize_textarea_field(wp_unslash($_POST['stack'])) : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
        );

        if ('' === $entry['message']) {
            wp_send_json_success(array('captured' => false));
        }

        $errors = get_option(self::CLIENT_ERRORS_OPTION, array());
        if (!is_array($errors)) {
            $errors = array();
        }

        array_unshift($errors, $entry);
        $errors = array_slice($errors, 0, 50);
        update_option(self::CLIENT_ERRORS_OPTION, $errors, false);

        wp_send_json_success(array('captured' => true));
    }

    /**
     * Inject client-side error collector.
     */
    public function inject_client_error_snippet() {
        if (is_admin() && !TracePilot_Helpers::current_user_can_manage()) {
            return;
        }
        ?>
        <script>
        (function() {
            if (window.__wpalErrorCaptureLoaded) {
                return;
            }
            window.__wpalErrorCaptureLoaded = true;

            function send(payload) {
                try {
                    var data = new window.FormData();
                    data.append('action', 'tracepilot_capture_client_error');
                    data.append('page', window.location.href);
                    data.append('message', payload.message || '');
                    data.append('source', payload.source || '');
                    data.append('stack', payload.stack || '');

                    if (window.fetch) {
                        window.fetch(<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>, {
                            method: 'POST',
                            body: data,
                            credentials: 'same-origin'
                        });
                    }
                } catch (e) {}
            }

            window.addEventListener('error', function(event) {
                send({
                    message: event.message || 'JavaScript error',
                    source: event.filename || '',
                    stack: event.error && event.error.stack ? event.error.stack : ''
                });
            });

            window.addEventListener('unhandledrejection', function(event) {
                var reason = event.reason || {};
                send({
                    message: reason.message || String(reason) || 'Unhandled promise rejection',
                    source: 'unhandledrejection',
                    stack: reason.stack || ''
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Run diagnostics scanner.
     *
     * @param bool $persist Persist report/timeline/logs.
     * @return array
     */
    public function run_diagnostics($persist = false) {
        $inventory = $this->get_environment_inventory();
        $issues = array_merge(
            $this->scan_system_health($inventory),
            $this->scan_conflicts($inventory),
            $this->scan_runtime_errors($inventory)
        );

        $issues = $this->decorate_issues($issues);
        $summary = $this->build_scan_summary($issues);
        $timeline = $this->build_issue_timeline($issues, $persist);
        $correlations = $this->build_change_correlations($issues, $timeline);
        $conflict_plan = $this->build_conflict_plan($issues, $inventory);
        $report = array(
            'generated_at' => current_time('mysql'),
            'health_score' => $summary['health_score'],
            'counts' => $summary['counts'],
            'inventory' => $inventory,
            'issues' => $issues,
            'safe_mode' => $this->get_safe_mode_status(),
            'timeline' => $timeline,
            'correlations' => $correlations,
            'conflict_plan' => $conflict_plan,
            'history' => $this->get_scan_history(8),
            'assistant_hint' => __('Ask things like “Why is my site slow?” or “What should I disable first?”', 'wp-activity-logger-pro'),
        );

        if ($persist) {
            update_option(self::REPORT_OPTION, $this->compact_report($report), false);
            $this->append_history($report);
            $this->record_report_logs($report);
            $this->send_critical_alerts($report);
        }

        return $report;
    }

    /**
     * Return latest saved report.
     *
     * @return array
     */
    public function get_latest_report() {
        $report = get_option(self::REPORT_OPTION, array());
        return is_array($report) ? $report : array();
    }

    /**
     * Return saved scan history.
     *
     * @param int $limit Rows to return.
     * @return array
     */
    public function get_scan_history($limit = 10) {
        $history = get_option(self::HISTORY_OPTION, array());
        if (!is_array($history)) {
            return array();
        }

        return array_slice($history, 0, max(1, (int) $limit));
    }

    /**
     * Gather system inventory.
     *
     * @return array
     */
    private function get_environment_inventory() {
        global $wpdb, $wp_version;

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $active_plugins = array();
        foreach ((array) get_option('active_plugins', array()) as $plugin_file) {
            $data = isset($plugins[$plugin_file]) ? $plugins[$plugin_file] : array();
            $active_plugins[] = array(
                'file' => $plugin_file,
                'name' => !empty($data['Name']) ? $data['Name'] : $plugin_file,
                'version' => !empty($data['Version']) ? $data['Version'] : '',
                'requires_php' => !empty($data['RequiresPHP']) ? $data['RequiresPHP'] : '',
                'requires_wp' => !empty($data['RequiresWP']) ? $data['RequiresWP'] : '',
            );
        }

        $theme = wp_get_theme();
        TracePilot_Helpers::init();

        return array(
            'wordpress_version' => $wp_version,
            'php_version' => phpversion(),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'rest_routes' => function_exists('rest_get_server') ? count((array) rest_get_server()->get_routes()) : 0,
            'cron_total' => count((array) _get_cron_array()),
            'active_plugins' => $active_plugins,
            'active_theme' => array(
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'stylesheet' => $theme->get_stylesheet(),
                'requires_php' => $theme->get('RequiresPHP'),
                'requires_wp' => $theme->get('RequiresWP'),
            ),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'table_ready' => $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', TracePilot_Helpers::$db_table)) === TracePilot_Helpers::$db_table,
            'log_total' => $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', TracePilot_Helpers::$db_table)) === TracePilot_Helpers::$db_table
                ? (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . TracePilot_Helpers::$db_table)
                : 0,
            'log_columns' => $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', TracePilot_Helpers::$db_table)) === TracePilot_Helpers::$db_table
                ? $wpdb->get_col('DESC ' . TracePilot_Helpers::$db_table, 0)
                : array(),
        );
    }

    /**
     * Core system scanner.
     *
     * @param array $inventory Environment inventory.
     * @return array
     */
    private function scan_system_health($inventory) {
        global $wpdb;

        $issues = array();

        $issues[] = $this->make_issue(
            'active_software',
            'info',
            sprintf(
                __('%1$d active plugins and theme %2$s are currently loaded.', 'wp-activity-logger-pro'),
                count($inventory['active_plugins']),
                $inventory['active_theme']['name']
            ),
            __('This is the current active software stack being scanned.', 'wp-activity-logger-pro'),
            array($this->make_fix(__('Review active components', 'wp-activity-logger-pro'), __('Use this inventory when narrowing conflicts or performance issues.', 'wp-activity-logger-pro'), 'high'))
        );

        if (version_compare($inventory['php_version'], '8.0', '<')) {
            $issues[] = $this->make_issue(
                'php_version',
                'critical',
                sprintf(__('PHP %s is below the recommended level.', 'wp-activity-logger-pro'), $inventory['php_version']),
                __('An older PHP version can break plugin compatibility and reduce stability.', 'wp-activity-logger-pro'),
                array($this->make_fix(__('Upgrade PHP', 'wp-activity-logger-pro'), __('Move the site to PHP 8.0 or newer.', 'wp-activity-logger-pro'), 'high'))
            );
        }

        foreach ((array) $inventory['active_plugins'] as $plugin) {
            if (!empty($plugin['requires_php']) && version_compare($inventory['php_version'], $plugin['requires_php'], '<')) {
                $issues[] = $this->make_issue(
                    'plugin_php_compatibility_' . md5($plugin['file']),
                    'critical',
                    sprintf(__('Plugin %1$s requires PHP %2$s or newer.', 'wp-activity-logger-pro'), $plugin['name'], $plugin['requires_php']),
                    __('This plugin may behave unpredictably because the server PHP version is lower than the plugin requirement.', 'wp-activity-logger-pro'),
                    array(
                        $this->make_fix(__('Upgrade PHP', 'wp-activity-logger-pro'), __('Raise the site PHP version to match this plugin requirement.', 'wp-activity-logger-pro'), 'high'),
                        $this->make_fix(__('Temporarily disable the plugin', 'wp-activity-logger-pro'), __('If upgrade is not immediate, disable the incompatible plugin first.', 'wp-activity-logger-pro'), 'medium')
                    ),
                    array('plugins' => array($plugin['file']))
                );
            }

            if (!empty($plugin['requires_wp']) && version_compare($inventory['wordpress_version'], $plugin['requires_wp'], '<')) {
                $issues[] = $this->make_issue(
                    'plugin_wp_compatibility_' . md5($plugin['file']),
                    'warning',
                    sprintf(__('Plugin %1$s expects WordPress %2$s or newer.', 'wp-activity-logger-pro'), $plugin['name'], $plugin['requires_wp']),
                    __('This version gap can cause admin breakage, editor problems, or missing functions.', 'wp-activity-logger-pro'),
                    array(
                        $this->make_fix(__('Update WordPress', 'wp-activity-logger-pro'), __('Bring WordPress to the version required by this plugin.', 'wp-activity-logger-pro'), 'high')
                    ),
                    array('plugins' => array($plugin['file']))
                );
            }
        }

        if (!empty($inventory['active_theme']['requires_php']) && version_compare($inventory['php_version'], $inventory['active_theme']['requires_php'], '<')) {
            $issues[] = $this->make_issue(
                'theme_php_compatibility',
                'critical',
                sprintf(__('Theme %1$s requires PHP %2$s or newer.', 'wp-activity-logger-pro'), $inventory['active_theme']['name'], $inventory['active_theme']['requires_php']),
                __('The active theme expects a newer PHP version than the server currently provides.', 'wp-activity-logger-pro'),
                array(
                    $this->make_fix(__('Upgrade PHP', 'wp-activity-logger-pro'), __('Raise PHP to a compatible version for the active theme.', 'wp-activity-logger-pro'), 'high')
                )
            );
        }

        if (!$inventory['wp_debug']) {
            $issues[] = $this->make_issue(
                'debug_mode',
                'info',
                __('WP_DEBUG is disabled.', 'wp-activity-logger-pro'),
                __('Debug mode is off, so WordPress will be less verbose while you investigate issues.', 'wp-activity-logger-pro'),
                array($this->make_fix(__('Enable debug temporarily', 'wp-activity-logger-pro'), __('Turn on WP_DEBUG briefly while investigating a hard failure.', 'wp-activity-logger-pro'), 'advanced'))
            );
        }

        if (0 === (int) $inventory['rest_routes']) {
            $issues[] = $this->make_issue(
                'rest_api',
                'critical',
                __('REST API routes are unavailable.', 'wp-activity-logger-pro'),
                __('The REST API appears unhealthy, which can break modern editors, AJAX tools, and integrations.', 'wp-activity-logger-pro'),
                array(
                    $this->make_fix(__('Regenerate permalinks', 'wp-activity-logger-pro'), __('Open Permalinks settings and save once to refresh rewrite rules.', 'wp-activity-logger-pro'), 'high'),
                    $this->make_fix(__('Check security/firewall rules', 'wp-activity-logger-pro'), __('A security plugin or server rule may be blocking REST requests.', 'wp-activity-logger-pro'), 'medium'),
                )
            );
        }

        $cron = (array) _get_cron_array();
        $overdue = 0;
        $now = time();
        foreach ($cron as $timestamp => $hooks) {
            if ((int) $timestamp < $now - HOUR_IN_SECONDS) {
                $overdue += count((array) $hooks);
            }
        }
        if ($overdue > 0) {
            $issues[] = $this->make_issue(
                'cron_health',
                $overdue > 10 ? 'warning' : 'info',
                sprintf(__('There are %d overdue cron events.', 'wp-activity-logger-pro'), $overdue),
                __('Scheduled WordPress tasks are falling behind, which can affect emails, cleanup jobs, and automations.', 'wp-activity-logger-pro'),
                array(
                    $this->make_fix(__('Trigger WP-Cron manually', 'wp-activity-logger-pro'), __('Visit the site or configure a real server cron to call wp-cron.php.', 'wp-activity-logger-pro'), 'high')
                )
            );
        }

        if (empty($inventory['table_ready'])) {
            $issues[] = $this->make_issue(
                'database_table',
                'critical',
                __('The activity log table is missing.', 'wp-activity-logger-pro'),
                __('Without the main table, the plugin cannot store activity or issue data.', 'wp-activity-logger-pro'),
                array($this->make_fix(__('Recreate plugin tables', 'wp-activity-logger-pro'), __('Deactivate and reactivate the plugin or run the built-in repair flow.', 'wp-activity-logger-pro'), 'high'))
            );
        } else {
            $expected_columns = array('time', 'site_id', 'user_id', 'username', 'user_role', 'action', 'description', 'severity', 'ip', 'browser', 'context');
            $missing_columns = array_diff($expected_columns, (array) $inventory['log_columns']);
            if (!empty($missing_columns)) {
                $issues[] = $this->make_issue(
                    'database_schema',
                    'warning',
                    sprintf(__('The activity log table is missing %s.', 'wp-activity-logger-pro'), implode(', ', $missing_columns)),
                    __('The log schema is older than the current plugin expectations. Logging may partially work but some features can fail or lose context.', 'wp-activity-logger-pro'),
                    array(
                        $this->make_fix(__('Run the table upgrade flow', 'wp-activity-logger-pro'), __('Reactivate the plugin or trigger the table creation routine so missing columns are added.', 'wp-activity-logger-pro'), 'high')
                    ),
                    array('missing_columns' => array_values($missing_columns))
                );
            }
        }

        if (wp_convert_hr_to_bytes($inventory['memory_limit']) < 128 * 1024 * 1024) {
            $issues[] = $this->make_issue(
                'memory_limit',
                'warning',
                sprintf(__('Memory limit is %s.', 'wp-activity-logger-pro'), $inventory['memory_limit']),
                __('A low PHP memory limit can trigger white screens, export failures, or editor crashes.', 'wp-activity-logger-pro'),
                array($this->make_fix(__('Increase PHP memory', 'wp-activity-logger-pro'), __('Raise WordPress memory to 128M or higher.', 'wp-activity-logger-pro'), 'medium'))
            );
        }

        if ((int) $inventory['max_execution_time'] > 0 && (int) $inventory['max_execution_time'] < 60) {
            $issues[] = $this->make_issue(
                'execution_time',
                'warning',
                sprintf(__('Max execution time is %s seconds.', 'wp-activity-logger-pro'), $inventory['max_execution_time']),
                __('Long tasks like scans, imports, and exports may stop before they finish.', 'wp-activity-logger-pro'),
                array($this->make_fix(__('Raise max execution time', 'wp-activity-logger-pro'), __('Increase PHP max_execution_time to at least 120 seconds.', 'wp-activity-logger-pro'), 'medium'))
            );
        }

        if (!empty($wpdb->last_error)) {
            $issues[] = $this->make_issue(
                'database_error',
                'warning',
                __('A recent database error was detected during the current request.', 'wp-activity-logger-pro'),
                $this->explain_error_message($wpdb->last_error),
                array($this->make_fix(__('Review recent database queries', 'wp-activity-logger-pro'), __('Inspect query-heavy features and verify table structure.', 'wp-activity-logger-pro'), 'advanced')),
                array('raw_error' => $wpdb->last_error)
            );
        }

        return $issues;
    }

    /**
     * Conflict detector.
     *
     * @param array $inventory Inventory.
     * @return array
     */
    private function scan_conflicts($inventory) {
        $issues = array();
        $client_errors = get_option(self::CLIENT_ERRORS_OPTION, array());
        $recent_error = is_array($client_errors) && !empty($client_errors) ? $client_errors[0] : array();

        if (!empty($recent_error['message'])) {
            $plugins = $this->extract_plugins_from_text($recent_error['message'] . ' ' . $recent_error['stack'] . ' ' . $recent_error['source']);
            $issues[] = $this->make_issue(
                'javascript_error',
                'warning',
                __('Recent JavaScript runtime errors were captured in the browser.', 'wp-activity-logger-pro'),
                $this->explain_error_message($recent_error['message']),
                $this->get_suggestions_for_issue('javascript_error', array('plugins' => $plugins)),
                array('raw_error' => $recent_error['message'], 'page' => $recent_error['page'], 'plugins' => $plugins)
            );
        }

        $fatal = $this->get_recent_php_fatal_error();
        if (!empty($fatal['message'])) {
            $plugins = $this->extract_plugins_from_text($fatal['message'] . ' ' . $fatal['stack']);
            $issues[] = $this->make_issue(
                'php_fatal',
                'critical',
                __('A recent PHP fatal error was detected.', 'wp-activity-logger-pro'),
                $this->explain_error_message($fatal['message']),
                $this->get_suggestions_for_issue('php_fatal', array('plugins' => $plugins)),
                array('raw_error' => $fatal['message'], 'plugins' => $plugins)
            );
        }

        foreach ($this->detect_hook_collisions() as $collision) {
            $issues[] = $this->make_issue(
                'hook_collision_' . md5($collision['hook'] . '|' . implode(',', $collision['plugins'])),
                'warning',
                sprintf(__('Potential hook collision on %1$s between %2$s.', 'wp-activity-logger-pro'), $collision['hook'], implode(', ', $collision['plugins'])),
                __('Multiple plugins are attaching heavily to the same WordPress lifecycle point with the same priority. That can cause unexpected overrides or ordering problems.', 'wp-activity-logger-pro'),
                array(
                    $this->make_fix(__('Use safe mode to split-test plugins', 'wp-activity-logger-pro'), __('Disable half of the involved plugins in your admin-only safe mode session, then retry the failing page.', 'wp-activity-logger-pro'), 'high'),
                    $this->make_fix(__('Update the involved plugins', 'wp-activity-logger-pro'), __('Conflicts are often fixed in newer plugin releases.', 'wp-activity-logger-pro'), 'high'),
                ),
                $collision
            );
        }

        $special = $this->detect_special_conflicts();
        foreach ($special as $conflict) {
            $issues[] = $this->make_issue(
                'special_conflict_' . md5($conflict['message']),
                'critical',
                $conflict['message'],
                $conflict['explanation'],
                $conflict['suggestions'],
                $conflict['context']
            );
        }

        return $issues;
    }

    /**
     * Runtime and timeline-related checks.
     *
     * @param array $inventory Inventory.
     * @return array
     */
    private function scan_runtime_errors($inventory) {
        global $wpdb;
        $issues = array();

        TracePilot_Helpers::init();
        $updates = $wpdb->get_results(
            "SELECT time, action, object_name
            FROM " . TracePilot_Helpers::$db_table . "
            WHERE action IN ('plugin_activated','plugin_deactivated','plugin_updated','theme_switched','theme_updated','wordpress_updated','post_updated','vulnerability_detected')
            ORDER BY time DESC
            LIMIT 20"
        );

        if (!empty($updates)) {
            $latest = $updates[0];
            $issues[] = $this->make_issue(
                'issue_timeline_context',
                'info',
                sprintf(__('Recent site change recorded: %1$s at %2$s.', 'wp-activity-logger-pro'), $latest->action, TracePilot_Helpers::format_datetime($latest->time)),
                __('Use the timeline below to compare new issues against recent plugin, theme, and content changes.', 'wp-activity-logger-pro'),
                array(),
                array('recent_changes' => $updates)
            );
        }

        return $issues;
    }

    /**
     * Correlate issues against recent changes.
     *
     * @param array $issues Issues.
     * @param array $timeline Timeline rows.
     * @return array
     */
    private function build_change_correlations($issues, $timeline) {
        global $wpdb;

        TracePilot_Helpers::init();
        if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', TracePilot_Helpers::$db_table))) {
            return array();
        }

        $recent_changes = $wpdb->get_results(
            "SELECT time, action, object_name, context
            FROM " . TracePilot_Helpers::$db_table . "
            WHERE action IN ('plugin_updated','theme_updated','wordpress_updated','plugin_activated','plugin_deactivated','theme_switched','settings_updated')
            ORDER BY time DESC
            LIMIT 25"
        );

        if (empty($recent_changes) || empty($timeline)) {
            return array();
        }

        $correlations = array();
        foreach ($timeline as $entry) {
            $first_seen = strtotime($entry['first_seen']);
            if (!$first_seen) {
                continue;
            }

            foreach ($recent_changes as $change) {
                $change_time = strtotime($change->time);
                if (!$change_time) {
                    continue;
                }

                $delta = abs($first_seen - $change_time);
                if ($delta > DAY_IN_SECONDS * 2) {
                    continue;
                }

                $correlations[] = array(
                    'issue' => $entry['message'],
                    'issue_code' => $entry['issue_code'],
                    'change_action' => $change->action,
                    'change_label' => $change->object_name ? $change->object_name : $change->action,
                    'change_time' => $change->time,
                    'first_seen' => $entry['first_seen'],
                    'delta_hours' => round($delta / HOUR_IN_SECONDS, 1),
                );

                break;
            }
        }

        return array_slice($correlations, 0, 8);
    }

    /**
     * Build a binary conflict-isolation plan.
     *
     * @param array $issues Issues.
     * @param array $inventory Inventory.
     * @return array
     */
    private function build_conflict_plan($issues, $inventory) {
        $suspects = array();
        foreach ($issues as $issue) {
            if (!empty($issue['plugins'])) {
                foreach ((array) $issue['plugins'] as $plugin_slug) {
                    $suspects[$plugin_slug] = true;
                }
            }
        }

        $active_map = array();
        foreach ((array) $inventory['active_plugins'] as $plugin) {
            $slug = dirname($plugin['file']);
            if ('.' === $slug) {
                $slug = $plugin['file'];
            }
            $active_map[$slug] = $plugin;
        }

        $suspect_plugins = array();
        foreach (array_keys($suspects) as $slug) {
            if (isset($active_map[$slug])) {
                $suspect_plugins[] = $active_map[$slug];
            }
        }

        if (count($suspect_plugins) < 2) {
            $suspect_plugins = array_slice((array) $inventory['active_plugins'], 0, min(8, count((array) $inventory['active_plugins'])));
        }

        $half = (int) ceil(count($suspect_plugins) / 2);
        $group_a = array_slice($suspect_plugins, 0, $half);
        $group_b = array_slice($suspect_plugins, $half);

        return array(
            'suspect_count' => count($suspect_plugins),
            'group_a' => $group_a,
            'group_b' => $group_b,
            'summary' => __('Use admin-only safe mode to disable half of the suspected plugins, retest the failing page, then switch halves. This narrows the conflict quickly without affecting visitors.', 'wp-activity-logger-pro'),
        );
    }

    /**
     * Enhance issues with labels and confidence.
     *
     * @param array $issues Issues.
     * @return array
     */
    private function decorate_issues($issues) {
        foreach ($issues as &$issue) {
            if (empty($issue['explanation']) && !empty($issue['raw_error'])) {
                $issue['explanation'] = $this->explain_error_message($issue['raw_error']);
            }
            if (empty($issue['suggestions'])) {
                $issue['suggestions'] = $this->get_suggestions_for_issue($issue['code'], $issue);
            }
        }
        unset($issue);

        return $issues;
    }

    /**
     * Build scanner summary.
     *
     * @param array $issues Issues.
     * @return array
     */
    private function build_scan_summary($issues) {
        $counts = array('critical' => 0, 'warning' => 0, 'info' => 0);
        $score = 100;

        foreach ($issues as $issue) {
            if (!isset($counts[$issue['severity']])) {
                continue;
            }
            $counts[$issue['severity']]++;
            if ('critical' === $issue['severity']) {
                $score -= 18;
            } elseif ('warning' === $issue['severity']) {
                $score -= 8;
            } else {
                $score -= 2;
            }
        }

        return array(
            'health_score' => max(0, $score),
            'counts' => $counts,
        );
    }

    /**
     * Build issue timeline state.
     *
     * @param array $issues Issues.
     * @param bool  $persist Persist state.
     * @return array
     */
    private function build_issue_timeline($issues, $persist) {
        $timeline = get_option(self::TIMELINE_OPTION, array());
        if (!is_array($timeline)) {
            $timeline = array();
        }

        $now = current_time('mysql');
        $active_keys = array();
        foreach ($issues as $issue) {
            $hash = md5($issue['code'] . '|' . $issue['message']);
            $active_keys[] = $hash;
            if (empty($timeline[$hash])) {
                $timeline[$hash] = array(
                    'issue_code' => $issue['code'],
                    'message' => $issue['message'],
                    'severity' => $issue['severity'],
                    'first_seen' => $now,
                    'last_seen' => $now,
                    'count' => 1,
                );
            } else {
                $timeline[$hash]['last_seen'] = $now;
                $timeline[$hash]['count'] = isset($timeline[$hash]['count']) ? (int) $timeline[$hash]['count'] + 1 : 1;
            }
        }

        if ($persist) {
            update_option(self::TIMELINE_OPTION, $timeline, false);
        }

        $rows = array_values(array_filter($timeline, function($row) use ($active_keys) {
            return in_array(md5($row['issue_code'] . '|' . $row['message']), $active_keys, true);
        }));

        usort($rows, function($a, $b) {
            return strcmp($b['last_seen'], $a['last_seen']);
        });

        return array_slice($rows, 0, 12);
    }

    /**
     * Append compact history entries.
     *
     * @param array $report Report.
     */
    private function append_history($report) {
        $history = get_option(self::HISTORY_OPTION, array());
        if (!is_array($history)) {
            $history = array();
        }

        array_unshift(
            $history,
            array(
                'generated_at' => $report['generated_at'],
                'health_score' => $report['health_score'],
                'counts' => $report['counts'],
                'issues' => array_map(function($issue) {
                    return array(
                        'code' => $issue['code'],
                        'severity' => $issue['severity'],
                        'message' => $issue['message'],
                    );
                }, array_slice($report['issues'], 0, 12)),
            )
        );

        update_option(self::HISTORY_OPTION, array_slice($history, 0, 30), false);
    }

    /**
     * Record scan summary/issues in the activity log.
     *
     * @param array $report Report.
     */
    private function record_report_logs($report) {
        TracePilot_Helpers::log_activity(
            'system_scan_completed',
            sprintf(__('System scan completed with health score %d.', 'wp-activity-logger-pro'), (int) $report['health_score']),
            $report['counts']['critical'] > 0 ? 'error' : ($report['counts']['warning'] > 0 ? 'warning' : 'info'),
            array('context' => array('counts' => $report['counts']))
        );

        foreach (array_slice($report['issues'], 0, 10) as $issue) {
            TracePilot_Helpers::log_activity(
                'system_issue_detected',
                $issue['message'],
                $this->map_issue_severity_to_log($issue['severity']),
                array(
                    'object_type' => 'diagnostic_issue',
                    'object_name' => $issue['code'],
                    'context' => array(
                        'severity' => $issue['severity'],
                        'explanation' => $issue['explanation'],
                        'suggestions' => $issue['suggestions'],
                    ),
                )
            );
        }
    }

    /**
     * Send real-time critical alerts.
     *
     * @param array $report Report.
     */
    private function send_critical_alerts($report) {
        if (empty($report['counts']['critical']) || empty(tracepilot_for_wordpress()->notifications)) {
            return;
        }

        $top = array_filter($report['issues'], function($issue) {
            return 'critical' === $issue['severity'];
        });
        $top = array_slice(array_values($top), 0, 3);

        $message = sprintf(
            __('Critical site issues detected. Health score %1$d. Top issue: %2$s', 'wp-activity-logger-pro'),
            (int) $report['health_score'],
            !empty($top[0]['message']) ? $top[0]['message'] : __('Unknown critical issue', 'wp-activity-logger-pro')
        );

        tracepilot_for_wordpress()->notifications->send_custom_notification(
            'critical_site_issue',
            $message,
            'error',
            array('issues' => $top, 'health_score' => $report['health_score'])
        );
    }

    /**
     * Detect potential hook collisions.
     *
     * @return array
     */
    private function detect_hook_collisions() {
        global $wp_filter;

        $hooks_to_scan = array(
            'plugins_loaded',
            'init',
            'wp_enqueue_scripts',
            'template_redirect',
            'rest_api_init',
            'woocommerce_init',
            'woocommerce_checkout_process',
        );

        $collisions = array();
        foreach ($hooks_to_scan as $hook_name) {
            if (empty($wp_filter[$hook_name]) || !is_object($wp_filter[$hook_name])) {
                continue;
            }

            $callbacks = isset($wp_filter[$hook_name]->callbacks) ? $wp_filter[$hook_name]->callbacks : array();
            foreach ($callbacks as $priority => $items) {
                $plugins = array();
                foreach ($items as $item) {
                    $file = $this->resolve_callback_file(isset($item['function']) ? $item['function'] : null);
                    $plugin = $this->classify_source_from_file($file);
                    if ('core' !== $plugin && 'theme' !== $plugin && $plugin) {
                        $plugins[$plugin] = true;
                    }
                }

                if (count($plugins) >= 2 && count($items) >= 4) {
                    $collisions[] = array(
                        'hook' => $hook_name,
                        'priority' => (int) $priority,
                        'plugins' => array_keys($plugins),
                        'callback_count' => count($items),
                    );
                }
            }
        }

        return array_slice($collisions, 0, 8);
    }

    /**
     * Detect more specific special conflicts.
     *
     * @return array
     */
    private function detect_special_conflicts() {
        $issues = array();
        $client_errors = get_option(self::CLIENT_ERRORS_OPTION, array());
        $recent_error = is_array($client_errors) && !empty($client_errors) ? $client_errors[0] : array();
        $text = strtolower((string) (($recent_error['message'] ?? '') . ' ' . ($recent_error['stack'] ?? '')));

        if (false !== strpos($text, 'woocommerce') && preg_match('/plugins\/([^\/]+)\//', $text, $matches)) {
            $other = sanitize_text_field($matches[1]);
            if ('woocommerce' !== $other) {
                $issues[] = array(
                    'message' => sprintf(__('Conflict detected between WooCommerce and %s on a recent frontend page.', 'wp-activity-logger-pro'), $other),
                    'explanation' => __('WooCommerce appears in the failing stack alongside another plugin, which usually means the second plugin is interrupting WooCommerce behavior on that page.', 'wp-activity-logger-pro'),
                    'suggestions' => array(
                        $this->make_fix(__('Disable the conflicting plugin in safe mode', 'wp-activity-logger-pro'), __('Start safe mode and disable the suspected plugin only for your admin session, then retest checkout privately.', 'wp-activity-logger-pro'), 'high'),
                        $this->make_fix(__('Update both plugins', 'wp-activity-logger-pro'), __('Compatibility fixes often ship in plugin updates.', 'wp-activity-logger-pro'), 'high'),
                    ),
                    'context' => array('plugins' => array('woocommerce', $other), 'page' => $recent_error['page'] ?? ''),
                );
            }
        }

        return $issues;
    }

    /**
     * Get recent PHP fatal error from debug log path.
     *
     * @return array
     */
    private function get_recent_php_fatal_error() {
        $debug_path = WP_CONTENT_DIR . '/debug.log';
        if (!file_exists($debug_path) || !is_readable($debug_path)) {
            return array();
        }

        $contents = @file_get_contents($debug_path, false, null, max(0, filesize($debug_path) - 65536));
        if (!$contents) {
            return array();
        }

        $lines = array_reverse(preg_split('/\r\n|\r|\n/', $contents));
        foreach ($lines as $line) {
            if (false !== stripos($line, 'fatal error')) {
                return array(
                    'message' => trim($line),
                    'stack' => trim($line),
                );
            }
        }

        return array();
    }

    /**
     * Explain technical errors in human language.
     *
     * @param string $message Raw error.
     * @return string
     */
    private function explain_error_message($message) {
        $message = (string) $message;
        $patterns = array(
            '/undefined function wc_get_product/i' => __('WooCommerce is not loaded properly. This usually happens when another plugin interrupts WooCommerce before it finishes loading.', 'wp-activity-logger-pro'),
            '/allowed memory size/i' => __('PHP ran out of memory while handling the request. The site needs a higher memory limit or a lighter workload on that page.', 'wp-activity-logger-pro'),
            '/class .* not found/i' => __('A plugin or theme expected a PHP class that never loaded. This usually points to an update problem, a file loading issue, or a plugin conflict.', 'wp-activity-logger-pro'),
            '/call to undefined function/i' => __('A required PHP function is missing during execution. Another component may be loading too early, too late, or not at all.', 'wp-activity-logger-pro'),
            '/rest/i' => __('A REST request is failing. This can affect the block editor, AJAX-powered settings, and modern plugin screens.', 'wp-activity-logger-pro'),
            '/cron/i' => __('A scheduled task is failing or delayed. Background jobs may not be running reliably.', 'wp-activity-logger-pro'),
        );

        foreach ($patterns as $pattern => $explanation) {
            if (preg_match($pattern, $message)) {
                return $explanation;
            }
        }

        return __('This issue indicates that WordPress or one of its extensions is failing during runtime. The suggestions below focus on the safest next debugging steps.', 'wp-activity-logger-pro');
    }

    /**
     * Get rule-based smart suggestions.
     *
     * @param string $code Issue code.
     * @param array  $context Issue context.
     * @return array
     */
    private function get_suggestions_for_issue($code, $context = array()) {
        $suggestions = array();

        if (false !== strpos($code, 'php_fatal') || false !== strpos($code, 'hook_collision') || false !== strpos($code, 'special_conflict')) {
            $suggestions[] = $this->make_fix(__('Disable suspected plugin in safe mode', 'wp-activity-logger-pro'), __('Use admin-only safe mode so visitors are unaffected while you isolate the failure.', 'wp-activity-logger-pro'), 'high');
            $suggestions[] = $this->make_fix(__('Update plugin/theme', 'wp-activity-logger-pro'), __('Install the latest updates for the affected software.', 'wp-activity-logger-pro'), 'high');
        }

        if (false !== strpos($code, 'memory')) {
            $suggestions[] = $this->make_fix(__('Increase PHP memory', 'wp-activity-logger-pro'), __('Raise WordPress memory and test the failing page again.', 'wp-activity-logger-pro'), 'medium');
        }

        if (false !== strpos($code, 'rest_api')) {
            $suggestions[] = $this->make_fix(__('Regenerate permalinks', 'wp-activity-logger-pro'), __('Save permalink settings once to rebuild rewrite rules.', 'wp-activity-logger-pro'), 'high');
            $suggestions[] = $this->make_fix(__('Clear cache', 'wp-activity-logger-pro'), __('Clear page cache, server cache, and CDN cache if present.', 'wp-activity-logger-pro'), 'medium');
        }

        if (false !== strpos($code, 'javascript_error')) {
            $suggestions[] = $this->make_fix(__('Clear cache', 'wp-activity-logger-pro'), __('Clear browser and site cache, then test again to avoid stale JavaScript bundles.', 'wp-activity-logger-pro'), 'high');
        }

        if (empty($suggestions)) {
            $suggestions[] = $this->make_fix(__('Review latest updates', 'wp-activity-logger-pro'), __('Check what changed recently, especially plugin/theme updates and configuration edits.', 'wp-activity-logger-pro'), 'medium');
        }

        return $suggestions;
    }

    /**
     * Answer contextual assistant questions.
     *
     * @param string $question User question.
     * @return string
     */
    private function answer_contextual_question($question) {
        $question = strtolower($question);
        $report = $this->get_latest_report();
        $issues = !empty($report['issues']) ? $report['issues'] : array();

        if (false !== strpos($question, 'slow')) {
            foreach ($issues as $issue) {
                if (in_array($issue['code'], array('memory_limit', 'execution_time', 'cron_health'), true)) {
                    return sprintf(
                        __('The strongest slowdown signal right now is: %1$s. Suggested next step: %2$s', 'wp-activity-logger-pro'),
                        $issue['message'],
                        !empty($issue['suggestions'][0]['title']) ? $issue['suggestions'][0]['title'] : __('review diagnostics', 'wp-activity-logger-pro')
                    );
                }
            }

            return __('No major performance-specific issue stands out in the latest scan. I would next check heavy plugins, caching, and slow server resources.', 'wp-activity-logger-pro');
        }

        if (false !== strpos($question, 'disable') || false !== strpos($question, 'conflict')) {
            if (!empty($report['conflict_plan']['group_a'])) {
                $names = wp_list_pluck((array) $report['conflict_plan']['group_a'], 'name');
                return sprintf(
                    __('Start with the first isolation batch: %1$s. If the issue disappears, the conflict is inside that group. If not, switch to the second batch.', 'wp-activity-logger-pro'),
                    implode(', ', array_slice($names, 0, 4))
                );
            }

            foreach ($issues as $issue) {
                if (false !== strpos($issue['code'], 'hook_collision') || false !== strpos($issue['code'], 'special_conflict')) {
                    return sprintf(__('Start with this suspected conflict: %s. The safest path is to use admin-only safe mode and disable half of the suspected plugins, then retest.', 'wp-activity-logger-pro'), $issue['message']);
                }
            }
        }

        if (false !== strpos($question, 'start') || false !== strpos($question, 'after update')) {
            if (!empty($report['correlations'][0])) {
                $correlation = $report['correlations'][0];
                return sprintf(
                    __('The strongest timeline clue is that "%1$s" first appeared near the change "%2$s" around %3$s.', 'wp-activity-logger-pro'),
                    $correlation['issue'],
                    $correlation['change_label'],
                    TracePilot_Helpers::format_datetime($correlation['change_time'])
                );
            }
        }

        if (!empty($issues[0])) {
            return sprintf(__('Top current issue: %1$s. Human explanation: %2$s', 'wp-activity-logger-pro'), $issues[0]['message'], $issues[0]['explanation']);
        }

        return __('The latest scan does not show a major active issue. Run a fresh scan after reproducing the problem for more context.', 'wp-activity-logger-pro');
    }

    /**
     * Safe mode plugin filter.
     *
     * @param array $plugins Active plugins.
     * @return array
     */
    public function filter_active_plugins_for_safe_mode($plugins) {
        $status = $this->get_safe_mode_status();
        if (empty($status['enabled']) || empty($status['plugins'])) {
            return $plugins;
        }

        return array_values(array_diff((array) $plugins, (array) $status['plugins']));
    }

    /**
     * Safe mode network plugin filter.
     *
     * @param array $plugins Network plugins.
     * @return array
     */
    public function filter_network_active_plugins_for_safe_mode($plugins) {
        $status = $this->get_safe_mode_status();
        if (empty($status['enabled']) || empty($status['plugins'])) {
            return $plugins;
        }

        foreach ((array) $status['plugins'] as $plugin_file) {
            unset($plugins[$plugin_file]);
        }

        return $plugins;
    }

    /**
     * Current safe mode status.
     *
     * @return array
     */
    public function get_safe_mode_status() {
        if (empty($_COOKIE[self::SAFE_MODE_COOKIE]) || !is_user_logged_in()) {
            return array('enabled' => false, 'plugins' => array());
        }

        $token = sanitize_text_field(wp_unslash($_COOKIE[self::SAFE_MODE_COOKIE]));
        $payload = get_transient($this->get_safe_mode_transient_key($token));
        if (empty($payload['user_id']) || (int) $payload['user_id'] !== get_current_user_id()) {
            return array('enabled' => false, 'plugins' => array());
        }

        return array(
            'enabled' => true,
            'plugins' => isset($payload['plugins']) ? (array) $payload['plugins'] : array(),
            'enabled_at' => isset($payload['enabled_at']) ? (int) $payload['enabled_at'] : 0,
        );
    }

    /**
     * Safe mode transient key.
     *
     * @param string $token Token.
     * @return string
     */
    private function get_safe_mode_transient_key($token) {
        return 'tracepilot_safe_mode_' . md5($token);
    }

    /**
     * Create issue payload.
     *
     * @param string $code Code.
     * @param string $severity Severity.
     * @param string $message Message.
     * @param string $explanation Explanation.
     * @param array  $suggestions Suggestions.
     * @param array  $context Extra context.
     * @return array
     */
    private function make_issue($code, $severity, $message, $explanation, $suggestions = array(), $context = array()) {
        return array_merge(
            array(
                'code' => $code,
                'severity' => $severity,
                'message' => $message,
                'explanation' => $explanation,
                'suggestions' => $suggestions,
            ),
            $context
        );
    }

    /**
     * Create a fix suggestion.
     *
     * @param string $title Title.
     * @param string $description Description.
     * @param string $confidence Confidence.
     * @return array
     */
    private function make_fix($title, $description, $confidence) {
        return array(
            'title' => $title,
            'description' => $description,
            'confidence' => $confidence,
        );
    }

    /**
     * Resolve callback file.
     *
     * @param mixed $callback Callback.
     * @return string
     */
    private function resolve_callback_file($callback) {
        try {
            if (is_array($callback) && isset($callback[0], $callback[1])) {
                $reflection = new ReflectionMethod($callback[0], $callback[1]);
                return (string) $reflection->getFileName();
            }

            if ($callback instanceof Closure || is_string($callback)) {
                $reflection = new ReflectionFunction($callback);
                return (string) $reflection->getFileName();
            }
        } catch (Exception $e) {
            return '';
        } catch (ReflectionException $e) {
            return '';
        }

        return '';
    }

    /**
     * Classify source from file path.
     *
     * @param string $file File path.
     * @return string
     */
    private function classify_source_from_file($file) {
        $file = (string) $file;
        if ('' === $file) {
            return 'unknown';
        }

        if (0 === strpos($file, WP_PLUGIN_DIR)) {
            $relative = ltrim(str_replace(WP_PLUGIN_DIR, '', $file), '/');
            return strtok($relative, '/');
        }

        if (0 === strpos($file, get_theme_root())) {
            return 'theme';
        }

        if (0 === strpos($file, ABSPATH)) {
            return 'core';
        }

        return 'custom';
    }

    /**
     * Extract plugin names from error text.
     *
     * @param string $text Text.
     * @return array
     */
    private function extract_plugins_from_text($text) {
        preg_match_all('#plugins/([^/\s]+)/#i', (string) $text, $matches);
        return !empty($matches[1]) ? array_values(array_unique(array_map('sanitize_text_field', $matches[1]))) : array();
    }

    /**
     * Map issue severity to activity log severity.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function map_issue_severity_to_log($severity) {
        if ('critical' === $severity) {
            return 'error';
        }
        if ('warning' === $severity) {
            return 'warning';
        }
        return 'info';
    }

    /**
     * Compact report for storage.
     *
     * @param array $report Report.
     * @return array
     */
    private function compact_report($report) {
        $report['issues'] = array_slice((array) $report['issues'], 0, 20);
        foreach ($report['issues'] as &$issue) {
            $issue['suggestions'] = array_slice((array) $issue['suggestions'], 0, 3);
        }
        unset($issue);

        if (!empty($report['timeline'])) {
            $report['timeline'] = array_slice((array) $report['timeline'], 0, 12);
        }

        if (!empty($report['correlations'])) {
            $report['correlations'] = array_slice((array) $report['correlations'], 0, 8);
        }

        if (!empty($report['history'])) {
            $report['history'] = array_slice((array) $report['history'], 0, 8);
        }

        if (!empty($report['conflict_plan'])) {
            $report['conflict_plan']['group_a'] = array_slice((array) $report['conflict_plan']['group_a'], 0, 6);
            $report['conflict_plan']['group_b'] = array_slice((array) $report['conflict_plan']['group_b'], 0, 6);
        }

        return $report;
    }

    /**
     * Render an admin notice for critical scan results.
     */
    public function render_admin_alert_notice() {
        if (!TracePilot_Helpers::current_user_can_manage()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || false === strpos((string) $screen->id, 'wp-activity-logger-pro')) {
            return;
        }

        $report = $this->get_latest_report();
        if (empty($report['counts']['critical'])) {
            return;
        }

        $generated_at = !empty($report['generated_at']) ? strtotime($report['generated_at']) : 0;
        if (!$generated_at || (time() - $generated_at) > DAY_IN_SECONDS * 2) {
            return;
        }

        $top_issue = !empty($report['issues'][0]['message']) ? $report['issues'][0]['message'] : __('Critical site issue detected.', 'wp-activity-logger-pro');
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e('WP Activity Logger Diagnostics Alert:', 'wp-activity-logger-pro'); ?></strong>
                <?php echo esc_html($top_issue); ?>
                <?php
                printf(
                    esc_html__('Latest health score: %d.', 'wp-activity-logger-pro'),
                    (int) $report['health_score']
                );
                ?>
            </p>
        </div>
        <?php
    }
}
