<?php
namespace HTK\UI_UX;

class HTK_UI_UX {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_ui_assets'));
        add_action('admin_footer', array($this, 'render_feedback_widget'));
        add_action('wp_ajax_htk_submit_feedback', array($this, 'ajax_submit_feedback'));
        
        // Add accessibility features
        add_filter('htk_admin_body_class', array($this, 'add_high_contrast_class'));
        add_action('admin_init', array($this, 'register_ui_settings'));
    }

    public function enqueue_ui_assets($hook) {
        // Only load on HTK plugin pages
        if (strpos($hook, 'htk') === false) {
            return;
        }

        // Enqueue Tailwind CSS
        wp_enqueue_style(
            'htk-tailwind',
            'https://cdn.tailwindcss.com',
            array(),
            HTK_VERSION
        );

        // Enqueue custom UI styles
        wp_enqueue_style(
            'htk-ui-css',
            HTK_PLUGIN_URL . 'assets/css/admin/ui-ux.css',
            array('htk-tailwind'),
            HTK_VERSION
        );

        // Enqueue UI JavaScript
        wp_enqueue_script(
            'htk-ui-js',
            HTK_PLUGIN_URL . 'assets/js/admin/ui-ux.js',
            array('jquery'),
            HTK_VERSION,
            true
        );

        wp_localize_script('htk-ui-js', 'htkUI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htk_ui_nonce'),
            'strings' => array(
                'feedbackSuccess' => __('Thank you for your feedback!', 'htk'),
                'feedbackError' => __('Failed to submit feedback.', 'htk')
            )
        ));
    }

    public function register_ui_settings() {
        register_setting('htk_ui_options', 'htk_high_contrast', array(
            'type' => 'boolean',
            'default' => false
        ));

        register_setting('htk_ui_options', 'htk_font_size', array(
            'type' => 'string',
            'default' => 'medium'
        ));

        add_settings_section(
            'htk_ui_accessibility',
            __('Accessibility Settings', 'htk'),
            array($this, 'render_accessibility_section'),
            'htk-settings'
        );

        add_settings_field(
            'htk_high_contrast',
            __('High Contrast Mode', 'htk'),
            array($this, 'render_high_contrast_field'),
            'htk-settings',
            'htk_ui_accessibility'
        );

        add_settings_field(
            'htk_font_size',
            __('Font Size', 'htk'),
            array($this, 'render_font_size_field'),
            'htk-settings',
            'htk_ui_accessibility'
        );
    }

    public function render_accessibility_section() {
        echo '<p>' . __('Customize the appearance to improve accessibility.', 'htk') . '</p>';
    }

    public function render_high_contrast_field() {
        $high_contrast = get_option('htk_high_contrast', false);
        ?>
        <label class="htk-toggle">
            <input type="checkbox" 
                   name="htk_high_contrast" 
                   <?php checked($high_contrast); ?>>
            <span class="htk-toggle-slider"></span>
        </label>
        <p class="description">
            <?php _e('Enable high contrast mode for better visibility', 'htk'); ?>
        </p>
        <?php
    }

    public function render_font_size_field() {
        $font_size = get_option('htk_font_size', 'medium');
        ?>
        <select name="htk_font_size">
            <option value="small" <?php selected($font_size, 'small'); ?>>
                <?php _e('Small', 'htk'); ?>
            </option>
            <option value="medium" <?php selected($font_size, 'medium'); ?>>
                <?php _e('Medium', 'htk'); ?>
            </option>
            <option value="large" <?php selected($font_size, 'large'); ?>>
                <?php _e('Large', 'htk'); ?>
            </option>
        </select>
        <p class="description">
            <?php _e('Choose your preferred font size', 'htk'); ?>
        </p>
        <?php
    }

    public function add_high_contrast_class($classes) {
        if (get_option('htk_high_contrast', false)) {
            $classes .= ' htk-high-contrast';
        }
        return $classes;
    }

    public function render_feedback_widget() {
        if (strpos(get_current_screen()->id, 'htk') === false) {
            return;
        }
        ?>
        <div class="htk-feedback-widget">
            <button type="button" class="htk-feedback-toggle">
                <?php _e('Feedback', 'htk'); ?>
            </button>
            
            <div class="htk-feedback-form">
                <h3><?php _e('Help us improve', 'htk'); ?></h3>
                <form id="htk-feedback-form">
                    <div class="htk-form-row">
                        <label for="feedback_type">
                            <?php _e('Type of feedback', 'htk'); ?>
                        </label>
                        <select id="feedback_type" name="feedback_type" required>
                            <option value="suggestion">
                                <?php _e('Suggestion', 'htk'); ?>
                            </option>
                            <option value="bug">
                                <?php _e('Bug Report', 'htk'); ?>
                            </option>
                            <option value="praise">
                                <?php _e('Praise', 'htk'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="htk-form-row">
                        <label for="feedback_message">
                            <?php _e('Your feedback', 'htk'); ?>
                        </label>
                        <textarea id="feedback_message" 
                                  name="feedback_message" 
                                  rows="4" 
                                  required></textarea>
                    </div>
                    
                    <div class="htk-form-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Submit Feedback', 'htk'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public function ajax_submit_feedback() {
        check_ajax_referer('htk_ui_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $type = isset($_POST['feedback_type']) ? 
            sanitize_text_field($_POST['feedback_type']) : '';
        $message = isset($_POST['feedback_message']) ? 
            sanitize_textarea_field($_POST['feedback_message']) : '';

        if (!$type || !$message) {
            wp_send_json_error(__('Missing required fields.', 'htk'));
        }

        // Here you would typically save the feedback to the database
        // For now, we'll just simulate a successful submission
        wp_send_json_success(array(
            'message' => __('Thank you for your feedback!', 'htk')
        ));
    }
} 