<?php

class CheckfrontModel extends DataObject {
    const FormFieldTokenDelimeter = ',';
    const DefaultAction = 'response';

    private static $db = array();

    /**
     * Checkfront map maps between paths and a flat structure with different actions as key. glob type wildcards
     * can be used to map an 'action' to a field/path map.
     *
     * NB: the top-level key is not a match into the data, but a way to select what map to use for a
     * given operation.
     *
     * @var array
     */
    private static $checkfront_map = array(
        //    example maps showing from nested arrays to a data object field and from a dataobject to api paramters
        //
        //    'item/*' => array(
        //        'version' => 'Version',                 // map from in['version'] to out['Version']
        //        'request.status' => 'Status',           // map from in['request']['summary'] to out['Status']
        //        'item.rate.summary' => 'RateSummary'    // map from in['item']['rate']['summary'] to out['RateSummary']
        //    ),
        //    'modelToAPICall' => array(
        //        'Version' => 'version',                 // map from in['Version'] to out['version']
        //        'RateSlip' => 'slip[]',                 // map from in['RateSlip'] to out['slip[]'] (e.g. for multiple items)
        //    ),
        //    'booking/*' => array(                          // wildcard match all requests like 'booking/*' using 'glob' syntax
        //          'Version' => 'version'
        //      )
        //
    );

    /**
     * Create a model instance and set data using config.checkfront_map paths.
     *
     * @param array $data
     * @param string $forAction
     * @param bool $updateNulls
     * @return CheckfrontModel
     */
    public static function create_from_checkfront(array $data, $forAction = self::DefaultAction, $updateNulls = true) {
        /** @var CheckfrontModel $model */

        $model = parent::create();
        return $model->fromCheckfront($data, $forAction, $updateNulls);
    }

    /**
     * Use config.checkfront_map to get info from $data array and map to model fields.
     *
     * @param array $data
     * @param bool $updateNulls - if true and value not found then update model field to null.
     * @param string $forAction - key in the checkfront_map to use to lookup path/localName map
     * @return $this
     * @fluent
     */
    public function fromCheckfront(array $data, $forAction = self::DefaultAction, $updateNulls = true) {
        if ($map = $this->checkfront_map($forAction)) {
            CheckfrontModule::map_to_model($data, $map, $this, $updateNulls);
        }
        return $this;
    }

    /**
     * Use the config.checkfront_map on the model instance to map from data object
     * to an array.
     *
     * @param string $forAction
     * @param bool $skipNulls - don't put null values from this dataobject in output array.
     * @return null|array - might be empty.
     */
    public function toCheckfront($forAction, $skipNulls = true) {
        if ($map = $this->checkfront_map($forAction)) {
            return CheckfrontModule::model_to_map($this->toMap(), $map, $skipNulls);
        }
    }

    /**
     * Return map fields for an action from config.checkfront_map. Allows wildcards via
     * fnmatch on config top level ('action') key against provided action.
     *
     * @param $forAction
     * @return array|null map of fields (could be empty) or null if not found.
     */
    public function checkfront_map($forAction) {
        $map = $this->config()->get('checkfront_map');

        if (array_key_exists($forAction, $map)) {
            // found could be empty
            return $map[$forAction];

        } else {
            $fieldMap = [];
            // try using wildcards instead building list of all matches
            foreach ($map as $action => $actionFields) {
                // use glob syntax to match against action
                if (fnmatch($action, $forAction)) {
                    $fieldMap += $actionFields;
                }
            }
            // if none found return null
            return $fieldMap ?: null;
        }
    }


}