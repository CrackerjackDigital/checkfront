<?php
use \Defuse\Crypto\Crypto as Crypto;

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

    private static $allowed_actions = array(
        'index' => true
    );

    private static $form_name = self::FormName;

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
        try {
            if ($request->isPOST()) {

                if ($this->isAction($request, CheckfrontAccessKeyForm::SubmitButtonName)) {

                    // process AccessKeyForm submission
                    return $this->showBookingForm($request);

                } elseif ($this->isAction($request, CheckfrontBookingForm::SubmitButtonName)) {

                    // process BookingForm submission
                    return $this->book($request);
                }
            } else {

                // on GET show the access key form (the post actions will show the other forms of the flow).
                return $this->showAccessKeyForm($request);
            }
        } catch (Exception $e) {
            // bad token
            $this()->httpError(500, $e->getMessage());
        }
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
        if (!$detokenised = CheckfrontModule::session()->getData('Token')) {
            if ($accessKey) {
                $request = $this()->getRequest();

                if ($token = $request->param(self::TokenParam)) {

                    // we strip out the access key from the static variable cached values as it won't be used again
                    CheckfrontModule::session()->setTokenInfo(
                        CheckfrontCryptoService::decrypt_token($accessKey, $token)
                    );

            } else {

                    throw new Exception("No token");
                }

            }
        };
        if ($which) {
            if (!array_key_exists($which, $detokenised)) {
                throw new Exception("Bad which '$which'");
            }
            if (empty($detokenised[$which])) {
                throw new Exception("No which '$which'");
            }
            return $detokenised[$which];
        }
        return $detokenised;
    }

    /**
     * Check if we already have a checkfront session. If not then create one with the package
     * identified on the request url as Token. The existance of the session implies the package
     * has already been added so doesn't re-add.
     *
     * NB: the AccessKey entered on the 'showAccessKey' form should be
     */
    protected function showBookingForm(SS_HTTPRequest $request) {

        // access key posted by AccessKeyForm is from the original link generator so is bin2hex encoded
        // we need to hex2bin it before using it.
        $accessKey = Crypto::hexTobin($request->postVar(CheckfrontAccessKeyForm::AccessKeyFieldName));

        if (!$accessKey) {
            throw new Exception("No access key posted");
        }

        // check entered key is a valid Crypto key first, should throw exception if not
        Crypto::encrypt('somethingrandomheredontcarewhat', $accessKey);

        $session = CheckfrontModule::session();

        $cachedPackage = $session->getData(self::PackageSessionKey);

        $package = null;

        if ($cachedPackage) {

            $package = CheckfrontPackageModel::create()->fromCheckfront($cachedPackage);

        } else {
            // no session, try fetch package again and store in session
            $session->clearData(self::PackageSessionKey);

            $packageID = $this->getTokenInfo(CheckfrontModule::TokenAccessKeyIndex);

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

        $form = $this->buildPackageBookingForm(
            $this->getTokenInfo(CheckfrontModule::TokenItemIDIndex),
            $this->getTokenInfo(CheckfrontModule::TokenStartDateIndex),
            $this->getTokenInfo(CheckfrontModule::TokenEndDateIndex)
        );

        return array(
            'Package' => $package,
            'CheckfrontForm' => $form
        );
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return array
     */
    protected function showAccessKeyForm(SS_HTTPRequest $request) {
        $form = $this->buildAccessKeyForm();

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
     * @return CheckfrontForm
     */
    protected function book(SS_HTTPRequest $request) {
        // only post request should route here
        $postVars = $request->postVars();

        $packageID = $this->getTokenInfo(CheckfrontModule::TokenAccessKeyIndex);

        if ($request->isPOST()) {

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
     * Returns a simple form where user can enter the access key they have been provided.
     *
     * Form will post back to the request url.
     *
     * @return CheckfrontAccessKeyForm
     */
    private function buildAccessKeyForm() {
        $form = new CheckfrontAccessKeyForm(
            $this->owner,
            '',
            new FieldList(),
            new FieldList()
        );
        $form->setFormAction('/' . $this()->getRequest()->getURL());
        return $form;
    }

    /**
     * Returns a form suitable for booking a package.
     *
     * Form will post back to the request url.
     *
     * @param $packageID
     * @param $startDate
     * @param $endDate
     *
     * @return CheckfrontForm
     */
    private function buildPackageBookingForm($packageID, $startDate, $endDate) {
        $fields = new FieldList();

        if ($packageResponse = $this->api()->fetchPackage($packageID, $startDate, $endDate)) {

            if ($packageResponse->isValid()) {

                // add the package items to the field list which will make the form as fields
                /** @var CheckfrontModel $item */
                foreach ($packageResponse->getPackageItems() as $item) {
                    $fields->merge($item->fieldsForForm('form'));
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

}