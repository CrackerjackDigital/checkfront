<?php

interface CheckfrontAPICacheInterface {
    const DefaultExpireSeconds = 0;

    /**
     * Return value from cache using name and any subsequent args as the key.
     * @param $name
     * @return mixed
     */
    public function get($name);

    /**
     * Set value using name and subsequent arguments as the key.
     *
     * @param $value
     * @param $name
     * @param int|null $expireInSeconds
     *
     * @return CheckfrontAPICacheInterface
     */
    public function set($value, $name, $expireInSeconds = self::DefaultExpireSeconds);

    /**
     * Clear cache using name and subsequent arguments as the key.
     * @param $name
     * @return CheckfrontAPICacheInterface
     */
    public function clear($name);

    /**
     * Return true or false depending on if an item exists in the cache using name and subsequent
     * arguments as the key.
     *
     * @param $name
     * @return bool
     */
    public function exists($name = null);
}