<?php
class CheckfrontAPIImplementation extends Checkfront implements CheckfrontAPIInterface {
    const SDKVersion = '1.3';
    const APIVersion = '3.0';

    /**
     * Check that this implementation's versions match the SDK versions then call parent::__construct with config etc.
     * If versions don't match throw an exception.
     *
     * @param array $config
     * @param string $sessionID
     * @throws Exception
     */
    public function __construct(array $config = array(), $sessionID = '') {
        if (static::SDKVersion !== $this->sdk_version) {
            throw new Exception("Bad SDK Version $this->sdk_version");
        }
        if (static::APIVersion !== $this->api_version) {
            throw new Exception("Bad API Version $this->api_version");
        }
        parent::__construct($config, $sessionID);
    }

    /**
     * Factory method (not injector factory method - this is called by CheckfrontAPIImplementationFactory).
     * @param $params
     * @return CheckfrontAPIImplementation
     */
    public static function create($params) {
        // in 3.0 configuration is specified as array to constructor so we build the config array
        // and pass, other option could be to create the API first and then pass to the configure methods on
        // config and authenticator
        $config = array_merge(
            CheckfrontModule::api_config()->configure(),
            CheckfrontModule::api_authenticator()->configure(),
            $params
        );
        return new self($config);
    }

    /**
     * Override empty parent to store the session ID and data in CheckfrontSession.
     * @param $sessionID
     * @param array $data
     */
    public function session($sessionID, $data = array()) {
        CheckfrontModule::session()
            ->setID($sessionID)
            ->setData('request', $data);

        parent::session($sessionID, $data);
    }
}
