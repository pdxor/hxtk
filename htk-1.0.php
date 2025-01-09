/**
 * Plugin Name: Hackathon Tool Kit
 * Plugin URI: https://example.com/htk
 * Description: A comprehensive toolkit for managing hackathons
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: htk
 * Domain Path: /languages
 * License: GPL v2 or later
 */

namespace HTK;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HTK_VERSION', '1.0.0');
define('HTK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HTK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HTK_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
final class Plugin {
    /**
     * Single instance of the plugin
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Plugin components
     *
     * @var array
     */
    private array $components = [];

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_autoloader();
        $this->init_hooks();
        $this->load_components();
    }

    /**
     * Initialize autoloader
     */
    private function init_autoloader(): void {
        spl_autoload_register(function ($class) {
            $prefix = 'HTK\\';
            $base_dir = HTK_PLUGIN_DIR . 'includes/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Plugin initialization
        add_action('plugins_loaded', [$this, 'init_plugin']);
        add_action('init', [$this, 'load_textdomain']);

        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', [$this, 'check_version']);
            add_filter('plugin_action_links_' . HTK_PLUGIN_BASENAME, [$this, 'add_action_links']);
        }

        // Maintenance hook
        add_action('htk_daily_maintenance', [$this, 'do_maintenance']);
    }

    /**
     * Load plugin components
     */
    private function load_components(): void {
        $this->components = [
            'admin' => new Admin\Admin(),
            'post_types' => new PostTypes\PostTypes(),
            'project_management' => new ProjectManagement\ProjectManagement(),
            'security' => new Security\Security(),
            'development' => new Development\Development(),
            'ui_ux' => new UI_UX\UI_UX(),
            'resources' => new Resources\Resources(),
            'testing' => new Testing\Testing(),
        ];

        // Initialize each component
        foreach ($this->components as $component) {
            if (method_exists($component, 'init')) {
                $component->init();
            }
        }
    }

    /**
     * Plugin activation
     */
    public function activate(): void {
        require_once HTK_PLUGIN_DIR . 'includes/class-htk-activator.php';
        Activator::activate();

        // Schedule maintenance task
        if (!wp_next_scheduled('htk_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'htk_daily_maintenance');
        }

        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        require_once HTK_PLUGIN_DIR . 'includes/class-htk-deactivator.php';
        Deactivator::deactivate();

        // Clear scheduled tasks
        wp_clear_scheduled_hook('htk_daily_maintenance');
        delete_transient('htk_doing_upgrade');

        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init_plugin(): void {
        // Initialize plugin functionality
        do_action('htk_init');
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain(): void {
        load_plugin_textdomain('htk', false, dirname(HTK_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Check plugin version and run updates if necessary
     */
    public function check_version(): void {
        if (get_option('htk_version') !== HTK_VERSION) {
            require_once HTK_PLUGIN_DIR . 'includes/class-htk-upgrader.php';
            Upgrader::maybe_upgrade();
            update_option('htk_version', HTK_VERSION);
        }
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified plugin action links
     */
    public function add_action_links(array $links): array {
        $plugin_links = [
            sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=htk-settings')),
                esc_html__('Settings', 'htk')
            ),
            sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('tools.php?page=htk-tests')),
                esc_html__('Run Tests', 'htk')
            ),
        ];
        return array_merge($plugin_links, $links);
    }

    /**
     * Perform daily maintenance tasks
     */
    public function do_maintenance(): void {
        require_once HTK_PLUGIN_DIR . 'includes/class-htk-maintenance.php';
        Maintenance::run_daily_tasks();
    }

    /**
     * Get a plugin component
     *
     * @param string $component Component name
     * @return object|null Component instance or null if not found
     */
    public function get_component(string $component) {
        return $this->components[$component] ?? null;
    }
}

/**
 * Returns the main instance of the plugin
 *
 * @return Plugin
 */
function htk(): Plugin {
    return Plugin::get_instance();
}

// Initialize the plugin
htk();