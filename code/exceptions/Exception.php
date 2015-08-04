<?php

/**
 * Indicates exception came from Checkfront module.
 */
class CheckfrontException extends Exception {
    const TypeOK = 'good';
    const TypeError = 'error';
    const TypeWarning = 'warning';

    private $type = '';

    public function __construct($message, $type, $code = null, $previous = null) {
        $this->type = $type;
        parent::__construct($message, $code, $previous);
    }

    public function getType() {
        return $this->type;
    }

}