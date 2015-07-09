<?php

class CheckfrontTest extends SapphireTest {
    // config used across all checkfront tests.
    private static $config = array(
        'CheckfrontAPIConfig'             => array(
            'host'       => 'digitalhod.checkfront.com',
            'account_id' => 'off'
        ),
        'CheckfrontAPITokenAuthenticator' => array(
            'api_key'    => '71d3866f9a7ecd6db1c3244a0ce72dd5305fc899',
            'api_secret' => '56a06aa1bfb7fc70427785644ff3e631ad3e12265a4f44e2e8f889004166fa6a'
        )
    );

    public function setUpOnce() {
        parent::setUpOnce();
        $this->loadConfig(self::$config);
    }


    /**
     * Set class configurations according to config.config, $replace parameter and optional passed $config:
     *
     *  if $replace is false then
     *      if non-empty config then merge with existing self.config (parameter values override shared)
     *      if empty $config then just the existing self.config gets loaded
     *
     *  if $replace is true
     *      if non-empty config then use $config without merging with existing self.config
     *      if empty $config then no changes get made, including to existing self.config
     *
     * @param array $config
     * @param bool $replace
     */
    protected function loadConfig(array $config = array(), $replace = false) {
        if (!$replace) {
            $config = array_merge(
                self::$config,
                $config
            );
        }
        foreach ($config as $className => $configValues) {
            foreach ($configValues as $name => $value) {
                Config::inst()->update($className, $name, $value);
            }
        }
    }

}