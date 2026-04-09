/**
* WP Activity Logger Pro - Real-time Push Notifications
*/
(function($) {
    'use strict';
    
    // Check if push notifications are enabled
    const pushEnabled = TracePilot_PUSH && TracePilot_PUSH.enabled === '1';
    
    if (!pushEnabled) {
        return;
    }
    
    // Variables
    let socket = null;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const reconnectDelay = 5000; // 5 seconds
    
    // Initialize on document ready
    $(document).ready(function() {
        if (isAdminPage()) {
            initPushNotifications();
        }
    });
    
    // Initialize push notifications
    function initPushNotifications() {
        // Check if the browser supports WebSockets
        if (!window.WebSocket) {
            console.error('WebSockets are not supported in this browser.');
            return;
        }
        
        // Connect to WebSocket server
        connectWebSocket();
        
        // Setup notification permission
        setupNotificationPermission();
    }
    
    // Connect to WebSocket server
    function connectWebSocket() {
        // Use secure WebSocket if the site is using HTTPS
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.host;
        const wsUrl = `${protocol}//${host}/wp-json/wpal/v1/push`;
        
        try {
            socket = new WebSocket(wsUrl);
            
            socket.onopen = function() {
                console.log('WebSocket connection established');
                reconnectAttempts = 0;
                
                // Send authentication
                socket.send(JSON.stringify({
                    action: 'authenticate',
                    nonce: TracePilot_PUSH.nonce
                }));
            };
            
            socket.onmessage = function(event) {
                handlePushMessage(event.data);
            };
            
            socket.onclose = function() {
                console.log('WebSocket connection closed');
                
                // Attempt to reconnect
                if (reconnectAttempts < maxReconnectAttempts) {
                    reconnectAttempts++;
                    setTimeout(connectWebSocket, reconnectDelay);
                }
            };
            
            socket.onerror = function(error) {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.error('Error connecting to WebSocket:', error);
        }
    }
    
    // Handle push message
    function handlePushMessage(data) {
        try {
            const message = JSON.parse(data);
            
            if (message.type === 'log') {
                // Show browser notification
                showNotification(message.data);
                
                // Update live feed if on dashboard page
                if ($('#tracepilot-live-feed').length) {
                    prependToLiveFeed(message.data);
                }
            }
        } catch (error) {
            console.error('Error parsing push message:', error);
        }
    }
    
    // Show browser notification
    function showNotification(log) {
        if (!('Notification' in window)) {
            return;
        }
        
        if (Notification.permission === 'granted') {
            const title = 'Activity Log: ' + log.action;
            const options = {
                body: 'User: ' + log.username + '\nIP: ' + log.ip,
                icon: TracePilot_PUSH.icon || '/wp-content/plugins/wp-activity-logger-pro/assets/img/notification-icon.png',
                tag: 'tracepilot-notification'
            };
            
            const notification = new Notification(title, options);
            
            notification.onclick = function() {
                window.focus();
                notification.close();
                
                // Navigate to logs page if not already there
                if (!window.location.href.includes('page=wp-activity-logger-pro-logs')) {
                    window.location.href = TracePilot_PUSH.logs_url;
                }
            };
        }
    }
    
    // Prepend to live feed
    function prependToLiveFeed(log) {
        const severityClass = getSeverityClass(log.severity);
        
        let html = '<div class="live-feed-item mb-2 p-2 border-bottom" style="display:none;">';
        html += '<div class="d-flex justify-content-between">';
        html += '<span class="fw-bold">' + escapeHtml(log.username) + '</span>';
        html += '<span class="text-muted small">' + formatDateTime(log.time) + '</span>';
        html += '</div>';
        html += '<div>' + escapeHtml(log.action) + '</div>';
        html += '<div class="d-flex justify-content-between align-items-center mt-1">';
        html += '<span class="badge ' + severityClass + '">' + (log.severity || 'info').toUpperCase() + '</span>';
        html += '<span class="text-muted small">' + escapeHtml(log.ip) + '</span>';
        html += '</div>';
        html += '</div>';
        
        const $newItem = $(html).prependTo('#tracepilot-live-feed');
        $newItem.slideDown();
        
        // Remove oldest item if there are more than 10
        const $items = $('#tracepilot-live-feed .live-feed-item');
        if ($items.length > 10) {
            $items.last().slideUp(function() {
                $(this).remove();
            });
        }
    }
    
    // Setup notification permission
    function setupNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('This browser does not support desktop notifications');
            return;
        }
        
        if (Notification.permission === 'default') {
            // Only request permission if the user is on the settings page
            if (window.location.href.includes('page=wp-activity-logger-pro-settings')) {
                Notification.requestPermission();
            }
        }
    }
    
    // Helper function to check if on admin page
    function isAdminPage() {
        return typeof window.wp !== 'undefined' && typeof window.wp.blocks === 'undefined';
    }
    
    // Helper function to get severity class
    function getSeverityClass(severity) {
        switch (severity) {
            case 'error':
                return 'bg-danger';
            case 'warning':
                return 'bg-warning text-dark';
            case 'info':
            default:
                return 'bg-success';
        }
    }
    
    // Helper function to format date and time
    function formatDateTime(dateTimeStr) {
        const date = new Date(dateTimeStr);
        return date.toLocaleString();
    }
    
    // Helper function to escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        
        return str
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
 })(jQuery);