<?php
/**
 * WP Activity Logger Tracker
 *
 * @package WP Activity Logger
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class TracePilot_Tracker {
    /**
     * Constructor
     */
    public function __construct() {
        // Track user login
        add_action('wp_login', array($this, 'track_user_login'), 10, 2);
        
        // Track user logout
        add_action('wp_logout', array($this, 'track_user_logout'));
        
        // Track failed login
        add_action('wp_login_failed', array($this, 'track_failed_login'));
        
        // Track user registration
        add_action('user_register', array($this, 'track_user_registration'));
        
        // Track user profile update
        add_action('profile_update', array($this, 'track_profile_update'), 10, 2);
        
        // Track password reset
        add_action('after_password_reset', array($this, 'track_password_reset'));
        
        // Track post actions
        add_action('transition_post_status', array($this, 'track_post_status'), 10, 3);
        add_action('post_updated', array($this, 'track_post_update'), 10, 3);
        add_action('before_delete_post', array($this, 'track_post_delete'), 10, 1);
        
        // Track comment actions
        add_action('wp_insert_comment', array($this, 'track_comment_insert'), 10, 2);
        add_action('edit_comment', array($this, 'track_comment_update'));
        add_action('trash_comment', array($this, 'track_comment_trash'));
        add_action('spam_comment', array($this, 'track_comment_spam'));
        add_action('unspam_comment', array($this, 'track_comment_unspam'));
        add_action('delete_comment', array($this, 'track_comment_delete'));
        
        // Track plugin actions
        add_action('activated_plugin', array($this, 'track_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'track_plugin_deactivation'));
        
        // Track theme actions
        add_action('switch_theme', array($this, 'track_theme_switch'), 10, 3);
        
        // Track WordPress updates
        add_action('upgrader_process_complete', array($this, 'track_wordpress_update'), 10, 2);
        
        // Track user role changes
        add_action('set_user_role', array($this, 'track_user_role_change'), 10, 3);
        
        // Track options changes
        add_action('updated_option', array($this, 'track_option_update'), 10, 3);
        
        // Track file edits
        add_action('wp_ajax_edit-theme-plugin-file', array($this, 'track_file_edit'), 1);
        
        // Track custom events
        add_action('tracepilot_track_custom_event', array($this, 'track_custom_event'), 10, 3);
        add_action('wpal_track_custom_event', array($this, 'track_custom_event'), 10, 3);
    }

    /**
     * Track user login
     */
    public function track_user_login($user_login, $user) {
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'user_login',
            sprintf(__('User %s logged in', 'tracepilot'), $user_login),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user_login,
                'user_role' => $user_data['role'],
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track user logout
     */
    public function track_user_logout() {
        // Get current user
        $user = wp_get_current_user();
        
        if ($user->ID === 0) {
            return;
        }
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'user_logout',
            sprintf(__('User %s logged out', 'tracepilot'), $user->user_login),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track failed login
     */
    public function track_failed_login($username) {
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'login_failed',
            sprintf(__('Failed login attempt for user %s', 'tracepilot'), $username),
            'warning',
            array(
                'username' => $username,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track user registration
     */
    public function track_user_registration($user_id) {
        // Get user
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'user_registered',
            sprintf(__('New user registered: %s', 'tracepilot'), $user->user_login),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'user_email' => $user->user_email,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track profile update
     */
    public function track_profile_update($user_id, $old_user_data) {
        // Get user
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'profile_updated',
            sprintf(__('User profile updated: %s', 'tracepilot'), $user->user_login),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track password reset
     */
    public function track_password_reset($user) {
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'password_reset',
            sprintf(__('Password reset for user %s', 'tracepilot'), $user->user_login),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track post status changes
     */
    public function track_post_status($new_status, $old_status, $post) {
        // Skip auto-drafts and revisions
        if ($post->post_type === 'revision' || $post->post_status === 'auto-draft') {
            return;
        }
        
        // Skip if status hasn't changed
        if ($new_status === $old_status) {
            return;
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Determine action
        $action = '';
        $message = '';
        
        if ($old_status === 'new' && $new_status === 'auto-draft') {
            return; // Skip auto-drafts
        } elseif ($old_status === 'auto-draft' && $new_status === 'draft') {
            $action = 'post_created';
            $message = sprintf(__('%s created: %s', 'tracepilot'), ucfirst($post->post_type), $post->post_title);
        } elseif ($new_status === 'publish' && $old_status !== 'publish') {
            $action = 'post_published';
            $message = sprintf(__('%s published: %s', 'tracepilot'), ucfirst($post->post_type), $post->post_title);
        } elseif ($old_status === 'publish' && $new_status !== 'publish') {
            $action = 'post_unpublished';
            $message = sprintf(__('%s unpublished: %s', 'tracepilot'), ucfirst($post->post_type), $post->post_title);
        } elseif ($new_status === 'trash') {
            $action = 'post_trashed';
            $message = sprintf(__('%s moved to trash: %s', 'tracepilot'), ucfirst($post->post_type), $post->post_title);
        } elseif ($old_status === 'trash' && $new_status !== 'trash') {
            $action = 'post_restored';
            $message = sprintf(__('%s restored from trash: %s', 'tracepilot'), ucfirst($post->post_type), $post->post_title);
        } else {
            $action = 'post_updated';
            $message = sprintf(__('%s updated: %s', 'tracepilot'), ucfirst($post->post_type), $post->post_title);
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            $action,
            $message,
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }

    /**
     * Track updates when a post/page stays in the same status.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post_after Updated post object.
     * @param WP_Post $post_before Previous post object.
     */
    public function track_post_update($post_id, $post_after, $post_before) {
        if (!$post_after instanceof WP_Post || !$post_before instanceof WP_Post) {
            return;
        }

        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || 'auto-draft' === $post_after->post_status) {
            return;
        }

        if ($post_after->post_status !== $post_before->post_status) {
            return;
        }

        if ($post_after->post_modified_gmt === $post_before->post_modified_gmt) {
            return;
        }

        if (
            $post_after->post_title === $post_before->post_title &&
            $post_after->post_content === $post_before->post_content &&
            $post_after->post_excerpt === $post_before->post_excerpt &&
            $post_after->menu_order === $post_before->menu_order
        ) {
            return;
        }

        $user = wp_get_current_user();
        $user_data = $this->get_user_data($user);
        $geo_data = $this->get_geolocation_data();

        TracePilot_Helpers::log_activity(
            'post_updated',
            sprintf(__('%s updated: %s', 'tracepilot'), ucfirst($post_after->post_type), $post_after->post_title),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'object_id' => $post_after->ID,
                'object_type' => $post_after->post_type,
                'object_name' => $post_after->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code'],
                'context' => array(
                    'old_status' => $post_before->post_status,
                    'new_status' => $post_after->post_status,
                    'post_type' => $post_after->post_type,
                    'title_changed' => $post_after->post_title !== $post_before->post_title,
                    'content_changed' => $post_after->post_content !== $post_before->post_content,
                    'excerpt_changed' => $post_after->post_excerpt !== $post_before->post_excerpt,
                ),
            )
        );
    }

    /**
     * Track permanent post/page deletions.
     *
     * @param int $post_id Post ID.
     */
    public function track_post_delete($post_id) {
        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return;
        }

        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ('auto-draft' === $post->post_status) {
            return;
        }

        $user = wp_get_current_user();
        $user_data = $this->get_user_data($user);
        $geo_data = $this->get_geolocation_data();

        TracePilot_Helpers::log_activity(
            'post_deleted',
            sprintf(__('%s deleted: %s', 'tracepilot'), ucfirst($post->post_type), $post->post_title),
            'warning',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'object_id' => $post->ID,
                'object_type' => $post->post_type,
                'object_name' => $post->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code'],
            )
        );
    }
    
    /**
     * Track comment insert
     */
    public function track_comment_insert($comment_id, $comment) {
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Get post
        $post = get_post($comment->comment_post_ID);
        
        if (!$post) {
            return;
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'comment_added',
            sprintf(__('Comment added to %s: %s', 'tracepilot'), $post->post_title, wp_trim_words($comment->comment_content, 10)),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'comment_id' => $comment_id,
                'comment_author' => $comment->comment_author,
                'comment_content' => wp_trim_words($comment->comment_content, 20),
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track comment update
     */
    public function track_comment_update($comment_id) {
        // Get comment
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return;
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Get post
        $post = get_post($comment->comment_post_ID);
        
        if (!$post) {
            return;
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'comment_updated',
            sprintf(__('Comment updated on %s', 'tracepilot'), $post->post_title),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'comment_id' => $comment_id,
                'comment_author' => $comment->comment_author,
                'comment_content' => wp_trim_words($comment->comment_content, 20),
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track comment trash
     */
    public function track_comment_trash($comment_id) {
        // Get comment
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return;
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Get post
        $post = get_post($comment->comment_post_ID);
        
        if (!$post) {
            return;
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'comment_trashed',
            sprintf(__('Comment trashed on %s', 'tracepilot'), $post->post_title),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'comment_id' => $comment_id,
                'comment_author' => $comment->comment_author,
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track comment spam
     */
    public function track_comment_spam($comment_id) {
        // Get comment
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return;
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Get post
        $post = get_post($comment->comment_post_ID);
        
        if (!$post) {
            return;
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'comment_spam',
            sprintf(__('Comment marked as spam on %s', 'tracepilot'), $post->post_title),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'comment_id' => $comment_id,
                'comment_author' => $comment->comment_author,
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track comment unspam
     */
    public function track_comment_unspam($comment_id) {
        // Get comment
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return;
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Get post
        $post = get_post($comment->comment_post_ID);
        
        if (!$post) {
            return;
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'comment_unspam',
            sprintf(__('Comment unmarked as spam on %s', 'tracepilot'), $post->post_title),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'comment_id' => $comment_id,
                'comment_author' => $comment->comment_author,
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track comment delete
     */
    public function track_comment_delete($comment_id) {
        // Get comment
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return;
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Get post
        $post = get_post($comment->comment_post_ID);
        
        if (!$post) {
            return;
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'comment_deleted',
            sprintf(__('Comment deleted from %s', 'tracepilot'), $post->post_title),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'comment_id' => $comment_id,
                'comment_author' => $comment->comment_author,
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track plugin activation
     */
    public function track_plugin_activation($plugin) {
        // Get plugin data
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'plugin_activated',
            sprintf(__('Plugin activated: %s', 'tracepilot'), $plugin_data['Name']),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'plugin' => $plugin,
                'plugin_name' => $plugin_data['Name'],
                'plugin_version' => $plugin_data['Version'],
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track plugin deactivation
     */
    public function track_plugin_deactivation($plugin) {
        // Get plugin data
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'plugin_deactivated',
            sprintf(__('Plugin deactivated: %s', 'tracepilot'), $plugin_data['Name']),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'plugin' => $plugin,
                'plugin_name' => $plugin_data['Name'],
                'plugin_version' => $plugin_data['Version'],
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track theme switch
     */
    public function track_theme_switch($new_name, $new_theme, $old_theme) {
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'theme_switched',
            sprintf(__('Theme switched from %s to %s', 'tracepilot'), $old_theme->get('Name'), $new_name),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'old_theme' => $old_theme->get('Name'),
                'new_theme' => $new_name,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Track WordPress update
     */
    public function track_wordpress_update($upgrader, $options) {
        if (!isset($options['type'])) {
            return;
        }

        $lifecycle_action = isset($options['action']) ? sanitize_key($options['action']) : 'update';

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $user = wp_get_current_user();
        $user_data = $this->get_user_data($user);
        $geo_data = $this->get_geolocation_data();

        $base_args = array(
            'user_id' => $user->ID,
            'username' => $user->user_login,
            'user_role' => $user_data['role'],
            'ip' => $geo_data['ip'],
            'location' => $geo_data['location'],
            'country' => $geo_data['country'],
            'country_code' => $geo_data['country_code'],
        );

        if ('core' === $options['type']) {
            TracePilot_Helpers::log_activity(
                'wordpress_updated',
                sprintf(__('WordPress updated to version %s', 'tracepilot'), get_bloginfo('version')),
                'info',
                array_merge(
                    $base_args,
                    array(
                        'object_type' => 'core',
                        'object_name' => 'wordpress',
                        'context' => array(
                            'version' => get_bloginfo('version'),
                        ),
                    )
                )
            );
            return;
        }

        if (!in_array($options['type'], array('plugin', 'theme'), true) || empty($options['plugins']) && empty($options['themes'])) {
            return;
        }

        if ('plugin' === $options['type'] && !empty($options['plugins'])) {
            foreach ((array) $options['plugins'] as $plugin_file) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false);
                $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin_file;
                $plugin_version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : '';

                $event_key = 'plugin_updated';
                $event_message = __('Plugin updated: %s', 'tracepilot');
                $severity = 'info';
                if ('install' === $lifecycle_action) {
                    $event_key = 'plugin_installed';
                    $event_message = __('Plugin installed: %s', 'tracepilot');
                } elseif ('delete' === $lifecycle_action) {
                    $event_key = 'plugin_deleted';
                    $event_message = __('Plugin deleted: %s', 'tracepilot');
                    $severity = 'warning';
                }

                TracePilot_Helpers::log_activity(
                    $event_key,
                    sprintf($event_message, $plugin_name),
                    $severity,
                    array_merge(
                        $base_args,
                        array(
                            'object_type' => 'plugin',
                            'object_name' => $plugin_name,
                            'context' => array(
                                'plugin' => $plugin_file,
                                'version' => $plugin_version,
                            ),
                        )
                    )
                );
            }

            return;
        }

        if ('theme' === $options['type'] && !empty($options['themes'])) {
            foreach ((array) $options['themes'] as $stylesheet) {
                $theme = wp_get_theme($stylesheet);
                $theme_name = $theme->exists() ? $theme->get('Name') : $stylesheet;
                $theme_version = $theme->exists() ? $theme->get('Version') : '';

                $event_key = 'theme_updated';
                $event_message = __('Theme updated: %s', 'tracepilot');
                $severity = 'info';
                if ('install' === $lifecycle_action) {
                    $event_key = 'theme_installed';
                    $event_message = __('Theme installed: %s', 'tracepilot');
                } elseif ('delete' === $lifecycle_action) {
                    $event_key = 'theme_deleted';
                    $event_message = __('Theme deleted: %s', 'tracepilot');
                    $severity = 'warning';
                }

                TracePilot_Helpers::log_activity(
                    $event_key,
                    sprintf($event_message, $theme_name),
                    $severity,
                    array_merge(
                        $base_args,
                        array(
                            'object_type' => 'theme',
                            'object_name' => $theme_name,
                            'context' => array(
                                'stylesheet' => $stylesheet,
                                'version' => $theme_version,
                            ),
                        )
                    )
                );
            }
        }
    }
    
    /**
     * Track user role change
     */
    public function track_user_role_change($user_id, $role, $old_roles) {
        // Get user
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        // Get current user
        $current_user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($current_user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'user_role_changed',
            sprintf(__('User role changed for %s from %s to %s', 'tracepilot'), $user->user_login, implode(', ', $old_roles), $role),
            'info',
            array(
                'user_id' => $current_user->ID,
                'username' => $current_user->user_login,
                'user_role' => $user_data['role'],
                'target_user_id' => $user_id,
                'target_username' => $user->user_login,
                'old_role' => implode(', ', $old_roles),
                'new_role' => $role,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code'],
                'context' => json_encode(array(
                    'old_role' => implode(', ', $old_roles),
                    'new_role' => $role
                ))
            )
        );
    }
    
    /**
     * Track option update
     */
    public function track_option_update($option, $old_value, $value) {
        // Skip some options
        $skip_options = array(
            '_transient_',
            '_site_transient_',
            'cron',
            'active_plugins',
            'recently_activated',
            'uninstall_plugins',
            'widget_',
            'theme_mods_',
            'tracepilot_',
            'wp_activity_logger_',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_',
            'wp_mail_smtp_debug_log_events_',
            'wp_mail_smtp_debug_log_events',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug',
            'wp_mail_smtp',
            'wp_mail_smtp_',
            'wp_mail_smtp_debug',
            'wp_mail_smtp_debug_events',
            'wp_mail_smtp_debug_events_',
            'wp_mail_smtp_debug_',
            'wp_mail_smtp_debug_log',
            'wp_mail_smtp_debug_log_',
        );
        
        foreach ($skip_options as $skip_option) {
            if (strpos($option, $skip_option) === 0) {
                return;
            }
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Skip if no user is logged in
        if ($user->ID === 0) {
            return;
        }
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Format values for logging
        $old_value_formatted = $this->format_option_value($old_value);
        $value_formatted = $this->format_option_value($value);
        
        // Log activity
        TracePilot_Helpers::log_activity(
            'option_updated',
            sprintf(__('Option updated: %s', 'tracepilot'), $option),
            'info',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'option' => $option,
                'old_value' => $old_value_formatted,
                'new_value' => $value_formatted,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code']
            )
        );
    }
    
    /**
     * Format option value for logging
     */
    private function format_option_value($value) {
        if (is_array($value) || is_object($value)) {
            return '[Complex Value]';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return substr((string) $value, 0, 100);
        }
    }
    
    /**
     * Track file edit
     */
    public function track_file_edit() {
        // Check if this is a file edit request
        $request_action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : '';
        if ('edit-theme-plugin-file' !== $request_action) {
            return;
        }
        
        // Check if file is set
        if (!isset($_POST['file'])) {
            return;
        }
        
        // Get file
        $file = sanitize_text_field(wp_unslash($_POST['file']));
        
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Determine file type
        $file_type = '';
        $action = '';
        
        if (strpos($file, 'wp-content/themes/') !== false) {
            $file_type = 'theme';
            $action = 'theme_edited';
        } elseif (strpos($file, 'wp-content/plugins/') !== false) {
            $file_type = 'plugin';
            $action = 'plugin_edited';
        } else {
            $file_type = 'file';
            $action = 'file_edited';
        }
        
        // Log activity
        TracePilot_Helpers::log_activity(
            $action,
            sprintf(__('%s edited: %s', 'tracepilot'), ucfirst($file_type), $file),
            'warning',
            array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'user_role' => $user_data['role'],
                'file' => $file,
                'file_type' => $file_type,
                'ip' => $geo_data['ip'],
                'location' => $geo_data['location'],
                'country' => $geo_data['country'],
                'country_code' => $geo_data['country_code'],
                'object_type' => $file_type,
                'object_name' => $file
            )
        );
    }
    
    /**
     * Track custom event
     */
    public function track_custom_event($action, $description, $severity = 'info', $context = array()) {
        // Get current user
        $user = wp_get_current_user();
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Get geolocation data
        $geo_data = $this->get_geolocation_data();
        
        // Add user and geolocation data to context
        $context = array_merge($context, array(
            'user_id' => $user->ID,
            'username' => $user->user_login,
            'user_role' => $user_data['role'],
            'ip' => $geo_data['ip'],
            'location' => $geo_data['location'],
            'country' => $geo_data['country'],
            'country_code' => $geo_data['country_code']
        ));
        
        // Log activity
        TracePilot_Helpers::log_activity(
            $action,
            $description,
            $severity,
            $context
        );
    }
    
    /**
     * Get user data
     */
    private function get_user_data($user) {
        if (!$user || !$user->ID) {
            return array(
                'role' => 'guest',
                'roles' => array('guest'),
                'capabilities' => array()
            );
        }
        
        // Get user roles
        $roles = (array) $user->roles;
        
        // Get primary role
        $role = !empty($roles) ? $roles[0] : '';
        
        return array(
            'role' => $role,
            'roles' => $roles,
            'capabilities' => (array) $user->allcaps
        );
    }
    
    /**
     * Get geolocation data
     */
    private function get_geolocation_data() {
        // Get IP address
        $ip = $this->get_ip_address();
        
        // Check if geolocation is enabled
        $options = get_option('wpal_options', array());
        $enable_geolocation = isset($options['enable_geolocation']) ? (bool) $options['enable_geolocation'] : false;
        
        if (!$enable_geolocation) {
            return array(
                'ip' => $ip,
                'location' => '',
                'country' => '',
                'country_code' => ''
            );
        }
        
        // Try to get from cache
        $cache_key = 'tracepilot_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return array(
                'ip' => $ip,
                'location' => $cached['city'] . ', ' . $cached['region'] . ', ' . $cached['country'],
                'country' => $cached['country'],
                'country_code' => $cached['country_code']
            );
        }
        
        // Check if IP is local
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return array(
                'ip' => $ip,
                'location' => 'Local',
                'country' => 'Local',
                'country_code' => 'LO'
            );
        }
        
        // Try to get geolocation data
        if (class_exists('TracePilot_Geolocation')) {
            $geolocation = new TracePilot_Geolocation();
            $geo_data = $geolocation->get_ip_geolocation($ip);
            
            if (!is_wp_error($geo_data)) {
                return array(
                    'ip' => $ip,
                    'location' => $geo_data['city'] . ', ' . $geo_data['region'] . ', ' . $geo_data['country'],
                    'country' => $geo_data['country'],
                    'country_code' => $geo_data['country_code']
                );
            }
        }
        
        // Fallback
        return array(
            'ip' => $ip,
            'location' => '',
            'country' => '',
            'country_code' => ''
        );
    }
    
    /**
     * Get IP address
     */
    private function get_ip_address() {
        return class_exists('TracePilot_Helpers') ? TracePilot_Helpers::get_ip_address() : '';
    }
}
