<?php
namespace HTK\Core;

class HTK_Integration {
    private $required_capabilities = array(
        'hackathon_manager' => array(
            'edit_hackathons',
            'edit_others_hackathons',
            'publish_hackathons',
            'read_private_hackathons',
            'delete_hackathons'
        )
    );

    private $minimum_wp_version = '5.8';
    private $required_plugins = array(
        'advanced-custom-fields/acf.php' => '5.0'
    );

    public function __construct() {
        // Core WordPress integration
        add_action('init', array($this, 'register_roles_capabilities'));
        add_action('admin_init', array($this, 'check_wordpress_compatibility'));
        add_filter('map_meta_cap', array($this, 'map_hackathon_capabilities'), 10, 4);
        
        // Plugin integration checks
        add_action('admin_init', array($this, 'check_plugin_compatibility'));
        add_action('admin_notices', array($this, 'display_compatibility_notices'));

        // Content integration
        add_action('pre_get_posts', array($this, 'modify_hackathon_queries'));
        add_filter('the_content', array($this, 'filter_hackathon_content'));
        
        // REST API integration
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // User authentication hooks
        add_filter('htk_user_can_access', array($this, 'check_user_access'), 10, 3);
    }

    /**
     * Register custom roles and capabilities
     */
    public function register_roles_capabilities() {
        // Add Hackathon Manager role
        add_role('hackathon_manager', __('Hackathon Manager', 'htk'), array(
            'read' => true,
            'upload_files' => true
        ));

        // Get hackathon manager role object
        $role = get_role('hackathon_manager');

        // Add custom capabilities
        foreach ($this->required_capabilities['hackathon_manager'] as $cap) {
            if ($role) {
                $role->add_cap($cap);
            }
        }

        // Add capabilities to administrator role
        $admin = get_role('administrator');
        if ($admin) {
            foreach ($this->required_capabilities['hackathon_manager'] as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    /**
     * Map meta capabilities for hackathon posts
     */
    public function map_hackathon_capabilities($caps, $cap, $user_id, $args) {
        if ('edit_hackathon' == $cap) {
            $post = get_post($args[0]);
            if (empty($post)) return $caps;

            if ($post->post_author != $user_id) {
                $caps[] = 'edit_others_hackathons';
            } else {
                $caps[] = 'edit_hackathons';
            }
        }

        return $caps;
    }

    /**
     * Check WordPress version compatibility
     */
    public function check_wordpress_compatibility() {
        if (version_compare(get_bloginfo('version'), $this->minimum_wp_version, '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php printf(
                        __('HTK 1.0 requires WordPress version %s or higher. Please upgrade WordPress to use this plugin.', 'htk'),
                        $this->minimum_wp_version
                    ); ?></p>
                </div>
                <?php
            });
        }
    }

    /**
     * Check required plugin compatibility
     */
    public function check_plugin_compatibility() {
        foreach ($this->required_plugins as $plugin => $version) {
            if (!$this->is_plugin_active_and_compatible($plugin, $version)) {
                add_action('admin_notices', function() use ($plugin, $version) {
                    ?>
                    <div class="notice notice-error">
                        <p><?php printf(
                            __('HTK 1.0 requires %s version %s or higher.', 'htk'),
                            $this->get_plugin_name($plugin),
                            $version
                        ); ?></p>
                    </div>
                    <?php
                });
            }
        }
    }

    /**
     * Check if plugin is active and compatible
     */
    private function is_plugin_active_and_compatible($plugin, $min_version) {
        if (!is_plugin_active($plugin)) {
            return false;
        }

        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        return version_compare($plugin_data['Version'], $min_version, '>=');
    }

    /**
     * Modify main query for hackathon posts
     */
    public function modify_hackathon_queries($query) {
        if (!is_admin() && $query->is_main_query() && is_post_type_archive('hackathon')) {
            // Modify query parameters for hackathon archive
            $query->set('posts_per_page', 12);
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', '_hackathon_start_date');
            $query->set('order', 'DESC');
        }
    }

    /**
     * Filter hackathon content
     */
    public function filter_hackathon_content($content) {
        if (is_singular('hackathon')) {
            // Add custom content for hackathon posts
            $custom_content = $this->get_hackathon_custom_content(get_the_ID());
            return $custom_content . $content;
        }
        return $content;
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('htk/v1', '/hackathons', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_hackathons'),
                'permission_callback' => array($this, 'check_api_permission')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_hackathon'),
                'permission_callback' => array($this, 'check_api_permission')
            )
        ));
    }

    /**
     * Check API permissions
     */
    public function check_api_permission() {
        // Check if user is logged in and has required capabilities
        return current_user_can('edit_hackathons');
    }

    /**
     * Check user access to specific features
     */
    public function check_user_access($allowed, $feature, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        switch ($feature) {
            case 'manage_hackathon':
                return user_can($user_id, 'edit_hackathons');
            case 'view_participants':
                return user_can($user_id, 'read_private_hackathons');
            default:
                return $allowed;
        }
    }

    /**
     * Get custom hackathon content
     */
    private function get_hackathon_custom_content($post_id) {
        ob_start();
        ?>
        <div class="htk-hackathon-details">
            <?php
            // Get custom field values using WordPress functions
            $start_date = get_post_meta($post_id, '_hackathon_start_date', true);
            $end_date = get_post_meta($post_id, '_hackathon_end_date', true);
            $location = get_post_meta($post_id, '_hackathon_location', true);
            
            if ($start_date) {
                echo '<p class="htk-date">' . esc_html(date_i18n(get_option('date_format'), strtotime($start_date)));
                if ($end_date) {
                    echo ' - ' . esc_html(date_i18n(get_option('date_format'), strtotime($end_date)));
                }
                echo '</p>';
            }
            
            if ($location) {
                echo '<p class="htk-location">' . esc_html($location) . '</p>';
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get plugin name from path
     */
    private function get_plugin_name($plugin_path) {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
        return $plugin_data['Name'];
    }
}