<?php
class CheckfrontEventModel extends CheckfrontModel {
    private static $db = array(
        'EventID' => 'Int',
        'Title' => 'Varchar(32)',
        'StartDate' => 'SS_DateTime',
        'EndDate' => 'SS_DateTime'
    );
    private static $checkfront_map = array(
        'response' => array(
            'item_id' => 'EventID',
            'name' => 'Title',
            'start_date' => 'StartDate',
            'end_date' => 'EndDate'
        ),
        'javascript' => array(
            'EventID' => 'id',
            'Title' => 'name',
            'StartDate' => 'start_date',
            'EndDate' => 'end_date'
        )
    );

}