<?php
namespace HTK\Admin;

class HTK_Admin_UI {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_body_class', array($this, 'add_admin_body_class'));
        add_action('admin_notices', array($this, 'render_notifications'));
        add_action('wp_ajax_htk_dismiss_notice', array($this, 'ajax_dismiss_notice'));
    }

    public function enqueue_admin_assets($hook) {
        // Only load on HTK plugin pages
        if (strpos($hook, 'htk') === false) {
            return;
        }

        // Main styles
        wp_enqueue_style(
            'htk-admin-styles',
            HTK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HTK_VERSION
        );

        // jQuery UI for enhanced interactions
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_script('jquery-effects-core');

        // Custom scripts
        wp_enqueue_script(
            'htk-admin-scripts',
            HTK_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-tabs', 'jquery-ui-accordion'),
            HTK_VERSION,
            true
        );

        // Localize script
        wp_localize_script('htk-admin-scripts', 'htkAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htk_admin_nonce'),
            'strings' => array(
                'saveSuccess' => __('Changes saved successfully!', 'htk'),
                'saveError' => __('Error saving changes. Please try again.', 'htk'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'htk')
            )
        ));
    }

    public function add_admin_body_class($classes) {
        if (isset($_GET['page']) && strpos($_GET['page'], 'htk') !== false) {
            $classes .= ' htk-admin-page';
        }
        return $classes;
    }

    /**
     * Render admin header with navigation
     */
    public static function render_header($active_tab = '') {
        $tabs = array(
            'dashboard' => array(
                'label' => __('Dashboard', 'htk'),
                'icon' => 'dashicons-dashboard'
            ),
            'development' => array(
                'label' => __('Development', 'htk'),
                'icon' => 'dashicons-editor-code'
            ),
            'resources' => array(
                'label' => __('Resources', 'htk'),
                'icon' => 'dashicons-book'
            ),
            'project-management' => array(
                'label' => __('Projects', 'htk'),
                'icon' => 'dashicons-portfolio'
            )
        );

        ?>
        <div class="htk-admin-header">
            <div class="htk-branding">
                <img src="<?php echo HTK_PLUGIN_URL; ?>assets/images/mit-logo.png" 
                     alt="MIT Reality Hack" 
                     class="htk-logo">
                <h1><?php _e('HTK 1.0', 'htk'); ?></h1>
            </div>

            <nav class="htk-admin-nav" role="navigation" aria-label="<?php esc_attr_e('Main navigation', 'htk'); ?>">
                <!-- Skip link for accessibility -->
                <a href="#htk-main-content" class="screen-reader-text">
                    <?php _e('Skip to main content', 'htk'); ?>
                </a>

                <div class="htk-nav-tabs wp-clearfix">
                    <?php foreach ($tabs as $tab_id => $tab) : ?>
                        <a href="<?php echo admin_url('admin.php?page=htk-' . $tab_id); ?>"
                           class="htk-nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>"
                           aria-current="<?php echo $active_tab === $tab_id ? 'page' : 'false'; ?>">
                            <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                            <span class="htk-tab-label"><?php echo esc_html($tab['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>
        <div id="htk-main-content" class="htk-admin-content">
        <?php
    }

    /**
     * Render admin footer
     */
    public static function render_footer() {
        ?>
        </div><!-- .htk-admin-content -->
        <div class="htk-admin-footer">
            <p><?php printf(
                __('MIT Reality Hack Toolkit Version %s', 'htk'),
                HTK_VERSION
            ); ?></p>
        </div>
        <?php
    }

    /**
     * Render notification system
     */
    public function render_notifications() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'htk') === false) {
            return;
        }

        $notifications = get_option('htk_notifications', array());
        if (empty($notifications)) {
            return;
        }

        foreach ($notifications as $id => $notice) {
            $class = 'notice htk-notice';
            $class .= isset($notice['type']) ? ' notice-' . $notice['type'] : ' notice-info';
            ?>
            <div id="htk-notice-<?php echo esc_attr($id); ?>" 
                 class="<?php echo esc_attr($class); ?>" 
                 data-notice-id="<?php echo esc_attr($id); ?>">
                <p><?php echo wp_kses_post($notice['message']); ?></p>
                <?php if (empty($notice['persistent'])) : ?>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">
                            <?php _e('Dismiss this notice.', 'htk'); ?>
                        </span>
                    </button>
                <?php endif; ?>
            </div>
            <?php
        }
    }

    /**
     * Handle notice dismissal
     */
    public function ajax_dismiss_notice() {
        check_ajax_referer('htk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $notice_id = isset($_POST['notice_id']) ? sanitize_key($_POST['notice_id']) : '';
        if (!$notice_id) {
            wp_die(-1);
        }

        $notifications = get_option('htk_notifications', array());
        unset($notifications[$notice_id]);
        update_option('htk_notifications', $notifications);

        wp_die(1);
    }

    /**
     * Add a notification
     */
    public static function add_notification($message, $type = 'info', $persistent = false) {
        $notifications = get_option('htk_notifications', array());
        $id = 'htk_' . wp_generate_password(6, false);
        
        $notifications[$id] = array(
            'message' => $message,
            'type' => $type,
            'persistent' => $persistent,
            'created' => time()
        );

        update_option('htk_notifications', $notifications);
        return $id;
    }
}