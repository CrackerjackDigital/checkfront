<?php

class CheckfrontAPIPackagesResponse extends CheckfrontAPIResponse {
    const SimpleMatchKey = 'request';
    /**
     * Convenience semantic method calls through to getData.
     *
     * @return null|SS_List
     */
    public function getPackages() {
        if ($this->isValid()) {
            return new ArrayList(
                array_map(
                    function($itemData) {
                        return CheckfrontPackageModel::create_from_checkfront($itemData);
                    },
                    $this['items']
                )
            );
        }
    }
    /**
     * Check if the request returned what we want, returns:
     *      true if there is expected data in the response
     *      false if there isn't (e.g. expected data not found)
     *
     *  If returns false then getPackages should return null.
     *  If isError then this should return false too
     *
     * @return boolean
     */
    public function isValid()
    {
        if (parent::isValid()) {
            return isset($this['items']) ? true : false;
        }
        return false;
    }

    /**
     * Return the number of items returned (maybe one for a single model), 0 if none or null
     * if not found.
     *
     * @return integer|null
     */
    public function getCount()
    {
        if ($this->isValid()) {
            return isset($this['request']['records']) ? $this['request']['records'] : null;
        }
    }
}