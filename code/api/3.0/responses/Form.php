<?php

class CheckfrontAPIFormResponse extends CheckfrontAPIResponse {
    const FieldContainerKey = 'booking_form_ui';

    // array of response keys which are not considered to be form fields when fieldlist is built.
    private static $not_form_fields = array();

    public function isValid() {
        return isset($this->data[self::FieldContainerKey]);
    }

    /**
     * @param array $required - fields which are mandatory are added here
     * @param array $infoFields - fields which aren't directly form fields are added here
     *
     * @return FieldList - list of FormFields (may be empty)
     */
    public function getFormFields(array &$required = array(), array &$infoFields = array()) {
        $fields = array();

        if ($this->isValid()) {
            $notFormFields = $this->config()->get('not_form_fields') ?: array();

            foreach ($this->getCheckfrontFieldDefinitions() as $fieldName => $definition) {
                // check if field is an info field (not a form field)
                if (array_key_exists($fieldName, $notFormFields)) {
                    // add field to infoFields by-reference array
                    $infoFields[$notFormFields[$fieldName]] = $definition;

                } else {
                    // this should be a form field if name not in info fields
                    if ($field = static::form_field_factory($fieldName, $definition, $required)) {
                        $fields[$fieldName] = $field;
                    }
                }
            }

        }
        return new FieldList($fields);
    }

    /**
     * Returns an array of the checkfront fields definintions returned in the response json.
     * @return array
     */
    private function getCheckfrontFieldDefinitions() {
        return isset($this[self::FieldContainerKey])
            ? $this[self::FieldContainerKey]
            : [];
    }

    /**
     * Returns a FormField based on the supplied $fieldName and $definition, or null if can't
     * decode the information.
     *
     * @param $fieldName
     * @param $definition - checkfront's description of the field
     * @param array $required - fields marked as required are added here
     * @return FormField|null
     */
    private static function form_field_factory($fieldName, $definition, array &$required) {
        $type = isset($definition['define']['layout']['type'])
            ? $definition['define']['layout']['type']
            : null;

        $field = null;

        if ($type) {

            $label = isset($definition['define']['layout']['lbl'])
                ? $definition['define']['layout']['lbl']
                : $fieldName;

            if (isset($definition['define']['required'])) {
                if ($definition['define']['required']) {
                    $required[] = $fieldName;
                }
            }
            $options = isset($definition['define']['layout']['options'])
                ? $definition['define']['layout']['options']
                : [];

            $value = isset($definition['value'])
                ? $definition['value']
                : null;

            /** @var FormField $field */
            switch ($type) {
                case 'select':
                    $field = (new DropdownField($fieldName, $label, $options, $value))->addExtraClass('select-box');
                    break;
                case 'textarea':
                    $field = new TextareaField($fieldName, $label, $value);
                    break;
                case 'text':
                    $field = (new TextField($fieldName, $label, $value))->addExtraClass('input-text long-text');
                    break;
            }
        }
        return $field;
    }

    /**
     * Return the number of items returned or 0 if none.
     *
     * @return integer
     */
    public function getCount()
    {
        // TODO: Implement getCount() method.
    }

}