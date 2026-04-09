=== TracePilot for WordPress ===
Contributors: wprashed
Tags: activity log, audit log, security, diagnostics, monitoring, logging
Requires at least: 6.0
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track WordPress activity, investigate conflicts, review security signals, and export audit-ready logs from one modern admin dashboard.

== Description ==

TracePilot for WordPress helps site owners, agencies, and administrators understand what is happening inside WordPress.

The plugin records user and system activity, highlights suspicious patterns, offers diagnostics for common site issues, and provides a clear export workflow for compliance or troubleshooting.

= Core features =

* Activity logging for logins, settings changes, content updates, plugin and theme actions, and other tracked events.
* Modern dashboard with charts, summaries, and recent event visibility.
* Searchable log stream with filters for severity, role, action, date range, and multisite context.
* Log detail view with timeline context and response actions.
* Threat detection tools for suspicious logins, file changes, and privilege-related events.
* Diagnostics scanner with issue explanations, safe mode debugging, timeline, and change correlation.
* Software vulnerability intelligence settings for Wordfence, Patchstack, and WPScan integrations.
* File integrity baseline and scan tools.
* Export tools for CSV, JSON, XML, and plain-text report output.
* Privacy-oriented controls for IP anonymization, context redaction, and user log export/delete helpers.

= Built for administrators =

TracePilot for WordPress is designed for:

* site owners who need an audit trail
* agencies managing client sites
* support engineers investigating regressions
* administrators reviewing plugin conflicts and security signals
* teams handling compliance requests and log exports

= Developer-friendly foundation =

The plugin follows WordPress patterns for escaping, sanitization, AJAX nonce checks, and translatable strings. It also includes a helper API for custom activity entries.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it from the WordPress admin plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `TracePilot` in the admin menu.
4. Review the `Settings`, `Diagnostics`, and `Threat Detection` pages to configure the plugin for your workflow.

== Frequently Asked Questions ==

= What does the plugin log? =

It logs tracked user and system events such as authentication activity, settings changes, content updates, and selected plugin or theme operations.

= Can I filter the logs? =

Yes. The log stream supports filtering by text search, role, action, severity, date range, and site context on multisite installs.

= Does the plugin support multisite? =

Yes. The plugin includes multisite-aware log retrieval and site filters for supported admin views.

= Can I export data for privacy or compliance requests? =

Yes. You can export filtered logs or export/delete log history for a specific user from the settings tools.

= Does it support diagnostics and conflict testing? =

Yes. The diagnostics area includes system checks, issue explanations, change correlation, and admin-session safe mode for plugin conflict testing.

= Does the plugin include vulnerability scanning? =

It includes configuration for software vulnerability intelligence sources and combines that data with file-integrity checks when configured.

== Screenshots ==

1. Dashboard with activity summaries and charts.
2. Log stream with filters and event cards.
3. Detailed log modal with timeline context.
4. Diagnostics scanner with issue explanations and safe mode tools.
5. Threat Detection and vulnerability intelligence controls.
6. Export screen with filterable report generation.

== Changelog ==

= 1.3.1 =
* Rebranded plugin identity to TracePilot for WordPress in plugin metadata, docs, and admin menu labels.
* Updated admin menu icon to a security-focused shield icon.
* Refined naming language in key settings and export messages.

= 1.3.0 =
* Added WordPress.org-ready readme content and full repository documentation.
* Improved standards coverage for sanitization, escaping, and translatable strings in key admin flows.
* Added stricter Search Console option sanitization and export request handling.
* Refined logs and diagnostics UI behavior, including improved filters and control consistency.

= 1.2.9 =
* Added log stream action filters and refreshed checkbox styling.
* Improved diagnostics layout and card behavior.

= 1.2.8 =
* Reorganized settings into multiple tabs.
* Converted diagnostics into sub-tabs.

== Upgrade Notice ==

= 1.3.1 =
This release introduces TracePilot branding updates and admin menu polish.
