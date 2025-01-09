<?php
namespace HTK\Resources;

class HTK_Resources {
    private $resources;
    private $mentors;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_resources_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_resources_assets'));
        add_action('wp_ajax_htk_submit_mentor_request', array($this, 'ajax_submit_mentor_request'));
        
        $this->init_resources();
        $this->init_mentors();
    }

    private function init_resources() {
        $this->resources = array(
            'development-tools' => array(
                'title' => __('Development Tools', 'htk'),
                'items' => array(
                    array(
                        'name' => 'Unity XR',
                        'description' => __('Complete XR development platform', 'htk'),
                        'url' => 'https://unity.com/unity/features/xr',
                        'icon' => 'unity'
                    ),
                    array(
                        'name' => 'Three.js',
                        'description' => __('JavaScript 3D library for WebXR', 'htk'),
                        'url' => 'https://threejs.org/',
                        'icon' => 'threejs'
                    ),
                    array(
                        'name' => 'A-Frame',
                        'description' => __('Web framework for building VR experiences', 'htk'),
                        'url' => 'https://aframe.io/',
                        'icon' => 'aframe'
                    )
                )
            ),
            'learning' => array(
                'title' => __('Learning Resources', 'htk'),
                'items' => array(
                    array(
                        'name' => 'XR Development Hub',
                        'description' => __('Comprehensive XR development tutorials', 'htk'),
                        'url' => '#',
                        'icon' => 'tutorial'
                    ),
                    array(
                        'name' => 'WebXR Documentation',
                        'description' => __('Official WebXR Device API documentation', 'htk'),
                        'url' => 'https://developer.mozilla.org/en-US/docs/Web/API/WebXR_Device_API',
                        'icon' => 'docs'
                    )
                )
            ),
            'communities' => array(
                'title' => __('Communities', 'htk'),
                'items' => array(
                    array(
                        'name' => 'XR Developers Discord',
                        'description' => __('Connect with XR developers worldwide', 'htk'),
                        'url' => '#',
                        'icon' => 'discord'
                    ),
                    array(
                        'name' => 'Reddit r/WebXR',
                        'description' => __('WebXR development community', 'htk'),
                        'url' => 'https://www.reddit.com/r/WebXR/',
                        'icon' => 'reddit'
                    )
                )
            )
        );
    }

    private function init_mentors() {
        $this->mentors = array(
            array(
                'id' => 1,
                'name' => 'Sarah Johnson',
                'expertise' => 'Unity XR Development',
                'availability' => 'Weekdays',
                'bio' => 'Senior XR Developer with 5+ years of experience'
            ),
            array(
                'id' => 2,
                'name' => 'Michael Chen',
                'expertise' => 'WebXR & Three.js',
                'availability' => 'Weekends',
                'bio' => 'WebXR specialist and open source contributor'
            )
        );
    }

    public function add_resources_menu() {
        add_submenu_page(
            'htk-admin',
            __('Resources', 'htk'),
            __('Resources', 'htk'),
            'manage_options',
            'htk-resources',
            array($this, 'render_resources_page')
        );
    }

    public function enqueue_resources_assets($hook) {
        if ('htk-1-0_page_htk-resources' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'htk-resources-css',
            HTK_PLUGIN_URL . 'assets/css/admin/resources.css',
            array(),
            HTK_VERSION
        );

        wp_enqueue_script(
            'htk-resources-js',
            HTK_PLUGIN_URL . 'assets/js/admin/resources.js',
            array('jquery'),
            HTK_VERSION,
            true
        );

        wp_localize_script('htk-resources-js', 'htkResources', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htk_resources_nonce'),
            'strings' => array(
                'requestSuccess' => __('Mentor request submitted successfully!', 'htk'),
                'requestFail' => __('Failed to submit mentor request.', 'htk')
            )
        ));
    }

    public function render_resources_page() {
        ?>
        <div class="wrap htk-resources">
            <h1><?php _e('XR Development Resources', 'htk'); ?></h1>

            <!-- Resources Grid -->
            <div class="htk-resources-grid">
                <?php foreach ($this->resources as $category => $data) : ?>
                    <div class="htk-resource-category">
                        <h2><?php echo esc_html($data['title']); ?></h2>
                        <div class="htk-resource-items">
                            <?php foreach ($data['items'] as $item) : ?>
                                <a href="<?php echo esc_url($item['url']); ?>" 
                                   class="htk-resource-item"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <div class="htk-resource-icon <?php echo esc_attr($item['icon']); ?>"></div>
                                    <h3><?php echo esc_html($item['name']); ?></h3>
                                    <p><?php echo esc_html($item['description']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Mentors Section -->
            <div class="htk-mentors-section">
                <h2><?php _e('Connect with Mentors', 'htk'); ?></h2>
                <div class="htk-mentors-grid">
                    <?php foreach ($this->mentors as $mentor) : ?>
                        <div class="htk-mentor-card">
                            <div class="htk-mentor-info">
                                <h3><?php echo esc_html($mentor['name']); ?></h3>
                                <p class="htk-mentor-expertise">
                                    <?php echo esc_html($mentor['expertise']); ?>
                                </p>
                                <p class="htk-mentor-availability">
                                    <?php echo esc_html($mentor['availability']); ?>
                                </p>
                                <p class="htk-mentor-bio">
                                    <?php echo esc_html($mentor['bio']); ?>
                                </p>
                            </div>
                            <button type="button" 
                                    class="button button-primary htk-request-mentor"
                                    data-mentor-id="<?php echo esc_attr($mentor['id']); ?>">
                                <?php _e('Request Mentorship', 'htk'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Mentor Request Modal -->
        <div id="htk-mentor-modal" class="htk-modal">
            <div class="htk-modal-content">
                <div class="htk-modal-header">
                    <h2><?php _e('Request Mentorship', 'htk'); ?></h2>
                    <button type="button" class="htk-modal-close">×</button>
                </div>
                <div class="htk-modal-body">
                    <form id="htk-mentor-request-form">
                        <input type="hidden" name="mentor_id" id="mentor_id">
                        <div class="htk-form-row">
                            <label for="project_description">
                                <?php _e('Project Description', 'htk'); ?>
                            </label>
                            <textarea id="project_description" 
                                      name="project_description" 
                                      rows="4" 
                                      required></textarea>
                        </div>
                        <div class="htk-form-row">
                            <label for="help_needed">
                                <?php _e('What kind of help do you need?', 'htk'); ?>
                            </label>
                            <textarea id="help_needed" 
                                      name="help_needed" 
                                      rows="4" 
                                      required></textarea>
                        </div>
                        <div class="htk-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Submit Request', 'htk'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_submit_mentor_request() {
        check_ajax_referer('htk_resources_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $mentor_id = isset($_POST['mentor_id']) ? intval($_POST['mentor_id']) : 0;
        $project_description = isset($_POST['project_description']) ? 
            sanitize_textarea_field($_POST['project_description']) : '';
        $help_needed = isset($_POST['help_needed']) ? 
            sanitize_textarea_field($_POST['help_needed']) : '';

        if (!$mentor_id || !$project_description || !$help_needed) {
            wp_send_json_error(__('Missing required fields.', 'htk'));
        }

        // Here you would typically save the mentor request to the database
        // For now, we'll just simulate a successful request
        wp_send_json_success(array(
            'message' => __('Your mentorship request has been submitted successfully!', 'htk')
        ));
    }
}