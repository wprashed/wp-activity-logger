# TracePilot for WordPress - Installation Guide

This guide will walk you through the process of installing and setting up TracePilot for WordPress on your WordPress site.

## System Requirements

Before installing, ensure your system meets the following requirements:

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- PHP extensions: json, mysqli
- WordPress user with administrator privileges

## Installation Methods

### Method 1: Install via WordPress Admin (Recommended)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Click the **Upload Plugin** button at the top of the page
4. Click **Choose File** and select the `tracepilot-for-wordpress.zip` file
5. Click **Install Now**
6. After installation completes, click **Activate Plugin**

### Method 2: Install via FTP

1. Extract the `tracepilot-for-wordpress.zip` file on your computer
2. Connect to your website using an FTP client
3. Navigate to the `/wp-content/plugins/` directory
4. Upload the `tracepilot-for-wordpress` folder to this directory
5. Log in to your WordPress admin dashboard
6. Navigate to **Plugins**
7. Find "TracePilot for WordPress" and click **Activate**

## Post-Installation Setup

After activating the plugin, follow these steps to complete the setup:

### 1. Database Tables

The plugin will automatically create the necessary database tables upon activation. If you encounter any issues, you can manually trigger table creation:

1. Navigate to **TracePilot > Settings**
2. Click the **Diagnostics** tab
3. Click the **Repair Tables** button

### 2. Configure Basic Settings

1. Navigate to **TracePilot > Settings**
2. Set your preferred log retention period (default is 30 days)
3. Select which user roles to track
4. Choose which events to log
5. Click **Save Changes**

### 3. Set Up Notifications (Optional)

If you want to receive notifications for certain events:

1. Navigate to **TracePilot > Notifications**
2. Enable email notifications
3. Add recipient email addresses
4. Select which events should trigger notifications
5. Click **Save Changes**

### 4. Configure Access Control (Optional)

To control which users can access the logs:

1. Navigate to **TracePilot > Settings**
2. Click the **Access Control** tab
3. Select which user roles can view, manage, and export logs
4. Click **Save Changes**

## Verifying Installation

To verify that the plugin is working correctly:

1. Navigate to **TracePilot > Dashboard**
2. You should see recent activity, including your own login and the plugin activation
3. Navigate to **TracePilot > Logs** to view detailed activity logs

## Troubleshooting

### Common Installation Issues

#### Database Table Creation Failed

If the plugin fails to create database tables:

1. Check that your database user has sufficient privileges
2. Try deactivating and reactivating the plugin
3. Check the WordPress debug log for specific error messages

#### Plugin Menu Not Appearing

If the TracePilot menu doesn't appear:

1. Clear your browser cache
2. Log out and log back in to WordPress
3. Check that your user has administrator privileges

#### Logs Not Being Recorded

If activities are not being logged:

1. Navigate to **TracePilot > Settings**
2. Ensure that the relevant event types are enabled
3. Check the WordPress debug log for errors
4. Verify that the database tables exist and are properly structured

## Upgrading

When upgrading from a previous version:

1. Back up your WordPress database
2. Deactivate the current version of the plugin
3. Follow the installation steps above
4. The plugin will automatically update database tables if necessary

## Uninstallation

If you need to uninstall the plugin:

1. Navigate to **Plugins**
2. Deactivate "TracePilot for WordPress"
3. Click **Delete**

Note: By default, the plugin will retain its database tables and settings when deleted. To completely remove all data:

1. Navigate to **TracePilot > Settings**
2. Click the **Advanced** tab
3. Check "Delete all data when plugin is uninstalled"
4. Click **Save Changes**
5. Then proceed with the uninstallation

## Getting Help

If you encounter any issues during installation:

- Review our [Frequently Asked Questions](faq.md)

## Next Steps

Now that you've installed TracePilot for WordPress, you might want to:

- [Configure detailed settings](user-guide.md#settings)
- [Set up custom event tracking](developer-guide.md#logging-custom-events)
- [Create scheduled exports](user-guide.md#scheduled-exports)
