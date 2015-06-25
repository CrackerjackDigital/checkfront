<?php

/**
 * Base class or instance for forms used in checkfront module.
 */
class CheckfrontForm extends Form {

    /**
     * Return config.name or config.name[key] if key provided and config.name is an array.
     *
     * @param $name
     * @param string|null $key
     * @return mixed
     */
    protected static function get_config_setting($name, $key = null) {
        $value = static::config()->get($name);
        if ($key && is_array($value) && array_key_exists($key, $value)) {
            return $value[$key];
        }
        return $value;
    }
}