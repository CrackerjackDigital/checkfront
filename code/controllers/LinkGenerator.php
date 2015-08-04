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
            return $this->generateLinks($request);
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
    protected function generateLinks(SS_HTTPRequest $request) {
        $postVars = $request->postVars();

        $packageID = $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName];

        $packageResponse = CheckfrontModule::api()->fetchPackage($packageID);

        if (!$package = $packageResponse->getPackage()) {
            throw new CheckfrontException(_t('Package.NoSuchPackageMessage', "Package {id}not found", array('id' => $packageID)), CheckfrontException::TypeError);
        }
/*
        if (!$organiserEvent = $packageResponse->getEvent($postVars[CheckfrontLinkGeneratorForm::OrganiserEventFieldName])) {
            throw new CheckfrontException(_t('Package.NoSuchEventMessage', "{type}event not found", array('type' => 'Organiser ')));
        }
        if (!$individualEvent = $packageResponse->getEvent($postVars[CheckfrontLinkGeneratorForm::IndividualEventFieldName])) {
            throw new CheckfrontException(_t('Package.NoSuchEventMessage', "{type}event not found", array('type' => 'Individual ')));
        }
*/
        $accessKey = CheckfrontModule::crypto()->generate_key();

        $organiserLink = $this->makeLink(
            $accessKey,
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName],
            $postVars[CheckfrontLinkGeneratorForm::OrganiserStartDate],
            $postVars[CheckfrontLinkGeneratorForm::OrganiserEndDate],
            $postVars[CheckfrontLinkGeneratorForm::LinkTypeFieldName],
            CheckfrontModule::UserTypeOrganiser,
            $postVars[CheckfrontLinkGeneratorForm::PaymentTypeFieldName]
        );

        $individualLink = $this->makeLink(
            $accessKey,
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName],
            $postVars[CheckfrontLinkGeneratorForm::IndividualStartDate],
            $postVars[CheckfrontLinkGeneratorForm::IndividualEndDate],
            $postVars[CheckfrontLinkGeneratorForm::LinkTypeFieldName],
            CheckfrontModule::UserTypeIndividual,
            $postVars[CheckfrontLinkGeneratorForm::PaymentTypeFieldName]
        );

        $form = $this->buildLinkGeneratorForm();

        return $this->renderWith(
            array('CheckfrontLinkGenerator', 'Page'),
            array(
                'ShowOutput' => true,
                'Package' => $package,
                'Posted' => new ArrayData($postVars),
                'OrganiserLink' => $organiserLink,
                'IndividualLink' => $individualLink,
                'AccessKey' => $accessKey,
                'CheckfrontForm' => $form
            )
        );
    }

    /**
     * Returns and shortened URL which will redirect from CheckfrontModule.config.shortened_endpoint to
     * the full booking path when hit.
     *
     * @param $accessKey
     * @param $itemID
     * @param $startDate
     * @param $endDate
     * @param $linkType
     * @param $userType
     * @param $paymentType
     *
     * @return String
     * @throws ValidationException
     * @throws null
     */
    protected static function makeLink($accessKey, $itemID, $startDate, $endDate, $linkType, $userType, $paymentType) {
        $endPoint = Controller::join_links(
            CheckfrontModule::PrivateEndPoint,
            'package'
        );
        $fullURL = CheckfrontModule::make_link($accessKey, $endPoint, $itemID, $startDate, $endDate, $linkType, $userType, $paymentType);

        $shortURL = new CheckfrontShortenedURL(array(
            'URL' => $fullURL
        ));
        $shortURL->write();

        return Controller::join_links(
            Director::absoluteBaseURL(),
            CheckfrontModule::shorturl_endpoint(),
            $shortURL->Key
        );
    }
}