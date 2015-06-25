<?php

class CheckfrontLinkGeneratorForm extends CheckfrontForm {
    const FormName = 'CheckfrontLinkGeneratorForm';
    const PackageIDFieldName = 'PackageID';
    const StartDateFieldName = 'StartDate';
    const EndDateFieldName = 'EndDate';
    const AccessKeyFieldName = 'AccessKey';
    const TypeFieldName = 'Type';

    private static $allowed_actions = array(
        'generate'
    );

    private static $access_types = array(
        'private' => 'Private',
        'public' => 'Public'
    );

    private static $date_field_config = array(
        'showcalender' => true,
        'min' => '+1 day',
        'max' => '+2 year'
    );

    public function __construct($controller, $name, $fields, $actions, $validator = null) {
        $request = $controller->getRequest();

        $fields = $fields ?: new FieldList();
        $actions = $actions ?: new FieldList();

        $fields->merge(
            new FieldList(array(
                $this->makePackageIDField($request),
                $this->makeDateField($request, self::StartDateFieldName),
                $this->makeDateField($request, self::EndDateFieldName),
                new DropdownField(
                    self::TypeFieldName,
                    _t(__CLASS__ . '.TypeFieldLabel'),
                    array_keys(     // keys are the options
                        static::access_types()
                    )
                ),
                new TextField(
                    self::AccessKeyFieldName,
                    _t(__CLASS__ . '.AccessKeyFieldLabel')
                )
            ))
        );
        $actions->merge(
            new FieldList(array(
                new FormAction('generate', _t(__CLASS__ . '.GenerateButtonText'))
            ))
        );
        // all fields are mandatory
        $validator = new RequiredFields(
            array_keys($fields->toArray())
        );

        parent::__construct(
            $controller,
            $name,
            $fields,
            $actions,
            $validator
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
    public function generate() {
        return $this->controller->generate($this->controller->getRequest());
    }

    /**
     * @param SS_HTTPRequest $request
     * @param null $initValue
     * @param null $label
     * @return DropdownField
     */
    protected function makePackageIDField(SS_HTTPRequest $request, $initValue = null) {
        $name = self::PackageIDFieldName;

        $options = array();

        // list skus available via the API or get empty array if fails
        $packages = CheckfrontModule::api()->listPackages(
            $request->postVar(self::StartDateFieldName) ?: self::get_config_setting('date_field_config', 'min'),
            $request->postVar(self::EndDateFieldName) ?: self::get_config_setting('date_field_config', 'max')
        )->getPackages();

        foreach ($packages ?: array() as $package) {
            $options[$package->ItemID] = $package->Title;
        }

        $field = new DropdownField(
            $name,
            _t(__CLASS__ . ".{$name}FieldLabel"),
            $options,
            $initValue ?: $request->postVar(self::PackageIDFieldName)
        );
        $field->setEmptyString(_t('CheckfrontLinkGeneratorForm.PackageIDField.EmptyString', 'Type name of package'));

        return $field;
    }

    /**
     * @param SS_HTTPRequest $request
     * @param $name
     * @param $initValue - if null then controller.curr.request.postvar.name will be used.
     * @return DateField
     */
    protected function makeDateField(SS_HTTPRequest $request, $name, $initValue = null) {
        $dateField = new DateField(
            $name,
            _t(__CLASS__ . ".{$name}FieldLabel"),
            $initValue ?: $request->postVar($name)
        );
        foreach ($this->config()->get('date_field_config') as $option => $value) {
            $dateField->setConfig($option, $value);
        }
        return $dateField;
    }

}