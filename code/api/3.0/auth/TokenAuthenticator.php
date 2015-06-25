<?php

class CheckfrontAPITokenAuthenticator extends Object
    implements CheckfrontAPIAuthenticatorInterface
{
    const AuthType = 'token';

    private static $api_key = '';

    private static $api_secret = '';


    /**
     * @return string
     */
    public function getAuthType()
    {
        return static::AuthType;
    }

    /**
     * Configure the api for authentication method, in case of 3.0 we don't and can't do much here so we just
     * return an array of configuration options suitable for handing to SDK constructor config.
     *
     * @param CheckfrontAPIInterface|null $notused
     * @param array $params optional add/override
     * @return array
     */
    public function configure(CheckfrontAPIInterface $notused = null, array $params = array())
    {
        return [
            'auth_type' => $this->getAuthType(),
            'api_key' => $this->config()->get('api_key'),
            'api_secret' => $this->config()->get('api_secret')
        ];
    }
}