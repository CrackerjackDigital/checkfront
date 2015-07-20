<?php

/**
 * A package oriented controller extension which can be added to a Page_Controller to provide
 * checkfont package booking functionality.
 * Expects an item ID on the incoming request which points to a Package in Checkfront. Uses http request
 * mode GET/POST to figure out what action to perform.
 */
class CheckfrontPackageControllerExtension extends CheckfrontControllerExtension {
    const TemplateName      = 'CheckfrontBookingPage';
    const FormName          = 'CheckfrontForm';
    const MessageKey        = 'Message';
    const PackageSessionKey = 'package';
    const PackageIDKey      = 'package-id';
    const FormSessionKey    = 'form';
    const TokenParam        = 'Token';

    private static $allowed_actions = array(
        'package' => true
    );

    private static $url_handlers = array(
        'package/$Token!' => 'package'
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


    public function PackageList() {
        $packageList = $this->api()->listPackages()->getPackages();
        return $packageList;
    }

    /**
     * Figures out if we are GET or POST and with posted info determines if we need
     * to enter AccessToken or can access the booking form. Returns page with correct form.
     *
     * @param SS_HTTPRequest $request
     *
     * @return HTMLText - rendered 'CheckfrontBookingPage' template
     */
    public function package(SS_HTTPRequest $request) {
        $result = array();
        $message = '';
        $isPublic = $this->owner->checkfrontPublicPage();

        try {

            if (isset($request)) {
                if ($request->isPOST()) {

                    if ($this->isAction($request, CheckfrontAccessKeyForm::SubmitButtonName)) {

                        // process AccessKeyForm submission
                        $result = $this->buildBookingForm($request);

                    } elseif ($this->isAction($request, CheckfrontPackageBookingForm::SubmitButtonName)) {

                        // process BookingForm submission
                        $result = $this->book($request);
                    }
                } else {
                    if ($isPublic) {
                        // no access key required
                        if ($request->param(self::TokenParam)) {
                            // url has a token so show the booking form

                            $result = $this->buildBookingForm($request);

                        } else {
                            // no token, render the page which should show the package list
                            $result = array(
                                'PackageList' => $this->PackageList()
                            );
                        }


                    } else {
                        // on GET show the access key form (the post actions will show the other forms of the flow).
                        $result = $this->buildAccessKeyForm($request);
                    }
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $result = array_merge(
            array(
                'Message' => $message,
            ),
            $result
        );
        return $this->owner->customise($result)->renderWith(array(self::TemplateName, 'Page'));
    }

    /**
     * Check if we already have a checkfront session. If not then create one with the package
     * identified on the request url as Token. The existance of the session implies the package
     * has already been added so doesn't re-add.
     * NB: the AccessKey entered on the 'showAccessKey' form should be
     *
     * @return array [ Message | CheckfrontForm => form, Package => package, Event = event|null ]
     */
    protected function buildBookingForm(SS_HTTPRequest $request) {
        $message = '';
        $result = array();

        try {

            // access key posted by AccessKeyForm is from cryptofier.generate_key via the original link generator
            $accessKey = $request->postVar(CheckfrontAccessKeyForm::AccessKeyFieldName);

            if (!$this->owner->checkfrontPublicPage()) {
                try {
                    // check entered key is a valid Crypto key first, should throw exception if not
                    CheckfrontModule::crypto()->encrypt('somethingrandomheredontcarewhat', $accessKey);

                    list($packageID, $startDate, $endDate, $linkType, $userType, $paymentType) = $this->getTokenInfo(null, $accessKey);

                } catch (CryptofierException $e) {
                    throw new CheckfrontException("Invalid access token");
                }
            } else {
                list($packageID, $startDate, $endDate, $linkType, $userType, $paymentType) = $this->getTokenInfo();
            }

            if (is_numeric($packageID)) {
                /** @var CheckfrontAPIPackageResponse $packageResponse */
                if ($packageResponse = $this->api()->fetchPackage($packageID)) {

                    if ($packageResponse->isValid()) {
                        // re-query with start and end date
                        if ($packageResponse = $this->api()->fetchPackage($packageID, $startDate, $endDate)) {

                            if ($packageResponse->isValid()) {
                                $package = $packageResponse->getPackage();

                                // now build the form
                                $fields = new FieldList();

                                // add a hidden accessKey field
                                $fields->push(new HiddenField(CheckfrontForm::AccessKeyFieldName, '', $accessKey));

                                // if not organiser then add visible start and end date fields for the actual booking
                                if ($userType !== CheckfrontModule::UserTypeOrganiser) {
                                    $fields->merge(array(
                                            CheckfrontForm::make_date_field(
                                                CheckfrontForm::StartDateFieldName,
                                                'Start Date',
                                                $startDate,
                                                CheckfrontModule::NullDate
                                            ),
                                            CheckfrontForm::make_date_field(
                                                CheckfrontForm::EndDateFieldName,
                                                'End Date',
                                                CheckfrontModule::NullDate,
                                                $endDate
                                            )
                                        )
                                    );
                                } else {
                                    $fields->merge(array(
                                        new HiddenField(CheckfrontForm::StartDateFieldName, '', $startDate),
                                        new HiddenField(CheckfrontForm::EndDateFieldName, '', $endDate)
                                    ));
                                }

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
                                        )
                                    )
                                );

                                // maybe mode down, we can still show a booking form even without items?
                                /** @var Form $form */
                                $form = new CheckfrontPackageBookingForm(
                                    $this->owner,
                                    self::FormName,
                                    $fields,
                                    new FieldList()
                                );
                                $form->setFormAction('/' . $this->owner->getRequest()->getURL());

                                // TODO: make sure there's nothing nasty we're loading here
                                $form->loadDataFrom($request->postVars());

                                $result = array(
                                    self::FormName => $form,
                                    'CurrentPackage' => $package
                                );
                            }
                        }
                    } else {
                        throw new CheckfrontException( _t("Package.NoSuchPackageMessage", "Sorry, the package is no longer available"));
                    }
                }
            }
        } catch (CheckfrontException $e) {
            $message = $e->getMessage();
        }

        return array_merge(
            $result,
            array(
                self::MessageKey => $message
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
    protected function buildAccessKeyForm(SS_HTTPRequest $request) {

        CheckfrontModule::session()->clear(null);

        $form = new CheckfrontAccessKeyForm(
            $this->owner,
            '',
            new FieldList(),
            new FieldList()
        );
        $form->setFormAction('/' . $this->owner->getRequest()->getURL());
        return array(
            self::FormName => $form
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
        $result = array();

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

                    $result = array_merge(
                        array(
                            self::FormName => $this->buildBookingForm($request)
                        ),
                        array(
                            self::MessageKey => $bookingResponse->getMessage()
                        )
                    );
                }
            }
        }

        return array_merge(
            array(
                self::MessageKey => 'Thanks for booking, you will receive email confirmation shortly'
            ),
            $result,
            $templateData
        );
    }

    /**
     *
     * Check if the current URL maps to a page on the site, in which case is 'Public' otherwise is private
     * (e.g. goes directly to controller).
     *
     * @return bool
     */
    protected function isPublic() {
        /** @var ContentController $controller */
        $controller = $this->owner;
        $url = $controller->getRequest()->getURL();

        if ($page = SiteTree::get_by_link($url)) {
            return true;
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
            $request = $this->owner->getRequest();

            if ($token = $request->param(self::TokenParam)) {

                $detokenised = CheckfrontModule::decrypt_token($accessKey, $token);

                // cache the token for retrieval later
                CheckfrontModule::session()->setToken(
                    $detokenised
                );

            } else {
                throw new CheckfrontException("No token");
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
    /**
     * Override parent return types mainly for ease of coding.
     * @return CheckfrontAPIImplementation|CheckfrontAPIBookingFormEndpoint|CheckfrontAPIPackagesEndpoint|CheckfrontAPIItemsEndpoint|CheckfrontAPIBookingEndpoint|CheckfrontAPISessionEndpoint
     */
    protected function api() {
        return parent::api();
    }


    /**
     * @return ContentController
     */
    public function __invoke() {
        return $this->owner;
    }
}