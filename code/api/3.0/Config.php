<?php

class CheckfrontAPIConfig extends Object implements CheckfrontAPIConfigInterface {
    // set via Injector config
    private static $host;
    // set via Injector config
    private static $app_id;
    // set via Injector config
    private static $client_ip;
    // set via Injector config
    private static $account_id;

    /**
     * Configures the API for use. - in case of 3.0 version we don't do
     * (and can't do due to property access permissions) anything to the api,
     * just return an array with options
     *
     * @param CheckfrontAPIInterface $unused
     * @param array $addOrOverride - add these or change them to provided values if exist in self.config.
     * @return array
     */
    public static function configure(CheckfrontAPIInterface $unused = null, array $addOrOverride = array()) {
        $config = static::config();
        $result = array_merge(
            [
                'host' => $config->get('host'),
                'app_id' => $config->get('app_id'),
                'client_ip' => $config->get('client_ip'),
                'account_id' => $config->get('account_id')
            ],
            $addOrOverride
        );
        return $result;
    }
}