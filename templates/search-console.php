<?php
/**
 * Google Search Console template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$gsc = new TracePilot_Google_Search_Console();
$is_connected = $gsc->is_connected();
$sites = $is_connected ? $gsc->get_sites() : array();
$options = get_option('tracepilot_gsc_options', array());
?>

<div class="wrap tracepilot-wrap">
    <section class="tracepilot-hero tracepilot-hero-compact">
        <div>
            <p class="tracepilot-eyebrow"><?php esc_html_e('Search performance', 'wp-activity-logger-pro'); ?></p>
            <h1 class="tracepilot-page-title"><?php esc_html_e('Google Search Console', 'wp-activity-logger-pro'); ?></h1>
            <p class="tracepilot-hero-copy"><?php esc_html_e('Connect Search Console to compare search visibility with on-site activity and operational events.', 'wp-activity-logger-pro'); ?></p>
        </div>
        <?php if ($is_connected) : ?>
            <div class="tracepilot-hero-actions">
                <button id="tracepilot-gsc-disconnect" class="tracepilot-btn tracepilot-btn-danger"><?php esc_html_e('Disconnect', 'wp-activity-logger-pro'); ?></button>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!$is_connected) : ?>
        <section class="tracepilot-split">
            <article class="tracepilot-panel">
                <div class="tracepilot-panel-head">
                    <div>
                        <h2><?php esc_html_e('Connection Settings', 'wp-activity-logger-pro'); ?></h2>
                        <p><?php esc_html_e('Enter your Google OAuth client details and then authorize the plugin.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                </div>
                <form method="post" action="options.php" class="tracepilot-form-stack">
                    <?php settings_fields('tracepilot_gsc_options'); ?>
                    <label>
                        <span><?php esc_html_e('Google API Client ID', 'wp-activity-logger-pro'); ?></span>
                        <input type="text" name="tracepilot_gsc_options[client_id]" class="tracepilot-input" value="<?php echo esc_attr(isset($options['client_id']) ? $options['client_id'] : ''); ?>" required>
                    </label>
                    <label>
                        <span><?php esc_html_e('Google API Client Secret', 'wp-activity-logger-pro'); ?></span>
                        <input type="password" name="tracepilot_gsc_options[client_secret]" class="tracepilot-input" value="<?php echo esc_attr(isset($options['client_secret']) ? $options['client_secret'] : ''); ?>" required>
                    </label>
                    <div class="tracepilot-inline-actions">
                        <button type="submit" class="tracepilot-btn tracepilot-btn-primary"><?php esc_html_e('Save Credentials', 'wp-activity-logger-pro'); ?></button>
                        <?php if (!empty($options['client_id']) && !empty($options['client_secret'])) : ?>
                            <a href="<?php echo esc_url($gsc->get_auth_url()); ?>" class="tracepilot-btn tracepilot-btn-secondary"><?php esc_html_e('Connect to Google', 'wp-activity-logger-pro'); ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </article>

            <article class="tracepilot-panel">
                <div class="tracepilot-panel-head">
                    <div>
                        <h2><?php esc_html_e('Setup Checklist', 'wp-activity-logger-pro'); ?></h2>
                        <p><?php esc_html_e('Use these values inside Google Cloud Console when creating the OAuth app.', 'wp-activity-logger-pro'); ?></p>
                    </div>
                </div>
                <div class="tracepilot-stack">
                    <div class="tracepilot-note"><?php esc_html_e('Enable the Google Search Console API in your selected Google Cloud project.', 'wp-activity-logger-pro'); ?></div>
                    <div class="tracepilot-note"><?php esc_html_e('Create an OAuth client with application type set to Web application.', 'wp-activity-logger-pro'); ?></div>
                    <div>
                        <strong><?php esc_html_e('Authorized redirect URI', 'wp-activity-logger-pro'); ?></strong>
                        <div class="tracepilot-code-inline"><?php echo esc_html(admin_url('admin.php?page=wp-activity-logger-pro-search-console&oauth=callback')); ?></div>
                    </div>
                    <div class="tracepilot-note"><?php esc_html_e('After saving the client ID and secret here, click “Connect to Google” to complete authorization.', 'wp-activity-logger-pro'); ?></div>
                </div>
            </article>
        </section>
    <?php else : ?>
        <section class="tracepilot-panel">
            <div class="tracepilot-panel-head">
                <div>
                    <h2><?php esc_html_e('Connected Properties', 'wp-activity-logger-pro'); ?></h2>
                    <p><?php esc_html_e('Search Console is connected. Choose a property below if you want to extend this page with reports next.', 'wp-activity-logger-pro'); ?></p>
                </div>
            </div>
            <?php if (empty($sites)) : ?>
                <div class="tracepilot-empty-panel">
                    <strong><?php esc_html_e('No properties returned', 'wp-activity-logger-pro'); ?></strong>
                    <p><?php esc_html_e('The Google account is connected, but no Search Console sites were returned yet.', 'wp-activity-logger-pro'); ?></p>
                </div>
            <?php else : ?>
                <div class="tracepilot-list">
                    <?php foreach ($sites as $site) : ?>
                        <div class="tracepilot-list-row">
                            <div>
                                <strong><?php echo esc_html($site->getSiteUrl()); ?></strong>
                                <div class="tracepilot-list-subtext"><?php echo esc_html($site->getPermissionLevel()); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
