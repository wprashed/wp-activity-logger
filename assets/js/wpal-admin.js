/**
 * WP Activity Logger Pro Admin JavaScript.
 */
(function($) {
    'use strict';

    function request(data) {
        return $.ajax({
            url: wpal_admin_vars.ajax_url,
            type: 'POST',
            data: Object.assign({ nonce: wpal_admin_vars.nonce }, data)
        });
    }

    function modal() {
        return $('#wpal-log-details-modal');
    }

    window.wpalRenderLineChart = function(canvasId, labels, values) {
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

    window.wpalRenderDoughnutChart = function(canvasId, labels, values) {
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
        if (window.wpalDashboardTrend) {
            window.wpalRenderLineChart('wpal-dashboard-trend', window.wpalDashboardTrend.labels, window.wpalDashboardTrend.values);
        }

        if (window.wpalLogsSeverity) {
            window.wpalRenderDoughnutChart('wpal-logs-severity', window.wpalLogsSeverity.labels, window.wpalLogsSeverity.values);
        }
    }

    function initTables() {
        if ($.fn.dataTable && $('#wpal-logs-table.wpal-data-table').length && !$.fn.dataTable.isDataTable('#wpal-logs-table')) {
            $('#wpal-logs-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
        }
    }

    function initDatepickers() {
        if ($.fn.datepicker) {
            $('.wpal-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });
        }
    }

    function initNoticeTray() {
        const wrap = $('.wpal-wrap').first();
        if (!wrap.length) {
            return;
        }

        let tray = $('.wpal-notice-stack').first();
        if (!tray.length) {
            tray = $('<div class="wpal-notice-stack" />');
            wrap.before(tray);
        }

        const selectors = [
            '#wpbody-content > .notice',
            '#wpbody-content > .error',
            '#wpbody-content > .updated',
            '#wpbody-content > .update-nag',
            '.wpal-wrap .notice',
            '.wpal-wrap .error',
            '.wpal-wrap .updated',
            '.wpal-wrap .update-nag'
        ];

        const seen = new window.Set();
        $(selectors.join(',')).each(function() {
            const notice = $(this);
            if (!notice.length || notice.closest('.wpal-notice-stack').length) {
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
            notice.addClass('wpal-notice-card');
            tray.append(notice);
        });

        if (!tray.children().length) {
            tray.remove();
        }
    }

    $(document).on('click', '.wpal-view-log', function() {
        request({
            action: 'wpal_get_log_details',
            log_id: $(this).data('log-id'),
            site_id: $(this).data('site-id') || 0
        }).done(function(html) {
            modal().find('.wpal-modal-body').html(html);
            modal().addClass('is-open');
        });
    });

    $(document).on('click', '.wpal-modal-close', function() {
        modal().removeClass('is-open');
    });

    $(document).on('click', '.wpal-modal', function(event) {
        if ($(event.target).is('.wpal-modal')) {
            modal().removeClass('is-open');
        }
    });

    $(document).on('click', '.wpal-delete-log', function() {
        const button = $(this);
        if (!window.confirm(wpal_admin_vars.confirm_delete)) {
            return;
        }

        request({
            action: 'wpal_delete_log',
            log_id: button.data('log-id'),
            site_id: button.data('site-id') || 0
        }).done(function(response) {
            if (response.success) {
                const row = button.closest('tr');
                if ($.fn.dataTable && $.fn.dataTable.isDataTable('#wpal-logs-table')) {
                    $('#wpal-logs-table').DataTable().row(row).remove().draw();
                } else {
                    row.remove();
                }
            } else if (response.data && response.data.message) {
                window.alert(response.data.message);
            }
        });
    });

    $('#wpal-delete-all-logs').on('click', function() {
        if (!window.confirm(wpal_admin_vars.confirm_delete_all)) {
            return;
        }

        request({ action: 'wpal_delete_all_logs' }).done(function(response) {
            if (response.success) {
                window.location.reload();
            }
        });
    });

    $(document).on('click', '.wpal-archive-log', function() {
        const button = $(this);
        request({
            action: 'wpal_archive_log',
            log_id: button.data('log-id'),
            site_id: button.data('site-id') || 0
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

    $(document).on('click', '.wpal-restore-log', function() {
        const button = $(this);
        request({
            action: 'wpal_restore_log',
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

    $(document).on('click', '.wpal-delete-archived-log', function() {
        const button = $(this);
        request({
            action: 'wpal_delete_archived_log',
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

    $(document).on('click', '.wpal-block-ip', function() {
        const ip = $(this).data('ip');
        if (!ip) {
            return;
        }
        request({ action: 'wpal_block_ip', ip: ip }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.wpal-force-logout', function() {
        const userId = $(this).data('user-id');
        if (!userId) {
            return;
        }
        request({ action: 'wpal_force_logout_user', user_id: userId }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.wpal-reset-password', function() {
        const userId = $(this).data('user-id');
        if (!userId) {
            return;
        }
        request({ action: 'wpal_reset_user_password', user_id: userId }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.wpal-delete-user-logs', function() {
        const userId = $(this).data('user-id');
        if (!userId || !window.confirm('Delete all logs for this user?')) {
            return;
        }
        request({ action: 'wpal_delete_user_logs', user_id: userId }).done(function(response) {
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

    $('#wpal-settings-form').on('submit', function(event) {
        event.preventDefault();
        const feedback = $('#wpal-settings-feedback');
        feedback.text('Saving...');

        request({
            action: 'wpal_save_settings',
            replace_mode: 1,
            wpal_options: collectOptions(this)
        }).done(function(response) {
            feedback.text(response.success ? response.data.message : 'Unable to save settings.');
        });
    });

    $('#wpal-reset-settings').on('click', function() {
        if (!window.confirm('Reset all settings to defaults?')) {
            return;
        }

        request({ action: 'wpal_reset_settings' }).done(function(response) {
            if (response.success) {
                window.location.reload();
            }
        });
    });

    $('#wpal-export-user-logs').on('click', function() {
        const userId = $('#wpal-privacy-user-id').val();
        if (!userId) {
            window.alert('Enter a user ID first.');
            return;
        }
        const url = `${wpal_admin_vars.ajax_url}?action=wpal_export_user_logs&nonce=${encodeURIComponent(wpal_admin_vars.nonce)}&user_id=${encodeURIComponent(userId)}`;
        window.location.href = url;
    });

    $('#wpal-delete-user-logs-btn').on('click', function() {
        const userId = $('#wpal-privacy-user-id').val();
        if (!userId || !window.confirm('Delete all logs for this user?')) {
            return;
        }
        request({ action: 'wpal_delete_user_logs', user_id: userId }).done(function(response) {
            if (response.success) {
                window.alert(response.data.message);
            }
        });
    });

    $('#wpal-run-diagnostics').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        button.prop('disabled', true).text('Running scan...');

        request({ action: 'wpal_run_diagnostics' }).done(function(response) {
            if (response.success) {
                window.location.reload();
                return;
            }

            window.alert(response.data && response.data.message ? response.data.message : 'Unable to run the diagnostics scan.');
            button.prop('disabled', false).text(originalText);
        }).fail(function() {
            window.alert('Unable to run the diagnostics scan.');
            button.prop('disabled', false).text(originalText);
        });
    });

    function getSafeModeSelection() {
        return ($('#wpal-safe-mode-plugins').val() || []).filter(Boolean);
    }

    function setSafeModeSelection(plugins) {
        $('#wpal-safe-mode-plugins').val((plugins || []).filter(Boolean)).trigger('change');
    }

    function enableSafeMode(trigger) {
        const button = $(trigger);
        const originalText = button.text();
        button.prop('disabled', true).text('Enabling...');

        request({
            action: 'wpal_enable_safe_mode',
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

        request({ action: 'wpal_disable_safe_mode' }).done(function(response) {
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

    $('#wpal-enable-safe-mode, #wpal-enable-safe-mode-inline').on('click', function() {
        enableSafeMode(this);
    });

    $(document).on('click', '.wpal-safe-mode-preset', function() {
        let plugins = [];
        try {
            plugins = JSON.parse($(this).attr('data-plugins') || '[]');
        } catch (error) {
            plugins = [];
        }

        setSafeModeSelection(plugins);
        enableSafeMode(this);
    });

    $('#wpal-disable-safe-mode, #wpal-disable-safe-mode-inline').on('click', function() {
        disableSafeMode(this);
    });

    $('#wpal-ask-diagnostics-ai').on('click', function() {
        const question = $('#wpal-diagnostics-question').val().trim();
        const answerBox = $('#wpal-diagnostics-answer');
        const button = $(this);

        if (!question) {
            answerBox.text('Ask a question first.');
            return;
        }

        const originalText = button.text();
        button.prop('disabled', true).text('Thinking...');
        answerBox.text('Checking the latest scan context...');

        request({
            action: 'wpal_ask_diagnostics_ai',
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
    });
})(jQuery);
