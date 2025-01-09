<?php
namespace HTK\Testing;

class HTK_Testing_Refinement {
    private $test_results;
    private $feedback_data;

    public function __construct() {
        add_action('admin_init', array($this, 'register_testing_settings'));
        add_action('admin_menu', array($this, 'add_testing_menu'));
        add_action('wp_ajax_htk_run_tests', array($this, 'ajax_run_tests'));
        add_action('wp_ajax_htk_save_feedback', array($this, 'ajax_save_feedback'));
        add_action('wp_ajax_htk_get_test_history', array($this, 'ajax_get_test_history'));
        
        // Schedule automated tests
        if (!wp_next_scheduled('htk_automated_tests')) {
            wp_schedule_event(time(), 'daily', 'htk_automated_tests');
        }
        add_action('htk_automated_tests', array($this, 'run_automated_tests'));
    }

    public function register_testing_settings() {
        register_setting('htk_testing', 'htk_testing_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_testing_options')
        ));

        add_settings_section(
            'htk_testing_section',
            __('Testing Configuration', 'htk'),
            array($this, 'render_testing_section'),
            'htk-testing'
        );

        add_settings_field(
            'htk_test_environment',
            __('Test Environment', 'htk'),
            array($this, 'render_environment_field'),
            'htk-testing',
            'htk_testing_section'
        );

        add_settings_field(
            'htk_automated_testing',
            __('Automated Testing', 'htk'),
            array($this, 'render_automated_testing_field'),
            'htk-testing',
            'htk_testing_section'
        );
    }

    public function add_testing_menu() {
        add_submenu_page(
            'htk-admin',
            __('Testing & Refinement', 'htk'),
            __('Testing', 'htk'),
            'manage_options',
            'htk-testing',
            array($this, 'render_testing_page')
        );
    }

    public function render_testing_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_enqueue_style('htk-testing-css');
        wp_enqueue_script('htk-testing-js');
        ?>
        <div class="wrap htk-testing">
            <h1><?php _e('Testing & Refinement', 'htk'); ?></h1>

            <div class="htk-tabs">
                <nav class="htk-tabs-navigation">
                    <button class="htk-tab-button active" data-tab="functional">
                        <?php _e('Functional Testing', 'htk'); ?>
                    </button>
                    <button class="htk-tab-button" data-tab="usability">
                        <?php _e('Usability Testing', 'htk'); ?>
                    </button>
                    <button class="htk-tab-button" data-tab="compatibility">
                        <?php _e('Compatibility Testing', 'htk'); ?>
                    </button>
                    <button class="htk-tab-button" data-tab="feedback">
                        <?php _e('User Feedback', 'htk'); ?>
                    </button>
                </nav>

                <div class="htk-tab-content active" id="functional">
                    <div class="htk-card">
                        <h2><?php _e('Functional Tests', 'htk'); ?></h2>
                        <div class="htk-test-groups">
                            <?php $this->render_functional_tests(); ?>
                        </div>
                        <div class="htk-action-buttons">
                            <button class="htk-button htk-button-primary" id="htk-run-functional-tests">
                                <?php _e('Run All Tests', 'htk'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="htk-tab-content" id="usability">
                    <div class="htk-card">
                        <h2><?php _e('Usability Tests', 'htk'); ?></h2>
                        <div class="htk-usability-metrics">
                            <?php $this->render_usability_metrics(); ?>
                        </div>
                        <div class="htk-action-buttons">
                            <button class="htk-button htk-button-primary" id="htk-start-usability-test">
                                <?php _e('Start New Test', 'htk'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="htk-tab-content" id="compatibility">
                    <div class="htk-card">
                        <h2><?php _e('Compatibility Tests', 'htk'); ?></h2>
                        <div class="htk-compatibility-matrix">
                            <?php $this->render_compatibility_matrix(); ?>
                        </div>
                        <div class="htk-action-buttons">
                            <button class="htk-button htk-button-primary" id="htk-check-compatibility">
                                <?php _e('Check Compatibility', 'htk'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="htk-tab-content" id="feedback">
                    <div class="htk-card">
                        <h2><?php _e('User Feedback', 'htk'); ?></h2>
                        <div class="htk-feedback-form">
                            <?php $this->render_feedback_form(); ?>
                        </div>
                        <div class="htk-feedback-summary">
                            <?php $this->render_feedback_summary(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="htk-test-results">
                <h2><?php _e('Test Results', 'htk'); ?></h2>
                <div class="htk-results-container"></div>
            </div>
        </div>
        <?php
    }

    private function render_functional_tests() {
        $test_groups = array(
            'core' => array(
                'label' => __('Core Functionality', 'htk'),
                'tests' => array(
                    'post_types' => __('Custom Post Types', 'htk'),
                    'taxonomies' => __('Taxonomies', 'htk'),
                    'meta_boxes' => __('Meta Boxes', 'htk'),
                    'user_roles' => __('User Roles and Capabilities', 'htk')
                )
            ),
            'features' => array(
                'label' => __('Feature Tests', 'htk'),
                'tests' => array(
                    'onboarding' => __('Onboarding Flow', 'htk'),
                    'project_management' => __('Project Management', 'htk'),
                    'resources' => __('Resources Section', 'htk'),
                    'security' => __('Security Features', 'htk')
                )
            ),
            'integration' => array(
                'label' => __('Integration Tests', 'htk'),
                'tests' => array(
                    'wordpress' => __('WordPress Integration', 'htk'),
                    'database' => __('Database Operations', 'htk'),
                    'api' => __('API Endpoints', 'htk'),
                    'external_services' => __('External Services', 'htk')
                )
            )
        );

        foreach ($test_groups as $group_key => $group) {
            ?>
            <div class="htk-test-group">
                <h3><?php echo esc_html($group['label']); ?></h3>
                <div class="htk-test-list">
                    <?php foreach ($group['tests'] as $test_key => $test_label) : ?>
                        <div class="htk-test-item">
                            <label class="htk-checkbox">
                                <input type="checkbox" 
                                       name="tests[]" 
                                       value="<?php echo esc_attr($group_key . '_' . $test_key); ?>"
                                       checked>
                                <span class="htk-checkbox-label"><?php echo esc_html($test_label); ?></span>
                            </label>
                            <span class="htk-test-status"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }

    private function render_usability_metrics() {
        $metrics = array(
            'task_completion' => array(
                'label' => __('Task Completion Rate', 'htk'),
                'value' => '85%',
                'trend' => 'up'
            ),
            'time_on_task' => array(
                'label' => __('Average Time on Task', 'htk'),
                'value' => '2.5m',
                'trend' => 'down'
            ),
            'error_rate' => array(
                'label' => __('Error Rate', 'htk'),
                'value' => '3%',
                'trend' => 'down'
            ),
            'satisfaction' => array(
                'label' => __('User Satisfaction', 'htk'),
                'value' => '4.2/5',
                'trend' => 'up'
            )
        );

        foreach ($metrics as $metric_key => $metric) {
            ?>
            <div class="htk-metric-card">
                <div class="htk-metric-header">
                    <h4><?php echo esc_html($metric['label']); ?></h4>
                    <span class="htk-trend htk-trend-<?php echo esc_attr($metric['trend']); ?>"></span>
                </div>
                <div class="htk-metric-value">
                    <?php echo esc_html($metric['value']); ?>
                </div>
            </div>
            <?php
        }
    }

    private function render_compatibility_matrix() {
        $environments = array(
            'wordpress' => array(
                'label' => __('WordPress Versions', 'htk'),
                'versions' => array('5.8', '5.9', '6.0', '6.1')
            ),
            'php' => array(
                'label' => __('PHP Versions', 'htk'),
                'versions' => array('7.4', '8.0', '8.1', '8.2')
            ),
            'browsers' => array(
                'label' => __('Browsers', 'htk'),
                'versions' => array('Chrome', 'Firefox', 'Safari', 'Edge')
            )
        );

        ?>
        <table class="htk-compatibility-table">
            <thead>
                <tr>
                    <th><?php _e('Environment', 'htk'); ?></th>
                    <?php foreach ($environments['wordpress']['versions'] as $version) : ?>
                        <th><?php echo esc_html($version); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($environments as $env_key => $environment) : ?>
                    <tr>
                        <td><?php echo esc_html($environment['label']); ?></td>
                        <?php foreach ($environment['versions'] as $version) : ?>
                            <td class="htk-compatibility-status" 
                                data-environment="<?php echo esc_attr($env_key); ?>"
                                data-version="<?php echo esc_attr($version); ?>">
                                <span class="htk-status-indicator"></span>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_feedback_form() {
        ?>
        <form id="htk-feedback-form" class="htk-form">
            <div class="htk-form-group">
                <label for="feedback-type"><?php _e('Feedback Type', 'htk'); ?></label>
                <select id="feedback-type" name="feedback_type" required>
                    <option value="bug"><?php _e('Bug Report', 'htk'); ?></option>
                    <option value="feature"><?php _e('Feature Request', 'htk'); ?></option>
                    <option value="improvement"><?php _e('Improvement Suggestion', 'htk'); ?></option>
                    <option value="other"><?php _e('Other', 'htk'); ?></option>
                </select>
            </div>

            <div class="htk-form-group">
                <label for="feedback-title"><?php _e('Title', 'htk'); ?></label>
                <input type="text" id="feedback-title" name="title" required>
            </div>

            <div class="htk-form-group">
                <label for="feedback-description"><?php _e('Description', 'htk'); ?></label>
                <textarea id="feedback-description" name="description" rows="4" required></textarea>
            </div>

            <div class="htk-form-group">
                <label for="feedback-priority"><?php _e('Priority', 'htk'); ?></label>
                <select id="feedback-priority" name="priority">
                    <option value="low"><?php _e('Low', 'htk'); ?></option>
                    <option value="medium"><?php _e('Medium', 'htk'); ?></option>
                    <option value="high"><?php _e('High', 'htk'); ?></option>
                    <option value="critical"><?php _e('Critical', 'htk'); ?></option>
                </select>
            </div>

            <div class="htk-form-group">
                <label for="feedback-attachments"><?php _e('Attachments', 'htk'); ?></label>
                <input type="file" id="feedback-attachments" name="attachments[]" multiple>
            </div>

            <button type="submit" class="htk-button htk-button-primary">
                <?php _e('Submit Feedback', 'htk'); ?>
            </button>
        </form>
        <?php
    }

    private function render_feedback_summary() {
        $feedback_stats = $this->get_feedback_statistics();
        ?>
        <div class="htk-feedback-stats">
            <div class="htk-stat-card">
                <h4><?php _e('Total Feedback', 'htk'); ?></h4>
                <div class="htk-stat-value"><?php echo esc_html($feedback_stats['total']); ?></div>
            </div>
            <div class="htk-stat-card">
                <h4><?php _e('Open Issues', 'htk'); ?></h4>
                <div class="htk-stat-value"><?php echo esc_html($feedback_stats['open']); ?></div>
            </div>
            <div class="htk-stat-card">
                <h4><?php _e('Resolved', 'htk'); ?></h4>
                <div class="htk-stat-value"><?php echo esc_html($feedback_stats['resolved']); ?></div>
            </div>
        </div>

        <div class="htk-feedback-chart">
            <canvas id="htk-feedback-trends"></canvas>
        </div>
        <?php
    }

    public function ajax_run_tests() {
        check_ajax_referer('htk_testing', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $tests = isset($_POST['tests']) ? (array) $_POST['tests'] : array();
        $results = $this->run_test_suite($tests);

        wp_send_json_success($results);
    }

    private function run_test_suite($tests) {
        $results = array();

        foreach ($tests as $test) {
            $test_parts = explode('_', $test);
            $group = $test_parts[0];
            $test_name = $test_parts[1];

            $method_name = "test_{$group}_{$test_name}";
            if (method_exists($this, $method_name)) {
                try {
                    $result = $this->$method_name();
                    $results[$test] = array(
                        'status' => $result['status'],
                        'message' => $result['message']
                    );
                } catch (\Exception $e) {
                    $results[$test] = array(
                        'status' => 'error',
                        'message' => $e->getMessage()
                    );
                }
            }
        }

        $this->save_test_results($results);
        return $results;
    }

    private function save_test_results($results) {
        $history = get_option('htk_test_history', array());
        $history[] = array(
            'timestamp' => current_time('mysql'),
            'results' => $results
        );

        // Keep only last 10 test runs
        if (count($history) > 10) {
            array_shift($history);
        }

        update_option('htk_test_history', $history);
    }

    public function ajax_save_feedback() {
        check_ajax_referer('htk_testing', 'nonce');

        $feedback = array(
            'type' => sanitize_text_field($_POST['feedback_type']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'priority' => sanitize_text_field($_POST['priority']),
            'status' => 'open',
            'created_at' => current_time('mysql'),
            'user_id' => get_current_user_id()
        );

        // Handle file uploads
        if (!empty($_FILES['attachments'])) {
            $feedback['attachments'] = $this->handle_feedback_attachments($_FILES['attachments']);
        }

        $feedback_id = $this->save_feedback_to_database($feedback);

        if ($feedback_id) {
            wp_send_json_success(array('feedback_id' => $feedback_id));
        } else {
            wp_send_json_error('Failed to save feedback');
        }
    }

    private function handle_feedback_attachments($files) {
        $attachments = array();
        $upload_dir = wp_upload_dir();
        $feedback_dir = $upload_dir['basedir'] . '/htk-feedback';

        if (!file_exists($feedback_dir)) {
            wp_mkdir_p($feedback_dir);
        }

        foreach ($files['name'] as $key => $value) {
            if ($files['error'][$key] === 0) {
                $tmp_name = $files['tmp_name'][$key];
                $name = sanitize_file_name($files['name'][$key]);
                $target = $feedback_dir . '/' . $name;

                if (move_uploaded_file($tmp_name, $target)) {
                    $attachments[] = array(
                        'name' => $name,
                        'path' => str_replace($upload_dir['basedir'], '', $target),
                        'type' => $files['type'][$key]
                    );
                }
            }
        }

        return $attachments;
    }

    private function save_feedback_to_database($feedback) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_feedback';

        $result = $wpdb->insert(
            $table_name,
            array(
                'type' => $feedback['type'],
                'title' => $feedback['title'],
                'description' => $feedback['description'],
                'priority' => $feedback['priority'],
                'status' => $feedback['status'],
                'attachments' => maybe_serialize($feedback['attachments']),
                'created_at' => $feedback['created_at'],
                'user_id' => $feedback['user_id']
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    private function get_feedback_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_feedback';

        $stats = array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'open' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'open'"),
            'resolved' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'resolved'")
        );

        return $stats;
    }

    public function run_automated_tests() {
        $test_suite = array(
            'core_post_types',
            'core_taxonomies',
            'core_meta_boxes',
            'core_user_roles',
            'features_onboarding',
            'features_project_management',
            'features_resources',
            'features_security'
        );

        $results = $this->run_test_suite($test_suite);
        $this->notify_test_results($results);
    }

    private function notify_test_results($results) {
        $failed_tests = array_filter($results, function($result) {
            return $result['status'] === 'error' || $result['status'] === 'failed';
        });

        if (!empty($failed_tests)) {
            $admin_email = get_option('admin_email');
            $subject = sprintf(
                __('[%s] Automated Test Results: Failed Tests', 'htk'),
                get_bloginfo('name')
            );

            $message = __("The following tests have failed:\n\n", 'htk');
            foreach ($failed_tests as $test => $result) {
                $message .= sprintf(
                    "%s: %s\n",
                    $test,
                    $result['message']
                );
            }

            wp_mail($admin_email, $subject, $message);
        }
    }
} 