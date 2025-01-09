<?php
namespace HTK\Tests;

class HTK_Test_Suite {
    private $test_results = array();
    private $errors = array();

    public function __construct() {
        add_action('admin_menu', array($this, 'add_test_menu'));
    }

    public function render_test_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('HTK Integration Tests', 'htk'); ?></h1>
            
            <div class="htk-test-controls">
                <button id="run-tests" class="button button-primary">
                    <?php _e('Run Tests', 'htk'); ?>
                </button>
            </div>

            <div class="htk-test-results">
                <div class="htk-test-summary"></div>
                <div class="htk-test-details"></div>
            </div>
        </div>
        <?php
    }

    private function test_plugin_dependencies() {
        $required_plugins = array(
            'advanced-custom-fields/acf.php' => '5.0'
        );

        $missing_plugins = array();
        foreach ($required_plugins as $plugin => $version) {
            if (!is_plugin_active($plugin)) {
                $missing_plugins[] = $plugin;
            } else {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                if (version_compare($plugin_data['Version'], $version, '<')) {
                    $missing_plugins[] = sprintf(
                        '%s (requires version %s)',
                        $plugin_data['Name'],
                        $version
                    );
                }
            }
        }

        return array(
            'status' => empty($missing_plugins),
            'message' => empty($missing_plugins)
                ? __('All plugin dependencies are satisfied', 'htk')
                : sprintf(__('Missing or incompatible plugins: %s', 'htk'), implode(', ', $missing_plugins))
        );
    }

    private function test_rest_api() {
        $endpoints = rest_get_server()->get_routes('htk/v1');
        $required_endpoints = array(
            '/htk/v1/hackathons',
            '/htk/v1/participants'
        );

        $missing_endpoints = array_diff($required_endpoints, array_keys($endpoints));

        return array(
            'status' => empty($missing_endpoints),
            'message' => empty($missing_endpoints)
                ? __('All REST API endpoints are registered', 'htk')
                : sprintf(__('Missing endpoints: %s', 'htk'), implode(', ', $missing_endpoints))
        );
    }

    private function test_content_integration() {
        $post_types = array('hackathon', 'hackathon_participant');
        $integration_issues = array();

        foreach ($post_types as $post_type) {
            // Check if post type supports required features
            $post_type_obj = get_post_type_object($post_type);
            if (!$post_type_obj) {
                $integration_issues[] = sprintf(__('Post type %s not found', 'htk'), $post_type);
                continue;
            }

            // Check required features
            $required_features = array('title', 'editor', 'custom-fields');
            foreach ($required_features as $feature) {
                if (!post_type_supports($post_type, $feature)) {
                    $integration_issues[] = sprintf(
                        __('Post type %s does not support %s', 'htk'),
                        $post_type,
                        $feature
                    );
                }
            }

            // Check taxonomy integration
            $taxonomies = get_object_taxonomies($post_type);
            $required_taxonomies = array('project_category');
            $missing_taxonomies = array_diff($required_taxonomies, $taxonomies);
            if (!empty($missing_taxonomies)) {
                $integration_issues[] = sprintf(
                    __('Post type %s is missing taxonomies: %s', 'htk'),
                    $post_type,
                    implode(', ', $missing_taxonomies)
                );
            }
        }

        return array(
            'status' => empty($integration_issues),
            'message' => empty($integration_issues)
                ? __('Content integration is properly configured', 'htk')
                : implode('. ', $integration_issues)
        );
    }

    private function test_file_permissions() {
        $upload_dir = wp_upload_dir();
        $required_dirs = array(
            HTK_PLUGIN_DIR . 'uploads',
            $upload_dir['basedir'] . '/htk'
        );

        $permission_issues = array();
        foreach ($required_dirs as $dir) {
            if (!file_exists($dir)) {
                if (!wp_mkdir_p($dir)) {
                    $permission_issues[] = sprintf(
                        __('Could not create directory: %s', 'htk'),
                        $dir
                    );
                    continue;
                }
            }

            if (!wp_is_writable($dir)) {
                $permission_issues[] = sprintf(
                    __('Directory not writable: %s', 'htk'),
                    $dir
                );
            }
        }

        return array(
            'status' => empty($permission_issues),
            'message' => empty($permission_issues)
                ? __('File permissions are correctly set', 'htk')
                : implode('. ', $permission_issues)
        );
    }

    public function ajax_run_tests() {
        check_ajax_referer('htk_run_tests', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'htk'));
        }

        $results = $this->run_tests();
        wp_send_json_success($results);
    }

    private function get_test_summary($results) {
        $total = count($results);
        $passed = count(array_filter($results, function($result) {
            return $result['status'] === true;
        }));

        return array(
            'total' => $total,
            'passed' => $passed,
            'failed' => $total - $passed
        );
    }
}