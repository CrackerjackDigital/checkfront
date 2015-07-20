<?php

class CheckfrontLinkGeneratorForm extends CheckfrontForm {
    const FormName         = 'CheckfrontLinkGeneratorForm';
    const SubmitButtonName = 'generate';
    const DefaultEndpoint  = 'private';
    const OrganiserStartDate = 'OrganiserStartDate';
    const OrganiserEndDate = 'OrganiserEndDate';
    const IndividualStartDate = 'IndividualStartDate';
    const IndividualEndDate = 'IndividualEndDate';

    private static $allowed_actions = array(
        'generate' => true
    );

    /**
     * Creates form with:
     *  -   Package Selector dropdown
     *  -   Start Date field
     *  -   End Date field
     *  -   'Type' field (public/private) which indicates endpoint to book package.
     *  -   'generate' action.
     *
     * @param array $controller
     * @param array $nameOverride - use this instead of default self.FormName
     * @param $fields
     * @param $actions
     * @param null $validator
     */
    public function __construct($controller, $nameOverride, $fields, $actions, $validator = null) {
        /** @var SS_HTTPRequest $request */
        $request = $controller->getRequest();

        $fields  = $fields ? : new FieldList();
        $actions = $actions ? : new FieldList();

        // list skus available via the API or get empty array if fails
        if ($apiResponse = CheckfrontModule::api()->listPackages()) {

            $fields->push(
                $this->makePackageSelectorField($apiResponse, $request)
            );

            // add private endpoint, user type and payment type fields
            $fields->merge(array(
                $this->makeDateField(self::OrganiserStartDate, ''),
                $this->makeDateField(self::OrganiserEndDate, ''),
                $this->makeDateField(self::IndividualStartDate, ''),
                $this->makeDateField(self::IndividualEndDate, ''),
                $this->makeLinkTypeField(),
                $this->makePaymentTypeField()
            ));
            $actions->merge(
                new FieldList(array(
                    new FormAction(
                        static::SubmitButtonName,
                        $this->getFieldLabel(static::SubmitButtonName)
                    )
                ))
            );
        }
        // all fields are mandatory
        $validator = new RequiredFields(
            array_keys($fields->toArray())
        );

        parent::__construct(
            $controller,
            $nameOverride ? : self::FormName,
            $fields,
            $actions,
            $validator
        );
    }

    /**
     * Return a package selector and bound event selectors (one for each user type), with javascript template
     * included to filter events by package.
     *
     * NB: not used at the moment as checkfront api won't return both packages and package items at the same time!!!
     *
     * @param CheckfrontAPIPackagesResponse $apiResponse
     * @param SS_HTTPRequest $request
     *
     * @return DisplayLogicWrapper
     */
    public function makePackageAndEventSelectorField(CheckfrontAPIPackagesResponse $apiResponse, SS_HTTPRequest $request) {
        $fields = new FieldList(array(
            $this->makePackageSelectorField(
                $apiResponse,
                $request,
                self::PackageIDFieldName
            )
        ));
        foreach (CheckfrontModule::user_types() as $userType => $title) {
            $fields->push(
                $this->makePackageEventSelectorField(
                    $apiResponse,
                    $request,
                    $title . 'Event'
                )
            );
        };

        return new DisplayLogicWrapper(new CompositeField($fields));
    }

    /**
     * Returns a drop-down field configured from an api.listPackages call.
     *
     * NB commented code is for if they (checkfront) get events and items returning at same
     * time for packages via API at the moment can be one or the other depedning on the package
     * 'parent' or 'group' type.
     *
     * @param CheckfrontAPIPackagesResponse $apiResponse
     * @param SS_HTTPRequest $request
     * @param string $name
     *
     * @return DropdownField
     */

    protected function makePackageSelectorField(CheckfrontAPIPackagesResponse $apiResponse, SS_HTTPRequest $request, $name = self::PackageIDFieldName) {
        $options = $this->getAvailablePackagesMap($apiResponse);

        $field = new DropdownField(
            $name,
            $this->getFieldLabel($name),
            $options,
            $request->postVar($name)
        );
        //  $field->addExtraClass(self::PackageSelector);
        $field->setAttribute('placeholder', $this->getFieldLabel($name, 'FieldEmptyString'));
        $field->setEmptyString($this->getFieldLabel($name, 'FieldEmptyString'));

        return $field;
    }

    /**
     * REturn a list of packages as an ID => Title map suitable for using in a dropdown list.
     *
     * @param CheckfrontAPIPackageResponse $apiResponse
     *
     * @return array
     */
    protected function getAvailablePackagesMap(CheckfrontAPIPackagesResponse $apiResponse) {
        $options = array();
        if ($packages = $apiResponse->getPackages()) {
            foreach ($packages as $package) {
                $options[$package->ItemID] = $package->Title;
            }
        }
        return $options;
    }

    protected function makeLinkTypeField($defaultEndpoint = self::DefaultEndpoint) {
        $field = new HiddenField(
            self::LinkTypeFieldName,
            $this->getFieldLabel(self::LinkTypeFieldName),
            'private'
        );
        return $field;
    }

    protected function makePaymentTypeField() {
        return new DropdownField(
            self::PaymentTypeFieldName,
            $this->getFieldLabel(self::PaymentTypeFieldName),
            CheckfrontModule::payment_types()
        );
    }


    /**
     * Package/links can be private or public
     * @return array
     */
    public static function access_types() {
        return static::config()->get('access_types');
    }

    /**
     * Logic is in the controller so call there with the incoming requesst.
     * @return mixed
     */
    public function generate_link() {
        return $this->controller->generate_link($this->controller->getRequest());
    }

}