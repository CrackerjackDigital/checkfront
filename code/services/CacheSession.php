<?php

/**
 * Implements a key-value cache based on PHP/SilverStripe Session.
 */
class CheckfrontCacheSession extends CheckfrontCache
    implements CheckfrontAPICacheInterface {
    /**
     * Return true or false depending on if an item exists in the cache.
     *
     * @param $name
     *
     * @return boolean
     */
    public function exists($name = null) {
        return (bool)Session::get($this->key(func_get_args()));
    }

    /**
     * Return key suitable for storing expiry info for corresponding data key.
     *
     * @param $key
     *
     * @internal param array $values
     * @return string
     */
    public function expiryKey($key) {
        return "expires-$key";
    }

    /**
     * Return value from cache using name and any subsequent args as the key.
     *
     * @param $name
     *
     * @return mixed
     */
    public function get($name) {
        $key       = $this->key(func_get_args());
        $expiryKey = $this->expiryKey($key);

        if ($expiresTimestamp = Session::get($expiryKey)) {
            if ($expiresTimestamp < time()) {
                // expired so clear value and expiry key and return null forcing a refresh
                Session::clear($expiryKey);
                Session::clear($key);
                return null;
            }
        }

        return Session::get($key);
    }

    /**
     * Set name to value using name and subsequent args as the key. Also resets expiry period to null.
     *
     * @param $value
     * @param $name
     * @param int|null $expireInSeconds
     *      0 = clear expiry key
     *      null = leave expiry alone
     *      else set/reset the expiry key
     *
     * @return CheckfrontAPICacheInterface
     * @fluent
     */
    public function set($value, $name, $expireInSeconds = self::DefaultExpireSeconds) {
        $key       = $this->key(array_slice(func_get_args(), 1));
        $expiryKey = $this->expiryKey($key);

        Session::set($key, $value);

        if (is_numeric($expireInSeconds)) {
            if ($expireInSeconds !== 0) {
                Session::set($expiryKey, time() + $expireInSeconds);
            } else {
                Session::clear($expiryKey);
            }
        }

        return $this;
    }

    /**
     * Clear cache using name and subsequent arguments as the key.
     *
     * @param $name
     *
     * @return CheckfrontAPICacheInterface
     * @fluent
     */
    public function clear($name) {
        $key = $this->key(func_get_args());
        Session::clear($key);
        Session::clear($this->expiryKey($key));

        return $this;
    }
}