<?php
use Defuse\Crypto\Crypto as Crypto;
/**
 * Module class provides API Facade, common functionality, configuration etc and a test
 * surface.
 */
class CheckfrontModule extends Object implements CheckfrontAPIInterface {
    const DefaultAvailabilityNumDays = 365;
    const DefaultStartDate = 'today';
    const LinkPartDelimeter = '|';  // something not likely to be in data/time fields.

    /** @var  string override the installed path of checkfront module */
    private static $module_path;

    /** @var string patch seperator used for traverse  */
    private static $path_seperator = '.';

    private static $package_category_id = '';

    private static $crypto_link_salt = '';


    /**
     * Return instance of the API interface, which is probably an APIFacade or APIImplementation
     *
     * NB: add endpoints which have extended the implementation to the return typehints to get better automcomplete in
     * ide's which support it.
     *
     * @return CheckfrontAPIFacade|CheckfrontAPIImplementation|CheckfrontAPIPackagesEndpoint|CheckfrontAPIItemsEndpoint
     */
    public static function api() {
        return Injector::inst()->get('CheckfrontAPI');
    }

    /**
     * @return CheckfrontAPIConfigInterface
     */
    public static function api_config() {
        return Injector::inst()->get('CheckfrontConfig');
    }

    /**
     * @return CheckfrontAPIAuthenticatorInterface
     */
    public static function api_authenticator() {
        return Injector::inst()->get('CheckfrontAuthenticator');
    }

    /**
     * @return CheckfrontSessionInterface
     */
    public static function session() {
        return Injector::inst()->get('CheckfrontSession');
    }

    /**
     * Return an encoded version of passed parameters which can be passed on link into the system booking pages.
     * Dates will be converted to 'checkfront' dates so YYYYMMDD.
     *
     * @param $packageID
     * @param $startDate
     * @param $endDate
     * @return null|string
     */
    public static function encode_link_segment($packageID, $startDate, $endDate) {
        try {
            $startDate = CheckfrontModule::checkfront_date($startDate);
            $endDate = CheckfrontModule::checkfront_date($endDate);

            $value = implode(
                self::LinkPartDelimeter,
                array(
                    $packageID,
                    $startDate,
                    $endDate
                )
            );

            return Crypto::encrypt($value, self::crypto_link_salt());
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Return array of packageID, $startDate, $endDate from a provided encoded link segment. Start data and end date
     * will be 'checkfront' dates so YYYYMMDD.
     *
     * @param $link
     * @return null|array - [packageID, startDate, endDate]
     */
    public function decode_link_segment($link) {
        try {
            return explode(
                self::LinkPartDelimeter,
                Crypto::decrypt($link, self::crypto_link_salt())
            );
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return string
     * @throws Exception if no config.crypto_link_salt set
     */
    public static function crypto_link_salt() {
        if (!$salt = static::config()->get('crypto_link_salt')) {
            throw new Exception("No salt");
        }
        return $salt;
    }

    /**
     * @return string|integer
     * @throws Exception if no config.package_category_id set
     */
    public static function package_category_id() {
        if (!$id = static::config()->get('package_category_id')) {
            throw new Exception("No package category id");
        }
        return $id;
    }

    /**
     *
     * Given data in a nested array, a field map to a flat structure and a dataobject to set field values
     * on populate the model.
     *
     * @param array $data
     * @param array $fieldMap
     * @param DataObject $model - model to receive parsed value as field values
     * @param boolean $updateNulls - if value not found in data, set the field to null on the model
     * @return integer - number of items found
     */
    public static function map_to_model(array $data, array $fieldMap, DataObject $model, $updateNulls = true) {
        $pathSeperator = static::config()->get('path_seperator');
        $numFound = 0;

        foreach ($fieldMap as $path => $localName) {

            $path = explode($pathSeperator, $path);
            $pathLength = count($path);
            $parsed = 0;

            $value = $data;

            while ($part = array_shift($path)) {
                if (isset($value[$part])) {
                    $value = $value[$part];
                    $parsed++;
                } else {
                    // failed to walk the full path, break out
                    break;
                }
            }
            $found = $parsed === $pathLength;

            $numFound++;

            // don't try and set an array as the value
            if ($found && !is_array($value)) {

                $model->$localName = $value;

            } elseif ($updateNulls) {

                $model->$localName = null;
            }
        }
        return $numFound;
    }

    /**
     * Returns an array build as a nested structure mapping flat values in the array or DataObject passed
     * to a nested array structure using the provided fieldMap (essentially the reveres of
     * map_to_model which is easier to understand).
     *
     * e.g. with map 'RateTitle' => 'rate.summary.title' and data['RateTitle'] = 'Fred' output
     * with be
     *  array(
     *      'rate' => array(
     *          'summary' => array(
     *              'title' => 'Fred'
     *          )
     *      )
     *  )
     *
     * @param $modelOrArray - flat source key/value pairs, e.g. from DataObject.toMap
     * @param $fieldMap - map of source keys to output structure with '.' syntax
     * @param $skipNulls - if value not in $data or null don't include in output array
     * @return array
     */
    public static function model_to_map($modelOrArray, $fieldMap, $skipNulls) {
        if ($modelOrArray instanceof DataObject) {
            $modelOrArray = $modelOrArray->toMap();
        }

        $pathSeperator = static::config()->get('path_seperator');

        $data = array();

        foreach ($fieldMap as $localName => $remoteName) {
            $path = explode($pathSeperator, $remoteName);
            $pathLength = count($path);
            $parsed = 1;


            if (array_key_exists($localName, $modelOrArray)  || !$skipNulls) {

                $value = $modelOrArray[$localName];

                while ($part = array_shift($path)) {
                    if (!isset($data[$part])) {
                        if ($parsed === $pathLength) {

                            $data[$part] = $value;

                        } elseif (!array_key_exists($part, $data)) {

                            $data[$part] = array();

                        }
                    }
                    $parsed++;
                }
            }
        }
        return $data;

    }

    /**
     * Convert a date passed as 'YYYY-MM-DD', SS_Datetime or year, month, day
     * to checkfront 'YYYYMMDD' format.
     *
     * @param string|null $dateOrYear
     * @param null $month
     * @param null $day
     * @return bool|mixed|string
     * @throws Exception
     */
    public static function checkfront_date($dateOrYear, $month = null, $day = null) {
        if (empty($dateOrYear)) {
            return date('Ymd');
        } elseif ($dateOrYear instanceof SS_Datetime) {
            // build from SS_Datetime
            $result = $dateOrYear->Year() . $dateOrYear->Month() . $dateOrYear->Day();
        } elseif (is_int($dateOrYear)) {
            // year is an integer year

            if (func_num_args() === 1 && ($dateOrYear > mktime(0, 0, 0, 1, 1, 1970))) {
                // $dateOrYear is a unix timestamp
                $result = date('Ymd', $dateOrYear);
            } elseif (func_num_args() === 3) {
                // $dateOrYear or Year, month and day supplied
                $result = date('Ymd', mktime(0, 0, 0, $month, $day, $dateOrYear));
            } else {
                throw new Exception("Need either 1 or 3 arguments when dataOrYear is an integer");
            }
        } elseif (3 === explode($dateOrYear, '-')) {
            // probably formatted as YYYY-MM-DD

            $result = str_replace(array('-', '_'), '', $dateOrYear);
            if (!is_int($result)) {
                throw new Exception("Invalid date passed: '$dateOrYear'");
            }
        } else {
            // this may be something that strtotime can use e.g. 'today' or '+2 month'?
            $unixTime = strtotime($dateOrYear);
            if ($unixTime === false) {
                throw new Exception("Invalid date passed: '$dateOrYear'");
            }
            $result = date('Ymd', $unixTime);
        }
        return $result;
    }

    /**
     * Return base directory to where the module is installed.
     *
     * @return string
     */
    public static function module_path() {
        return static::config()->get('module_path') ?: realpath(__DIR__ . '/../');
    }



}