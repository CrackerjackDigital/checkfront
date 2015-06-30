<?php

/**
 * Main functionality for this is added by CheckfrontPackageControllerExtension extension in extensions.yml.
 */
class CheckfrontPackageController extends ContentController {
    private static $allowed_actions = array(
        'index' => true
    );

    /**
     * Pass through to CheckfrontPackageControllerExtension.
     *
     * @param SS_HTTPRequest $request
     *
     * @return mixed
     */
    public function index(SS_HTTPRequest $request) {
        // extension should handle this, return the first result
        $results = $this->extend('index', $request);
        return reset($results);
    }

}