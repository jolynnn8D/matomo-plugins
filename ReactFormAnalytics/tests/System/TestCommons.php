<?php


namespace Piwik\Plugins\ReactFormAnalytics\tests\System;


use Piwik\DataTable\Row;
use Piwik\Plugins\ReactFormAnalytics\Commons;

class TestCommons
{
    const VALID_ACTION = array(
        Commons::EVENT_NAME => TestCommons::VALID_FIELD_NAME,
        Commons::EVENT_VALUE => 5,
        Commons::TIME_KEY => 10,
    );

    const VALID_EXISTING_FIELD_ACTION = array(
        Commons::EVENT_NAME => TestCommons::VALID_FIELD_NAME_2,
        Commons::EVENT_VALUE => 5,
    );

    const VALID_FORM_COLLECTION = array(
        TestCommons::VALID_FORM_NAME => TestCommons::VALID_FORM
    );
    const VALID_FORM = array(
        'label' => TestCommons::VALID_FORM_NAME,
        'views' => 1,
        'users' => 1,
        'average_time' => 600,
        'nb_fields' => 2,
        'fields' => TestCommons::VALID_INCOMING_FIELD_COLLECTION,
    );

    const VALID_EMPTY_FIELD_COLLECTION = array();
    const VALID_FORM_NAME = "3";
    const VALID_FIELD_COLLECTION = array(
        TestCommons::VALID_FIELD_NAME => TestCommons::VALID_NEW_FIELD,
        TestCommons::VALID_FIELD_NAME_2 => TestCommons::VALID_FIELD);

    const VALID_NEW_FIELD =  array(
        'label' => TestCommons::VALID_FIELD_NAME,
        'users' => 0,
        'average_time' => 0,
        'average_clicks' => 0
    );

    const VALID_NEW_FIELD_2 =  array(
        'label' => TestCommons::VALID_FIELD_NAME_2,
        'users' => 0,
        'average_time' => 0,
        'average_clicks' => 0
    );
    const VALID_FIELD = array(
        'label' => TestCommons::VALID_FIELD_NAME_2,
        'users' => TestCommons::VALID_USERS,
        'average_time' => TestCommons::VALID_AVERAGE_TIME,
        'average_clicks' => TestCommons::VALID_AVERAGE_CLICKS,
    );

    const VALID_INCOMING_FIELD = array(
        'label' => TestCommons::VALID_FIELD_NAME_2,
        'users' => 1,
        'average_time' => 86,
        'average_clicks' => 36.7,
    );
    const VALID_INCOMING_FIELD_COLLECTION = array(
        TestCommons::VALID_FIELD_NAME_2 => TestCommons::VALID_INCOMING_FIELD
    );

    const VALID_FIELD_NAME = 'Test Form Field';
    const VALID_FIELD_NAME_2 = 'Test Form Field 2';
    const VALID_USERS = 10;
    const VALID_AVERAGE_TIME = 223.5;
    const VALID_AVERAGE_CLICKS = 25.7;

    const EXPECTED_UPDATED_FIELD = array(
        'label' => TestCommons::VALID_FIELD_NAME_2,
        'users' => 11,
        'average_time' => 211,
        'average_clicks' => 26.7,
    );

    const EXPECTED_UPDATED_FIELD_COLLECTION = array(
        TestCommons::VALID_FIELD_NAME => TestCommons::VALID_NEW_FIELD,
        TestCommons::VALID_FIELD_NAME_2 => TestCommons::EXPECTED_UPDATED_FIELD
    );

    const EXPECTED_UPDATED_FORM_COLLECTION = array(
        TestCommons::VALID_FORM_NAME => TestCommons::EXPECTED_UPDATED_FORM
    );

    const EXPECTED_UPDATED_FORM = array(
        'label' => TestCommons::VALID_FORM_NAME,
        'views' => 1,
        'users' => 1,
        'average_time' => 600,
        'nb_fields' => 3,
        'fields' => TestCommons::EXPECTED_UPDATED_NEW_FIELD_COLLECTION,
    );

    const EXPECTED_UPDATED_NEW_FIELD = array(
        'label' => TestCommons::VALID_FIELD_NAME,
        'users' => 1,
        'average_time' => 10,
        'average_clicks' => 1
    );

    const EXPECTED_UPDATED_NEW_FIELD_COLLECTION = array(
        TestCommons::VALID_FIELD_NAME_2 => TestCommons::VALID_INCOMING_FIELD,
        TestCommons::VALID_FIELD_NAME => TestCommons::VALID_NEW_FIELD,
    );

    const EXPECTED_INDIVIDUAL_FIELD_EVENT = array(
        Commons::INDIV_FIELD => array(
            'label' => Commons::INDIV_FIELD,
            'views' => 1,
            'users' => 0,
            'average_time' => 0,
            'nb_fields' => 1,
            'fields' => array(
                TestCommons::VALID_FIELD_NAME => TestCommons::EXPECTED_UPDATED_NEW_FIELD,
            )
        )
    );
    public static function constructValidFormRow() {
        return new Row(array(
            Row::COLUMNS => array(
                'label' => TestCommons::VALID_FORM_NAME,
                'views' => 5,
                'users' => 2,
                'average_time' => 450,
                'nb_fields' => 2,
                'fields' => TestCommons::VALID_FIELD_COLLECTION,
            )
        ));
    }

    public static function constructExpectedFormRow() {
        return new Row(array(
            Row::COLUMNS => array(
                'label' => TestCommons::VALID_FORM_NAME,
                'views' => 6,
                'users' => 3,
                'average_time' => 500,
                'nb_fields' => 2,

                'fields' => TestCommons::EXPECTED_UPDATED_FIELD_COLLECTION,
            )
        ));
    }
}