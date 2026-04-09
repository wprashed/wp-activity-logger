# TracePilot for WordPress - Frequently Asked Questions

## General Questions

### What is TracePilot for WordPress?

TracePilot for WordPress is a comprehensive activity tracking solution for WordPress. It logs user and system activities, providing detailed reports, real-time notifications, and advanced filtering capabilities.

### How is this different from other logging plugins?

TracePilot for WordPress offers several advantages:
- More comprehensive event tracking
- Real-time notifications
- Advanced export options
- User-friendly interface
- IP geolocation
- Detailed context for each activity
- Developer API for custom event tracking

### Will this plugin slow down my site?

The plugin is designed to be lightweight and efficient. It uses optimized database queries and indexes to minimize performance impact. In most cases, users won't notice any slowdown.

For high-traffic sites, we recommend:
- Setting a reasonable log retention period
- Being selective about which events to track
- Using the database cleanup tools regularly

## Features and Functionality

### What activities does the plugin track?

The plugin tracks a wide range of activities, including:

**User Activities:**
- Logins and login attempts
- User registrations
- Profile updates
- Password changes
- Role changes

**Content Activities:**
- Post/page creation, updates, and deletions
- Comment activities
- Media uploads and deletions
- Category/tag changes

**System Activities:**
- Plugin installations, updates, and deletions
- Theme changes
- WordPress core updates
- Widget changes
- Menu modifications

**WooCommerce Activities (if applicable):**
- Order status changes
- Product modifications
- Coupon usage
- Settings changes

### Can I track custom events from my own plugins?

Yes, we provide a developer API that allows you to log custom events. See our [Developer Guide](developer-guide.md) for details.

### How long are logs kept?

By default, logs are kept for 30 days. You can adjust this in the Settings page to keep logs for a shorter or longer period, depending on your needs.

### Can I export the logs?

Yes, you can export logs in several formats:
- CSV (for spreadsheet analysis)
- JSON (for data processing)
- PDF (for reporting)

You can also set up scheduled exports to automatically send logs to an email address on a regular basis.

### Does the plugin support notifications?

Yes, you can configure email notifications for specific events. For example, you might want to be notified about failed login attempts, plugin updates, or user role changes.

## Technical Questions

### Is this plugin compatible with multisite?

Yes, TracePilot for WordPress is fully compatible with WordPress multisite installations. It can track activities across all sites in your network.

### Does it work with page builders?

Yes, the plugin is compatible with popular page builders like Elementor, Beaver Builder, Divi, and others. It will track content changes made through these builders.

### Can I migrate logs from another logging plugin?

Currently, we don't provide an automated migration tool. However, if you need to migrate from a specific plugin, contact our support team for assistance.

### Is the plugin GDPR compliant?

Yes, the plugin is designed with privacy in mind. It includes:
- Tools to manage and export user data
- Options to anonymize IP addresses
- Clear data retention policies
- Data export capabilities

### How does IP geolocation work?

The plugin uses a geolocation database to determine the approximate location of IP addresses. This information is displayed in the log details. No external API calls are made for this feature, ensuring privacy and performance.

## Troubleshooting

### Some activities aren't being logged. Why?

Check the following:
1. Ensure the relevant event types are enabled in Settings
2. Verify that the user role is being tracked
3. Check if any other plugins might be interfering with the logging process
4. Look for errors in the WordPress debug log

### The plugin is using too much database space. What can I do?

To reduce database usage:
1. Decrease the log retention period
2. Be more selective about which events to track
3. Use the database optimization tools in the Diagnostics page
4. Consider exporting and then clearing older logs

### I'm not receiving email notifications. How can I fix this?

If notifications aren't working:
1. Check your spam folder
2. Verify the email settings in the Notifications page
3. Test your WordPress email functionality with another plugin
4. Consider using an SMTP plugin to improve email deliverability

### How can I see who deleted a specific post?

Look for log entries with the action "post_deleted" or similar. The log will show:
- Who performed the deletion
- When it happened
- Details about the deleted post (if available)

## Licensing and Support

### Do I need to renew my license?

Yes, your license needs to be renewed annually to continue receiving updates and support. You can find your license information in the Settings page.

### How do I get support?

Support is available through:
- Our [documentation](https://example.com/docs)
- Email support at support@example.com
- Our support portal at https://example.com/support

### Can I use this plugin on multiple sites?

This depends on your license:
- Single site license: One WordPress installation
- Developer license: Up to 5 sites
- Agency license: Unlimited sites

### Is there a refund policy?

Yes, we offer a 30-day money-back guarantee. If you're not satisfied with the plugin, contact our support team within 30 days of purchase for a full refund.

## Advanced Usage

### Can I create custom reports?

While the plugin doesn't include a report builder, you can:
1. Use the export feature to get the data you need
2. Filter logs before exporting to focus on specific activities
3. Use the exported data with your preferred reporting tools

### Is there an API for retrieving log data?

Yes, developers can use our API to retrieve and analyze log data programmatically. See the [Developer Guide](developer-guide.md) for details.

### Can I customize the email notification templates?

Yes, you can customize the email templates in the Notifications settings. You can use HTML and several variables to personalize the notifications to match your brand and include relevant information about the logged event.

### Can I integrate with third-party services?

Yes, you can use webhooks to send log data to external services. This allows integration with:
- Slack for team notifications
- Custom dashboards
- Security monitoring services
- Data analytics platforms

## Future Development

### What features are planned for future releases?

We're constantly improving the plugin based on user feedback. Some planned features include:
- Advanced reporting dashboard
- More notification channels (Slack, SMS, etc.)
- Enhanced user activity tracking
- Integration with popular security plugins
- Mobile app for on-the-go monitoring

### Can I request a feature?

We welcome feature requests from our users. Please send your ideas to features@example.com or use the feature request form on our website.

### How often is the plugin updated?

We typically release:
- Minor updates (bug fixes) monthly
- Major feature updates quarterly
- Security updates as needed

All updates are announced on our website and through the WordPress admin dashboard.

## Getting Started

### What should I do after installing the plugin?

After installation, we recommend:
1. Reviewing the default settings
2. Setting up notifications for critical events
3. Exploring the dashboard to understand the available information
4. Checking the logs regularly to establish a baseline of normal activity

### Are there any tutorials available?

Yes, we provide:
- Video tutorials on our YouTube channel
- Step-by-step guides in our documentation
- Webinars for advanced usage (scheduled monthly)

Visit our website for links to all these resources.

### How can I get the most out of this plugin?

To maximize the value of TracePilot for WordPress:
1. Configure it to track the events most relevant to your site
2. Set up notifications for critical security events
3. Review logs regularly to understand normal patterns
4. Use the export feature for compliance documentation
5. Integrate with your existing security practices

If you have any other questions not covered here, please don't hesitate to contact our support team.