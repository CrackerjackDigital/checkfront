<?php
class CheckfrontAPIPackageResponse extends CheckfrontAPIResponse {

    private static $package_items_path = 'item.product_group_children';

    /**
     * @return CheckfrontPackageModel|null
     */
    public function getPackage() {
        if ($this->isValid()) {
            return CheckfrontPackageModel::create()->fromCheckfront($this->data);
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
            if (isset($this->data['item']['product_group_children'])) {

                $packageItems = $this->data['item']['product_group_children'] ?: [];

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