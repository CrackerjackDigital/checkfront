<?php
class CheckfrontAPIPackageResponse extends CheckfrontAPIResponse {

    /**
     * @return CheckfrontPackageModel|null
     */
    public function getPackage() {
        if ($this->isValid()) {
            return CheckfrontPackageModel::create()->fromCheckfront($this->data['item']);
        }
    }

    /**
     * Get the events for this package.
     *
     * @return SS_List
     */
    public function getEvents() {
        $list = new ArrayList();
        if (isset($this->data['events'])) {
            foreach ($this->data['events'] as $event) {
                $list->push(CheckfrontEventModel::create_from_checkfront($event));
            }
        }
        return $list;
    }

    /**
     * Return the event with the provided ID or null if not found for the package.
     *
     * @param $eventID
     *
     * @convenience
     *
     * @return CheckfrontEventModel|null
     */
    public function getEvent($eventID) {
        return $this->getEvents()->filter('EventID', $eventID)->first();
    }

    /**
     * Gets the package items using config.package_item_path as index into data and
     * return a list of CheckfrontItemModels.
     *
     * @return SS_List may be empty
     */
    public function getPackageItems() {
        $list = new ArrayList();

        if ($this->isValid()) {
            $path = self::get_config_setting('package_items_path');

            $packageItems = CheckfrontModule::lookup_path($path, $this->data, $found);

            if ($packageItems && $found) {

                foreach ($packageItems as $item) {
                    $list->push(
                        CheckfrontItemModel::create()->fromCheckfront($item)
                    );
                }
            }
        }
        return $list;
    }

    /**
     * Check if the request returned what we want
     *      true if there is expected data in the response
     *      false if there isn't (e.g. data item not set)
     *
     *  If isError then this should return false too
     *
     * @return boolean
     */
    public function isValid()
    {
        return isset($this->data['item']) && !$this->isError();
    }
}