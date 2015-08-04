<?php

class CheckfrontBookingModel extends CheckfrontModel {

    private static $db = array(
        'Name' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Phone' => 'Varchar(255)',
        'Address' => 'Text',
        'City' => 'Varchar(255)',
        'Country' => 'Varchar(32)',
        'Region' => 'Varchar(255)',
        'PostalZip' => 'Varchar(32)'
    );

    private static $checkfront_map = array(
        'from-form' => array(
            'customer_name' => 'Name',
            'customer_email' => 'Email',
            'customer_phone' => 'Phone',
            'customer_address' => 'Address',
            'customer_city' => 'City',
            'customer_country' => 'Country',
            'customer_region' => 'Region',
            'customer_postal_zip' => 'PostalZip'
        ),
        'booking/create' => array(
            'Name' => "form[customer_name]",
            'Email' => "form[customer_email]",
            'Phone' => "form[customer_phone]",
            'Address' => "form[customer_address]",
            'City' => "form[customer_city]",
            'Country' => "form[customer_country]",
            'Region' => "form[customer_region]",
            'PostalZip' => "form[customer_zip]"
        )
    );
}