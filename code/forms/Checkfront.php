<?php

/**
 * Base class or instance for forms used in checkfront module.
 */
class CheckfrontForm extends Form {
    const PackageIDFieldName = 'PackageID';
    const StartDateFieldName = 'StartDate';
    const EndDateFieldName = 'EndDate';
    const LinkTypeFieldName = 'LinkType';
    const UserTypeFieldName = 'UserType';
    const PaymentTypeFieldName = 'PaymentType';

    const AccessKeyFieldName = 'AccessKey';

    private static $date_field_config = array(
        'showcalendar' => true,
        'datevalueformat' => 'YYYY-MM-DD',
        'min' => CheckfrontModule::DefaultStartDate,
        'max' => CheckfrontModule::DefaultEndDate,
    );

    /**
     * Return config.name or config.name[key] if key provided and config.name is an array.
     *
     * @param $name
     * @param string|null $key
     * @return mixed
     */
    protected static function get_config_setting($name, $key = null) {
        $value = static::config()->get($name);
        if ($key && is_array($value) && array_key_exists($key, $value)) {
            return $value[$key];
        }
        return $value;
    }

    /**
     * Returns a drop-down field configured from an api.listPackages call.
     *
     * @param SS_HTTPRequest $request
     * @param null $initValue
     * @return DropdownField
     */

    protected function makePackageSelectorField(SS_HTTPRequest $request, $initValue = null) {
        $name = self::PackageIDFieldName;

        $options = array();

        $startDate = $this->makeDate($request, self::StartDateFieldName, 'min');
        $endDate = $this->makeDate($request, self::EndDateFieldName, 'max');

        // list skus available via the API or get empty array if fails
        $packages = CheckfrontModule::api()->listPackages(
            $startDate,
            $endDate
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
        $field->setEmptyString(_t('CheckfrontLinkGeneratorForm.PackageIDFieldEmptyString', 'Select a package'));

        return $field;
    }


    /**
     * Returns a text field where the access key can be entered.
     *
     * @return TextField
     */
    protected function makeAccessKeyField() {
        $field = new TextField(
            self::AccessKeyFieldName,
            _t(__CLASS__ . '.AccessKeyFieldLabel')
        );
        return $field;
    }

    /**
     * @param SS_HTTPRequest $request
     * @param $name
     * @return DateField
     */
    protected function makeDateField(SS_HTTPRequest $request, $name) {
        $dateField = new DateField(
            $name,
            _t(__CLASS__ . ".{$name}FieldLabel"),
            $request->postVar($name)
        );
        // set the min and max dates to calculated dates not 'relative' dates as this
        // seems to break SilverStripe/jquery datefield?
        $config = array_merge(
            $this->config()->get('date_field_config'),
            array(
                'min' => $this->makeDate($request, static::StartDateFieldName, 'min'),
                'max' => $this->makeDate($request, static::EndDateFieldName, 'max'),
            )
        );

        foreach ($config as $option => $value) {
            $dateField->setConfig($option, $value);
        }
        return $dateField;
    }


    /**
     * @param SS_HTTPRequest $request
     * @param $fieldName
     * @param $configKey
     * @return bool|string
     */
    protected function makeDate(SS_HTTPRequest $request, $fieldName, $configKey) {
        return $request->postVar($fieldName)
            ?: date('Y-m-d', strtotime(self::get_config_setting('date_field_config', $configKey)));
    }
}