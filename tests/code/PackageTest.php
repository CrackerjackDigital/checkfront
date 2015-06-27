<?php


class CheckfrontPackageTest extends CheckfrontTest {
    public function testPackage() {
        /** @var CheckfrontAPIImplementation|CheckfrontAPIPackagesEndpoint|CheckfrontAPIBookingFormEndpoint $api */
        $api = CheckfrontModule::api();
        $response = $api->fetchPackage(95);

        if ($package = $response->getPackage()) {
            if ($sessionID = $api->addPackageToSession($package)) {

                $formResponse = $api->fetchBookingForm();

                $form = $formResponse->getForm();


            }
        }
    }
}