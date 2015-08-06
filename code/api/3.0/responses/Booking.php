<?php

class CheckfrontAPIBookingResponse extends CheckfrontAPIResponse {

    public function getBooking() {
        return CheckfrontBookingModel::create_from_checkfront($this->data, 'response');
    }

    public function getItems() {
        if (isset($this->data['booking']['items'])) {
            return new CheckfrontItemIterator($this->data['booking']['items']);
        }
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
            return $this->path('request.error.details', $found);
        }
    }
    public function getPaymentURL() {
        return $this->data['request']['data']['url'];
    }
}