<?php
/**
 * Plugin maintenance functionality
 */

namespace HTK;

/**
 * Class Maintenance
 * Handles plugin maintenance tasks
 */
class Maintenance {
    /**
     * Run daily maintenance tasks
     */
    public static function run_daily_tasks(): void {
        self::cleanup_old_notifications();
        self::cleanup_transients();
        self::cleanup_old_logs();
        self::optimize_tables();
    }

    /**
     * Clean up old notifications
     */
    private static function cleanup_old_notifications(): void {
        global $wpdb;

        // Remove notifications older than 30 days
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_value LIKE %s 
                AND CAST(
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(option_value, '\"created\":', -1), 
                        '}', 
                        1
                    ) AS UNSIGNED
                ) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))",
                $wpdb->esc_like('htk_notification_') . '%',
                '%"created":"%'
            )
        );
    }

    /**
     * Clean up transients
     */
    private static function cleanup_transients(): void {
        global $wpdb;

        // Delete expired transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_name NOT LIKE %s",
                $wpdb->esc_like('_transient_htk_') . '%',
                $wpdb->esc_like('_transient_htk_doing_') . '%'
            )
        );

        // Delete expired timeout options
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_timeout_htk_') . '%'
            )
        );
    }

    /**
     * Clean up old logs
     */
    private static function cleanup_old_logs(): void {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/htk/logs';

        if (!is_dir($log_dir)) {
            return;
        }

        $files = glob($log_dir . '/*.log');
        $now = time();

        foreach ($files as $file) {
            // Remove logs older than 30 days
            if ($now - filemtime($file) >= 30 * DAY_IN_SECONDS) {
                @unlink($file);
            }
        }
    }

    /**
     * Optimize database tables
     */
    private static function optimize_tables(): void {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'htk_tasks',
            $wpdb->prefix . 'htk_ideas',
            $wpdb->prefix . 'htk_timeline_events'
        ];

        foreach ($tables as $table) {
            // Check if table exists
            $table_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $table
                )
            );

            if ($table_exists) {
                // Optimize table
                $wpdb->query("OPTIMIZE TABLE {$table}");
            }
        }
    }

    /**
     * Run hourly cleanup tasks
     */
    public static function run_hourly_tasks(): void {
        self::cleanup_temp_files();
        self::check_upload_directory();
    }

    /**
     * Clean up temporary files
     */
    private static function cleanup_temp_files(): void {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/htk/temp';

        if (!is_dir($temp_dir)) {
            return;
        }

        $files = glob($temp_dir . '/*');
        $now = time();

        foreach ($files as $file) {
            // Remove temp files older than 1 hour
            if ($now - filemtime($file) >= HOUR_IN_SECONDS) {
                @unlink($file);
            }
        }
    }

    /**
     * Check and secure upload directory
     */
    private static function check_upload_directory(): void {
        $upload_dir = wp_upload_dir();
        $htk_upload_dir = $upload_dir['basedir'] . '/htk';

        // Ensure directory exists
        if (!file_exists($htk_upload_dir)) {
            wp_mkdir_p($htk_upload_dir);
        }

        // Check .htaccess
        $htaccess_file = $htk_upload_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Check index.php
        $index_file = $htk_upload_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }

        // Set proper permissions
        @chmod($htk_upload_dir, 0755);
        @chmod($htaccess_file, 0644);
        @chmod($index_file, 0644);
    }
} 