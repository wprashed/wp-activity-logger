# External Services & Data Flow

TracePilot for WordPress supports optional integrations with external services.  
No third-party service is required for basic activity logging.

## Privacy-first defaults

- Geolocation enrichment is **disabled by default**.
- Alert delivery is **disabled by default** until channels are configured.
- Vulnerability lookups happen only when scans are run and providers are enabled.

## Services used

### 1. Telegram Bot API

- **Service:** Telegram Bot API (`api.telegram.org`)
- **Why it is used:** Deliver alert messages to Telegram channels/chats.
- **Data sent:** Alert text payloads and event metadata included in the message body.
- **When sent:** Only if Telegram token + chat ID are configured and notifications are enabled.
- **Terms:** <https://telegram.org/tos>
- **Privacy:** <https://telegram.org/privacy>

### 2. Wordfence Vulnerability Intelligence API

- **Service:** Wordfence (`www.wordfence.com`)
- **Why it is used:** Compare installed software versions against known vulnerability records.
- **Data sent:** Request metadata and API authorization header (if API key is configured).
- **When sent:** During vulnerability scans when Wordfence provider is enabled.
- **Terms:** <https://www.wordfence.com/terms-of-service/>
- **Privacy:** <https://www.wordfence.com/privacy-policy/>

### 3. Patchstack Vulnerability Database API

- **Service:** Patchstack (`patchstack.com`)
- **Why it is used:** Enrich vulnerability checks for plugins/themes/core.
- **Data sent:** Request metadata and optional API key header (if configured).
- **When sent:** During vulnerability scans when Patchstack provider is enabled.
- **Terms:** <https://patchstack.com/terms-of-service/>
- **Privacy:** <https://patchstack.com/privacy-policy/>

### 4. WPScan API

- **Service:** WPScan (`wpscan.com`)
- **Why it is used:** Fetch vulnerability records by plugin/theme slug and core version.
- **Data sent:** API token (if configured) and requested software identifiers.
- **When sent:** During vulnerability scans when WPScan provider is enabled.
- **Terms:** <https://wpscan.com/terms-of-service/>
- **Privacy:** <https://wpscan.com/privacy-policy/>

### 5. ip-api geolocation service

- **Service:** ip-api (`ip-api.com`)
- **Why it is used:** Optional IP geolocation enrichment for diagnostics and security context.
- **Data sent:** The IP address being enriched.
- **When sent:** Only when geolocation is enabled in plugin settings.
- **Terms/Privacy:** <https://ip-api.com/docs/legal>

### 6. Google Search Console API

- **Service:** Google APIs (`googleapis.com`)
- **Why it is used:** Fetch search performance metrics in the Search Console module.
- **Data sent:** OAuth credentials/tokens, selected site URL, date range, and dimensions.
- **When sent:** Only after administrator authorization and explicit data fetch actions.
- **Terms:** <https://policies.google.com/terms>
- **Privacy:** <https://policies.google.com/privacy>
- **User data policy:** <https://developers.google.com/terms/api-services-user-data-policy>

## How to disable all external requests

1. Disable notifications and clear webhook/Telegram fields in `Settings > Notifications`.
2. Disable vulnerability scanning providers in `Settings > Security`.
3. Keep geolocation disabled in `Settings > Privacy`.
4. Disconnect Google Search Console in `Search Console`.
