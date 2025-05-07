<?php
/**
 * Tracker class for WP Activity Logger Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WPAL_Tracker {
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here
    }

    /**
     * Initialize tracker
     */
    public function init() {
        // Track user login
        add_action('wp_login', array($this, 'track_login'), 10, 2);
        
        // Track user logout
        add_action('wp_logout', array($this, 'track_logout'));
        
        // Track failed login
        add_action('wp_login_failed', array($this, 'track_login_failed'));
        
        // Track post creation and updates
        add_action('save_post', array($this, 'track_post_save'), 10, 3);
        
        // Track post deletion
        add_action('delete_post', array($this, 'track_post_delete'));
        
        // Track user creation
        add_action('user_register', array($this, 'track_user_register'));
        
        // Track user profile update
        add_action('profile_update', array($this, 'track_profile_update'), 10, 2);
        
        // Track user deletion
        add_action('delete_user', array($this, 'track_user_delete'));
        
        // Track plugin activation
        add_action('activated_plugin', array($this, 'track_plugin_activation'));
        
        // Track plugin deactivation
        add_action('deactivated_plugin', array($this, 'track_plugin_deactivation'));
        
        // Track theme switch
        add_action('switch_theme', array($this, 'track_theme_switch'), 10, 3);
        
        // Track WordPress updates
        add_action('upgrader_process_complete', array($this, 'track_wordpress_update'), 10, 2);
    }

    /**
     * Track user login
     */
    public function track_login($user_login, $user) {
        WPAL_Helpers::log_activity(
            'user_login',
            sprintf(__('User %s logged in', 'wp-activity-logger-pro'), $user_login),
            'info',
            'user',
            $user->ID,
            $user_login
        );
    }

    /**
     * Track user logout
     */
    public function track_logout() {
        $current_user = wp_get_current_user();
        
        if ($current_user->ID === 0) {
            return;
        }
        
        WPAL_Helpers::log_activity(
            'user_logout',
            sprintf(__('User %s logged out', 'wp-activity-logger-pro'), $current_user->user_login),
            'info',
            'user',
            $current_user->ID,
            $current_user->user_login
        );
    }

    /**
     * Track failed login
     */
    public function track_login_failed($username) {
        WPAL_Helpers::log_activity(
            'login_failed',
            sprintf(__('Failed login attempt for user %s', 'wp-activity-logger-pro'), $username),
            'warning',
            'user',
            0,
            $username
        );
    }

    /**
     * Track post save
     */
    public function track_post_save($post_id, $post, $update) {
        // Skip auto-saves and revisions
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Skip if post is not published
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Get post type label
        $post_type_obj = get_post_type_object($post->post_type);
        $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
        
        if ($update) {
            WPAL_Helpers::log_activity(
                'post_updated',
                sprintf(__('%s "%s" updated', 'wp-activity-logger-pro'), $post_type_label, $post->post_title),
                'info',
                $post->post_type,
                $post_id,
                $post->post_title
            );
        } else {
            WPAL_Helpers::log_activity(
                'post_created',
                sprintf(__('%s "%s" created', 'wp-activity-logger-pro'), $post_type_label, $post->post_title),
                'info',
                $post->post_type,
                $post_id,
                $post->post_title
            );
        }
    }

    /**
     * Track post delete
     */
    public function track_post_delete($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        // Get post type label
        $post_type_obj = get_post_type_object($post->post_type);
        $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
        
        WPAL_Helpers::log_activity(
            'post_deleted',
            sprintf(__('%s "%s" deleted', 'wp-activity-logger-pro'), $post_type_label, $post->post_title),
            'warning',
            $post->post_type,
            $post_id,
            $post->post_title
        );
    }

    /**
     * Track user register
     */
    public function track_user_register($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        WPAL_Helpers::log_activity(
            'user_registered',
            sprintf(__('User %s registered', 'wp-activity-logger-pro'), $user->user_login),
            'info',
            'user',
            $user_id,
            $user->user_login
        );
    }

    /**
     * Track profile update
     */
    public function track_profile_update($user_id, $old_user_data) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        WPAL_Helpers::log_activity(
            'profile_updated',
            sprintf(__('User %s profile updated', 'wp-activity-logger-pro'), $user->user_login),
            'info',
            'user',
            $user_id,
            $user->user_login
        );
    }

    /**
     * Track user delete
     */
    public function track_user_delete($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        WPAL_Helpers::log_activity(
            'user_deleted',
            sprintf(__('User %s deleted', 'wp-activity-logger-pro'), $user->user_login),
            'warning',
            'user',
            $user_id,
            $user->user_login
        );
    }

    /**
     * Track plugin activation
     */
    public function track_plugin_activation($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
        
        WPAL_Helpers::log_activity(
            'plugin_activated',
            sprintf(__('Plugin "%s" activated', 'wp-activity-logger-pro'), $plugin_name),
            'info',
            'plugin',
            0,
            $plugin_name
        );
    }

    /**
     * Track plugin deactivation
     */
    public function track_plugin_deactivation($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
        
        WPAL_Helpers::log_activity(
            'plugin_deactivated',
            sprintf(__('Plugin "%s" deactivated', 'wp-activity-logger-pro'), $plugin_name),
            'info',
            'plugin',
            0,
            $plugin_name
        );
    }

    /**
     * Track theme switch
     */
    public function track_theme_switch($new_theme_name, $new_theme, $old_theme) {
        $old_theme_name = $old_theme ? $old_theme->get('Name') : __('Unknown', 'wp-activity-logger-pro');
        
        WPAL_Helpers::log_activity(
            'theme_switched',
            sprintf(__('Theme switched from "%s" to "%s"', 'wp-activity-logger-pro'), $old_theme_name, $new_theme_name),
            'info',
            'theme',
            0,
            $new_theme_name
        );
    }

    /**
     * Track WordPress update
     */
    public function track_wordpress_update($upgrader, $options) {
        if (!isset($options['action']) || $options['action'] !== 'update') {
            return;
        }
        
        // WordPress core update
        if ($options['type'] === 'core') {
            $wp_version = get_bloginfo('version');
            
            WPAL_Helpers::log_activity(
                'wordpress_updated',
                sprintf(__('WordPress updated to version %s', 'wp-activity-logger-pro'), $wp_version),
                'info',
                'core',
                0,
                $wp_version
            );
        }
        
        // Plugin updates
        if ($options['type'] === 'plugin' && !empty($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
                $plugin_version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : '';
                
                WPAL_Helpers::log_activity(
                    'plugin_updated',
                    sprintf(__('Plugin "%s" updated to version %s', 'wp-activity-logger-pro'), $plugin_name, $plugin_version),
                    'info',
                    'plugin',
                    0,
                    $plugin_name
                );
            }
        }
        
        // Theme updates
        if ($options['type'] === 'theme' && !empty($options['themes'])) {
            foreach ($options['themes'] as $theme) {
                $theme_data = wp_get_theme($theme);
                $theme_name = $theme_data->get('Name');
                $theme_version = $theme_data->get('Version');
                
                WPAL_Helpers::log_activity(
                    'theme_updated',
                    sprintf(__('Theme "%s" updated to version %s', 'wp-activity-logger-pro'), $theme_name, $theme_version),
                    'info',
                    'theme',
                    0,
                    $theme_name
                );
            }
        }
    }
}