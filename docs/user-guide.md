# WP Activity Logger Pro - User Guide

## Introduction

Welcome to the WP Activity Logger Pro user guide. This document will help you understand how to use the plugin effectively to monitor and track activities on your WordPress site.

## Getting Started

After activating the plugin, you'll find a new menu item "Activity Logger" in your WordPress admin menu. This is your gateway to all the plugin's features.

## Dashboard

The Dashboard provides an overview of recent activities on your site. Here you'll find:

- **Activity Summary**: Quick stats on total activities, users, and severity levels
- **Recent Activities**: The most recent actions logged on your site
- **Activity Trends**: Charts showing activity patterns over time
- **Top Users**: Users with the most activity
- **Common Actions**: Most frequent actions performed

You can refresh individual widgets using the refresh icon in the top-right corner of each widget.

## Activity Logs

The Activity Logs page displays a comprehensive list of all logged activities. Features include:

### Viewing Logs

- **Sorting**: Click on column headers to sort by that column
- **Searching**: Use the search box to find specific logs
- **Pagination**: Navigate through multiple pages of logs

### Filtering Logs

Use the filters at the top of the page to narrow down logs by:

- Date range
- User
- Action type
- Severity level
- IP address

### Log Details

Click the "View" button (eye icon) on any log entry to see detailed information, including:

- Complete user information
- Detailed action description
- Context data (additional information specific to the action)
- Browser and device information
- IP address information

### Managing Logs

- **Delete**: Remove individual log entries using the delete button
- **Delete All**: Clear all logs (use with caution)

## Export

The Export page allows you to download logs for offline analysis or record-keeping.

### Export Options

- **Format**: Choose from CSV, JSON, or PDF
- **Date Range**: Select the time period for the logs
- **Filters**: Apply the same filters available in the Activity Logs page
- **Fields**: Select which fields to include in the export

### Scheduled Exports

Set up automatic exports on a schedule:

1. Enable scheduled exports in the Export settings
2. Configure the frequency (daily, weekly, monthly)
3. Set the export format and filters
4. Provide an email address to receive the exports

## Settings

The Settings page allows you to configure how the plugin works.

### General Settings

- **Log Retention**: How long to keep logs (days)
- **User Tracking**: Which user roles to track
- **IP Tracking**: Enable/disable IP address logging
- **User Agent Tracking**: Enable/disable browser/device logging

### Event Settings

Configure which events to log:

- **User Events**: Logins, profile updates, etc.
- **Content Events**: Post/page creation, updates, deletions
- **System Events**: Plugin/theme updates, WordPress updates
- **WooCommerce Events**: Orders, products, etc. (if WooCommerce is active)
- **Custom Events**: Enable tracking of custom events from other plugins

### Access Control

- **View Logs**: Select which user roles can view logs
- **Manage Logs**: Select which user roles can delete logs
- **Export Logs**: Select which user roles can export logs

## Notifications

The Notifications page allows you to set up alerts for specific events.

### Email Notifications

1. Enable email notifications
2. Add recipient email addresses
3. Select which events trigger notifications
4. Customize the email template

### Notification Logs

View a history of sent notifications, including:
- When the notification was sent
- The recipient(s)
- The triggering event
- Delivery status

## Troubleshooting

### Common Issues

- **Missing Logs**: Check the Event Settings to ensure the events you want to track are enabled
- **Database Size**: If your database is growing too large, reduce the log retention period
- **Performance Issues**: If you notice slowdowns, try disabling logging for frequent events

### Diagnostics

The Diagnostics page provides information about your system and the plugin's status:

- WordPress environment
- Server information
- Database status
- Plugin configuration
- Log table status

This information is valuable when seeking support.

## Conclusion

WP Activity Logger Pro provides comprehensive activity tracking for your WordPress site. By regularly reviewing the logs and setting up appropriate notifications, you can maintain better security and gain valuable insights into how your site is being used.
