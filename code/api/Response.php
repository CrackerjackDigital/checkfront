<?php

/**
 * Wrap a result from checkfront api with some convenience methods and object property access.
 *
 * Class CheckfrontAPIResult
 */
class CheckfrontAPIResponse extends Object
    implements CheckfrontAPIResponseInterface
{
    const SimpleMatchKey = 'request';

    const RawDataArray = 'array';

    protected $data = array();

    private static $status_ok = 'OK';

    public function __construct(array $data = array()) {
        $this->data = $data;
        parent::__construct();
    }

    /**
     * Return a status message, e.g. 'ok' or error text.
     * @return string|null
     */
    public function getStatus() {
        return isset($this->data['request']['status']) ? $this->data['request']['status'] : null;
    }

    /**
     * Return a usefull translated message, e.g. 'ok' or error text.
     * @return string|null
     */
    public function getMessage() {
        return $this->getStatus();
    }

    /**
     * Return returned version string or null if not found in response.
     * @return string|null
     */
    public function getVersion() {
        return isset($this->data['version']) ? $this->data['version'] : null;
    }

    /**
     * Returns booking.session.id from response data or null if not there.
     * @return null
     */
    public function getSessionID() {
        if(isset($this->data['booking']['session']['id'])) {
            return $this->data['booking']['session']['id'];
        }
        return null;

    }

    /**
     * Check if the request was invalid, e.g. bad/missing parameters but we got something back from API.
     *
     * For HTTP errors an exception will be thrown when the request is made instead as maybe config or remote endpoint
     * wrong or not available at the moment but probably not recoverable.
     *
     * If this returns true then getNativeCode should return the API error result code if there is one,
     * and getMessage should return the error message if there is one.
     *
     * @return boolean
     *      true if request failed (bad url, invalid parameters passed etc)
     *      false if something returned (maybe empty though)
     */
    public function isError()
    {
        return $this->getStatus() !== $this->config()->get('status_ok');
    }

    /**
     * Call from inherited classes for basic validity checks before specific ones.
     * Initially just returns the opposite of isError.
     *
     * @return bool
     */
    public function isValid() {
        return !$this->isError();
    }

    /**
     * Returns the raw data from the response.
     *
     * @param string $format - does nothing at the moment, always returns an array.
     * @return array
     */
    public function getRawData($format = self::RawDataArray) {
        return $this->data;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return is_array($this->data) && array_key_exists($offset, $this->data);
    }

    /**
     * ArrayAccess implementation
     * @throws Exception
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new CheckfrontException("Invalid key '$offset'", CheckfrontException::TypeError);
        }
        return $this->data[$offset];
    }

    /**
     * ArrayAccess implementation
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }

    /**
     * Return the number of items returned (maybe one for a single model) or 0 if none.
     *
     * @return integer|null
     */
    public function getCount()
    {
        if ($this->isValid()) {
            return isset($this['request']['records']) ? $this['request']['records'] : null;
        }
    }

    /**
     * Lookup 'manually' configured config setting optionally with key lookup if it's an array. We need to
     * do this because Response classes are versioned so outside of normal SilverStripe config mechanism.
     *
     * @param $name
     * @param null|string $key
     *
     * @return array|scalar
     */
    protected static function get_config_setting($name, $key = null) {
        $value = Config::inst()->get(get_called_class(), $name);
        if ($key && $value && is_array($value) && array_key_exists($key, $value)) {
            return $value[$key];
        }
        return $value;
    }
}