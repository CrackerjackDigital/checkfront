<?php
class CheckfrontItemModel extends CheckfrontModel {
    private static $db = array(
        'ItemID' => 'Int',
        'SKU' => 'Varchar(32)',
        'Title' => 'Varchar(32)',
        'Summary' => 'Text',
        'Content' => 'HTMLText',
        'Unit' => 'enum("Day,Hour,Week")',
        'CategoryID' => 'Int',
        'Available' => 'Int',
        'RateSlip' => 'CheckfrontSlip',
        'RateSummaryTitle' => 'Varchar(32)',
        'RateSummaryDetails' => 'Text'
    );
    private static $checkfront_map = array(
        'response' => array(
            'item_id' => 'ItemID',
            'sku' => 'SKU',
            'name' => 'Title',
            'summary' => 'Summary',
            'details' => 'Content',
            'unit' => 'Unit',
            'category_id' => 'CategoryID',
            'available' => 'Available',
            'rate.slip' => 'RateSlip',
            'rate.summary.title' => 'RateSummaryTitle',
            'rate.summary.details' => 'RateSummaryDetails',
            'rate.summary.date' => 'RateSummaryDate',
        )
    );

    /**
     *
     * @return CompositeField
     */
    public function fieldsForForm() {

        $fieldList = new FieldList([
            new HiddenField('ItemID[' . $this->ItemID . ']', '', $this->ItemID),
            new HiddenField('Unit[' . $this->ItemID . ']', '', $this->Unit)
        ]);

        $numAvailable = $this->Available;
        // unlimited so give text box
        if ($numAvailable === 2147483647) {
            $fieldList->push(
                new NumericField('Quantity[' . $this->ItemID . ']', $this->Title)
            );
        } else {
            $fieldList->push(
                new DropdownField('Quantity[' . $this->ItemID . ']', $this->Title, range(0, $this->Available))
            );
        }

        return new CompositeField($fieldList);
    }

}