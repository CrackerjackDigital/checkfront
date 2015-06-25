<?php

/**
 * Implements a key-value cache based on PHP/SilverStripe Session.
 */
class CheckfrontCacheSession extends CheckfrontCache
    implements CheckfrontAPICacheInterface
{
    /**
     * Return true or false depending on if an item exists in the cache.
     * @param $name
     * @return mixed
     */
    public function exists($name = null)
    {
        return (bool)Session::get($this->key(func_get_args()));
    }

    /**
     * Return value from cache using name and any subsequent args as the key.
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        $expiryKey = $this->key(["expires-"] + func_get_args());

        if ($expiresTimestamp = Session::get($expiryKey)) {
            if ($expiresTimestamp < time()) {
                Session::clear($expiryKey);
                Session::clear($this->key(func_get_args()));
            }
        }
        return Session::get($this->key(func_get_args()));
    }

    /**
     * Set name to value using name and subsequent args as the key. Also resets expiry period to null.
     *
     * @param $name
     * @param $value
     * @return CheckfrontAPICacheInterface
     * @fluent
     */
    public function set($value, $name)
    {
        $key = $this->key(array_slice(func_get_args(), 1));

        Session::set($key, $value);

        if (!is_null($this->expireInSeconds)) {
            Session::set($this->key(["expires-", $key]), time() + $this->expireInSeconds);
            $this->expireInSeconds = null;
        }
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
        Session::clear($this->key(func_get_args()));
        return $this;
    }
}