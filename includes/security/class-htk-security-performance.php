<?php
namespace HTK\Security;

class HTK_Security_Performance {
    private $security_options;
    private $cache_handler;

    public function __construct() {
        // Security Hooks
        add_action('init', array($this, 'init_security_measures'));
        add_action('admin_init', array($this, 'register_security_settings'));
        add_action('admin_menu', array($this, 'add_security_menu'));
        
        // Performance Hooks
        add_action('init', array($this, 'init_performance_measures'));
        add_action('wp_ajax_htk_clear_cache', array($this, 'ajax_clear_cache'));
        add_filter('htk_query_optimization', array($this, 'optimize_queries'));
        
        // Monitoring
        add_action('admin_init', array($this, 'schedule_security_scans'));
        add_action('htk_security_scan', array($this, 'perform_security_scan'));
        
        $this->init_options();
        $this->setup_cache_handler();
    }

    private function init_options() {
        $this->security_options = get_option('htk_security_options', array(
            'enable_rate_limiting' => true,
            'max_login_attempts' => 5,
            'lockout_duration' => 15, // minutes
            'enable_2fa' => false,
            'file_upload_validation' => true,
            'allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'),
            'max_upload_size' => 5242880, // 5MB
            'enable_audit_log' => true
        ));
    }

    private function setup_cache_handler() {
        // Initialize cache handler based on available systems
        if (wp_using_ext_object_cache()) {
            $this->cache_handler = new \HTK\Cache\External_Cache_Handler();
        } else {
            $this->cache_handler = new \HTK\Cache\Transient_Cache_Handler();
        }
    }

    /**
     * Security Measures
     */
    public function init_security_measures() {
        // Rate Limiting
        if ($this->security_options['enable_rate_limiting']) {
            add_filter('wp_authenticate_user', array($this, 'check_login_attempts'), 10, 2);
            add_action('wp_login_failed', array($this, 'log_failed_attempt'));
        }

        // File Upload Security
        if ($this->security_options['file_upload_validation']) {
            add_filter('upload_mimes', array($this, 'restrict_upload_types'));
            add_filter('wp_handle_upload_prefilter', array($this, 'validate_file_upload'));
        }

        // XSS Protection
        add_filter('the_content', array($this, 'sanitize_output'));
        add_filter('the_title', array($this, 'sanitize_output'));
        add_filter('comment_text', array($this, 'sanitize_output'));

        // CSRF Protection
        add_action('init', array($this, 'start_session'));
        add_action('admin_init', array($this, 'verify_nonce'));

        // SQL Injection Prevention
        add_filter('query', array($this, 'validate_sql_query'));
    }

    public function check_login_attempts($user, $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        $attempts = get_transient('login_attempts_' . $user->user_login);
        
        if ($attempts >= $this->security_options['max_login_attempts']) {
            $lockout_time = get_transient('login_lockout_' . $user->user_login);
            
            if ($lockout_time) {
                return new \WP_Error(
                    'too_many_attempts',
                    sprintf(
                        __('Too many failed login attempts. Please try again in %d minutes.', 'htk'),
                        ceil(($lockout_time - time()) / 60)
                    )
                );
            }
        }

        return $user;
    }

    public function log_failed_attempt($username) {
        $attempts = get_transient('login_attempts_' . $username);
        $attempts = $attempts ? $attempts + 1 : 1;
        
        set_transient(
            'login_attempts_' . $username,
            $attempts,
            HOUR_IN_SECONDS
        );

        if ($attempts >= $this->security_options['max_login_attempts']) {
            set_transient(
                'login_lockout_' . $username,
                time() + ($this->security_options['lockout_duration'] * MINUTE_IN_SECONDS),
                $this->security_options['lockout_duration'] * MINUTE_IN_SECONDS
            );
        }

        if ($this->security_options['enable_audit_log']) {
            $this->log_security_event('failed_login', array(
                'username' => $username,
                'ip' => $this->get_client_ip(),
                'attempts' => $attempts
            ));
        }
    }

    public function restrict_upload_types($mime_types) {
        $allowed_types = array();
        foreach ($this->security_options['allowed_file_types'] as $ext) {
            if (isset($mime_types[$ext])) {
                $allowed_types[$ext] = $mime_types[$ext];
            }
        }
        return $allowed_types;
    }

    public function validate_file_upload($file) {
        // Check file size
        if ($file['size'] > $this->security_options['max_upload_size']) {
            $file['error'] = sprintf(
                __('File size exceeds limit of %s MB.', 'htk'),
                $this->security_options['max_upload_size'] / 1048576
            );
            return $file;
        }

        // Validate file content
        $tmp_name = $file['tmp_name'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        // Verify MIME type matches extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed_mimes = get_allowed_mime_types();
        
        if (!isset($allowed_mimes[$ext]) || $allowed_mimes[$ext] !== $mime_type) {
            $file['error'] = __('Invalid file type.', 'htk');
        }

        return $file;
    }

    public function sanitize_output($content) {
        return wp_kses_post($content);
    }

    /**
     * Performance Optimization
     */
    public function init_performance_measures() {
        // Query Optimization
        add_action('pre_get_posts', array($this, 'optimize_queries'));
        
        // Asset Optimization
        add_action('wp_enqueue_scripts', array($this, 'optimize_assets'), 999);
        
        // Database Optimization
        if (!wp_next_scheduled('htk_db_optimization')) {
            wp_schedule_event(time(), 'daily', 'htk_db_optimization');
        }
        add_action('htk_db_optimization', array($this, 'optimize_database'));
    }

    public function optimize_queries($query) {
        if (!is_admin() && $query->is_main_query()) {
            // Add only necessary fields
            $query->set('fields', 'ids');
            
            // Optimize pagination
            $query->set('no_found_rows', true);
            
            // Add specific fields needed
            $query->set('update_post_meta_cache', false);
            $query->set('update_post_term_cache', false);
            
            // Cache results
            $query->set('cache_results', true);
        }
        return $query;
    }

    public function optimize_assets() {
        // Combine and minify CSS
        global $wp_styles;
        $this->combine_assets($wp_styles, 'css');
        
        // Combine and minify JS
        global $wp_scripts;
        $this->combine_assets($wp_scripts, 'js');
    }

    private function combine_assets($wp_dependencies, $type) {
        if (!is_object($wp_dependencies)) {
            return;
        }

        $handles = array();
        foreach ($wp_dependencies->queue as $handle) {
            $handles[] = $handle;
        }

        if (empty($handles)) {
            return;
        }

        $cache_key = 'htk_combined_' . $type . '_' . md5(implode('', $handles));
        $combined = $this->cache_handler->get($cache_key);

        if (false === $combined) {
            $combined = '';
            foreach ($handles as $handle) {
                $src = $wp_dependencies->registered[$handle]->src;
                $content = file_get_contents($src);
                
                if ($type === 'css') {
                    $content = $this->minify_css($content);
                } else {
                    $content = $this->minify_js($content);
                }
                
                $combined .= $content . "\n";
            }
            
            $this->cache_handler->set($cache_key, $combined, WEEK_IN_SECONDS);
        }

        // Create combined file
        $upload_dir = wp_upload_dir();
        $combined_file = $upload_dir['basedir'] . '/cache/' . $cache_key . '.' . $type;
        
        if (!file_exists($combined_file)) {
            wp_mkdir_p(dirname($combined_file));
            file_put_contents($combined_file, $combined);
        }

        // Deregister original assets and register combined version
        foreach ($handles as $handle) {
            $wp_dependencies->dequeue($handle);
        }

        $wp_dependencies->add('htk-combined-' . $type, $combined_file);
        $wp_dependencies->enqueue('htk-combined-' . $type);
    }

    private function minify_css($css) {
        // Basic CSS minification
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//', '', $css);
        $css = str_replace(array(': ', ' {', '} '), array(':', '{', '}'), $css);
        return trim($css);
    }

    private function minify_js($js) {
        // Basic JS minification
        $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $js);
        $js = preg_replace('/\s+/', ' ', $js);
        return trim($js);
    }

    public function optimize_database() {
        global $wpdb;

        // Optimize tables
        $tables = $wpdb->get_results('SHOW TABLES');
        foreach ($tables as $table) {
            $table_name = current($table);
            $wpdb->query("OPTIMIZE TABLE $table_name");
        }

        // Clean post revisions
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->posts WHERE post_type = %s AND post_modified < %s",
                'revision',
                date('Y-m-d', strtotime('-30 days'))
            )
        );

        // Clean transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_%' AND option_value < " . time());

        // Clean autoloaded options
        $this->optimize_autoloaded_options();
    }

    private function optimize_autoloaded_options() {
        global $wpdb;
        
        $autoloaded = $wpdb->get_results(
            "SELECT option_name, length(option_value) as option_value_length 
             FROM $wpdb->options 
             WHERE autoload = 'yes' 
             ORDER BY option_value_length DESC"
        );

        foreach ($autoloaded as $option) {
            if ($option->option_value_length > 1000) {
                update_option($option->option_name, get_option($option->option_name), 'no');
            }
        }
    }

    /**
     * Security Monitoring
     */
    public function schedule_security_scans() {
        if (!wp_next_scheduled('htk_security_scan')) {
            wp_schedule_event(time(), 'daily', 'htk_security_scan');
        }
    }

    public function perform_security_scan() {
        $issues = array();

        // File permission check
        $this->check_file_permissions($issues);
        
        // Core file integrity check
        $this->check_core_integrity($issues);
        
        // Plugin vulnerability check
        $this->check_plugin_vulnerabilities($issues);
        
        // Save scan results
        update_option('htk_security_scan_results', array(
            'timestamp' => current_time('timestamp'),
            'issues' => $issues
        ));

        // Send notification if issues found
        if (!empty($issues)) {
            $this->send_security_notification($issues);
        }
    }

    private function check_file_permissions(&$issues) {
        $files_to_check = array(
            ABSPATH . 'wp-config.php' => '0400',
            ABSPATH . 'wp-includes' => '0755',
            ABSPATH . 'wp-admin' => '0755',
            WP_CONTENT_DIR => '0755'
        );

        foreach ($files_to_check as $file => $required_perms) {
            if (file_exists($file)) {
                $actual_perms = substr(sprintf('%o', fileperms($file)), -4);
                if ($actual_perms != $required_perms) {
                    $issues[] = array(
                        'type' => 'file_permission',
                        'file' => $file,
                        'current' => $actual_perms,
                        'required' => $required_perms
                    );
                }
            }
        }
    }

    private function check_core_integrity(&$issues) {
        require_once(ABSPATH . 'wp-admin/includes/update.php');
        $core_checksums = get_core_checksums();

        if (is_array($core_checksums)) {
            foreach ($core_checksums as $file => $checksum) {
                $file_path = ABSPATH . $file;
                if (file_exists($file_path)) {
                    if (md5_file($file_path) !== $checksum) {
                        $issues[] = array(
                            'type' => 'core_integrity',
                            'file' => $file
                        );
                    }
                }
            }
        }
    }

    private function check_plugin_vulnerabilities(&$issues) {
        $plugins = get_plugins();
        $api_url = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information';

        foreach ($plugins as $plugin_file => $plugin_data) {
            $slug = dirname($plugin_file);
            $response = wp_remote_get($api_url . '&slug=' . $slug);

            if (!is_wp_error($response)) {
                $plugin_info = json_decode(wp_remote_retrieve_body($response));
                if (isset($plugin_info->last_updated)) {
                    $last_update = strtotime($plugin_info->last_updated);
                    if ($last_update < strtotime('-1 year')) {
                        $issues[] = array(
                            'type' => 'plugin_vulnerability',
                            'plugin' => $plugin_data['Name'],
                            'last_update' => $plugin_info->last_updated
                        );
                    }
                }
            }
        }
    }

    private function send_security_notification($issues) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(
            __('[%s] Security Scan Results: Issues Found', 'htk'),
            get_bloginfo('name')
        );

        $message = __("The following security issues were found:\n\n", 'htk');
        
        foreach ($issues as $issue) {
            switch ($issue['type']) {
                case 'file_permission':
                    $message .= sprintf(
                        __("File Permission Issue:\nFile: %s\nCurrent: %s\nRequired: %s\n\n", 'htk'),
                        $issue['file'],
                        $issue['current'],
                        $issue['required']
                    );
                    break;
                    
                case 'core_integrity':
                    $message .= sprintf(
                        __("Core File Modified:\nFile: %s\n\n", 'htk'),
                        $issue['file']
                    );
                    break;
                    
                case 'plugin_vulnerability':
                    $message .= sprintf(
                        __("Plugin Security Concern:\nPlugin: %s\nLast Updated: %s\n\n", 'htk'),
                        $issue['plugin'],
                        $issue['last_update']
                    );
                    break;
            }
        }

        wp_mail($admin_email, $subject, $message);
    }

    private function log_security_event($type, $data) {
        if (!$this->security_options['enable_audit_log']) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_security_log';

        $wpdb->insert(
            $table_name,
            array(
                'event_type' => $type,
                'event_data' => maybe_serialize($data),
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
    }

    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (isset($_SERVER[$header])) {
                $ip = array_map('trim', explode(',', $_SERVER[$header]))[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
} 