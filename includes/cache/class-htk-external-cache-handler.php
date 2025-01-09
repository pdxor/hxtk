<?php
namespace HTK\Cache;

class External_Cache_Handler implements HTK_Cache_Handler {
    /**
     * Get a value from the external object cache
     *
     * @param string $key Cache key
     * @return mixed|false The cached value or false if not found
     */
    public function get($key) {
        return wp_cache_get($key, 'htk');
    }

    /**
     * Set a value in the external object cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $expiration Time until expiration in seconds
     * @return bool True on success, false on failure
     */
    public function set($key, $value, $expiration = 0) {
        return wp_cache_set($key, $value, 'htk', $expiration);
    }

    /**
     * Delete a value from the external object cache
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public function delete($key) {
        return wp_cache_delete($key, 'htk');
    }

    /**
     * Flush all cached items from the external object cache
     *
     * @return bool True on success, false on failure
     */
    public function flush() {
        return wp_cache_flush();
    }
} 