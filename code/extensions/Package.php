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

    /**
     * Figures out if we are GET or POST and with posted info determines if we need
     * to enter AccessToken or can access the booking form. Returns page with correct form.
     *
     * @param SS_HTTPRequest $request
     *
     * @return array
     */
    public function index(SS_HTTPRequest $request) {
        // laughed until I stopped.
        $result = array(
            'Message' => 'Something went wrong'
        );
        try {
            if ($request->isPOST()) {

                if ($this->isAction($request, CheckfrontAccessKeyForm::SubmitButtonName)) {

                    // process AccessKeyForm submission
                    $result = $this->buildBookingForm($request);

                } elseif ($this->isAction($request, CheckfrontBookingForm::SubmitButtonName)) {

                    // process BookingForm submission
                    $result = $this->book($request);
                }
            } elseif ($request->param(CheckfrontPackageControllerExtension::TokenParam)) {

                // on GET show the access key form (the post actions will show the other forms of the flow).
                $result = $this->buildAccessKeyForm($request);
            }
        } catch (Exception $e) {
            $result = array(
                'Message' => $e->getMessage()
            );
        }

        return $this()->renderWith(
            array('CheckfrontBookingPage', 'Page'),
            $result
        );
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
            if ($accessKey) {
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
            }
        };

        if ($detokenised) {

            if (!is_null($which)) {
                if (!array_key_exists($which, $detokenised)) {
                    throw new Exception("Bad which '$which'");
                }
                if (empty($detokenised[$which])) {
                    throw new Exception("No which '$which'");
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
    protected function buildBookingForm(SS_HTTPRequest $request) {

        // access key posted by AccessKeyForm is from cryptofier.generate_key via the original link generator
        $accessKey = $request->postVar(CheckfrontAccessKeyForm::AccessKeyFieldName);

        if (!$accessKey) {
            throw new Exception("No access key posted");
        }

        try {
            // check entered key is a valid Crypto key first, should throw exception if not
            CheckfrontModule::crypto()->encrypt('somethingrandomheredontcarewhat', $accessKey);
        } catch (CryptofierException $e) {
            Session::setFormMessage('', 'Invalid access key', 'bad');

            return $this()->redirectBack();
        }

        $tokenInfo = $this->getTokenInfo(null, $accessKey);

        $session = CheckfrontModule::session();

        $cachedPackage = $session->getData(self::PackageSessionKey);

        $package = null;

        if ($cachedPackage) {

            $package = CheckfrontPackageModel::create()->fromCheckfront($cachedPackage);

        } else {
            // no session, try fetch package again and store in session
            $session->clearData(self::PackageSessionKey);

            $packageID = $this->getTokenInfo(CheckfrontModule::TokenItemIDIndex, $accessKey);

            if (is_numeric($packageID)) {

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

        $form = $this->buildPackageBookingForm(
            $accessKey,
            $tokenInfo[CheckfrontModule::TokenItemIDIndex],
            $tokenInfo[CheckfrontModule::TokenStartDateIndex],
            $tokenInfo[CheckfrontModule::TokenEndDateIndex],
            $tokenInfo[CheckfrontModule::TokenLinkTypeIndex],
            $tokenInfo[CheckfrontModule::TokenUserTypeIndex]
        );

        return array(
            'Package'        => $package,
            'CheckfrontForm' => $form
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
        $form->setFormAction('/' . $this()->getRequest()->getURL());

        return array(
            'CheckfrontForm' => $form
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
    protected function book(SS_HTTPRequest $request) {
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

                $paymentMethod = $this->getTokenInfo(CheckfrontModule::TokenPaymentTypeIndex);

                if ($paymentMethod == CheckfrontModule::PaymentPayNow) {

                }

                if (true /*!$response->isValid() */) {
                    Session::setFormMessage(self::FormName, 'No way hose', 'bad');

                    return $this->buildBookingForm($request);
                }
            }
        }

        return array(
            'Message' => 'Thanks for booking, you will receive email confirmation shortly'
        );
    }



    /**
     * Returns a form suitable for booking a package.
     * Form will post back to the request url.
     *
     * @param $accessKey
     * @param $packageID
     * @param $startDate
     * @param $endDate
     * @param $linkType
     * @param $userType
     *
     * @return CheckfrontForm
     */
    private function buildPackageBookingForm($accessKey, $packageID, $startDate, $endDate, $linkType, $userType) {
        $fields = new FieldList(array(
            new HiddenField(CheckfrontAccessKeyForm::AccessKeyFieldName, '', $accessKey)
        ));

        if ($packageResponse = $this->api()->fetchPackage($packageID, $startDate, $endDate)) {

            if ($packageResponse->isValid()) {

                // add the package items to the field list which will make the form as fields
                /** @var CheckfrontModel $item */
                foreach ($packageResponse->getPackageItems() as $item) {
                    if ($this->shouldShowItem($item, $linkType, $userType)) {
                        $fields->merge($item->fieldsForForm('form'));
                    }
                }

            }
        }
        $form = new CheckfrontBookingForm(
            $this->owner,
            '',
            $fields,
            new FieldList()
        );
        $form->setFormAction('/' . $this()->getRequest()->getURL());

        return $form;
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
     * @param $linkType
     * @param $userType
     *
     * @return bool
     */
    private function shouldShowItem($item, $linkType, $userType) {
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