<?php

/**
 * Base class for Checkfront module extensions which will be added to page_controllers etc
 */
class CheckfrontControllerExtension extends SiteTreeExtension {
    // cache one of these when constructed.
    /** @var CheckfrontAPIImplementation  */
    private $api;

    public function __construct($dataRecord = null) {
        $this->api = CheckfrontModule::api();
        parent::__construct($dataRecord);
    }

    /**
     * @return CheckfrontAPIImplementation
     */
    public function api() {
        return $this->api;
    }
}