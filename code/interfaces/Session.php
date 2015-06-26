<?php

interface CheckfrontSessionInterface {

    /**
     * @return mixed
     */
    public function getID();

    /**
     * @param $sessionID
     *
     * @return CheckfrontSessionInterface
     * @fluent
     */
    public function setID($sessionID);

    /**
     * @param $what - key for value, will be prefixed with checkfront prefix in dot-notation before retrieval.
     *
     * @return mixed
     */
    public function getData($what);

    /**
     * @param $key
     *
     * @return string
     */
    public function setAccessKey($key);

    /**
     * @return string
     */
    public function getAcccessKey();

    /**
     * @param $what - key for value, will be prefixed with checkfront prefix in dot-notation before storage.
     * @param $data
     *
     * @return CheckfrontSessionInterface
     * @fluent
     */
    public function setData($what, $data);

    /**
     * Clear a specific data entry from the session, e.g. 'package' will clear session key 'checkfront.data.package'.
     *
     * @param $what
     *
     * @return $this
     * @fluent
     */
    public function clearData($what);


    /**
     * Clear value from session, or all checkfront related values if null (really null not just falsish).
     *
     * @param string $key
     *
     * @return void
     */
    public function clear($key);
}