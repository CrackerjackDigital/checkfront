<?php

/**
 * A package oriented controller, expects an item ID on the incoming request which points to a Package in Checkfront.
 */
class CheckfrontPackageControllerExtension extends CheckfrontControllerExtension {
    const FormName = 'CheckfrontBookingForm';
    const PackageSessionKey = 'package';
    const PackageIDKey = 'package-id';
    const FormSessionKey = 'form';

    const IDParam = 'CheckfrontID';     // NB keep in sync with the url_handlers.

    private static $url_handlers = array(
        'GET $CheckfrontID!' => 'package',
        'POST $CheckfrontID!' => 'book',
    );
    private static $allowed_actions = array(
        'package' => true,
        'book' => true
    );
    private static $form_name = self::FormName;

    /**
     * Override parent return types mainly for ease of coding.
     * @return CheckfrontAPIImplementation|CheckfrontAPIBookingFormEndpoint|CheckfrontAPIPackagesEndpoint|CheckfrontAPIItemsEndpoint|CheckfrontAPIBookingEndpoint|CheckfrontAPISessionEndpoint
     */
    public function api() {
        return parent::api();
    }

    /**
     * Check if we already have a checkfront session. If not then create one with the package
     * identified on the request url as CheckfrontID. The existance of the session implies the package
     * has already been added so doesn't re-add.
     */
    public function package(SS_HTTPRequest $request) {
        $package = null;
        $session = CheckfrontModule::session();

        $cachedPackage = $session->getData(self::PackageSessionKey);

        if ($cachedPackage) {

            $package = CheckfrontPackageModel::create()->fromCheckfront($cachedPackage);

        } else {
            // no session, try fetch package again and store in session
            $session->clearData(self::PackageSessionKey);

            $packageID = $this->CheckfrontID();

            if ($packageID) {

                if ($packageResponse = $this->api()->fetchPackage($packageID)) {

                    if ($packageResponse->isValid()) {
                        $package = $packageResponse->getPackage();

                        if ($package) {
                            CheckfrontModule::session()
                                ->setData(self::PackageSessionKey, $packageResponse->getRawData());
                        }
                    }

                }
            }
        }
        return array(
            'Package' => $package
        );
    }

    /**
     * returns a form suitable for booking a package.
     * @return CheckfrontForm|null
     */
    public function CheckfrontPackageBookingForm() {
//        $form = CheckfrontModule::session()->getData(self::FormSessionKey);
        // TODO: form caching
        $form = null;

        /** @var CheckfrontAPIFormResponse $response */
        if (!$form) {
            // rebuild response from session package raw data, this should be set by the 'package' call above on
            // page entry with a valid packageID

            $packageResponse = new CheckfrontAPIPackageResponse(
                CheckfrontModule::session()->getData(self::PackageSessionKey)
            );

            if ($packageResponse->isValid()) {

                $package = $packageResponse->getPackage();

                // push start and end dates to top of list
                $fields = new FieldList(
                    new DateField('StartDate', 'Start Date', $package->StartDate),
                    new DateField('EndDate', 'End Date', $package->EndDate)
                );

                // add the package items to the field list which will make the form as fields
                /** @var CheckfrontModel $item */
                foreach ($packageResponse->getPackageItems() as $item) {
                    $fields->merge($item->fieldsForForm('form'));
                }

                if ($response = $this->api()->fetchBookingForm()) {

                    if ($response->isValid()) {

                        // now add the booking fields to the fieldlist for the form
                        $bookingFields = $response->getFormFields();

                        $fields->merge(
                            $bookingFields
                        );

                        // TODO: required fields
                        $form = new CheckfrontForm(
                            $this->owner,
                            $this->owner->config()->get('form_name'),
                            $fields,
                            new FieldList(new FormAction('book', 'book'))
                        );
                        $formAction = Controller::join_links(
                            $this->owner->Link(),
                            $this->CheckfrontID()
                        );
                        $form->setFormAction(substr($formAction, 1));
                        // TODO: form caching
                        //                    CheckfrontModule::session()->setData(self::FormSessionKey, $form);
                    }
                }
            }
        }
        return $form;
    }

    public function CheckfrontID() {
        return $this->owner->getRequest()->param(self::IDParam);
    }

    public function CheckfrontPackage() {
        return CheckfrontModule::session()->getData(self::PackageSessionKey);
    }

    /**
     * -    setup session in checkfront
     * -    add package to session
     * -    return booking form for package
     *
     * @param SS_HTTPRequest $request
     * @return CheckfrontForm
     */
    public function book(SS_HTTPRequest $request) {
        // only post request should route here
        if ($request->isPOST()) {
            $postVars = $request->postVars();

            $startDate = $request->postVar('StartDate');
            $endDate = $request->postVar('EndDate');

            $ratedPackageResponse = $this->api()->fetchPackage(
                $this->CheckfrontID(),
                $startDate,
                $endDate
            );
            if ($ratedPackageResponse->isValid()) {
                $package = $ratedPackageResponse->getPackage();

                $this->api()->addPackageToSession($package);

                foreach ($postVars['ItemID'] as $index => $itemID) {

                    if (isset($postVars['Quantity'][$index])) {

                        if ($postVars['Quantity'][$index]) {

                            // now get rated item
                            $response = $this->api()->fetchItem($itemID, $startDate, $endDate);

                            if ($response->isValid()) {

                                $item = CheckfrontItemModel::create()->fromCheckfront(
                                    $response->getItem()
                                );

                                $this->api()->addItemToSession($item);

                            }
                        }
                    }
                }
                $booking = CheckfrontBookingModel::create()
                    ->fromCheckfront($postVars, 'from-form');

                $this->api()->makeBooking($booking);

                $this->api()->clearSession();
            }


        }
    }


    /**
     * Returns a link '/booking/package/{id}/book'.
     * @return String
     */
    public function getBookingLink() {
        return Controller::join_links(
            'book',
            $this->CheckfrontID(),
            'book'
        );
    }



}