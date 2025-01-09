<?php
/**
 * Plugin upgrade functionality
 */

namespace HTK;

/**
 * Class Upgrader
 * Handles plugin upgrades and database migrations
 */
class Upgrader {
    /**
     * Check and perform upgrades if necessary
     */
    public static function maybe_upgrade(): void {
        $current_version = get_option('htk_version', '0.0.0');
        $doing_upgrade = get_transient('htk_doing_upgrade');

        if ($doing_upgrade) {
            return;
        }

        set_transient('htk_doing_upgrade', true, 5 * MINUTE_IN_SECONDS);

        try {
            if (version_compare($current_version, '1.0.0', '<')) {
                self::upgrade_to_100();
            }

            // Add future version upgrades here
            // if (version_compare($current_version, '1.1.0', '<')) {
            //     self::upgrade_to_110();
            // }

            delete_transient('htk_doing_upgrade');
        } catch (\Exception $e) {
            delete_transient('htk_doing_upgrade');
            error_log('HTK Upgrade Error: ' . $e->getMessage());
        }
    }

    /**
     * Upgrade to version 1.0.0
     */
    private static function upgrade_to_100(): void {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Add new columns to tasks table
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}htk_tasks 
                ADD COLUMN IF NOT EXISTS priority varchar(20) AFTER status,
                ADD COLUMN IF NOT EXISTS updated_at datetime 
                    DEFAULT CURRENT_TIMESTAMP 
                    ON UPDATE CURRENT_TIMESTAMP"
            );

            // Set default priority for existing tasks
            $wpdb->query(
                "UPDATE {$wpdb->prefix}htk_tasks 
                SET priority = 'medium' 
                WHERE priority IS NULL"
            );

            // Add indexes for better performance
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}htk_tasks
                ADD INDEX IF NOT EXISTS hackathon_id (hackathon_id),
                ADD INDEX IF NOT EXISTS assigned_to (assigned_to)"
            );

            // Add new columns to ideas table
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}htk_ideas
                ADD COLUMN IF NOT EXISTS updated_at datetime 
                    DEFAULT CURRENT_TIMESTAMP 
                    ON UPDATE CURRENT_TIMESTAMP"
            );

            // Add indexes for ideas table
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}htk_ideas
                ADD INDEX IF NOT EXISTS hackathon_id (hackathon_id),
                ADD INDEX IF NOT EXISTS submitted_by (submitted_by)"
            );

            // Add new columns to timeline events table
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}htk_timeline_events
                ADD COLUMN IF NOT EXISTS updated_at datetime 
                    DEFAULT CURRENT_TIMESTAMP 
                    ON UPDATE CURRENT_TIMESTAMP"
            );

            // Add indexes for timeline events table
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}htk_timeline_events
                ADD INDEX IF NOT EXISTS hackathon_id (hackathon_id)"
            );

            // Update capabilities
            $admin = get_role('administrator');
            if ($admin) {
                $admin->add_cap('manage_hackathon_settings');
            }

            $organizer = get_role('hackathon_organizer');
            if ($organizer) {
                $organizer->add_cap('manage_hackathon_timeline');
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            // Log successful upgrade
            error_log('HTK: Successfully upgraded to version 1.0.0');
        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log('HTK Upgrade Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example method for future upgrades
     */
    private static function upgrade_to_110(): void {
        // Add upgrade logic for version 1.1.0
    }
} 