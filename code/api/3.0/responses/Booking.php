<?php

class CheckfrontAPIBookingResponse extends CheckfrontAPIResponse {

    public function getBooking() {

    }
    /**
     * Return the number of items returned (maybe one for a single model) or 0 if none.
     *
     * @return integer
     */
    public function getCount()
    {
        // TODO: Implement getCount() method.
    }
    public function getMessage() {
        if ($this->isError()) {
            return CheckfrontModule::lookup_path('request.error.details', $this->data, $found);
        }
    }
    public function getPaymentURL() {
        xdebug_break();
    }
}