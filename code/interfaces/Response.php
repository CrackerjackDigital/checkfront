<?php

/**
 * Interface for classes which encapsulate a response for the API. Extends ArrayAccess as implementations
 * should make access to data easy.
 *
 */
interface CheckfrontAPIResponseInterface extends ArrayAccess {
    /**
     * Return the number of items returned (maybe one for a single model) or 0 if none.
     *
     * @return integer
     */
    public function getCount();


    /**
     * Get the raw status returned, e.g. 'OK' or null if not found (which is probably an error).
     * @return string|null
     */
    public function getStatus();

    /**
     * Get any associated response message (maybe error message). This may translate an API response
     * to a localised message or something more meaningful we can show.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Return returned version string or null if not found in response.
     * @return string|null
     */
    public function getVersion();

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
    public function isError();

    /**
     * Check if the request returned what we want
     *      true if there is expected data in the response
     *      false if there isn't (e.g. nothing found)
     *
     *  If returns false then any of the get{Data} methods should return null.
     *  If isError then this should return false too
     *
     * @return boolean
     */
    public function isValid();

}