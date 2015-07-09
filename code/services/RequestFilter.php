<?php

class CheckfrontRequestFilter implements RequestFilter {

    /**
     * Filter executed before a request processes
     *
     * @param SS_HTTPRequest $request Request container object
     * @param Session $session        Request session
     * @param DataModel $model        Current DataModel
     *
     * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
     */
    public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {
        $endpoints = array_keys(CheckfrontModule::endpoints());
        if (in_array($request->getURL(), $endpoints)) {

        }
    }

    /**
     * Filter executed AFTER a request
     *
     * @param SS_HTTPRequest $request   Request container object
     * @param SS_HTTPResponse $response Response output object
     * @param DataModel $model          Current DataModel
     *
     * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
     */
    public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {
        // TODO: Implement postRequest() method.
    }
}