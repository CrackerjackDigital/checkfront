<?php

/**
 * Adds package API to the standard CheckfrontAPI
 */
class CheckfrontAPIPackagesEndpoint extends CheckfrontAPIEndpoint {
    private static $request_defaults = array(
        'listPackages' => array(
            'packages' => 1
        ),
        'fetchPackage' => array(
            'packages' => 1
        )
    );
    // tailor what item categories get shown to organisers and individuals
    private static $display_item_categories = array(
        'organiser' => array(),
        'individual' => array()
    );

    /**
     * Perform an 'item' request for the CheckfrontModule.package_category_id() category with 'packages' option set on.
     *
     * @param null $startDate
     * @param null $endDate
     * @param array $filters
     * @return CheckfrontAPIPackagesResponse
     */

    public function listPackages($startDate = null, $endDate = null, array $filters = array()) {
        static $cache = array();

        $cacheKey = md5(implode('|', array($startDate, $endDate, implode('|', $filters))));

        if (!isset($cache[$cacheKey])) {
            $params = self::request_params(
                __FUNCTION__,
                array(
                    'category_id' => CheckfrontModule::package_category_id(),
                    'packages' => true
                ),
                $this->buildDates($startDate, $endDate),
                $filters

            );
            $cache[$cacheKey] = CheckfrontAPIPackagesResponse::create($this()->api(
                new CheckfrontAPIRequest(
                    "item",
                    $params
                )
            ));
        }
        return $cache[$cacheKey];
    }

    /**
     *
     * Perform an 'item' request for the package item with 'packages' option set on.
     *
     * @param $checkfrontID
     * @param null|string $startDate
     * @param null|string $endDate
     * @param array $filters
     * @return CheckfrontAPIPackageResponse
     */
    public function fetchPackage($checkfrontID, $startDate = null, $endDate = null, array $filters = array()) {
        static $cache = array();

        $cacheKey = md5(implode('|', array($checkfrontID, $startDate, $endDate, implode('|', $filters))));

        if (!isset($cache[$cacheKey])) {

            $params = self::request_params(
                __FUNCTION__,
                array(
//                    'category_id' => CheckfrontModule::package_category_id(),
                    'packages' => true,
                    'product_group_type' => 'N'
                ),
                $this->buildDates($startDate, $endDate),
                $filters

            );
            $cache[$cacheKey] = CheckfrontAPIPackageResponse::create($this()->api(
                new CheckfrontAPIRequest(
                    "item/$checkfrontID",
                    $params
                )
            ));
        }
        return $cache[$cacheKey];
    }

    /**
     * Add package to the current checkfront session.
     *
     * @param CheckfrontPackageModel $package
     * @param array $addOrUpdateParams
     * @return CheckfrontAPIResponse
     */
    public function addPackageToSession(CheckfrontPackageModel $package, array $addOrUpdateParams = array()) {
        $params = array_merge(
            array(
                'session_id' => CheckfrontModule::session()->getID()
            ),
            $package->toCheckfront('booking/session'),
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