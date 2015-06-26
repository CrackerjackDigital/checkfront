<?php

class CheckfrontAccessKeyForm extends CheckfrontForm {
    const FormName = 'CheckfrontAccessKeyForm';
    const AccessKeyFieldName = 'AccessKey';
    const SubmitButtonName = 'verify';

    public function __construct($controller, $name, $fields, $actions, $validator = null) {
        $fields = $fields ?: new FieldList();
        $actions = $actions ?: new FieldList();

        $fields->merge(
            new FieldList(array(
                $this->makeAccessKeyField()
            ))
        );
        $actions->merge(
            new FieldList(array(
                new FormAction(self::SubmitButtonName, _t(__CLASS__ . '.SubmitButtonText'))
            ))
        );

        $validator = new RequiredFields(self::AccessKeyFieldName);

        parent::__construct(
            $controller,
            self::FormName,
            $fields,
            $actions,
            $validator
        );
    }

}