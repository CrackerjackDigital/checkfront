<?php

/**
 * Main functionality for this is added by CheckfrontPackageControllerExtension extension.
 */
class CheckfrontPackageController extends ContentController {
    private static $extensions = array(
        'CheckfrontPackageControllerExtension'
    );

    private static $allowed_actions = array(
        'index' => true
    );

}