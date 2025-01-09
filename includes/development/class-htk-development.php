<?php
namespace HTK\Development;

class HTK_Development {
    private $code_templates;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_development_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_development_assets'));
        add_action('wp_ajax_htk_save_code', array($this, 'ajax_save_code'));
        add_action('wp_ajax_htk_load_template', array($this, 'ajax_load_template'));
        add_action('wp_ajax_htk_git_operation', array($this, 'ajax_git_operation'));
        
        $this->init_code_templates();
    }

    private function init_code_templates() {
        $this->code_templates = array(
            'unity' => array(
                'basic-vr' => array(
                    'name' => __('Basic VR Setup', 'htk'),
                    'description' => __('Basic VR scene setup with interaction system', 'htk'),
                    'language' => 'csharp',
                    'file' => 'unity-basic-vr.cs'
                ),
                'hand-tracking' => array(
                    'name' => __('Hand Tracking', 'htk'),
                    'description' => __('Hand tracking implementation for VR', 'htk'),
                    'language' => 'csharp',
                    'file' => 'unity-hand-tracking.cs'
                )
            ),
            'webxr' => array(
                'basic-scene' => array(
                    'name' => __('Basic WebXR Scene', 'htk'),
                    'description' => __('Basic WebXR scene with Three.js', 'htk'),
                    'language' => 'javascript',
                    'file' => 'webxr-basic-scene.js'
                ),
                'ar-scene' => array(
                    'name' => __('AR Scene', 'htk'),
                    'description' => __('WebXR AR scene with object placement', 'htk'),
                    'language' => 'javascript',
                    'file' => 'webxr-ar-scene.js'
                )
            ),
            'unreal' => array(
                'basic-vr' => array(
                    'name' => __('Basic VR Setup', 'htk'),
                    'description' => __('Basic VR pawn and gameplay setup', 'htk'),
                    'language' => 'cpp',
                    'file' => 'unreal-basic-vr.cpp'
                ),
                'motion-controller' => array(
                    'name' => __('Motion Controller', 'htk'),
                    'description' => __('Motion controller implementation', 'htk'),
                    'language' => 'cpp',
                    'file' => 'unreal-motion-controller.cpp'
                )
            )
        );
    }

    public function add_development_menu() {
        add_submenu_page(
            'htk-admin',
            __('Development', 'htk'),
            __('Development', 'htk'),
            'manage_options',
            'htk-development',
            array($this, 'render_development_page')
        );
    }

    public function enqueue_development_assets($hook) {
        if ('htk-1-0_page_htk-development' !== $hook) {
            return;
        }

        // Monaco Editor
        wp_enqueue_style(
            'monaco-editor',
            'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.33.0/min/vs/editor/editor.main.min.css',
            array(),
            '0.33.0'
        );

        wp_enqueue_script(
            'monaco-editor-loader',
            'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.33.0/min/vs/loader.min.js',
            array(),
            '0.33.0',
            true
        );

        // Our assets
        wp_enqueue_style(
            'htk-development-css',
            HTK_PLUGIN_URL . 'assets/css/admin/development.css',
            array(),
            HTK_VERSION
        );

        wp_enqueue_script(
            'htk-development-js',
            HTK_PLUGIN_URL . 'assets/js/admin/development.js',
            array('jquery'),
            HTK_VERSION,
            true
        );

        wp_localize_script('htk-development-js', 'htkDev', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htk_development_nonce'),
            'templates' => $this->code_templates,
            'strings' => array(
                'saveSuccess' => __('Code saved successfully!', 'htk'),
                'saveFail' => __('Failed to save code.', 'htk'),
                'gitSuccess' => __('Git operation completed successfully!', 'htk'),
                'gitFail' => __('Git operation failed.', 'htk'),
                'confirmDiscard' => __('Are you sure you want to discard your changes?', 'htk')
            )
        ));
    }

    public function render_development_page() {
        ?>
        <div class="wrap htk-development">
            <h1><?php _e('Development Environment', 'htk'); ?></h1>

            <div class="htk-dev-container">
                <!-- Sidebar -->
                <div class="htk-dev-sidebar">
                    <!-- Code Templates -->
                    <div class="htk-templates">
                        <h2><?php _e('Code Templates', 'htk'); ?></h2>
                        <div class="htk-template-list">
                            <?php foreach ($this->code_templates as $category => $templates) : ?>
                                <div class="htk-template-category">
                                    <h3><?php echo esc_html(ucfirst($category)); ?></h3>
                                    <?php foreach ($templates as $id => $template) : ?>
                                        <div class="htk-template-item" 
                                             data-id="<?php echo esc_attr($id); ?>"
                                             data-category="<?php echo esc_attr($category); ?>">
                                            <h4><?php echo esc_html($template['name']); ?></h4>
                                            <p><?php echo esc_html($template['description']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Git Integration -->
                    <div class="htk-git-integration">
                        <h2><?php _e('Version Control', 'htk'); ?></h2>
                        <div class="htk-git-controls">
                            <button type="button" class="button htk-git-btn" data-action="status">
                                <?php _e('Status', 'htk'); ?>
                            </button>
                            <button type="button" class="button htk-git-btn" data-action="stage">
                                <?php _e('Stage Changes', 'htk'); ?>
                            </button>
                            <button type="button" class="button htk-git-btn" data-action="commit">
                                <?php _e('Commit', 'htk'); ?>
                            </button>
                            <button type="button" class="button htk-git-btn" data-action="push">
                                <?php _e('Push', 'htk'); ?>
                            </button>
                        </div>
                        <div class="htk-git-output"></div>
                    </div>
                </div>

                <!-- Main Editor Area -->
                <div class="htk-dev-main">
                    <div class="htk-editor-controls">
                        <select id="htk-language-select">
                            <option value="javascript">JavaScript</option>
                            <option value="csharp">C#</option>
                            <option value="cpp">C++</option>
                            <option value="python">Python</option>
                        </select>
                        <button type="button" class="button htk-save-code">
                            <?php _e('Save', 'htk'); ?>
                        </button>
                    </div>
                    <div id="htk-code-editor"></div>
                </div>

                <!-- Debug Console -->
                <div class="htk-dev-console">
                    <div class="htk-console-header">
                        <h2><?php _e('Debug Console', 'htk'); ?></h2>
                        <button type="button" class="button htk-clear-console">
                            <?php _e('Clear', 'htk'); ?>
                        </button>
                    </div>
                    <div class="htk-console-output"></div>
                </div>
            </div>
        </div>

        <!-- Git Commit Modal -->
        <div id="htk-git-modal" class="htk-modal">
            <div class="htk-modal-content">
                <div class="htk-modal-header">
                    <h2><?php _e('Commit Changes', 'htk'); ?></h2>
                    <button type="button" class="htk-modal-close">×</button>
                </div>
                <div class="htk-modal-body">
                    <form id="htk-commit-form">
                        <div class="htk-form-row">
                            <label for="commit_message"><?php _e('Commit Message', 'htk'); ?></label>
                            <textarea id="commit_message" name="message" rows="4" required></textarea>
                        </div>
                        <div class="htk-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Commit', 'htk'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_save_code() {
        check_ajax_referer('htk_development_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $code = isset($_POST['code']) ? $_POST['code'] : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';
        $filename = isset($_POST['filename']) ? sanitize_file_name($_POST['filename']) : '';

        if (!$code || !$language || !$filename) {
            wp_send_json_error(__('Missing required parameters.', 'htk'));
        }

        // Create development directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $dev_dir = $upload_dir['basedir'] . '/htk/development';
        wp_mkdir_p($dev_dir);

        // Save the code file
        $file_path = $dev_dir . '/' . $filename;
        if (file_put_contents($file_path, $code)) {
            wp_send_json_success(array(
                'message' => __('Code saved successfully!', 'htk'),
                'path' => $file_path
            ));
        }

        wp_send_json_error(__('Failed to save code.', 'htk'));
    }

    public function ajax_load_template() {
        check_ajax_referer('htk_development_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';

        if (!isset($this->code_templates[$category][$template_id])) {
            wp_send_json_error(__('Template not found.', 'htk'));
        }

        $template = $this->code_templates[$category][$template_id];
        $template_file = HTK_PLUGIN_DIR . 'includes/development/templates/' . $template['file'];

        if (!file_exists($template_file)) {
            wp_send_json_error(__('Template file not found.', 'htk'));
        }

        $content = file_get_contents($template_file);
        wp_send_json_success(array(
            'content' => $content,
            'language' => $template['language']
        ));
    }

    public function ajax_git_operation() {
        check_ajax_referer('htk_development_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $action = isset($_POST['git_action']) ? sanitize_text_field($_POST['git_action']) : '';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';

        $upload_dir = wp_upload_dir();
        $dev_dir = $upload_dir['basedir'] . '/htk/development';

        switch ($action) {
            case 'status':
                $output = $this->git_status($dev_dir);
                break;
            case 'stage':
                $output = $this->git_stage($dev_dir);
                break;
            case 'commit':
                if (!$message) {
                    wp_send_json_error(__('Commit message is required.', 'htk'));
                }
                $output = $this->git_commit($dev_dir, $message);
                break;
            case 'push':
                $output = $this->git_push($dev_dir);
                break;
            default:
                wp_send_json_error(__('Invalid git action.', 'htk'));
        }

        if ($output['status'] === 0) {
            wp_send_json_success(array(
                'message' => $output['output']
            ));
        }

        wp_send_json_error(array(
            'message' => $output['error']
        ));
    }

    private function git_status($dir) {
        return $this->execute_git_command($dir, 'git status');
    }

    private function git_stage($dir) {
        return $this->execute_git_command($dir, 'git add .');
    }

    private function git_commit($dir, $message) {
        return $this->execute_git_command($dir, "git commit -m " . escapeshellarg($message));
    }

    private function git_push($dir) {
        return $this->execute_git_command($dir, 'git push');
    }

    private function execute_git_command($dir, $command) {
        $output = array();
        $return_var = 0;

        $current_dir = getcwd();
        chdir($dir);
        
        exec($command . ' 2>&1', $output, $return_var);
        
        chdir($current_dir);

        return array(
            'status' => $return_var,
            'output' => implode("\n", $output),
            'error' => $return_var !== 0 ? end($output) : ''
        );
    }
} 