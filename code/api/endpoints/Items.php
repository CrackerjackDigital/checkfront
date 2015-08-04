<?php

class CheckfrontAPIItemsEndpoint extends CheckfrontAPIEndpoint {

    private static $request_defaults = array(
        'listItems' => array(
        ),
        'fetchItem' => array(
        )
    );

    /**
     * @return CheckfrontPackageModel|null
     */
    public function getItem() {
        if ($this->isValid()) {
            return CheckfrontItemModel::create_from_checkfront($this->data);
        }
    }
    /**
     * @param string $startDate
     * @param int|string $numDays
     * @param array $filters
     * @return CheckfrontAPIItemsResponse
     * @throws CheckfrontAPIErrorException|Exception
     */
    public function listItems($startDate = 'today', $numDays = CheckfrontModule::DefaultAvailabilityNumDays, array $filters = array()) {
        $query = array_merge(
            array(
                "start_date" => CheckfrontModule::checkfront_date($startDate),
                "end_date" => CheckfrontModule::checkfront_date($numDays)
            ),
            $filters
        );
        return CheckfrontAPIItemsResponse::create($this()->api(
            new CheckfrontAPIRequest(
                "item",
                $query
            )
        ));
    }

    /**
     * Return a Rated Item with a Slip from Checkfront given the details passed.
     * @param $itemID
     * @param int $quantity - number of items to request (for slip)
     * @param null $startDate
     * @param null $endDate
     * @param array $filters
     * @return CheckfrontAPIItemResponse
     */
    public function fetchItem($itemID, $quantity, $startDate, $endDate, array $filters = array()) {
        $params = self::request_params(
            __FUNCTION__,
            array(
                'item_id' => $itemID,
                "param[qty]" => $quantity
            ),
            $this->buildDates($startDate, $endDate),
            $filters
        );
        // if we pass data as array we get a stdClass back, we just want to stick to arrays so append data to query instead.
        return CheckfrontAPIItemResponse::create($this()->api(
            new CheckfrontAPIRequest(
                "item/$itemID",
                $params
            )
        ));
    }

    /**
     * @param CheckfrontItemModel $item
     * @param array $addOrUpdateParams
     * @return CheckfrontAPIResponse
     */
    public function addItemToSession(CheckfrontItemModel $item, array $addOrUpdateParams = array()) {
        $params = array_merge(
            array(
                'session_id' => CheckfrontModule::session()->getID()
            ),
            $item->toCheckfront('booking/session'),
            $addOrUpdateParams
        );
        $response = new CheckfrontAPIResponse($this()->post(
            new CheckfrontAPIRequest(
                'booking/session',
                $params
            )
        ));
        return $response;

    }
}