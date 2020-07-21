<?php

namespace Piwik\Plugins\ReactFormAnalytics\tests\System;

use Monolog\Logger;
use Piwik\Plugins\Monolog\tests\Integration\Fixture\LoggerWrapper;
use Piwik\Plugins\ReactFormAnalytics\API;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class APIPrivateMethodsTest extends TestCase
{

    public function invokeReactFormAnalyticsAPIMethod($methodName, array $parameters = array()) {
        $logger = new \Psr\Log\NullLogger();
        $reactFormAnalyticsAPI = new API($logger);
        $reflection = new ReflectionClass(get_class($reactFormAnalyticsAPI));
        $method = $reflection -> getMethod($methodName);
        $method -> setAccessible(true);
        return $method -> invokeArgs($reactFormAnalyticsAPI, $parameters);
    }
    public function test_processIndividualFieldEvent_validInputs_success() {
        $methodName = 'processIndividualFieldEvent';

        $testEmptyUserEvents = $this -> invokeReactFormAnalyticsAPIMethod($methodName,
            array(TestCommons::VALID_ACTION, [])
        );
        $this->assertEquals(TestCommons::EXPECTED_INDIVIDUAL_FIELD_EVENT, $testEmptyUserEvents);

    }

    public function test_addFormField_validInputs_success() {
        $methodName = 'addFormField';
        $testAddNewField = $this -> invokeReactFormAnalyticsAPIMethod($methodName, array(
            TestCommons::VALID_ACTION, TestCommons::VALID_FORM_NAME, TestCommons::VALID_FORM_COLLECTION,
        ));
        $this->assertEquals(TestCommons::EXPECTED_UPDATED_FORM_COLLECTION, $testAddNewField);

        $testAddExistingField = $this ->invokeReactFormAnalyticsAPIMethod($methodName, array(
            TestCommons::VALID_EXISTING_FIELD_ACTION, TestCommons::VALID_FORM_NAME, TestCommons::VALID_FORM_COLLECTION,
        ));
        $this->assertEquals(TestCommons::VALID_FORM_COLLECTION, $testAddExistingField);
    }

    public function test_updateDataTableMetrics_validInputs_success() {
        $methodName = 'updateDataTableMetrics';
        $validFormRow = TestCommons::constructValidFormRow();
        $testValidFormRow = $this -> invokeReactFormAnalyticsAPIMethod($methodName, array(
            TestCommons::VALID_FORM,
            $validFormRow,
        ));
        $expectedFormRow = TestCommons::constructExpectedFormRow();
        $this -> assertEquals($expectedFormRow , $testValidFormRow );
    }

    public function test_updateFieldMetrics_validInputs_success() {
        $methodName = 'updateFieldMetrics';
        $testEmptyCurrFields = $this -> invokeReactFormAnalyticsAPIMethod($methodName, array(
            TestCommons::VALID_EMPTY_FIELD_COLLECTION,
            TestCommons::VALID_FIELD_NAME_2,
            TestCommons::VALID_FIELD,
        ));
        $this -> assertEquals(TestCommons::VALID_FIELD, $testEmptyCurrFields);

        $testUpdateExistingField = $this -> invokeReactFormAnalyticsAPIMethod($methodName, array(
           TestCommons::VALID_FIELD_COLLECTION,
           TestCommons::VALID_FIELD_NAME_2,
           TestCommons::VALID_INCOMING_FIELD,
        ));
        $this->assertEquals(TestCommons::EXPECTED_UPDATED_FIELD, $testUpdateExistingField);

        $testUpdateNoUser = $this -> invokeReactFormAnalyticsAPIMethod($methodName, array(
            TestCommons::VALID_FIELD_COLLECTION,
            TestCommons::VALID_FIELD_NAME_2,
            TestCommons::VALID_NEW_FIELD_2,
        ));
        $this->assertEquals(TestCommons::VALID_FIELD, $testUpdateNoUser);
    }


}
