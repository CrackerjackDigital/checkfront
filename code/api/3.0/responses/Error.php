<?php

class CheckfrontAPIErrorResponse extends CheckfrontAPIResponse {
    const SimpleMatchKey = 'Message';

    /**
     * Return the payload data from the api call.
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the Message from the result.
     *
     * @return string
     */
    public function getMessage() {
        return $this->data['Message'];
    }

    /**
     * Return the number of items returned or 0 if none.
     *
     *
     *
     * @return integer
     */
    public function getCount()
    {
        return 0;
    }

    /**
     * Get a native code for the response possibly translated from the API code or just something we can process
     * internally.
     *
     * @return mixed
     */
    public function getCode()
    {
        return 0;
    }

    /**
     * Return a native response 'code' if present we can lookup to find more
     * info for cause of error/why not isValid. Maybe also return HTTP Response Code?
     *
     * @return mixed
     */
    public function getNativeCode()
    {
        // TODO: Implement getNativeCode() method.
    }

    /**
     * Check if the request was made succesfulyl and responded with something.
     *
     * If this returns true then getNativeCode should return the API result code if there is one,
     * and getMessage should return the error message if there is one.
     *
     * NB for HTTP errors an exception will be thrown instead as that's bad news, maybe config but probably
     * not recoverable.
     *
     * @return boolean
     *      true if request failed (bad url, invalid parameters passed etc)
     *      false if something returned (maybe empty though)
     */
    public function isError()
    {
        return true;
    }

    /**
     * Check if the request returned what we want
     *      true if there is expected data in the response
     *      false if there isn't (e.g. nothing found)
     *
     *  If returns false then getData should return null
     *
     * @return boolean
     */
    public function isValid()
    {
        return false;
    }
}