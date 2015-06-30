<?php
class CheckfrontPackageModel extends CheckfrontModel {
    private static $db = array(
        'ItemID' => 'Int',
        'SKU' => 'Varchar(32)',
        'Title' => 'Varchar(32)',
        'Summary' => 'Text',
        'Content' => 'HTMLText',
        'Unit' => 'enum("Day,Hour,Week")',
        'RateSummaryTitle' => 'Varchar(32)',
        'RateSlip' => 'CheckfrontSlip',
        'RateStatus' => 'Varchar(32)',
        'StartDate' => 'SS_DateTime',
        'EndDate' => 'SS_DateTime'
    );
    private static $checkfront_map = array(
        'response' => array(
            'item_id' => 'ItemID',
            'sku' => 'SKU',
            'name' => 'Title',
            'summary' => 'Summary',
            'details' => 'Content',
            'unit' => 'Unit',
            'rate.summary.title' => 'RateSummaryTitle',
            'rate.slip' => 'RateSlip',
            'rate.status' => 'RateStatus'
        ),
        'booking/session' => array(
            'RateSlip' => 'slip'
        )
    );

    /**
     * Make a link/token to this booking
     *
     * @param string $linkType
     * @param string $userType
     * @param string $paymentType
     *
     * @return String
     */
    public function Link($linkType = 'public', $userType = 'individual', $paymentType = CheckfrontModule::PaymentPayNow) {
        return Controller::join_links(
            CheckfrontModule::endpoints('public'),
            CheckfrontModule::encrypt_token(
                null,
                $this->ItemID,
                '',
                '',
                $linkType,
                $userType,
                $paymentType
            )
        );
    }
}