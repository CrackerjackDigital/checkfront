<?php
// dynamically add CheckfrontModule.private_endpoint to the routing table.

Director::addRules(
    100,
    array(
        CheckfrontModule::private_endpoint() => 'CheckfrontPackageController'
    )
);