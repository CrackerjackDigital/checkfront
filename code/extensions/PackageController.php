<?php

/**
 * A package oriented controller extension which can be added to a Page_Controller to provide
 * checkfont package booking functionality.
 *
 * Expects an item ID on the incoming request which points to a Package in Checkfront. Uses http request
 * mode GET/POST to figure out what action to perform.
 */
class CheckfrontPackageControllerExtension extends CheckfrontControllerExtension {
    const FormName = 'CheckfrontBookingForm';
    const PackageSessionKey = 'package';
    const PackageIDKey = 'package-id';
    const FormSessionKey = 'form';
    const TokenParam = 'Token';     // NB keep in sync with the url_handlers.

    private static $url_handlers = array(
        'token' => 'gettoken',
        'GET $Token!' => 'package',
        'POST $Token!' => 'book'
    );
    private static $allowed_actions = array(
        'package' => true,
        'book' => true,
        'token' => true
    );
    private static $form_name = self::FormName;

    /**
     * Override parent return types mainly for ease of coding.
     * @return CheckfrontAPIImplementation|CheckfrontAPIBookingFormEndpoint|CheckfrontAPIPackagesEndpoint|CheckfrontAPIItemsEndpoint|CheckfrontAPIBookingEndpoint|CheckfrontAPISessionEndpoint
     */
    public function api() {
        return parent::api();
    }

    public function index(SS_HTTPRequest $request) {
        // TODO: handle what happens if no Token passed.
        return array();
    }

    public function gettoken(SS_HTTPRequest $request) {
        xdebug_break();
    }

    /**
     * Check if we already have a checkfront session. If not then create one with the package
     * identified on the request url as Token. The existance of the session implies the package
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

            $packageID = $this->CheckfrontItemID();

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
                            $this->CheckfrontItemID()
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

    /**
     * Return the ItemID from the passed encrypted token.
     *
     * @return array|null
     */
    public function CheckfrontItemID() {
        $accessKey = $this->owner->getRequest()->param(self::TokenParam);
        if (!$accessKey) {
            return $this->owner->redirect('auth');
        }
        return CheckfrontModule::decode_link_segment(
            $accessKey,
            CheckfrontModule::TokenItemIDIndex
        );
    }

    public function CheckfrontPackage() {
        return CheckfrontModule::session()->getData(self::PackageSessionKey);
    }

    /**
     * -    setup session in checkfront
     * -    add package to session
     * -    add items to session
     * -    call the 'book' endpoint to make the booking
     *
     * @param SS_HTTPRequest $request
     * @return CheckfrontForm
     */
    public function book(SS_HTTPRequest $request) {
        // only post request should route here

        $packageID = $this->CheckfrontItemID();

        if ($request->isPOST()) {
            $postVars = $request->postVars();

            $startDate = $request->postVar('StartDate');
            $endDate = $request->postVar('EndDate');

            $ratedPackageResponse = $this->api()->fetchPackage(
                $packageID,
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
            $this->CheckfrontItemID(),
            'book'
        );
    }



}