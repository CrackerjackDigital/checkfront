<?php

class CheckfrontPackageBookingForm extends CheckfrontForm {
    const SubmitButtonName = 'book';

    /**
     * Creates form with:
     *
     *  -   Checkfront booking form fields from call to api.fetchBookingForm
     *  -   A 'book' action
     *
     * @param Controller $controller
     * @param string $name
     * @param FieldList $fields
     * @param FieldList $actions
     * @param null $validator
     */
    public function __construct($controller, $name, $fields, $actions, $validator = null) {
        $fields = $fields ?: new FieldList();
        $actions = $actions ?: new FieldList();

        $required = array();

        // add the standard 'booking' fields (name etc)
        if ($response = CheckfrontModule::api()->fetchBookingForm()) {
            if ($response->isValid()) {

                // now add the booking fields to the fieldlist for the form
                $bookingFields = $response->getFormFields($required);

                $fields->merge(
                    $bookingFields
                );
            }
            $actions->push(
                new FormAction(static::SubmitButtonName, _t(__CLASS__ . ".SubmitButtonText"))
            );
        }
        $validator = new RequiredFields($required);

        parent::__construct(
            $controller,
            $name,
            $fields,
            $actions,
            $validator
        );
    }
}