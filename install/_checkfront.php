<?php

if (!defined('CHECKFRONT_API_VERSION')) {
    // this could e.g. be set in _ss_environment instead.
    define('CHECKFRONT_API_VERSION', '3.0');
}

if (!(defined('CHECKFRONT_USE_DEV') || defined('CHECKFRONT_USE_TEST') || defined('CHECKFRONT_USE_LIVE'))) {
    // one of these could be defined in _ss_environment instead, if not
    // set defaults from SS_ENVIRONMENT to configure what block in checkfront.yml is loaded
    if (Director::isDev()) {
        define('CHECKFRONT_USE_DEV', true);
    } elseif (Director::isTest()) {
        define('CHECKFRONT_USE_TEST', true);
    } else {
        define('CHECKFRONT_USE_LIVE', true);
    }
}

// setup loader to load sdk and classes from CHECKFRONT_API_VERSION/ paths under sdk/ and code/api/
new CheckfrontLoader(CHECKFRONT_API_VERSION);