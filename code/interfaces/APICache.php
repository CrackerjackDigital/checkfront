<?php

interface CheckfrontAPICacheInterface {

    /**
     *
     * @param $periodInSeconds
     * @return CheckfrontAPICacheInterface
     */
    public function expire($periodInSeconds);
    /**
     * Return value from cache using name and any subsequent args as the key.
     * @param $name
     * @return mixed
     */
    public function get($name);

    /**
     * Set value using name and subsequent arguments as the key.
     * @param $name
     * @param $value
     * @return CheckfrontAPICacheInterface
     */
    public function set($value, $name);

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