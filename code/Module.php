<?php
use Defuse\Crypto\Crypto as Crypto;
/**
 * Module class provides API Facade, common functionality, configuration etc and a test
 * surface.
 */
class CheckfrontModule extends Object implements CheckfrontAPIInterface {
    const DefaultStartDate = '+1 day';
    const DefaultEndDate = '+2 year';
    const DefaultAvailabilityNumDays = 731;

    const PrivateEndPoint = 'package/book';
    const LinkGeneratorEndPoint = 'checkfront/link-generator';

    // index of ItemID (e.g. package ID) in decrypted token array
    const TokenItemIDIndex = 0;
    const TokenStartDateIndex = 1;
    const TokenEndDateIndex = 2;
    const TokenLinkTypeIndex = 3;
    const TokenUserTypeIndex = 4;
    const TokenPaymentTypeIndex = 5;

    // internal payment method options govern where the user goes after booking
    const PaymentPayNow = 'pay-now';
    const PaymentPayLater = 'pay-later';


    /** @var  string override the installed path of checkfront module */
    private static $module_path;

    /** @var string patch seperator used for traverse  */
    private static $path_seperator = '.';

    /** @var string set in config the category_id of 'packages' in checkfront */
    private static $package_category_id = '';

    // we also need to set the 'public' endpoint which is link of the CheckfrontBookingPage
    // or CheckfrontPackageControllerExtension extended page model instance
    // NB: order is important for deconstruction here, don't move around!
    private static $endpoints = array(
        'public' => '',
        'private' => self::PrivateEndPoint,
        'link-generator' => self::LinkGeneratorEndPoint
    );

    /**
     * Return instance of the API interface, which is probably an APIFacade or APIImplementation
     *
     * NB: add endpoints which have extended the implementation to the return typehints to get better automcomplete in
     * ide's which support it.
     *
     * @return CheckfrontAPIFacade|CheckfrontAPIImplementation|CheckfrontAPIPackagesEndpoint|CheckfrontAPIItemsEndpoint|CheckfrontAPIBookingFormEndpoint
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
     * Use Injector to create configured 'CryptofierService'
     *
     * @return CryptofierImplementation
     */
    public static function crypto() {
        return Injector::inst()->get('CryptofierService');
    }

    /**
     * Use this call through to crypto.encrypt_token to force correct parameters and order. Pass null
     * where you don't have a parameter to pass through
     *
     * @param $accessKey
     * @param $itemID
     * @param $startDate
     * @param $endDate
     * @param $linkType
     * @param $userType
     * @param $paymentType
     *
     * @return string
     */
    public static function encrypt_token($accessKey, $itemID, $startDate, $endDate, $linkType, $userType, $paymentType) {
        return self::crypto()->encrypt_token(array(
                $itemID,
                $startDate,
                $endDate,
                $linkType,
                $userType,
                $paymentType
            ),
            $accessKey
        );
    }

    /**
     * Returns array of current payment method(s).
     * Array is map of:
     *
     *  PaymentMethod => Title
     *
     * NB: this would be a hook for integrating e.g. payment module via PaymentProcessor.get_supported_methods();
     *
     * @return array of payment methods as map of [Method => Method] suitable for use in e.g. dropdown.
     */
    public static function payment_methods() {
        return array(
            CheckfrontModule::PaymentPayNow => 'Pay now',
            CheckfrontModule::PaymentPayLater => 'Pay later'
        );
    }

    /**
     * Return the endpoints where the public, private and link_generator routes are.
     * TODO: make more dynamic as far as the 'public' one is going
     *
     * @param string $which - optional specific endpoint to get
     *
     * @return string endpoint suitable for SilverStripe routing
     * @throws Exception if no CheckfrontBookingPage found
     */
    public static function endpoints($which = null) {
        $endpoints = self::config()->get('endpoints');

        if (empty($endpoints['public'])) {

            if (!$page = CheckfrontBookingPage::get()->first()) {

                $implementors = ClassInfo::implementorsOf('CheckfrontPageExtension');
                if (!$implementors) {
                    throw new Exception("Need a public endpoint to be set in config or a published CheckfrontBookingPage atm");
                }

            }
            $endpoints['public'] = $page->Link();
        }

        return is_null($which) ? $endpoints : $endpoints[$which];

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
        $numFound = 0;

        foreach ($fieldMap as $path => $localName) {

            $value = self::lookup_path($path, $data, $found);
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

        $data = array();

        foreach ($fieldMap as $localName => $path) {

            if (array_key_exists($localName, $modelOrArray) || !$skipNulls) {

                self::build_path($path, $modelOrArray[$localName], $data);

            }
        }
        return $data;

    }

    /**
     * Traverse a path like 'item.summary.title' in data and return any found value.
     *
     * @param array|string $path
     * @param array $data
     * @param $found - set to true if found, false otherwise
     *
     * @return array
     */
    public static function lookup_path($path, array $data, &$found) {

        if (!is_array($path)) {
            $path = explode(static::config()->get('path_seperator'), $path);
        }
        $pathLength = count($path);
        $parsed = 0;

        while ($part = array_shift($path)) {
            if (isset($data[$part])) {
                $data = $data[$part];
                $parsed++;
            } else {
                // failed to walk the full path, break out
                break;
            }
        }
        $found = $parsed === $pathLength;
        return $data;
    }

    /**
     * @param array|string $path
     * @param $value
     * @param array $data
     */
    public static function build_path($path, $value, array &$data) {
        if (!is_array($path)) {
            $path = explode(static::config()->get('path_seperator'), $path);
        }

        $pathLength = count($path);
        $parsed = 1;

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