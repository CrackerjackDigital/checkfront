<?php

/**
 * A package oriented controller extension which can be added to a Page_Controller to provide
 * checkfont package booking functionality.
 * Expects an item ID on the incoming request which points to a Package in Checkfront. Uses http request
 * mode GET/POST to figure out what action to perform.
 */
class CheckfrontPackageControllerExtension extends CheckfrontControllerExtension {
    const FormName          = 'CheckfrontForm';
    const PackageSessionKey = 'package';
    const PackageIDKey      = 'package-id';
    const FormSessionKey    = 'form';
    const TokenParam        = 'Token';

    private static $allowed_actions = array(
        'index' => true
    );

    private static $url_handlers = array(
        'index' => 'package'
    );

    private static $form_name = self::FormName;

    private static $exclude_user_type_items = array(
        'individual' => array(
            'categories' => array(
                'Equipment' => 2,
                'Venue'     => 3
            )
        )
    );

    public static function get_extra_config($class, $extension, $args) {
        return array(
            'url_handlers' => array(
                '$' . self::TokenParam . '!' => 'index'
            )
        );
    }

    /**
     * @return ContentController
     */
    public function __invoke() {
        return $this->owner;
    }

    /**
     * Override parent return types mainly for ease of coding.
     * @return CheckfrontAPIImplementation|CheckfrontAPIBookingFormEndpoint|CheckfrontAPIPackagesEndpoint|CheckfrontAPIItemsEndpoint|CheckfrontAPIBookingEndpoint|CheckfrontAPISessionEndpoint
     */
    protected function api() {
        return parent::api();
    }

    public function PackageList() {
        $packageList = $this->api()->listPackages()->getPackages();
        return $packageList;
    }

    public function index(SS_HTTPRequest $request) {
        return $this->package($request);
    }

    /**
     * Figures out if we are GET or POST and with posted info determines if we need
     * to enter AccessToken or can access the booking form. Returns page with correct form.
     *
     * @param SS_HTTPRequest $request
     *
     * @return array
     */
    public function package(SS_HTTPRequest $request) {
        // laughed until I stopped.
        $result = array(
            'Message' => 'Something went wrong'
        );
        try {
            if (isset($request)) {
                if ($request->isPOST()) {

                    if ($this->isAction($request, CheckfrontAccessKeyForm::SubmitButtonName)) {

                        // process AccessKeyForm submission
                        $result = $this->buildBookingForm($request);

                    } elseif ($this->isAction($request, CheckfrontBookingForm::SubmitButtonName)) {

                        // process BookingForm submission
                        $result = $this->book($request);
                    }
                } elseif ($request->param(CheckfrontPackageControllerExtension::TokenParam)) {

                    if ($this->isPublic()) {
                        $result = $this->buildBookingForm($request);
                    } else {
                        // on GET show the access key form (the post actions will show the other forms of the flow).
                        $result = $this->buildAccessKeyForm($request);
                    }
                }
            }
        } catch (Exception $e) {
            $result = array(
                'Message' => $e->getMessage()
            );
        }
        return $this()->renderWith(
            array('CheckfrontBookingPage', 'Page'),
            array_merge(
                $result
            )
        );
    }

    protected function isPublic() {
        /** @var ContentController $controller */
        $controller = $this();
        $url = $controller->getRequest()->param('URLSegment');
        if ($url) {
            return $url == CheckfrontModule::endpoints('public');
        }

        return false;
    }

    /**
     * Check if we received a postVar 'action_$buttonName'
     *
     * @param $request
     * @param $buttonName
     *
     * @return mixed
     */
    protected function isAction($request, $buttonName) {
        return $request->postVar('action_' . $buttonName);
    }

    /**
     * Return the desctructured token or part thereof.
     *
     * @param string $which     - optional single item to return, otherwise returns all
     * @param string $accessKey - to decrypt token, must be supplied first time around or force by resupplying
     *
     * @throws Exception
     * @return array|null
     */
    public function getTokenInfo($which = null, $accessKey = null) {
        if (!$detokenised = CheckfrontModule::session()->getToken()) {
            $request = $this()->getRequest();

            if ($token = $request->param(self::TokenParam)) {

                $detokenised = CheckfrontModule::crypto()->decrypt_token($token, $accessKey);

                // cache the token for retrieval later
                CheckfrontModule::session()->setToken(
                    $detokenised
                );

            } else {
                throw new Exception("No token");
            }
        };

        if ($detokenised) {
            if (!is_null($which)) {
                if (!array_key_exists($which, $detokenised)) {
                    return null;
                }
                return $detokenised[$which];
            }
        }

        return $detokenised;
    }

    /**
     * Check if we already have a checkfront session. If not then create one with the package
     * identified on the request url as Token. The existance of the session implies the package
     * has already been added so doesn't re-add.
     * NB: the AccessKey entered on the 'showAccessKey' form should be
     */
    protected function buildBookingForm(SS_HTTPRequest $request, array $templateData = array()) {

        // access key posted by AccessKeyForm is from cryptofier.generate_key via the original link generator
        $accessKey = $request->postVar(CheckfrontAccessKeyForm::AccessKeyFieldName);

        if (!$this->isPublic()) {
            try {
                // check entered key is a valid Crypto key first, should throw exception if not
                CheckfrontModule::crypto()->encrypt('somethingrandomheredontcarewhat', $accessKey);
            } catch (CryptofierException $e) {
                Session::setFormMessage('', 'Invalid access key', 'bad');

                return $this()->redirectBack();
            }
        }

        $packageID = $this->getTokenInfo(CheckfrontModule::TokenItemIDIndex, $accessKey);

        if (is_numeric($packageID)) {

            if ($packageResponse = $this->api()->fetchPackage($packageID)) {

                if ($packageResponse->isValid()) {
                    $package = $packageResponse->getPackage();

                    $startDate = $request->postVar('StartDate') ?: date('Y-m-d', strtotime($this->getTokenInfo(CheckfrontModule::TokenStartDateIndex)));
                    $endDate = $request->postVar('EndDate') ?: date('Y-m-d', strtotime($this->getTokenInfo(CheckfrontModule::TokenEndDateIndex)));

                    $linkType = $this->getTokenInfo(CheckfrontModule::TokenLinkTypeIndex);
                    $userType = $this->getTokenInfo(CheckfrontModule::TokenUserTypeIndex);

                    // now build the form

                    // add a hidden 'accessKey' field and the StartDate and EndDate fields
                    $fields = new FieldList();

                    // add start and end date fields
                    $fields->merge(
                        new FieldList(array(
                            CheckfrontForm::make_date_field($request, CheckfrontForm::StartDateFieldName, $startDate),
                            CheckfrontForm::make_date_field($request, CheckfrontForm::EndDateFieldName, $endDate)
                        ))
                    );


                    if ($packageResponse = $this->api()->fetchPackage($packageID, $startDate, $endDate)) {

                        if ($packageResponse->isValid()) {

                            // add the package items to the field list which will make the form as fields
                            /** @var CheckfrontModel $item */
                            foreach ($packageResponse->getPackageItems() as $item) {
                                if ($this->shouldShowItem($item, $userType, $linkType)) {
                                    $fields->merge($item->fieldsForForm('form'));
                                }
                            }

                            $fields->merge(
                                new FieldList(array(
                                    new HiddenField(CheckfrontAccessKeyForm::AccessKeyFieldName, '', $accessKey)
                                ))
                            );

                            // maybe mode down, we can still show a booking form even without items?
                            /** @var Form $form */
                            $form = new CheckfrontBookingForm(
                                $this->owner,
                                '',
                                $fields,
                                new FieldList()
                            );
                            $form->setFormAction('/' . $this()->getRequest()->getURL());

                            // TODO: make sure there's nothing nasty we're loading here
                            $form->loadDataFrom($request->postVars());

                            // early return
                            return array_merge(
                                array(
                                    'Package'        => $package,
                                    'CheckfrontForm' => $form
                                ),
                                $templateData
                            );
                        }
                    }
                }
            }
        }
        return array_merge(
            $templateData,
            array(
                'Message' => 'Failed to get package from checkfront'
            )
        );
    }

    /**
    /**
     * Returns a simple form where user can enter the access key they have been provided.
     * Form will post back to the request url.
     *
     * @param SS_HTTPRequest $request
     *
     * @return array - CheckfrontForm => CheckfrontAccessKeyForm instance
     */
    protected function buildAccessKeyForm(SS_HTTPRequest $request, array $templateData = array()) {

        CheckfrontModule::session()->clear(null);

        $form = new CheckfrontAccessKeyForm(
            $this->owner,
            '',
            new FieldList(),
            new FieldList()
        );
        $form->setFormAction('/' . $this()->getRequest()->getURL());

        return array_merge(
            array(
                'CheckfrontForm' => $form
            ),
            $templateData
        );
    }


    /**
     * -    setup session in checkfront
     * -    add package to session
     * -    add items to session
     * -    call the 'book' endpoint to make the booking
     *
     * @param SS_HTTPRequest $request
     *
     * @return CheckfrontForm
     */
    protected function book(SS_HTTPRequest $request, array $templateData = array()) {
        // only post request should route here
        $postVars = $request->postVars();

        // initiall call should also
        $packageID = $this->getTokenInfo(CheckfrontModule::TokenItemIDIndex, $postVars[CheckfrontForm::AccessKeyFieldName]);

        if ($request->isPOST()) {

            $startDate = $request->postVar('StartDate');
            $endDate   = $request->postVar('EndDate');

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
                $booking = CheckfrontBookingModel::create()->fromCheckfront($postVars, 'from-form');

                $bookingResponse = $this->api()->makeBooking($booking);
                if ($bookingResponse->isValid()) {
                    $paymentMethod = $this->getTokenInfo(CheckfrontModule::TokenPaymentTypeIndex);

                    if ($paymentMethod == CheckfrontModule::PaymentPayNow) {

                    }
                } else {

                    Session::setFormMessage(self::FormName, 'No way hose', 'bad');

                    return $this->buildBookingForm($request, array(
                            'Message' => $bookingResponse->getMessage()
                        ));
                }
            }
        }

        return array_merge(
            array(
                'Message' => 'Thanks for booking, you will receive email confirmation shortly'
            ),
            $templateData
        );
    }


    /**
     * Applies rules to determine if an item should be added to the form depending on the item
     * properties and the booking preset link and item types. Returns false if:
     *
     * -    item CategoryID is in config.exclude_user_type_items.userType.categories
     *
     * Otherwise returns true
     *
     * @param $item
     * @param string $userType - individual/organisation
     * @param string $linkType - private/public
     *
     * @return bool
     */
    private function shouldShowItem($item, $userType, $linkType) {
        $userTypes = self::get_config_setting('exclude_user_type_items');

        // exclude by item category
        if (isset($userTypes[$userType]['categories'])) {
            return !in_array($item->CategoryID, $userTypes[$userType]['categories']);
        }
        return true;
    }

    /**
     * Return config.name or config.name[key] if key provided and config.name is an array.
     *
     * @param $name
     * @param string|null $key
     *
     * @return mixed
     */
    protected static function get_config_setting($name, $key = null) {
        $value = Config::inst()->get(get_called_class(), $name);
        if ($key && is_array($value) && array_key_exists($key, $value)) {
            return $value[$key];
        }

        return $value;
    }

}