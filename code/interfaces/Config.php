<?php

interface CheckfrontAPIConfigInterface {
    public static function configure(CheckfrontAPIInterface $api = null, array $options = array());
}