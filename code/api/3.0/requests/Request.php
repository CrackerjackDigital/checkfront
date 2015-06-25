<?php
/**
 * Very simple request class wrapper, almost unnecessary with 3.0 api.
 */
class CheckfrontAPIRequest extends Object {
    /** @var  string api endpoint e.g. "item/{id} */
    protected $endpoint;

    /** @var array data to pass with request, either as post or get */
    protected $data = array();

    /**
     * @param $endpoint
     * @param array|CheckfrontModel $arrayOrDataObject
     * @param array $extraData
     */
    public function __construct($endpoint, $arrayOrDataObject = array(), array $extraData = array()) {
        $this->endpoint = $endpoint;

        $this->data = array_merge(
            ($arrayOrDataObject instanceof CheckfrontModel)
                ? $arrayOrDataObject->toCheckfront($endpoint)
                : $arrayOrDataObject,
            $extraData
        );
    }

    /**
     * @return string
     */
    public function getEndpoint() {
        return $this->endpoint;
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }
}