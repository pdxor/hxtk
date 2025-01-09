<?php
namespace HTK\Cache;

class Transient_Cache_Handler implements HTK_Cache_Handler {
    /**
     * Prefix for all transient keys
     *
     * @var string
     */
    private $prefix = 'htk_cache_';

    /**
     * Get a value from the WordPress transients
     *
     * @param string $key Cache key
     * @return mixed|false The cached value or false if not found
     */
    public function get($key) {
        return get_transient($this->prefix . $key);
    }

    /**
     * Set a value in the WordPress transients
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $expiration Time until expiration in seconds
     * @return bool True on success, false on failure
     */
    public function set($key, $value, $expiration = 0) {
        // If no expiration is set, use one week as default
        if ($expiration === 0) {
            $expiration = WEEK_IN_SECONDS;
        }
        return set_transient($this->prefix . $key, $value, $expiration);
    }

    /**
     * Delete a value from the WordPress transients
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public function delete($key) {
        return delete_transient($this->prefix . $key);
    }

    /**
     * Flush all cached items from the WordPress transients
     *
     * @return bool True on success, false on failure
     */
    public function flush() {
        global $wpdb;
        
        $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE %s";
        $result = $wpdb->query($wpdb->prepare($sql, '_transient_' . $this->prefix . '%'));
        
        // Also delete timeout entries
        $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql, '_transient_timeout_' . $this->prefix . '%'));
        
        return $result !== false;
    }

    /**
     * Clean expired transients
     *
     * @return bool True on success, false on failure
     */
    public function clean_expired() {
        global $wpdb;
        
        $time = time();
        $sql = "DELETE a, b FROM $wpdb->options a
                INNER JOIN $wpdb->options b
                WHERE a.option_name LIKE %s
                AND b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                AND b.option_value < %d";
                
        $result = $wpdb->query($wpdb->prepare(
            $sql,
            $wpdb->esc_like('_transient_' . $this->prefix) . '%',
            $time
        ));
        
        return $result !== false;
    }
} 