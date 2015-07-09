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

        $organiserLink = $this->makeLink(
            $accessKey,
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName],
            $postVars[CheckfrontLinkGeneratorForm::OrganiserEventFieldName],
            $postVars[CheckfrontLinkGeneratorForm::LinkTypeFieldName],
            CheckfrontModule::UserTypeOrganiser,
            $postVars[CheckfrontLinkGeneratorForm::PaymentTypeFieldName]
        );

        $individualLink = $this->makeLink(
            $accessKey,
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName],
            $postVars[CheckfrontLinkGeneratorForm::IndividualEventFieldName],
            $postVars[CheckfrontLinkGeneratorForm::LinkTypeFieldName],
            CheckfrontModule::UserTypeIndividual,
            $postVars[CheckfrontLinkGeneratorForm::PaymentTypeFieldName]
        );

        $form = $this->buildLinkGeneratorForm();

        return $this->renderWith(
            array('CheckfrontLinkGenerator', 'Page'),
            array(
                'Package' => $package,
                'Posted' => $request->postVars(),
                'AccessKey' => $accessKey,
                'OrganiserLink' => $organiserLink,
                'IndividualLink' => $individualLink,
                'CheckfrontForm' => $form
            )
        );
    }

    protected static function makeLink($accessKey, $itemID, $event, $linkType, $userType, $paymentType) {
        return CheckfrontModule::make_link($accessKey, $itemID, $event, $linkType, $userType, $paymentType);
    }
}