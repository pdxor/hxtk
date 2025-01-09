<?php
namespace HTK\Integration;

class HTK_WordPress_Integration {
    public function __construct() {
        // User Authentication
        add_action('init', array($this, 'setup_user_roles'));
        add_filter('authenticate', array($this, 'check_hackathon_access'), 30, 3);
        
        // Content Management
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_hackathon_meta_boxes'));
        add_action('save_post', array($this, 'save_hackathon_meta'));
        
        // Plugin Compatibility
        add_action('plugins_loaded', array($this, 'check_plugin_compatibility'));
        add_action('admin_notices', array($this, 'display_compatibility_notices'));
        
        // Integration Settings
        add_action('admin_init', array($this, 'register_integration_settings'));
        add_action('admin_menu', array($this, 'add_integration_menu'));
    }

    /**
     * User Role Management
     */
    public function setup_user_roles() {
        // Add Hackathon Participant role
        add_role('hackathon_participant', __('Hackathon Participant', 'htk'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'upload_files' => true,
            'publish_hackathon_projects' => true
        ));

        // Add Hackathon Organizer role
        add_role('hackathon_organizer', __('Hackathon Organizer', 'htk'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'upload_files' => true,
            'publish_posts' => true,
            'edit_published_posts' => true,
            'manage_hackathon' => true,
            'edit_hackathon_settings' => true
        ));

        // Add capabilities to administrator
        $admin = get_role('administrator');
        $admin->add_cap('manage_hackathon');
        $admin->add_cap('edit_hackathon_settings');
    }

    public function check_hackathon_access($user, $username, $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        // Check if user has access to current hackathon
        $current_hackathon = get_option('htk_current_hackathon');
        if ($current_hackathon && !$this->user_has_hackathon_access($user->ID, $current_hackathon)) {
            return new WP_Error(
                'hackathon_access_denied',
                __('You do not have access to this hackathon.', 'htk')
            );
        }

        return $user;
    }

    private function user_has_hackathon_access($user_id, $hackathon_id) {
        $participant_ids = get_post_meta($hackathon_id, '_htk_participants', true);
        return in_array($user_id, (array) $participant_ids);
    }

    /**
     * Content Management
     */
    public function register_taxonomies() {
        // Register Project Categories
        register_taxonomy('project_category', 'hackathon_project', array(
            'labels' => array(
                'name' => __('Project Categories', 'htk'),
                'singular_name' => __('Project Category', 'htk')
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'project-category')
        ));

        // Register Project Tags
        register_taxonomy('project_tag', 'hackathon_project', array(
            'labels' => array(
                'name' => __('Project Tags', 'htk'),
                'singular_name' => __('Project Tag', 'htk')
            ),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'project-tag')
        ));
    }

    public function add_hackathon_meta_boxes() {
        add_meta_box(
            'htk_hackathon_settings',
            __('Hackathon Settings', 'htk'),
            array($this, 'render_hackathon_settings'),
            'hackathon',
            'normal',
            'high'
        );
    }

    public function render_hackathon_settings($post) {
        wp_nonce_field('htk_hackathon_settings', 'htk_hackathon_nonce');
        
        $settings = get_post_meta($post->ID, '_htk_hackathon_settings', true);
        ?>
        <div class="htk-form-group">
            <label class="htk-form-label">
                <?php _e('Registration Type', 'htk'); ?>
            </label>
            <select name="htk_registration_type" class="htk-form-select">
                <option value="open" <?php selected($settings['registration_type'], 'open'); ?>>
                    <?php _e('Open Registration', 'htk'); ?>
                </option>
                <option value="invite" <?php selected($settings['registration_type'], 'invite'); ?>>
                    <?php _e('Invite Only', 'htk'); ?>
                </option>
                <option value="closed" <?php selected($settings['registration_type'], 'closed'); ?>>
                    <?php _e('Closed', 'htk'); ?>
                </option>
            </select>
        </div>

        <div class="htk-form-group">
            <label class="htk-form-label">
                <?php _e('Content Visibility', 'htk'); ?>
            </label>
            <select name="htk_content_visibility" class="htk-form-select">
                <option value="public" <?php selected($settings['content_visibility'], 'public'); ?>>
                    <?php _e('Public', 'htk'); ?>
                </option>
                <option value="private" <?php selected($settings['content_visibility'], 'private'); ?>>
                    <?php _e('Private', 'htk'); ?>
                </option>
            </select>
        </div>
        <?php
    }

    public function save_hackathon_meta($post_id) {
        if (!isset($_POST['htk_hackathon_nonce']) || 
            !wp_verify_nonce($_POST['htk_hackathon_nonce'], 'htk_hackathon_settings')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_hackathon_settings')) {
            return;
        }

        $settings = array(
            'registration_type' => sanitize_text_field($_POST['htk_registration_type']),
            'content_visibility' => sanitize_text_field($_POST['htk_content_visibility'])
        );

        update_post_meta($post_id, '_htk_hackathon_settings', $settings);
    }

    /**
     * Plugin Compatibility
     */
    public function check_plugin_compatibility() {
        $this->compatibility_issues = array();
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.8', '<')) {
            $this->compatibility_issues[] = array(
                'type' => 'error',
                'message' => __('HTK requires WordPress version 5.8 or higher.', 'htk')
            );
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $this->compatibility_issues[] = array(
                'type' => 'error',
                'message' => __('HTK requires PHP version 7.4 or higher.', 'htk')
            );
        }

        // Check required plugins
        $required_plugins = array(
            'advanced-custom-fields/acf.php' => 'Advanced Custom Fields',
            'classic-editor/classic-editor.php' => 'Classic Editor'
        );

        foreach ($required_plugins as $plugin => $name) {
            if (!is_plugin_active($plugin)) {
                $this->compatibility_issues[] = array(
                    'type' => 'warning',
                    'message' => sprintf(
                        __('HTK recommends installing %s for optimal functionality.', 'htk'),
                        $name
                    )
                );
            }
        }

        // Store compatibility status
        update_option('htk_compatibility_status', empty($this->compatibility_issues));
    }

    public function display_compatibility_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        foreach ($this->compatibility_issues as $issue) {
            $class = 'notice notice-' . $issue['type'];
            printf(
                '<div class="%1$s"><p>%2$s</p></div>',
                esc_attr($class),
                esc_html($issue['message'])
            );
        }
    }

    /**
     * Integration Settings
     */
    public function register_integration_settings() {
        register_setting('htk_integration', 'htk_integration_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_integration_settings')
        ));

        add_settings_section(
            'htk_integration_section',
            __('WordPress Integration Settings', 'htk'),
            array($this, 'render_integration_section'),
            'htk-integration'
        );

        add_settings_field(
            'htk_user_sync',
            __('User Synchronization', 'htk'),
            array($this, 'render_user_sync_field'),
            'htk-integration',
            'htk_integration_section'
        );

        add_settings_field(
            'htk_content_permissions',
            __('Content Permissions', 'htk'),
            array($this, 'render_content_permissions_field'),
            'htk-integration',
            'htk_integration_section'
        );
    }

    public function add_integration_menu() {
        add_submenu_page(
            'htk-admin',
            __('WordPress Integration', 'htk'),
            __('Integration', 'htk'),
            'manage_options',
            'htk-integration',
            array($this, 'render_integration_page')
        );
    }

    public function render_integration_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap htk-integration">
            <h1><?php _e('WordPress Integration Settings', 'htk'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('htk_integration');
                do_settings_sections('htk-integration');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_integration_section() {
        echo '<p>' . __('Configure how HTK integrates with WordPress.', 'htk') . '</p>';
    }

    public function render_user_sync_field() {
        $settings = get_option('htk_integration_settings');
        ?>
        <label class="htk-toggle">
            <input type="checkbox" 
                   name="htk_integration_settings[user_sync]" 
                   <?php checked($settings['user_sync'], 1); ?>>
            <span class="htk-toggle-slider"></span>
        </label>
        <p class="description">
            <?php _e('Automatically sync hackathon participants with WordPress users', 'htk'); ?>
        </p>
        <?php
    }

    public function render_content_permissions_field() {
        $settings = get_option('htk_integration_settings');
        ?>
        <select name="htk_integration_settings[content_permissions]" class="htk-form-select">
            <option value="strict" <?php selected($settings['content_permissions'], 'strict'); ?>>
                <?php _e('Strict - Only hackathon participants can view content', 'htk'); ?>
            </option>
            <option value="moderate" <?php selected($settings['content_permissions'], 'moderate'); ?>>
                <?php _e('Moderate - Public can view, only participants can interact', 'htk'); ?>
            </option>
            <option value="open" <?php selected($settings['content_permissions'], 'open'); ?>>
                <?php _e('Open - All content is public', 'htk'); ?>
            </option>
        </select>
        <?php
    }

    public function sanitize_integration_settings($settings) {
        return array(
            'user_sync' => isset($settings['user_sync']) ? 1 : 0,
            'content_permissions' => sanitize_text_field($settings['content_permissions'])
        );
    }
} 