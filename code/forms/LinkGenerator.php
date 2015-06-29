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

        $startDate = $this->makeDate($request, self::StartDateFieldName, 'min');
        $endDate = $this->makeDate($request, self::EndDateFieldName, 'max');

        $endpoints = CheckfrontModule::endpoints();

        $linkTypes = array(
            $endpoints['public'] => 'public',
            $endpoints['private'] => 'private'
        );

        $userTypes = array(
            'organisation' => 'organisation',
            'individual' => 'individual'
        );

        $fields->merge(
            new FieldList(array(
                $this->makePackageSelectorField($request),
                $this->makeDateField($request, self::StartDateFieldName, $startDate),
                $this->makeDateField($request, self::EndDateFieldName, $endDate),
                new DropdownField(
                    self::LinkTypeFieldName,
                    _t(__CLASS__ . '.LinkTypeFieldLabel', 'Link type'),
                    $linkTypes
                ),
                new DropdownField(
                    self::UserTypeFieldName,
                    _t(__CLASS__ . '.UserTypeFieldLabel', 'User type'),
                    $userTypes
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
    public function generate() {
        return $this->controller->generate($this->controller->getRequest());
    }


}