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
        'PostalZip' => 'Varchar(32)',
        'Reference' => 'Varchar(32)',
        'BookingID' => 'Int',
        'Status' => 'Varchar(3)',
        'Note' => 'Text',
        'AmountPaid' => 'Decimal(11,2)',
        'AmountDue' => 'Decimal(11,2)',
        'TaxTotal' => 'Decimal(11,2)',
        'TaxIncTotal' => 'Decimal(11,2)',
        'Total' => 'Decimal(11,2)'
    );

    private static $checkfront_map = array(
        CheckfrontModel::DefaultFromAction => array(
            'booking.customer_name' => 'Name',
            'booking.customer_email' => 'Email',
            'booking.customer_phone' => 'Phone',
            'booking.customer_address' => 'Address',
            'booking.customer_city' => 'City',
            'booking.customer_country' => 'Country',
            'booking.customer_region' => 'Region',
            'booking.customer_postal_zip' => 'PostalZip',
            'booking.id' => 'Reference',
            'booking.booking_id' => 'BookingID',
            'booking.status_id' => 'Status',
            'booking.meta.note' => 'Note',
            'booking.amount_paid' => 'AmountPaid',
            'booking.amount_due' => 'AmmountDue',
            'booking.tax_total' => 'TaxTotal',
            'booking.tax_inc_total' => 'TaxIncTotal',
            'booking.total' => 'Total'
        ),
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