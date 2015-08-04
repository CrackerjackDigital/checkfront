<?php

/**
 * Base class or instance for forms used in checkfront module.
 */
class CheckfrontForm extends Form {
    const FormName = 'CheckfrontForm';

    const PackageIDFieldName = 'PackageID';
    const StartDateFieldName = 'StartDate';
    const EndDateFieldName = 'EndDate';
    const LinkTypeFieldName = 'LinkType';
    const PaymentTypeFieldName = 'PaymentType';
    const EventFieldPrefix = 'Event';

    const AccessKeyFieldName = 'AccessKey';

    // default config for date field
    private static $date_field_config = array(
        'showcalendar'    => true,
        'datevalueformat' => 'YYYY-MM-DD'
    );


    /**
     * Returns localised field label using format {ClassName}.{$fieldName}Label or $fieldName if not found.
     * e.g. 'CheckfrontLinkGeneratorForm.AccessKeyLabel'. If not found on this class then steps
     * back through class ancestry (e.g 'CheckfrontForm') to try and find it there.
     *
     * @param $fieldName
     * @param string $nameSuffix - default 'FieldLabel' but can use e.g. 'FieldEmptyString' etc
     *
     * @return string
     */
    protected function getFieldLabel($fieldName, $nameSuffix = 'FieldLabel') {
        $className = get_class($this);
        $label     = _t("$className.{$fieldName}{$nameSuffix}");
        if (!$label) {
            while ($className = get_parent_class($className)) {
                if ($className === 'Object') {
                    // stop at Object
                    break;
                }
                if ($label = _t("$className.{$fieldName}{$nameSuffix}")) {
                    break;
                }
            }
        }

        return $label ?: $fieldName;
    }

    /**
     * Return config.name or config.name[key] if key provided and config.name is an array. If key
     * doesn't exist in the array returns null.
     *
     * @param $name
     * @param string|null $key
     *
     * @return mixed
     */
    protected static function get_config_setting($name, $key = null) {
        $value = Config::inst()->get(get_called_class(), $name);

        if ($key && is_array($value)) {
            if (array_key_exists($key, $value)) {
                return $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Returns a text field where the access key can be entered.
     * @return TextField
     */
    protected function makeAccessKeyField() {
        $field = new TextField(
            self::AccessKeyFieldName,
            $this->getFieldLabel(self::AccessKeyFieldName)
        );

        return $field;
    }

    /**
     * Returns a DateField configured with start, end dates.
     *
     * @param $name
     * @param $value     - initial value
     * @param $startDate - start date which can be selected from, defaults to CheckfrontModule.DefaultStartDate
     * @param $endDate   - end date which can be selected to, defaults to CheckfrontModule.DefaultEndDate
     *
     * @return DateField
     */
    public function makeDateField($name, $value,
                                  $startDate = CheckfrontModule::DefaultStartDate,
                                  $endDate = CheckfrontModule::DefaultEndDate) {

        $label = $this->getFieldLabel($name);

        return self::make_date_field($name, $label, $value, $startDate, $endDate);
    }

    /**
     * Returns a DateField configured with start, end dates.
     *
     * @param $name
     * @param $value
     * @param $label
     * @param $startDate - start date which can be selected from, defaults to CheckfrontModule.DefaultStartDate
     * @param $endDate   - end date which can be selected to, defaults to CheckfrontModule.DefaultEndDate
     *
     * @return DateField
     */
    public static function make_date_field($name, $label, $value,
                                           $startDate = CheckfrontModule::DefaultStartDate,
                                           $endDate = CheckfrontModule::DefaultEndDate) {

        $dateField = new DateField(
            $name,
            $label,
            $value
        );
        // set the min and max dates to calculated dates not 'relative' dates as this
        // seems to break SilverStripe/jquery datefield?
        $config = array_merge(
            static::get_config_setting('date_field_config'),
            array(
                'min' => date('Y-m-d', strtotime($startDate ?: CheckfrontModule::DefaultStartDate)),
                'max' => date('Y-m-d', strtotime($endDate ?: CheckfrontModule::DefaultEndDate))
            )
        );

        foreach ($config as $option => $value) {
            $dateField->setConfig($option, $value);
        }

        return $dateField;
    }
}