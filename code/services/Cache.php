<?php

/**
 * Base/null cache doesn't cache.
 */
class CheckfrontCache extends Object implements CheckfrontAPICacheInterface {
    const KeySeperator = '_';
    const CachePrefix = 'CKF_';

    private static $cache_prefix = self::CachePrefix;

    protected $expireInSeconds = null;


    /**
     * Set the expiry period in seconds for the next set operation. Set needs to clear this.
     *
     * @param $periodInSeconds
     * @return CheckfrontAPICacheInterface
     */
    public function expire($periodInSeconds) {
        $this->expireInSeconds = $periodInSeconds;
        return $this;
    }
    /**
     * Return decorated key using name and and extra values passed as part of key.
     *
     * Exposes internal workings, use with caution.
     *
     * @param array $values
     * @return string
     */
    public function key(array $values)
    {
        return implode(static::KeySeperator, $values);
    }

    /**
     * Return value from cache using name and any subsequent args as the key.
     * @param $name
     * @return mixed|null
     */
    public function get($name)
    {
        return null;
    }

    /**
     * Set value using name and subsequent arguments as the key.
     * @param $name
     * @param $value
     * @return CheckfrontAPICacheInterface
     * @fluent
     */
    public function set($value, $name)
    {
        $this->expireInSeconds = null;
        return $this;
    }

    /**
     * Clear cache using name and subsequent arguments as the key.
     * @param $name
     * @return CheckfrontAPICacheInterface
     * @fluent
     */
    public function clear($name)
    {
        $this->expireInSeconds = null;
        return $this;
    }

    /**
     * For NullCache always returns false.
     *
     * @param $name
     * @return mixed
     */
    public function exists($name = null)
    {
        return false;
    }
}