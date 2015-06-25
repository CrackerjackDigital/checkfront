<?php
interface CheckfrontAPIAuthenticatorInterface {
    /**
     * Configure api for use with this authenticator. May do stuff to $api, may just return something usefull for
     * elsewhere (e.g an array of configuration options).
     *
     * @param CheckfrontAPIInterface $api
     * @param array $params
     * @internal param $CheckfrontAPIAuthenticatorInterface
     * @return mixed
     */
    public function configure(CheckfrontAPIInterface $api = null, array $params = array());
}