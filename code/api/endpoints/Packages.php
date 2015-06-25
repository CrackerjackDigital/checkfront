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
     * @param null $startDate
     * @param null $endDate
     * @param array $filters
     * @return CheckfrontAPIPackagesResponse
     */

    public function listPackages($startDate = null, $endDate = null, array $filters = array()) {
        $params = self::request_params(
            __FUNCTION__,
            array(
                'category_id' => CheckfrontModule::package_category_id(),
            ),
            $this->buildDates($startDate, $endDate),
            $filters

        );
        return CheckfrontAPIPackagesResponse::create($this()->api(
            new CheckfrontAPIRequest(
                "item",
                $params
            )
        ));
    }

    /**
     * @param $checkfrontID
     * @param null|string $startDate
     * @param null|string $endDate
     * @param array $filters
     * @return CheckfrontAPIPackageResponse
     */
    public function fetchPackage($checkfrontID, $startDate = null, $endDate = null, array $filters = array()) {
        $params = self::request_params(
            __FUNCTION__,
            array(
                'category_id' => static::get_config_setting('package_category_id'),
            ),
            $this->buildDates($startDate, $endDate),
            $filters

        );
        return CheckfrontAPIPackageResponse::create($this()->api(
            new CheckfrontAPIRequest(
                "item/$checkfrontID",
                $params
            )
        ));
    }

    /**
     * Add package to the current checkfront session.
     *
     * @param CheckfrontPackageModel $package
     * @param array $addOrUpdateParams
     * @return CheckfrontAPIResponse
     */
    public function addPackageToSession(CheckfrontPackageModel $package, array $addOrUpdateParams = array()) {
        $response = new CheckfrontAPIResponse($this()->post(
            new CheckfrontAPIRequest(
                'booking/session',
                $package,
                $addOrUpdateParams
            )
        ));
        return $response;
    }


}