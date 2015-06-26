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
            return $this->generate($request);
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
     *  -   AccessKey base64_encode so can copy/paste
     *  -   Link to copy paste to email
     *
     * @param SS_HTTPRequest $request
     *
     * @return HTMLText
     */
    protected function generate(SS_HTTPRequest $request) {
        $postVars = $request->postVars();

        $package = CheckfrontModule::api()->fetchPackage(
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName]
        )->getPackage();

        $accessKey = CheckfrontModule::crypto()->make_access_key();

        $link = $this->makeLink(
            $accessKey,
            $postVars[CheckfrontLinkGeneratorForm::TypeFieldName],
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName],
            $postVars[CheckfrontLinkGeneratorForm::StartDateFieldName],
            $postVars[CheckfrontLinkGeneratorForm::EndDateFieldName]
        );

        $form = $this->buildLinkGeneratorForm();

        // NB: for the output we encode (e.g. binToHex) the accessKey to make it more user-friendly
        return $this->renderWith(
            array('CheckfrontLinkGenerator', 'Page'),
            array(
                'Package' => $package,
                'Posted' => $request->postVars(),
                'AccessKey' => CheckfrontModule::crypto()->encode($accessKey),
                'BookingLink' => $link,
                'CheckfrontForm' => $form
            )
        );
    }

    /**
     * Returns link to booking on the site, with the encoded token further urlencoded
     *
     * @param $typeEndPoint - e.g 'public' or 'private'
     * @param $accessKey - plain access key (e.g. not base64 encoded)
     * @param $itemID
     * @param $startDate
     * @param $endDate
     *
     * @internal param array $parameters - e.g. from postVars
     *
     * @return string - link to page on site either via BookingPage or the CheckfrontPackageController
     */
    protected static function makeLink($accessKey, $typeEndPoint, $itemID, $startDate, $endDate) {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $typeEndPoint,
            CheckfrontModule::crypto()->encrypt_token(
                $accessKey,
                $itemID,
                $startDate,
                $endDate
            )
        );
    }
}