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
    const MessageTypeKey        = 'MessageType';
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

    private static $exclude_user_type_items = array();

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
        $messageType = '';
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
                            $result = $this->listPackages();
                        }


                    } else {
                        // on GET show the access key form (the post actions will show the other forms of the flow).
                        $result = $this->buildAccessKeyForm($request);
                    }
                }
            } else {
                $result = $this->listPackages();
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $result = new ArrayData(array_merge(
            array(
                self::MessageKey => $message,
                self::MessageTypeKey => $messageType
            ),
            $result ?: array()
        ));
        return $result->renderWith(array(self::TemplateName, 'Page'));
    }

    /**
     * @return array
     */
    protected function listPackages() {
        return array(
            'PackageList' => $this->api()->listPackages()->getPackages()
        );
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
        $messageTypeKey = '';

        $result = array();

        try {

            $accessKey = $request->postVar(CheckfrontAccessKeyForm::AccessKeyFieldName);

            $tokenInfo = $this->getTokenInfo(
                null,
                $accessKey
            );
            list($packageID, $startDate, $endDate) = $tokenInfo;

            if (is_numeric($packageID)) {
                /** @var CheckfrontAPIPackageResponse $packageResponse */
                if ($packageResponse = $this->api()->fetchPackage($packageID, $startDate, $endDate)) {
                    if ($packageResponse->isValid()) {
                        // maybe mode down, we can still show a booking form even without items?
                        /** @var Form $form */
                        $form = CheckfrontPackageBookingForm::factory(
                            $this(),
                            $packageResponse,
                            $tokenInfo,
                            $request->postVars()
                        );

                        $form->setFormAction('/' . $this->owner->getRequest()->getURL());

                        $result = array(
                            'CurrentPackage' => $packageResponse->getPackage(),
                            self::FormName => $form
                        );
                    } else {
                        throw new CheckfrontException("Sorry, the package is no longer available", CheckfrontException::TypeError);
                    }
                } else {
                    throw new CheckfrontException("Failed to fetch package", CheckfrontException::TypeError);
                }
            } else {
                throw new CheckfrontException("Bad package ID '$packageID'", CheckfrontException::TypeError);
            }
        } catch (CheckfrontException $e) {
            $message = $e->getMessage();
            $messageTypeKey = $e->getType();

            Session::setFormMessage(CheckfrontPackageBookingForm::FormName, $message, 'bad');
        }

        return array_merge(
            array(
                self::MessageKey => $message,
                self::MessageTypeKey => $messageTypeKey
            ),
            $result
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
        $message = '';
        $messageType = '';

        $result = array();

        // only post request should route here
        $postVars = $request->postVars();

        try {
            $this->clearCheckfrontSession();

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
                            if ($quantity = $postVars['Quantity'][$index]) {
                                /**
                                 * CheckfrontAPIItemResponse
                                 */
                                $response = $this->api()->fetchItem($itemID, $quantity, $startDate, $endDate);

                                if ($response->isValid()) {
                                    if ($item = $response->getItem()) {
                                        $this->api()->addItemToSession($item);
                                    }
                                } else {
                                    throw new CheckfrontBookingException($response->getMessage(), CheckfrontException::TypeError);
                                }
                            }
                        }
                    }
                    $bookingResponse = $this->api()->makeBooking(
                        CheckfrontBookingModel::create_from_checkfront($postVars, 'from-form')
                    );

                    if ($bookingResponse->isValid()) {
                        $paymentMethod = $this->getTokenInfo(
                            CheckfrontModule::TokenPaymentTypeIndex,
                            $postVars[CheckfrontForm::AccessKeyFieldName]
                        );

                        if ($paymentMethod == CheckfrontModule::PaymentPayNow) {
                            $message = 'Thanks for booking, please click the link below to complete payment on your booking';
                            $messageType = CheckfrontException::TypeOK;

                            if ($paymentURL = $bookingResponse->getPaymentURL()) {
                                $result = array(
                                    'PaymentURL' => $paymentURL
                                );

                                $this()->redirect($paymentURL);
                            }

                        } else {

                            $message = 'Thanks for booking, you will receive email confirmation shortly';
                            $messageType = CheckfrontException::TypeOK;

                            $result = array(
                                'CurrentPackage' => $package,
                                'Booking' => $bookingResponse->getBooking(),
                                'Items' => $bookingResponse->getItems()
                            );
                        }

                    } else {

                        throw new CheckfrontBookingException($bookingResponse->getMessage(), CheckfrontException::TypeError);

                    }
                }
            }


        } catch (CheckfrontException $e) {
            $message = $e->getMessage();
            $messageType = $e->getType();

            $this->api()->clearSession();

            Session::setFormMessage(CheckfrontPackageBookingForm::FormName, $message, 'bad');

            $result = $this->buildBookingForm($request);

        }
        return array_merge(
            array(
                self::MessageKey => $message,
                self::MessageTypeKey => $messageType
            ),
            $result
        );

    }

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
        $request = $this->owner->getRequest();

        if ($token = $request->param(self::TokenParam)) {

            $detokenised = CheckfrontModule::decrypt_token($accessKey, $token);

            if (!is_null($which)) {
                if (!array_key_exists($which, $detokenised)) {
                    return null;
                }
                return $detokenised[$which];
            }
            return $detokenised;

        } else {
            throw new CheckfrontException("No token", CheckfrontException::TypeError);
        }
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
    public function shouldShowItem($item, $userType, $linkType) {
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