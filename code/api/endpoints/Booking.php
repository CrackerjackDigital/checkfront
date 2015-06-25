<?php


class CheckfrontAPIBookingEndpoint extends CheckfrontAPIEndpoint {
    public function makeBooking(CheckfrontBookingModel $booking) {
        $sessionID = CheckfrontModule::session()->getID();

        $params = array_merge(
            array(
                'session_id' => $sessionID
            ),
            $booking->toCheckfront('booking/create')
        );

        return new CheckfrontAPIResponse($this()->api(
            new CheckfrontAPIRequest(
                'booking/create',
                $params
            )
        ));
    }
}