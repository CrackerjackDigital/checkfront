<?php

/**
 * Base class for extensions which add API endpoints to the CheckfrontAPI instance being used (e.g. a ChekfrontFacade).
 */
abstract class CheckfrontAPIEndpoint extends Extension {
    // default num days availability check should be for.
    private static $default_num_days = CheckfrontModule::DefaultAvailabilityNumDays;

    // strtotime compatible date to start querying availabilities from
    private static $default_start_date = CheckfrontModule::DefaultStartDate;

    // strtotime compatible date to end querying availabilities to. If not set then default_num_days is used.
    private static $default_end_date = '';

    private static $allowed_actions = array(
        'encode' => true
    );

    /**
     * @return CheckfrontAPIImplementation
     */
    public function __invoke() {
        return $this->owner;
    }


    public function buildDates($startDate, $endDate) {
        $numDays = static::get_config_setting('default_num_days');
        return array(
            'start_date' => CheckfrontModule::checkfront_date(
                    $startDate ?: static::get_config_setting('default_start_date')
                ),
            'end_date' => CheckfrontModule::checkfront_date(
                    $endDate ?: (static::get_config_setting('default_end_date') ?: "$startDate +$numDays day")
                )
        );
    }
    /**
     * Return merged config.request_params[$forCall] and any further parameters passed as
     * arrays to this call.
     *
     * @param $forCall
     * @return array
     */

    protected static function request_params($forCall /*, arrays ... */) {
        $params = static::get_config_setting('request_defaults', $forCall);

        $args = func_get_args();
        array_shift($args);

        foreach ($args as $arg) {
            if (is_array($arg)) {
                $params += $arg;
            }
        }
        return $params;
    }
    /**
     * Helper method to return config setting for called class, optionally returning value at key of array
     * if config variable is an array.
     *
     * @param $varName
     * @param null $key
     * @return array|null|scalar
     */
    protected static function get_config_setting($varName, $key = null) {
        if ($config = Config::inst()->get(get_called_class(), $varName)) {
            if ($key && is_array($config)) {
                return isset($config[$key]) ? $config[$key] : null;
            }
        }
        return $config;
    }
}