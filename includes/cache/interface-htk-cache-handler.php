<?php
namespace HTK\Cache;

interface HTK_Cache_Handler {
    /**
     * Get a value from cache
     *
     * @param string $key Cache key
     * @return mixed|false The cached value or false if not found
     */
    public function get($key);

    /**
     * Set a value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $expiration Time until expiration in seconds
     * @return bool True on success, false on failure
     */
    public function set($key, $value, $expiration = 0);

    /**
     * Delete a value from cache
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public function delete($key);

    /**
     * Flush all cached items
     *
     * @return bool True on success, false on failure
     */
    public function flush();
} 