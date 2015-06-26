<?php

class CheckfrontSession extends Object implements CheckfrontSessionInterface {
    const KeyPrefix = 'checkfront';

    const IDKey = 'id';
    const DataKey = 'data';
    const AccessKey = 'access_key';

    /**
     * Return saved checkfront session ID from php session.
     *
     * @return string|null
     */
    public function getID() {
        return Session::get(self::KeyPrefix . '.' . self::IDKey);
    }

    /**
     * Set the ID (used to store current checkfront sessionID)
     *
     * @param $sessionID
     * @return $this
     */
    public function setID($sessionID) {
        Session::set(self::KeyPrefix . '.' . self::IDKey, $sessionID);
        return $this;
    }

    /**
     * Return data from session with key e.g.  'checkfront.data.package' where $what is 'package'.
     *
     * @param $what
     * @return array|mixed|null|Session
     */
    public function getData($what)
    {
        return Session::get(self::KeyPrefix . '.' . self::DataKey . '.' . $what);
    }

    /**
     * Set value to session with key e.g. 'checkfront.data.package' where $what is 'package'.
     *
     * @param $what
     * @param $data
     * @return CheckfrontSessionInterface
     * @fluent
     */
    public function setData($what, $data)
    {
        Session::set(self::KeyPrefix . '.' . self::DataKey . '.' . $what, $data);
        return $this;
    }

    /**
     * Clear the value or all values if null (really null, not just falsih).
     *
     * Key could be e.g. 'id' to clear ID, or 'data.package' for stored package data, or just 'data' to clear all data.
     *
     * @param string $key
     * @return $this
     * @fluent
     */
    public function clear($key)
    {
        Session::clear(self::KeyPrefix . (is_null($key) ? '' : '.' . $key));
        return $this;
    }


    /**
     * Clear a specific data entry from the session, e.g. 'package' will clear session key 'checkfront.data.package'.
     *
     * @param $what
     * @return $this
     * @fluent
     */
    public function clearData($what) {
        $this->clear(self::DataKey . '.' . $what);
        return $this;
    }

    /**
     * @param $key
     *
     * @return CheckfrontSessionInterface
     * @fluent
     */
    public function setAccessKey($key) {
        $this->setData(self::AccessKey, $key);
        return $this;
    }

    /**
     * @return string
     */
    public function getAcccessKey() {
        return $this->getData(self::AccessKey);
    }

    /**
     * @return CheckfrontSessionInterface
     */
    public function clearAccessKey() {
        $this->clearData(self::AccessKey);
        return $this;
    }
}