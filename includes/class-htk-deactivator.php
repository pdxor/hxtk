<?php
/**
 * Plugin deactivation functionality
 */

namespace HTK;

/**
 * Class Deactivator
 * Handles plugin deactivation tasks
 */
class Deactivator {
    /**
     * Deactivate the plugin
     */
    public static function deactivate(): void {
        self::cleanup_roles_capabilities();
        self::cleanup_scheduled_tasks();
        self::cleanup_transients();
    }

    /**
     * Clean up roles and capabilities
     */
    private static function cleanup_roles_capabilities(): void {
        // Remove custom roles
        remove_role('hackathon_organizer');
        remove_role('hackathon_participant');

        // Remove capabilities from administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('manage_hackathon');
            $admin->remove_cap('participate_hackathon');
        }
    }

    /**
     * Clean up scheduled tasks
     */
    private static function cleanup_scheduled_tasks(): void {
        wp_clear_scheduled_hook('htk_daily_maintenance');
        wp_clear_scheduled_hook('htk_hourly_cleanup');
    }

    /**
     * Clean up transients
     */
    private static function cleanup_transients(): void {
        global $wpdb;

        // Delete all plugin-specific transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $wpdb->esc_like('_transient_htk_') . '%',
                $wpdb->esc_like('_transient_timeout_htk_') . '%'
            )
        );
    }
} 