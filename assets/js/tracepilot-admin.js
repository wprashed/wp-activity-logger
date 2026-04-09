/**
 * WP Activity Logger Pro Admin JavaScript.
 */
(function($) {
    'use strict';

    function request(data) {
        return $.ajax({
            url: tracepilot_admin_vars.ajax_url,
            type: 'POST',
            data: Object.assign({ nonce: tracepilot_admin_vars.nonce }, data)
        });
    }

    function modal() {
        return $('#tracepilot-log-details-modal');
    }

    function removeLogItem(button) {
        const row = button.closest('tr');
        if (row.length) {
            if ($.fn.dataTable && $.fn.dataTable.isDataTable('#tracepilot-logs-table')) {
                $('#tracepilot-logs-table').DataTable().row(row).remove().draw();
            } else {
                row.remove();
            }
            return;
        }

        const card = button.closest('.tracepilot-stream-card');
        if (card.length) {
            card.fadeOut(180, function() {
                $(this).remove();
            });
        }
    }

    window.tracepilotRenderLineChart = function(canvasId, labels, values) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: values,
                    borderColor: '#d25d2c',
                    backgroundColor: 'rgba(210, 93, 44, 0.14)',
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 3,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
    };

    window.tracepilotRenderDoughnutChart = function(canvasId, labels, values) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#1f6b5b', '#d25d2c', '#1b3c53', '#c9a227', '#8d99ae']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    };

    function initPageCharts() {
        if (window.tracepilotDashboardTrend) {
            window.tracepilotRenderLineChart('tracepilot-dashboard-trend', window.tracepilotDashboardTrend.labels, window.tracepilotDashboardTrend.values);
        }

        if (window.tracepilotLogsSeverity) {
            window.tracepilotRenderDoughnutChart('tracepilot-logs-severity', window.tracepilotLogsSeverity.labels, window.tracepilotLogsSeverity.values);
        }
    }

    function initTables() {
        if ($.fn.dataTable && $('#tracepilot-logs-table.tracepilot-data-table').length && !$.fn.dataTable.isDataTable('#tracepilot-logs-table')) {
            $('#tracepilot-logs-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
        }
    }

    function initDatepickers() {
        if ($.fn.datepicker) {
            $('.tracepilot-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });
        }
    }

    function initNoticeTray() {
        const wrap = $('.tracepilot-wrap').first();
        if (!wrap.length) {
            return;
        }

        let tray = $('.tracepilot-notice-stack').first();
        if (!tray.length) {
            tray = $('<div class="tracepilot-notice-stack" />');
            wrap.before(tray);
        }

        const selectors = [
            '#wpbody-content > .notice',
            '#wpbody-content > .error',
            '#wpbody-content > .updated',
            '#wpbody-content > .update-nag',
            '.tracepilot-wrap .notice',
            '.tracepilot-wrap .error',
            '.tracepilot-wrap .updated',
            '.tracepilot-wrap .update-nag'
        ];

        const seen = new window.Set();
        $(selectors.join(',')).each(function() {
            const notice = $(this);
            if (!notice.length || notice.closest('.tracepilot-notice-stack').length) {
                return;
            }

            if (notice.attr('id') === 'message' && notice.closest('form').length) {
                return;
            }

            const key = (notice.attr('id') || '') + '|' + $.trim(notice.text());
            if (seen.has(key)) {
                notice.remove();
                return;
            }

            seen.add(key);
            notice.addClass('tracepilot-notice-card');
            tray.append(notice);
        });

        if (!tray.children().length) {
            tray.remove();
        }
    }

    function initTabs() {
        $('[data-tracepilot-tabs]').each(function() {
            const root = $(this).closest('.tracepilot-panel');
            const buttons = $(this).find('.tracepilot-panel-tab');
            const panels = root.find('.tracepilot-tab-panel');

            function activateTab(target) {
                buttons.removeClass('is-active');
                panels.removeClass('is-active');

                buttons.filter(`[data-tab-target="${target}"]`).addClass('is-active');
                panels.filter(`[data-tab-panel="${target}"]`).addClass('is-active');
            }

            buttons.on('click', function() {
                activateTab($(this).attr('data-tab-target'));
            });

            const initial = buttons.filter('.is-active').attr('data-tab-target') || buttons.first().attr('data-tab-target');
            if (initial) {
                activateTab(initial);
            }
        });
    }

    $(document).on('click', '.tracepilot-view-log', function() {
        request({
            action: 'tracepilot_get_log_details',
            log_id: $(this).data('log-id'),
            site_id: $(this).data('site-id') || 0
        }).done(function(html) {
            modal().find('.tracepilot-modal-body').html(html);
            modal().addClass('is-open');
        });
    });

    $(document).on('click', '.tracepilot-modal-close', function() {
        modal().removeClass('is-open');
    });

    $(document).on('click', '.tracepilot-modal', function(event) {
        if ($(event.target).is('.tracepilot-modal')) {
            modal().removeClass('is-open');
        }
    });

    $(document).on('click', '.tracepilot-delete-log', function() {
        const button = $(this);
        if (!window.confirm(tracepilot_admin_vars.confirm_delete)) {
            return;
        }

        request({
            action: 'tracepilot_delete_log',
            log_id: button.data('log-id'),
            site_id: button.data('site-id') || 0
        }).done(function(response) {
            if (response.success) {
                removeLogItem(button);
            } else if (response.data && response.data.message) {
                window.alert(response.data.message);
            }
        });
    });

    $('#tracepilot-delete-all-logs').on('click', function() {
        if (!window.confirm(tracepilot_admin_vars.confirm_delete_all)) {
            return;
        }

        request({ action: 'tracepilot_delete_all_logs' }).done(function(response) {
            if (response.success) {
                window.location.reload();
            }
        });
    });

    $(document).on('click', '.tracepilot-archive-log', function() {
        const button = $(this);
        request({
            action: 'tracepilot_archive_log',
            log_id: button.data('log-id'),
            site_id: button.data('site-id') || 0
        }).done(function(response) {
            if (response.success) {
                removeLogItem(button);
            } else if (response.data && response.data.message) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.tracepilot-restore-log', function() {
        const button = $(this);
        request({
            action: 'tracepilot_restore_log',
            log_id: button.data('log-id')
        }).done(function(response) {
            if (response.success) {
                button.closest('tr').fadeOut(180, function() {
                    $(this).remove();
                });
            } else if (response.data && response.data.message) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.tracepilot-delete-archived-log', function() {
        const button = $(this);
        request({
            action: 'tracepilot_delete_archived_log',
            log_id: button.data('log-id')
        }).done(function(response) {
            if (response.success) {
                button.closest('tr').fadeOut(180, function() {
                    $(this).remove();
                });
            } else if (response.data && response.data.message) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.tracepilot-block-ip', function() {
        const ip = $(this).data('ip');
        if (!ip) {
            return;
        }
        request({ action: 'tracepilot_block_ip', ip: ip }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.tracepilot-force-logout', function() {
        const userId = $(this).data('user-id');
        if (!userId) {
            return;
        }
        request({ action: 'tracepilot_force_logout_user', user_id: userId }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.tracepilot-reset-password', function() {
        const userId = $(this).data('user-id');
        if (!userId) {
            return;
        }
        request({ action: 'tracepilot_reset_user_password', user_id: userId }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.tracepilot-delete-user-logs', function() {
        const userId = $(this).data('user-id');
        if (!userId || !window.confirm('Delete all logs for this user?')) {
            return;
        }
        request({ action: 'tracepilot_delete_user_logs', user_id: userId }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
                window.location.reload();
            }
        });
    });

    function collectOptions(form) {
        const data = {};
        $(form).find('input[type="checkbox"][name^="wpal_options["]').each(function() {
            const match = this.name.match(/^wpal_options\[([^\]]+)\](\[\])?$/);
            if (!match || match[2]) {
                return;
            }
            data[match[1]] = this.checked ? this.value : 0;
        });
        const formData = new window.FormData(form);

        formData.forEach(function(value, key) {
            const match = key.match(/^wpal_options\[([^\]]+)\](\[\])?$/);
            if (!match) {
                return;
            }

            const field = match[1];
            const isArray = Boolean(match[2]);

            if (isArray) {
                if (!Array.isArray(data[field])) {
                    data[field] = [];
                }
                data[field].push(value);
            } else {
                data[field] = value;
            }
        });

        return data;
    }

    $('#tracepilot-settings-form').on('submit', function(event) {
        event.preventDefault();
        const feedback = $('#tracepilot-settings-feedback');
        feedback.text('Saving...');

        request({
            action: 'tracepilot_save_settings',
            replace_mode: 1,
            wpal_options: collectOptions(this)
        }).done(function(response) {
            feedback.text(response.success ? response.data.message : 'Unable to save settings.');
        });
    });

    $('#tracepilot-reset-settings').on('click', function() {
        if (!window.confirm(tracepilot_admin_vars.confirm_reset_settings)) {
            return;
        }

        request({ action: 'tracepilot_reset_settings' }).done(function(response) {
            if (response.success) {
                window.location.reload();
            }
        });
    });

    $(document).on('click', '.tracepilot-export-user-logs-trigger', function() {
        const userId = $(this).closest('.tracepilot-inline-actions').find('.tracepilot-privacy-user-id-input').val();
        if (!userId) {
            window.alert(tracepilot_admin_vars.enter_user_id);
            return;
        }
        const url = `${tracepilot_admin_vars.ajax_url}?action=tracepilot_export_user_logs&nonce=${encodeURIComponent(tracepilot_admin_vars.nonce)}&user_id=${encodeURIComponent(userId)}`;
        window.location.href = url;
    });

    $(document).on('click', '.tracepilot-delete-user-logs-trigger', function() {
        const userId = $(this).closest('.tracepilot-inline-actions').find('.tracepilot-privacy-user-id-input').val();
        if (!userId || !window.confirm(tracepilot_admin_vars.confirm_delete_user_logs)) {
            return;
        }
        request({ action: 'tracepilot_delete_user_logs', user_id: userId }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $('#tracepilot-run-diagnostics').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        button.prop('disabled', true).text(tracepilot_admin_vars.running_scan);

        request({ action: 'tracepilot_run_diagnostics' }).done(function(response) {
            if (response.success) {
                window.location.reload();
                return;
            }

            window.alert(response.data && response.data.message ? response.data.message : tracepilot_admin_vars.scan_failed);
            button.prop('disabled', false).text(originalText);
        }).fail(function() {
            window.alert('Unable to run the diagnostics scan.');
            button.prop('disabled', false).text(originalText);
        });
    });

    function getSafeModeSelection() {
        return ($('#tracepilot-safe-mode-plugins').val() || []).filter(Boolean);
    }

    function setSafeModeSelection(plugins) {
        $('#tracepilot-safe-mode-plugins').val((plugins || []).filter(Boolean)).trigger('change');
    }

    function enableSafeMode(trigger) {
        const button = $(trigger);
        const originalText = button.text();
        button.prop('disabled', true).text('Enabling...');

        request({
            action: 'tracepilot_enable_safe_mode',
            plugins: getSafeModeSelection()
        }).done(function(response) {
            if (response.success) {
                window.location.reload();
                return;
            }

            window.alert(response.data && response.data.message ? response.data.message : 'Unable to enable safe mode.');
            button.prop('disabled', false).text(originalText);
        }).fail(function() {
            window.alert('Unable to enable safe mode.');
            button.prop('disabled', false).text(originalText);
        });
    }

    function disableSafeMode(trigger) {
        const button = $(trigger);
        const originalText = button.text();
        button.prop('disabled', true).text('Disabling...');

        request({ action: 'tracepilot_disable_safe_mode' }).done(function(response) {
            if (response.success) {
                window.location.reload();
                return;
            }

            window.alert(response.data && response.data.message ? response.data.message : 'Unable to disable safe mode.');
            button.prop('disabled', false).text(originalText);
        }).fail(function() {
            window.alert('Unable to disable safe mode.');
            button.prop('disabled', false).text(originalText);
        });
    }

    $('#tracepilot-enable-safe-mode, #tracepilot-enable-safe-mode-inline').on('click', function() {
        enableSafeMode(this);
    });

    $(document).on('click', '.tracepilot-safe-mode-preset', function() {
        let plugins = [];
        try {
            plugins = JSON.parse($(this).attr('data-plugins') || '[]');
        } catch (error) {
            plugins = [];
        }

        setSafeModeSelection(plugins);
        enableSafeMode(this);
    });

    $('#tracepilot-disable-safe-mode, #tracepilot-disable-safe-mode-inline').on('click', function() {
        disableSafeMode(this);
    });

    $('#tracepilot-ask-diagnostics-ai').on('click', function() {
        const question = $('#tracepilot-diagnostics-question').val().trim();
        const answerBox = $('#tracepilot-diagnostics-answer');
        const button = $(this);

        if (!question) {
            answerBox.text('Ask a question first.');
            return;
        }

        const originalText = button.text();
        button.prop('disabled', true).text('Thinking...');
        answerBox.text('Checking the latest scan context...');

        request({
            action: 'tracepilot_ask_diagnostics_ai',
            question: question
        }).done(function(response) {
            if (response.success && response.data && response.data.answer) {
                answerBox.text(response.data.answer);
            } else {
                answerBox.text('No answer was available for that question.');
            }
            button.prop('disabled', false).text(originalText);
        }).fail(function() {
            answerBox.text('Unable to get an answer right now.');
            button.prop('disabled', false).text(originalText);
        });
    });

    $(document).ready(function() {
        initDatepickers();
        initTables();
        initPageCharts();
        initNoticeTray();
        initTabs();
    });
})(jQuery);
