<?php

class CheckfrontBookingForm extends CheckfrontForm {
    const FormName = 'CheckfrontBookingForm';
    const SubmitButtonName = 'book';

    /**
     * Creates form with:
     *
     *  -   Checkfront booking form fields from call to api.fetchBookingForm
     *  -   A 'book' action
     *
     * @param array $controller
     * @param array $nameOverrideDefault
     * @param $fields
     * @param $actions
     * @param null $validator
     */
    public function __construct($controller, $nameOverrideDefault, $fields, $actions, $validator = null) {
        $fields = $fields ?: new FieldList();
        $actions = $actions ?: new FieldList();

        // add the standard 'booking' fields (name etc)
        if ($response = CheckfrontModule::api()->fetchBookingForm()) {
            if ($response->isValid()) {

                $required = array();

                // now add the booking fields to the fieldlist for the form
                $bookingFields = $response->getFormFields($required);

                $fields->merge(
                    $bookingFields
                );

                $paymentFields = CheckfrontPaymentForm::all_payment_method_fields($required);

                $fields->merge(
                    $paymentFields
                );
            }
            $actions->push(
                new FormAction(static::SubmitButtonName, _t(__CLASS__ . ".SubmitButtonText"))
            );
        }
        parent::__construct(
            $controller,
            $nameOverrideDefault ?: self::FormName,
            $fields,
            $actions
        );
    }
}