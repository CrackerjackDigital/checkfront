<?php

class CheckfrontLinkGeneratorForm extends CheckfrontForm {
    const FormName         = 'CheckfrontLinkGeneratorForm';
    const SubmitButtonName = 'generate';
    const DefaultEndpoint  = 'private';
    const OrganiserEventFieldName = 'OrganiserEvent';
    const IndividualEventFieldName = 'IndividualEvent';

    const PackageSelector = 'package-selector';
    const PackageEventSelector = 'package-event-selector';

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
                $this->makePackageAndEventSelectorField($apiResponse, $request)
            );

            // add private endpoint, user type and payment type fields
            $fields->merge(array(
                $this->makeLinkTypeField(),
//                $this->makeUserTypeField(),
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
     * @param CheckfrontAPIPackagesResponse $apiResponse
     * @param SS_HTTPRequest $request
     * @param string $name
     *
     * @return DropdownField
     */

    protected function makePackageSelectorField(CheckfrontAPIPackagesResponse $apiResponse, SS_HTTPRequest $request, $name = self::PackageIDFieldName) {
        $options = array();

        if ($packages = $apiResponse->getPackages()) {

            $jsArray = array();

            /** @var CheckfrontPackageModel $package */
            foreach ($packages as $package) {
                $options[$package->ItemID] = $package->Title;

                $packageResponse = CheckfrontModule::api()->fetchPackage($package->ItemID);
                if ($packageResponse->isValid()) {
                    $events = $packageResponse->getEvents();

                    foreach ($events as $eventID => $eventInfo) {
                        $jsArray[$package->ItemID][] = array_merge(
                            array(
                                'id' => $eventID
                            ),
                            $eventInfo
                        );
                    }
                }
            }
            $json = str_replace(array("'", '"'), array("\\'", "'"), Convert::array2json($jsArray));

            Requirements::javascript('framework/thirdparty/jquery/jquery.min.js');

            Requirements::javascriptTemplate(
                CheckfrontModule::module_path() . '/js/package-selector-field.js',
                array(
                    'PackageFieldSelector' => self::PackageSelector,
                    'PackageEventFieldSelector' => self::PackageEventSelector,
                    'PackageEventMap' => $json
                )
            );
        }
        $field = new DropdownField(
            $name,
            $this->getFieldLabel($name),
            $options,
            $request->postVar($name)
        );
        $field->addExtraClass(self::PackageSelector);
        $field->setAttribute('placeholder', $this->getFieldLabel($name, 'FieldEmptyString'));
        $field->setEmptyString($this->getFieldLabel($name, 'FieldEmptyString'));

        return $field;
    }

    /**
     * @param CheckfrontAPIPackagesResponse $apiResponse
     * @param SS_HTTPRequest $request
     * @param $name
     *
     * @return DropdownField
     */
    protected function makePackageEventSelectorField(CheckfrontAPIPackagesResponse $apiResponse, SS_HTTPRequest $request, $name) {
        $field = new DropdownField(
            $name,
            $this->getFieldLabel($name),
            array(),                        // list will be populated from js on change of package
            $request->postVar($name)
        );
        $field->addExtraClass(self::PackageEventSelector);
        $field->setAttribute('placeholder', $this->getFieldLabel($name, 'FieldEmptyString'));
        $field->setEmptyString($this->getFieldLabel($name, 'FieldEmptyString'));

        return $field;
    }

    protected function makeLinkTypeField($defaultEndpoint = self::DefaultEndpoint) {
        $endpoints = CheckfrontModule::endpoints();

        if (static::config()->get('allow_link_type_selection')) {
            $field = new DropdownField(
                self::LinkTypeFieldName,
                $this->getFieldLabel(self::LinkTypeFieldName),
                $endpoints
            );
        } else {
            $field = new HiddenField(
                self::LinkTypeFieldName,
                $this->getFieldLabel(self::LinkTypeFieldName),
                $endpoints[$defaultEndpoint]
            );
        }

        return $field;
    }

    protected function makeUserTypeField() {
        return new DropdownField(
            self::UserTypeFieldName,
            $this->getFieldLabel(self::UserTypeFieldName),
            CheckfrontModule::user_types()
        );
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