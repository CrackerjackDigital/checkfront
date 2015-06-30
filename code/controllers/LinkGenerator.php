<?php
use \Defuse\Crypto\Crypto as Crypto;

class CheckfrontLinkGeneratorController extends ContentController {
    private static $allowed_actions = array(
        'index' => true,
    );

    /**
     * Requires user to be logged in (via BasicAuth if not already logged in).
     * @return SS_HTTPResponse|void
     */
    public function init() {
        BasicAuth::requireLogin('Please login');
        parent::init();
    }

    public function index(SS_HTTPRequest $request) {
        if ($request->isPOST()) {
            return $this->generateLink($request);
        } else {
            return $this->show($request);
        }
    }

    protected function buildLinkGeneratorForm() {
        $form = new CheckfrontLinkGeneratorForm(
            $this,
            '',
            new FieldList(),
            new FieldList()
        );
        $form->setFormAction('/' . $this->getRequest()->getURL());
        return $form;
    }

    /**
     * Return rendered CheckfrontLinkGenerator template. This has embedded CheckfrontLinkGeneratorForm
     * where fields are shown.
     *
     * @param SS_HTTPRequest $request
     *
     * @return HTMLText
     */
    protected function show(SS_HTTPRequest $request) {
        return $this->renderWith(
            array('CheckfrontLinkGenerator', 'Page'),
            array(
                'CheckfrontForm' => $this->buildLinkGeneratorForm(),
                'Controller' => $this
            )
        );
    }

    /**
     * Renders the CheckfrontLinkGenerator template with filled in
     *  -   Package
     *  -   Posted array info
     *  -   AccessKey encoded so can copy/paste
     *  -   Link to copy paste to email
     *
     * @param SS_HTTPRequest $request
     *
     * @return HTMLText
     */
    protected function generateLink(SS_HTTPRequest $request) {
        $postVars = $request->postVars();

        $package = CheckfrontModule::api()->fetchPackage(
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName]
        )->getPackage();

        $accessKey = CheckfrontModule::crypto()->generate_key();

        $link = $this->makeLink(
            $accessKey,
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName],
            $postVars[CheckfrontLinkGeneratorForm::StartDateFieldName],
            $postVars[CheckfrontLinkGeneratorForm::EndDateFieldName],
            $postVars[CheckfrontLinkGeneratorForm::LinkTypeFieldName],
            $postVars[CheckfrontLinkGeneratorForm::UserTypeFieldName],
            $postVars[CheckfrontLinkGeneratorForm::PaymentTypeFieldName]
        );

        $form = $this->buildLinkGeneratorForm();

        return $this->renderWith(
            array('CheckfrontLinkGenerator', 'Page'),
            array(
                'Package' => $package,
                'Posted' => $request->postVars(),
                'AccessKey' => $accessKey,
                'BookingLink' => $link,
                'CheckfrontForm' => $form
            )
        );
    }

    /**
     * Returns link to booking on the site depending on options provided, this function
     * binds to the parameters in the token via the number of parameters on the method.
     *
     * @param $accessKey    - from Cryptofier.generate_key
     * @param $itemID
     * @param $startDate
     * @param $endDate
     * @param $linkType - e.g 'public' or 'private'
     * @param $userType - e.g 'organisation' or 'individual'
     * @param $paymentType - e.g 'pay-now' or 'pay-later'
     *
     * @return string - link to page on site either via BookingPage or the CheckfrontPackageController
     */
    protected static function makeLink($accessKey, $itemID, $startDate, $endDate, $linkType, $userType, $paymentType) {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $linkType,
            CheckfrontModule::crypto()->encrypt_token(array(
                    $itemID,
                    $startDate,
                    $endDate,
                    $linkType,
                    $userType,
                    $paymentType
                ),
                $accessKey
            )
        );
    }
}