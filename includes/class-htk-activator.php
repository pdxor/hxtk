<?php
/**
 * Plugin activation functionality
 */

namespace HTK;

/**
 * Class Activator
 * Handles plugin activation tasks
 */
class Activator {
    /**
     * Activate the plugin
     */
    public static function activate(): void {
        self::register_roles_capabilities();
        self::create_database_tables();
        self::setup_upload_directory();
    }

    /**
     * Register roles and capabilities
     */
    private static function register_roles_capabilities(): void {
        // Add hackathon organizer role
        add_role(
            'hackathon_organizer',
            __('Hackathon Organizer', 'htk'),
            [
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'upload_files' => true,
                'manage_hackathon' => true,
            ]
        );

        // Add hackathon participant role
        add_role(
            'hackathon_participant',
            __('Hackathon Participant', 'htk'),
            [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => true,
                'participate_hackathon' => true,
            ]
        );

        // Add capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_hackathon');
            $admin->add_cap('participate_hackathon');
        }
    }

    /**
     * Create required database tables
     */
    private static function create_database_tables(): void {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Tasks table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}htk_tasks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hackathon_id bigint(20) NOT NULL,
            title text NOT NULL,
            description longtext,
            assigned_to bigint(20),
            status varchar(20) NOT NULL,
            due_date datetime,
            priority varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY hackathon_id (hackathon_id),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
        dbDelta($sql);

        // Ideas table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}htk_ideas (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hackathon_id bigint(20) NOT NULL,
            title text NOT NULL,
            description longtext,
            submitted_by bigint(20),
            votes int DEFAULT 0,
            status varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY hackathon_id (hackathon_id),
            KEY submitted_by (submitted_by)
        ) $charset_collate;";
        dbDelta($sql);

        // Timeline events table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}htk_timeline_events (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hackathon_id bigint(20) NOT NULL,
            title text NOT NULL,
            description longtext,
            start_date datetime,
            end_date datetime,
            event_type varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY hackathon_id (hackathon_id)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Setup upload directory and security
     */
    private static function setup_upload_directory(): void {
        $upload_dir = wp_upload_dir();
        $htk_upload_dir = $upload_dir['basedir'] . '/htk';

        // Create directory if it doesn't exist
        if (!file_exists($htk_upload_dir)) {
            wp_mkdir_p($htk_upload_dir);
        }

        // Create .htaccess for security
        $htaccess_file = $htk_upload_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Create index.php for additional security
        $index_file = $htk_upload_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
} 