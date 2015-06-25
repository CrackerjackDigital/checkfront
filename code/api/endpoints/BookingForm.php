<?php

class CheckfrontAPIBookingFormEndpoint extends CheckfrontAPIEndpoint {

    // we can get back items in the api booking/form call which are not form fields.
    private static $field_map = array(
        'msg' => 'Messages',
        'errors' => 'Errors',
        'mode' => 'Mode',
        '_cnf' => 'Config'
    );
    /**
     * Return a FieldList of fields retrieved from the booking/form api call.
     * @api booking/form
     * @throws Exception
     * @return CheckfrontAPIFormResponse
     */
    public function fetchBookingForm() {
        return CheckfrontAPIFormResponse::create($this()->api(
            new CheckfrontAPIRequest(
                "booking/form"
            )
        ));
    }

}