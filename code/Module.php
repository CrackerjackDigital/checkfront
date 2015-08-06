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
    const DefaultCheckfrontDateFormat = 'Ymd';

    const NullDate = null;

    const PrivateEndPoint = 'private/book';
    const LinkGeneratorEndPoint = 'checkfront/link-generator';

    const APIServiceName = 'CheckfrontAPI';
    const APICacheServiceName = 'CheckfrontAPICache';
    const APIConfigServiceName = 'CheckfrontAPIConfig';
    const AuthenticatorServiceName = 'CheckfrontAuthenticator';
    const CryptoServiceName = 'CheckfrontCryptoService';
    const SessionServiceName = 'CheckfrontSession';
    const SessionEndPointService = 'CheckfrontAPISessionEndpoint';

    const TokenItemCount = 6;

    // index of ItemID (e.g. package ID) in decrypted token array
    const TokenItemIDIndex = 0;
    const TokenStartDateIndex = 1;
    const TokenEndDateIndex = 2;
    const TokenLinkTypeIndex = 3;
    const TokenUserTypeIndex = 4;
    const TokenPaymentTypeIndex = 5;

    const UserTypeOrganiser = 'organiser';
    const UserTypeIndividual = 'individual';

    // internal payment method options govern where the user goes after booking
    const PaymentPayNow = 'pay-now';
    const PaymentPayLater = 'pay-later';

    const LinkTypePublic = 'public';
    const LinkTypePrivate = 'private';

    /** @var string where the 'private' booking form should be accessed */
    private static $private_endpoint = self::PrivateEndPoint;

    /** @var  string override the installed path of checkfront module */
    private static $module_path;

    /** @var string patch seperator used for traverse */
    private static $path_seperator = '.';

    /** @var string set in config the category_id of 'packages' in checkfront */
    private static $package_category_id = '';

    /** @var string in format usefull to 'date()' function e.g. 'Ymd' */
    private static $checkfront_date_format = self::DefaultCheckfrontDateFormat;

    private static $user_types = array(
        self::UserTypeOrganiser  => 'Organiser',
        self::UserTypeIndividual => 'Individual'
    );
    private static $link_types = array(
        self::LinkTypePublic  => 'Public',
        self::LinkTypePrivate => 'Private',
    );
    private static $payment_types = array(
        self::PaymentPayNow   => 'Pay now',
        self::PaymentPayLater => 'Pay later'
    );

    /**
     * Return instance of the API interface, which is probably an APIFacade or APIImplementation
     * NB: add endpoints which have extended the implementation to the return typehints to get better automcomplete in
     * ide's which support it.
     * @return CheckfrontAPIBridge|CheckfrontAPIPackagesEndpoint|CheckfrontAPIItemsEndpoint|CheckfrontAPIBookingFormEndpoint
     */
    public static function api() {
        return Injector::inst()->get(self::APIServiceName);
    }

    /**
     * @return CheckfrontAPIConfigInterface
     */
    public static function api_config() {
        return Injector::inst()->get(self::APIConfigServiceName);
    }

    /**
     * @return CheckfrontAPICacheInterface
     */
    public static function api_cache() {
        return Injector::inst()->get(self::APICacheServiceName);
    }

    /**
     * @return CheckfrontAPIAuthenticatorInterface
     */
    public static function api_authenticator() {
        return Injector::inst()->get(self::AuthenticatorServiceName);
    }

    /**
     * @return CheckfrontSessionInterface
     */
    public static function session() {
        return Injector::inst()->get(self::SessionServiceName);
    }

    /**
     * Use Injector to create configured 'CryptoServiceName'
     * @return CryptofierImplementation
     */
    public static function crypto() {
        return Injector::inst()->get(self::CryptoServiceName);
    }

    /**
     * Returns default date format expected by Checkfront in date() function format, probably 'Ymd'.
     * @return string
     */
    public static function checkfront_date_format() {
        return (string)static::config()->get('checkfront_date_format');
    }

    /**
     * @return string private endpoint where 'private' bookings should be made, e.g. 'private/book'
     */
    public static function private_endpoint() {
        return (string)static::config()->get('private_endpoint');
    }

    /**
     * @return string path to url shortener
     */
    public static function shorturl_endpoint() {
        return (string)static::config()->get('shorturl_endpoint');
    }

    /**
     * Calls through to crypto.encrypt_token however force correct number of tokens at least. Pass null
     * where you don't have a parameter to pass through.
     *
     * @param $accessKey
     * @param $itemID
     * @param $event
     * @param $linkType
     * @param $userType
     * @param $paymentType
     *
     * @throws CheckfrontCryptoException
     * @internal param $organiserEvent
     * @internal param $individualEvent
     * @return string - valid token
     */
    public static function encrypt_token($accessKey, $itemID, $startDate, $endDate, $linkType, $userType, $paymentType) {
        try {
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
        } catch (Exception $e) {
            throw new CheckfrontCryptoException("Failed to encrypt token", $e->getCode(), $e);
        }
    }

    /**
     * @param $accessKey
     * @param $friendlyToken
     *
     * @return array
     * @throws CheckfrontCryptoException
     */
    public static function decrypt_token($accessKey, $friendlyToken) {
        try {
            $parts = self::crypto()->decrypt_token($friendlyToken, $accessKey);
            if (count($parts) !== self::TokenItemCount) {
                throw new CheckfrontCryptoException("Invalid number of items in token, '" . count($parts));
            }

            return $parts;

        } catch (CheckfrontCryptoException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new CheckfrontCryptoException("Failed to decrypt token", $e->getCode(), $e);
        }
    }

    /**
     * Returns link to booking on the site depending on options provided, this function
     * binds to the parameters in the token via the number of parameters on the method.
     *
     * @param $accessKey   - from Cryptofier.generate_key
     * @param $endPoint    - where this link will go to (e.g. page or controller endpoint).
     * @param $itemID
     * @param $linkType    - e.g 'public' or 'private'
     * @param $userType    - e.g. 'organiser' or 'individual'
     * @param $paymentType - e.g 'pay-now' or 'pay-later'
     *
     * @return string - link to page on site either via BookingPage or the CheckfrontPackageController
     */

    public static function make_link($accessKey, $endPoint, $itemID, $startDate, $endDate, $linkType, $userType, $paymentType) {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $endPoint,
            self::encrypt_token(
                $accessKey,
                $itemID,
                $startDate,
                $endDate,
                $linkType,
                $userType,
                $paymentType
            )
        );
    }


    /**
     * Returns array of current payment method(s).
     * Array is map of:
     *  PaymentMethod => Title
     * NB: this would be a hook for integrating e.g. payment module via PaymentProcessor.get_supported_methods();
     * @return array of payment methods as map of [Method => Method] suitable for use in e.g. dropdown.
     */
    public static function payment_methods() {
        return array(
            CheckfrontModule::PaymentPayNow   => 'Pay now',
            CheckfrontModule::PaymentPayLater => 'Pay later'
        );
    }


    /**
     * Return the available Link Types, as a map of 'value' => 'Title' suitable for use in a drop-down field.
     * e.g. [ 'public' => 'Public', ... ]
     * @return array
     */
    public static function link_types($type = null) {
        $types = self::config()->get('link_types');
        if ($type) {
            return $types[$type];
        }

        return $types;
    }

    /**
     * Return the available User Types, as a map of 'value' => 'Title' suitable for use in a drop-down field.
     * e.g. [ 'organiser' => 'Organiser', ... ]
     * @return array
     */
    public static function user_types($type = null) {
        $types = self::config()->get('user_types');
        if ($type) {
            return $types[$type];
        }

        return $types;
    }

    /**
     * Return the available Payment Types, as a map of 'value' => 'Title' suitable for use in a drop-down field.
     * e.g. [ 'pay-now' => 'Pay now', ... ]
     * @return array
     */
    public static function payment_types($type = null) {
        $types = self::config()->get('payment_types');
        if ($type) {
            return $types[$type];
        }

        return $types;
    }

    /**
     * @return string|integer
     * @throws Exception if no config.package_category_id set
     */
    public static function package_category_id() {
        if (!$id = static::config()->get('package_category_id')) {
            throw new CheckfrontException("No package category id", CheckfrontException::TypeError);
        }

        return $id;
    }

    /**
     * Given data in a nested array, a field map to a flat structure and a dataobject to set field values
     * on populate the model.
     *
     * @param array $data
     * @param array $fieldMap
     * @param DataObject $model    - model to receive parsed value as field values
     * @param boolean $updateNulls - if value not found in data, set the field to null on the model
     *
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
     * @param $fieldMap     - map of source keys to output structure with '.' syntax
     * @param $skipNulls    - if value not in $data or null don't include in output array
     *
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
        $parsed     = 0;
        $lastPart   = array();

        while ($part = array_shift($path)) {

            if (is_numeric($part)) {
                if (isset($lastPart[$part])) {
                    $data = $lastPart[$part];
                }
                $parsed++;
            } elseif (isset($data[$part])) {
                // save this incase next 'part' is a numeric array index
                $lastPart = $data[$part];

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
        $parsed     = 1;

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
     *
     * @return bool|mixed|string
     * @throws Exception
     */
    public static function checkfront_date($dateOrYear, $month = null, $day = null) {
        $checkfrontFormat = CheckfrontModule::checkfront_date_format();

        if (empty($dateOrYear)) {

            return date($checkfrontFormat);

        } elseif ($dateOrYear instanceof SS_Datetime) {

            // build from SS_Datetime
            $result = $dateOrYear->Year() . $dateOrYear->Month() . $dateOrYear->Day();

        } elseif (is_int($dateOrYear)) {
            // year is an integer year

            if (func_num_args() === 1 && ($dateOrYear > mktime(0, 0, 0, 1, 1, 1970))) {
                // $dateOrYear is probably a unix timestamp
                $result = date($checkfrontFormat, $dateOrYear);
            } elseif (func_num_args() === 3) {
                // $dateOrYear is year, and month and day supplied
                $result = date($checkfrontFormat, mktime(0, 0, 0, $month, $day, $dateOrYear));
            } else {
                throw new CheckfrontException("Need either 1 or 3 arguments when dataOrYear is an integer", CheckfrontException::TypeError);
            }

        } elseif (3 === explode($dateOrYear, '-')) {

            // probably formatted as YYYY-MM-DD
            $result = str_replace(array('-', '_'), '', $dateOrYear);
            if (!is_numeric($result)) {
                throw new CheckfrontException("Invalid date passed: '$dateOrYear'", CheckfrontException::TypeError);
            }

        } else {

            // this may be something that strtotime can use e.g. 'today' or '+2 month'?
            $unixTime = strtotime($dateOrYear);
            if ($unixTime === false) {
                throw new CheckfrontException("Invalid date passed: '$dateOrYear'", CheckfrontException::TypeError);
            }
            $result = date($checkfrontFormat, $unixTime);

        }

        return $result;
    }

    /**
     * Given a date 'YYYYMMDD' or an array with a key value in that format returns 'YYYY-MM-DD'.
     *
     * @param $valueOrArray
     * @param null $key - checkfront key name, e.g. 'start_date'
     *
     * @return string|null
     * @throws Exception
     */
    public static function from_checkfront_date($valueOrArray, $key = null) {
        $value = CheckfrontModule::NullDate;

        if ($valueOrArray) {
            if (is_array($valueOrArray)) {
                if ($key && isset($valueOrArray[$key])) {

                    $value = $valueOrArray[$key];
                    $year  = substr($value, 0, 4);
                    $month = substr($value, 5, 2);
                    $day   = substr($value, 7, 2);

                    $value = date('Y-m-d', mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));
                } else {
                    throw new CheckfrontException("Invalid date passed: '" . implode(',', $valueOrArray) . "'", CheckfrontException::TypeError);
                }
            } elseif (is_numeric($valueOrArray)) {
                $year  = substr($valueOrArray, 0, 4);
                $month = substr($valueOrArray, 5, 2);
                $day   = substr($valueOrArray, 7, 2);

                $value = date('Y-m-d', mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));
            } else {
                throw new CheckfrontException("Invalid date passed: '$valueOrArray'", CheckfrontException::TypeError);
            }
        } else {
            // no date is OK, just return default null date
        }

        return $value;
    }

    /**
     * Return base directory to where the module is installed.
     * @return string
     */
    public static function module_path() {
        return static::config()->get('module_path') ?: realpath(__DIR__ . '/../');
    }

}