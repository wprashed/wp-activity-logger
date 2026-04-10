# TracePilot for WordPress

TracePilot for WordPress is a modern WordPress activity log, diagnostics, and threat-review plugin built for administrators who need visibility, traceability, and safer debugging tools inside wp-admin.

## Live Demo

[Try Live Demo (No Setup Required)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wprashed/tracepilot-for-wordpress/refs/heads/main/blueprint.json)

## Overview

The plugin combines several admin-focused workflows in one place:

- Activity logging for user and system events
- Searchable audit trails with filters and exports
- Diagnostics and conflict detection with safe mode debugging
- Threat detection, file integrity checks, and vulnerability intelligence settings
- Privacy tools for user log export/delete requests

## Features (with icons)

- 🧾 **Activity audit log**: Records key user and system events including logins, settings changes, content edits, and more.
- ✍️ **Content change tracking**: Logs post/page updates, publish/unpublish, trash/restore, and deletion signals with helpful context.
- 🧩 **Plugin and theme lifecycle**: Tracks activation/deactivation plus update/install/delete signals for plugins and themes.
- 🔎 **Search and filters**: Find events by search text, severity, role, action key, date range, and site context (multisite).
- 📊 **Admin dashboard**: A clean summary view with quick insight into recent activity.
- 🧰 **System scanner**: Runs checks across server and WordPress signals and returns a health score with issue severity.
- 🧪 **Conflict detection signals**: Detects potential hook collisions and builds a binary conflict isolation plan.
- 🛡️ **Safe mode debugging**: Disable selected plugins only for your admin session so visitors never see the experiment.
- 🔔 **Real-time alerts**: Send alerts to Email, generic webhooks, Slack, Discord, and Telegram.
- 🧩 **Threat review workflow**: Surface suspicious patterns like failed logins and file-integrity signals for admin review.
- 🧬 **File integrity**: Create a baseline and scan core/plugin/theme files for new, deleted, or modified changes.
- 🧠 **Vulnerability intelligence**: Configure Wordfence, Patchstack, and WPScan lookups for installed plugins/themes/core.
- 📤 **Exports**: Download logs as CSV, JSON, XML, or a plain-text report for incident review.
- 🧹 **Retention and suppression**: Exclude noisy actions, suppress severities, and apply per-action retention rules.
- 🔐 **Privacy and GDPR guardrails**: IP anonymization/UI masking, context redaction keys, and per-user export/delete tools.
- 🌐 **Multisite support**: Aggregate logs across sites in network admin and filter by site/blog ID.

## Quick Start (choose your path)

- 🧾 **I need an audit trail**: Go to `TracePilot -> Activity Logs`, filter by `Action`/`Severity`, then export to CSV.
- 🧪 **A page is broken**: Go to `TracePilot -> Diagnostics`, run a scan, then use `Safe Mode` to isolate plugin conflicts privately.
- 🛡️ **I suspect suspicious activity**: Enable alerts, review threat rules, and build a file integrity baseline.
- 🔐 **I got a privacy request**: Use per-user export/delete tools and enable IP anonymization and context redaction keys.

## What gets logged (examples)

<details>
  <summary>✍️ Content events</summary>

  - Post/page publish, update, unpublish
  - Trash, restore, delete
</details>

<details>
  <summary>🔐 Authentication events</summary>

  - Login, logout
  - Failed login attempts
</details>

<details>
  <summary>🧩 Plugin and theme events</summary>

  - Activation and deactivation
  - Updates
  - Install/delete signals when WordPress reports them via the upgrader
</details>

<details>
  <summary>⚙️ Configuration signals</summary>

  - Settings/options changes that often explain “what changed?” regressions
</details>

## Highlights

### Activity logging

- Tracks user and system actions
- Stores severity, IP, role, object, and context data
- Provides a modern log stream and detailed modal view
- Supports multisite-aware retrieval on supported screens

### Diagnostics and conflict detection

- Runs a system scan and assigns a health score
- Explains technical issues in plain language
- Builds issue history and change correlation
- Includes admin-session safe mode for conflict testing

### Security workflow

- Threat detection rules for suspicious behavior
- File integrity baseline and comparison tools
- Vulnerability intelligence settings for Wordfence, Patchstack, and WPScan
- Alert routing for Email, generic webhooks, Slack, Discord, and Telegram

### Privacy and compliance

- IP anonymization
- Context redaction keys
- Retention controls
- Per-user export and delete tools

## Included admin areas

- Dashboard
- Activity Logs
- Analytics
- Threat Detection
- Server Recommendations
- Diagnostics
- Search Console
- Archive
- Export
- Settings

## Installation

1. Upload the plugin to `wp-content/plugins/tracepilot-for-wordpress`.
2. Activate it from the WordPress `Plugins` screen.
3. Open `TracePilot` from the admin menu.
4. Configure privacy, notifications, diagnostics, and threat detection settings to match your site.

## Common playbooks

<details>
  <summary>🧪 Conflict isolation in under 10 minutes</summary>

  1. Open `TracePilot -> Diagnostics` and run a scan.
  2. Review “hook collision” signals (if present) and the suggested plugin set.
  3. Start `Safe Mode` for your admin session only.
  4. Disable half the suspected plugins, retest the failing page, then switch halves.
</details>

<details>
  <summary>🔔 Set up real alerts (Slack/Discord/Telegram)</summary>

  1. Go to `TracePilot -> Settings -> Notifications`.
  2. Enable notifications.
  3. Add your Slack/Discord webhook or Telegram bot token + chat ID.
  4. Choose which severities/events should alert.
</details>

<details>
  <summary>🔐 Privacy-friendly logging (GDPR-style defaults)</summary>

  1. Enable GDPR guardrails.
  2. Turn on IP anonymization and UI masking.
  3. Add context redaction keys (tokens/emails/etc).
  4. Use per-user export/delete tools for privacy requests.
</details>

## Documentation map

- [Installation guide](docs/installation.md)
- [User guide](docs/user-guide.md)
- [FAQ](docs/faq.md)
- [Developer guide](docs/developer-guide.md)

## WordPress standards pass

This repository has been tightened toward WordPress plugin standards:

- admin inputs are sanitized before save
- key AJAX requests use nonce checks and capability checks
- major admin outputs are escaped
- user-facing strings are wrapped for translation
- metadata and readme files are aligned for WordPress distribution

## Developer example

```php
TracePilot_Helpers::init();

TracePilot_Helpers::log_activity(
    'custom_action',
    __('Custom action recorded from another plugin.', 'wp-activity-logger-pro'),
    'info',
    array(
        'object_type' => 'integration',
        'object_name' => 'Example integration',
    )
);
```

## Author

- Author: Rashed Hossain
- Website: [https://rashed.im/](https://rashed.im/)
- WordPress.org: [wprashed](https://profiles.wordpress.org/wprashed/)

## Version

Current documented release: `1.3.3`
