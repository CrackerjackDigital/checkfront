<?php

class CheckfrontPackageController extends ContentController {
/*
    private static $url_handlers = array(
        'GET $CheckfrontID!' => 'package',
        'POST $CheckfrontID!' => 'book'
    );
    // TODO: refine security, this is the 'private' package route
    private static $allowed_actions = array(
        'package' => true,
        'book' => true
    );
*/
    private static $extensions = array(
        'CheckfrontPackageControllerExtension'
    );
}