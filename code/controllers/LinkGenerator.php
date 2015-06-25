<?php


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
        if ($request->isGET()) {
            return $this->show($request);
        } else {
            return $this->generate($request);
        }
    }

    public function CheckfrontLinkGeneratorForm() {
        $form = new CheckfrontLinkGeneratorForm(
            $this,
            CheckfrontLinkGeneratorForm::FormName,
            new FieldList(),
            new FieldList()
        );
        $form->setFormAction($this->getRequest()->getURL());
        return $form;
    }

    protected function show(SS_HTTPRequest $request) {
        return $this->renderWith(array('CheckfrontLinkGenerator', 'Page'));
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

        $accessKey = CheckfrontModule::make_access_key();

        $link = $this->makeLink(
            $postVars[CheckfrontLinkGeneratorForm::TypeFieldName],
            $accessKey,
            $postVars[CheckfrontLinkGeneratorForm::PackageIDFieldName],
            $postVars[CheckfrontLinkGeneratorForm::StartDateFieldName],
            $postVars[CheckfrontLinkGeneratorForm::EndDateFieldName]
        );

        return $this->renderWith(
            array('CheckfrontLinkGenerator', 'Page'),
            array(
                'Package' => $package,
                'Posted' => $request->postVars(),
                'AccessKey' => base64_encode($accessKey),
                'Link' => $link
            )
        );
    }

    /**
     * Returns array of accessKey and link.
     *
     * @param $typeEndPoint - e.g 'public' or 'private'
     * @param $accessKey - plain access key (e.g. not base64 encoded)
     * @param $itemID
     * @param $startDate
     * @param $endDate
     *
     * @internal param array $parameters - e.g. from postVars
     *
     * @return array - [accessKey, $link]
     */
    protected static function makeLink($typeEndPoint, $accessKey, $itemID, $startDate, $endDate) {
        $link = Controller::join_links(
            Director::absoluteBaseURL(),
            $typeEndPoint,
            CheckfrontModule::encode_link_segment(
                $accessKey,
                $itemID,
                $startDate,
                $endDate
            )
        );
    }
}