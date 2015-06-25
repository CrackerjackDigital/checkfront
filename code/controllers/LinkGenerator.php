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
        return new CheckfrontLinkGeneratorForm(
            $this,
            CheckfrontLinkGeneratorForm::FormName,
            new FieldList(),
            new FieldList()
        );
    }

    protected function show(SS_HTTPRequest $request) {
        return $this->renderWith(array('CheckfrontLinkGenerator', 'Page'));
    }

    protected function generate(SS_HTTPRequest $request) {
        return $this->renderWith(
            array('CheckfrontLinkGenerator', 'Page'),
            array(
                'Posted' => $request->postVars(),
                'Link' => CheckfrontModule::generate_link(
                    $request->postVars()
                 )
            )
        );
    }
}