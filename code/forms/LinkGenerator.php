<?php

class CheckfrontLinkGeneratorForm extends CheckfrontForm {
    const FormName = 'CheckfrontLinkGeneratorForm';
    const SubmitButtonName = 'generate';

    private static $allowed_actions = array(
        'generate' =>  true
    );

    /**
     * Creates form with:
     *
     *  -   Package Selector dropdown
     *  -   Start Date field
     *  -   End Date field
     *  -   'Type' field (public/private) which indicates endpoint to book package.
     *
     *  -   'generate' action.
     *
     * @param array $controller
     * @param array $nameOverride - use this instead of default self.FormName
     * @param $fields
     * @param $actions
     * @param null $validator
     */
    public function __construct($controller, $nameOverride, $fields, $actions, $validator = null) {
        $request = $controller->getRequest();

        $fields = $fields ?: new FieldList();
        $actions = $actions ?: new FieldList();

        $startDate = $this->make_date($request, self::StartDateFieldName, 'min');
        $endDate = $this->make_date($request, self::EndDateFieldName, 'max');

        $endpoints = CheckfrontModule::endpoints();

        $linkTypes = array(
            $endpoints['public'] => 'public',
            $endpoints['private'] => 'private'
        );

        $userTypes = array(
            'organisation' => 'organisation',
            'individual' => 'individual'
        );

        $paymentTypes = array(
            'pay-now' => 'Pay now',
            'pay-later' => 'Pay later'
        );

        $fields->merge(
            new FieldList(array(
                $this->makePackageSelectorField($request),
                $this->make_date_field($request, self::StartDateFieldName, $startDate),
                $this->make_date_field($request, self::EndDateFieldName, $endDate),
                new HiddenField(
                    self::LinkTypeFieldName,
                    '',
                    $endpoints['private']
                ),
                new DropdownField(
                    self::UserTypeFieldName,
                    _t(__CLASS__ . '.' . self::UserTypeFieldName . 'Label', 'User type'),
                    $userTypes
                ),
                new DropdownField(
                    self::PaymentTypeFieldName,
                    _t(__CLASS__ . '.' . self::PackageIDFieldName . 'Label', 'Payment type'),
                    $paymentTypes
                )
            ))
        );
        $actions->merge(
            new FieldList(array(
                new FormAction(
                    static::SubmitButtonName,
                    _t(__CLASS__ . '.SubmitButtonText')
                )
            ))
        );
        // all fields are mandatory
        $validator = new RequiredFields(
            array_keys($fields->toArray())
        );

        parent::__construct(
            $controller,
            $nameOverride ?: self::FormName,
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
    public function generate_link() {
        return $this->controller->generate_link($this->controller->getRequest());
    }


}