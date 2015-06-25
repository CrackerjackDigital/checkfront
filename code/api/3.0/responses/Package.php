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
     *      false if there isn't (e.g. nothing found)
     *
     *  If returns false then getData should return null.
     *  If isError then this should return false too
     *
     * @return boolean
     */
    public function isValid()
    {
        return isset($this->data['item']) ? true : false;
    }
}