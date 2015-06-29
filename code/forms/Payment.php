<?php

/**
 * Form for accepting payments, fields are added as per CheckfrontModule::payment_methods(). If there
 * is more than one field then the 'PaymentMethod' field is added first and display_logic used
 * to hide/show other fields not matching the selected PaymentMethod.
 */
class CheckfrontPaymentForm extends CheckfrontForm {
    // should match expected Amount field name in payments module
    const PaymentAmountFieldName = 'Amount';

    // should match expected Currency field name in payments module
    const PaymentCurrencyFieldName = 'Currency';

    const MethodFieldName = 'PaymentMethod';

    const SubmitButtonName = 'continue';

    const SubmitAction = 'checkfront_payment';

    /**
     * Returns a form with
     *
     * @param array $controller
     * @param array $name
     * @param $fields
     * @param $actions
     * @param null $validator
     */
    public function __construct($controller, $name, $fields, $actions, $validator = null) {
        $required = array();

        $fields = $fields ? : self::all_payment_method_fields($required);

        $actions = $actions ? : self::action_fields($required);

        if ($required) {
            $validator = new RequiredFields($required);
        }

        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    /**
     * Return fields for all configured payment methods depending on SiteMode. If only one payment method
     * then that will be added as a hidden field, otherwise a drop-down is displayed where method can be selected.
     *
     * @param array &$required - required fields are added here
     * @return FieldList
     */
    public static function all_payment_method_fields(array &$required) {
        $fields = new FieldList();

        // add the payment method dropdown or hidden field if only one.
        $fields->push(
            self::payment_methods_field($required)
        );

        // loop through available methods and add fields for each, these will be configured
        // to show hide via display_logic module

        $paymentMethods = CheckfrontModule::payment_methods();

        foreach ($paymentMethods as $paymentMethod => $notused) {
            // this is the static method name for the form fields for that payment method.
            $methodName = "fields_for_$paymentMethod";

            $fields->merge(
                self::$methodName($required)
            );
        }

        return $fields;

    }

    /**
     * Return action fields for the form, atm just a submit.
     * @return FieldList
     */
    public function action_fields() {
        return new FieldList(array(
            new FormAction(self::SubmitAction, $this->labelForField(self::SubmitButtonName, 'continue'))
        ));
    }

    /**
     * Return dropdown with allowed/configured payment methods.
     * @param array &$required - required fields are added here
     * @return DropDownField
     */
    public static function payment_methods_field(array &$required) {
        // Create a dropdown select field for choosing gateway
        $configuredMethods = CheckfrontModule::payment_methods();


        if (count($configuredMethods) > 1) {
            $required[] = self::MethodFieldName;

            $source = array();

            foreach ($configuredMethods as $methodName) {
                $methodConfig        = PaymentFactory::get_factory_config($methodName);
                $source[$methodName] = $methodConfig['title'];
            }

            return new DropDownField(
                self::MethodFieldName,
                'Select Payment Method',
                $source
            );
        } else {
            // choose the first one.
            reset($configuredMethods);

            return new HiddenField(
                self::MethodFieldName,
                self::MethodFieldName,
                key($configuredMethods)
            );
        }
    }


    /**
     * Return a amount and currency fields.
     * @param array &$required - required fields are added here
     * @return FieldList
     */
    public static function payment_amount_fields(array &$required) {

        $required = array(
            self::PaymentAmountFieldName,
            self::PaymentCurrencyFieldName
        );

        return new FieldList(array(
            new DropdownField(self::PaymentCurrencyFieldName, self::labelForField(self::PaymentCurrencyFieldName, 'Currency')),
            new TextField(self::PaymentAmountFieldName, self::labelForField(self::PaymentAmountFieldName, 'Amount'))
        ));
    }

    /**
     * Return fields for a Credit Card payment type.
     * @param array &$required - required fields are added here
     * @return FieldList
     */
    public static function fields_for_PaymentExpressPxPay(array &$required) {

        $fields = new FieldList(array(
            new TextField('CCName', 'Name on Credit Card'),
            new TextField('CCNumber', 'Credit Card number'),
            new TextField('CCCSV', 'Credit Card Security Code'),
            new NumericField('CCExpiryMonth', 'Expiry month'),
            new NumericField('CCExpiryYear', 'Expiry year')
        ));
        /** @var FormField $field */
        foreach ($fields as $field) {
            $field->hideUnless(self::MethodFieldName)->isEqualTo('PaymentExpressPxPay');
            $required[] = $field->getName();
        }

        return $fields;
    }

    /**
     * Return simple fields for this dummy payment method.
     * @param array &$required - required fields are added here
     * @return FieldList
     */
    public static function fields_for_DummyMerchantHosted(array &$required) {
        $fields = new FieldList();

        $fields->push(
            new LiteralField('DummyMerchantHostedMessage', "<p>You have chosen the dummy merchant hosted payment method</p>")
        );
        foreach ($fields as $field) {
            $field->hideUnless(self::MethodFieldName)->isEqualTo('DummyMerchantHosted');
        }

        return $fields;
    }

    /**
     * Return simple fields for this dummy payment method.
     * @param array &$required - required fields are added here
     * @return FieldList
     */
    public static function fields_for_DummyGatewayHosted(array &$required) {
        $fields = new FieldList();

        $fields->push(
            new LiteralField('DummyGatewayHostedMessage', "<p>You have chosen the dummy gateway hosted payment method</p>")
        );

        foreach ($fields as $field) {
            $field->hideUnless(self::MethodFieldName)->isEqualTo('DummyGatewayHosted');
        }

        return $fields;
    }

    /*
     * Convenience function
     */
    private function labelForField($fieldName, $default) {
        return _t(__CLASS__ . ".$fieldName.Label", $default);
    }

}