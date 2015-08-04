<?php

class CheckfrontPackageBookingForm extends CheckfrontForm {
    const SubmitButtonName = 'book';


    public static function factory(CheckfrontAPIPackageResponse $packageResponse, array $info, array $data) {
        list($packageID, $startDate, $endDate, $linkType, $userType, $paymentType) = $info;

            // now build the form
        $fields = new FieldList();

        // add a hidden accessKey field
        $fields->push(new HiddenField(CheckfrontForm::AccessKeyFieldName, '', $data[CheckfrontForm::AccessKeyFieldName]));

        if ($userType === CheckfrontModule::UserTypeOrganiser) {
            // if organiser then add hidden start and end date fields for the actual booking
            $fields->merge(array(
                new HiddenField(CheckfrontForm::StartDateFieldName, '', $startDate),
                new HiddenField(CheckfrontForm::EndDateFieldName, '', $endDate)
            ));
        } else {
            // if not organiser then let user specify their start and end dates
            $fields->merge(array(
                CheckfrontForm::make_date_field(
                    CheckfrontForm::StartDateFieldName,
                    'Start Date',
                    $startDate,
                    $startDate,
                    $endDate
                ),
                CheckfrontForm::make_date_field(
                    CheckfrontForm::EndDateFieldName,
                    'End Date',
                    $endDate,
                    $startDate,
                    $endDate
                )
            ));
        }

        // add the package items to the field list which will make the form as fields
        /** @var CheckfrontModel $item */
        foreach ($packageResponse->getPackageItems() as $item) {
            if ($this->shouldShowItem($item, $userType, $linkType)) {
                $fields->merge($item->fieldsForForm('form'));
            }
        }

        $fields->merge(
            new FieldList(array(
                    new HiddenField(CheckfrontAccessKeyForm::AccessKeyFieldName, '', $accessKey)
                )
            )
        );


    }

    /**
     * Creates form with:
     *
     *  -   Checkfront booking form fields from call to api.fetchBookingForm
     *  -   A 'book' action
     *
     * @param Controller $controller
     * @param string $name
     * @param FieldList $fields
     * @param FieldList $actions
     * @param null $validator
     */
    public function __construct($controller, $name, $fields, $actions, $validator = null) {
        $fields = $fields ?: new FieldList();
        $actions = $actions ?: new FieldList();

        $required = array();

        // add the standard 'booking' fields (name etc)
        if ($response = CheckfrontModule::api()->fetchBookingForm()) {
            if ($response->isValid()) {

                // now add the booking fields to the fieldlist for the form
                $bookingFields = $response->getFormFields($required);

                $fields->merge(
                    $bookingFields
                );
            }
            $actions->push(
                new FormAction(static::SubmitButtonName, _t(__CLASS__ . ".SubmitButtonText"))
            );
        }
        $validator = new RequiredFields($required);

        parent::__construct(
            $controller,
            $name,
            $fields,
            $actions,
            $validator
        );
    }
}