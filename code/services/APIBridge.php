<?php
/**
 * API Facade calls through to implementation, adds DataObject style interface.
 */

class CheckfrontAPIBridge extends Object implements CheckfrontAPIInterface {

    /** @var CheckfrontAPIImplementation set by injector in checkfront module config.yml */
    private $implementation;


    /**
     * Set an implementation, this should be called by Injector
     *
     * @param CheckfrontAPIImplementation $implementation
     */
    public function setImplementation(CheckfrontAPIImplementation $implementation) {
        $this->implementation = $implementation;
    }


    /**
     * Proxy method to implementation.api call
     * @param CheckfrontAPIRequest $request
     * @return array
     */
    public function api(CheckfrontAPIRequest $request) {
        return $this->implementation->api(
            $request->getEndpoint(),
            $request->getData()
        );
    }
    /**
     * Proxy method to implementation.get call
     * @param CheckfrontAPIRequest $request
     * @return array
     */
    public function get(CheckfrontAPIRequest $request) {
        return $this->implementation->get(
            $request->getEndpoint(),
            $request->getData()
        );
    }
    /**
     * Proxy method to implementation.post call
     * @param CheckfrontAPIRequest $request
     * @return array
     */
    public function post(CheckfrontAPIRequest $request) {
        return $this->implementation->post(
            $request->getEndpoint(),
            $request->getData()
        );
    }

}