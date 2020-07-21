<?php

namespace Piwik\Plugins\UserFlow\tests\System;

use Piwik\Plugins\UserFlow\API;
use PHPUnit\Framework\TestCase;
use Piwik\Plugins\UserFlow\Commons;
use ReflectionClass;

class APIPrivateMethodsTest extends TestCase
{

    public function invokeUserFlowAPIMethod($methodName, array $parameters = array())
    {
        $userFlowAPI = new API();
        $reflection = new ReflectionClass(get_class($userFlowAPI));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($userFlowAPI, $parameters);
    }

    function test_searchDepth_validInputs_success() {
        $methodName = 'searchDepth';

        $validDataTable = TestCommons::constructDataTableWithSingleNode();
        $testSingleNode = $this -> invokeUserFlowAPIMethod($methodName,
        array($validDataTable -> getRowFromLabel(TestCommons::VALID_NODE_ID), 0, $validDataTable));
        $this -> assertEquals(TestCommons::EXPECTED_SINGLE_NODE_DEPTH, $testSingleNode);

        $validTree = TestCommons::constructDataTreeWithVaryingDepth();
        $testVaryingDepth = $this -> invokeUserFlowAPIMethod($methodName,
        array($validTree -> getRowFromLabel(TestCommons::VALID_NODE_ID), 0, $validTree));
        $this -> assertEquals(TestCommons::EXPECTED_TREE_DEPTH, $testVaryingDepth);

    }

    function test_retrieveActionId_validInputs_success() {
        $methodName = 'retrieveActionId';

        $validParentChildDataTable = TestCommons::constructDataTableWithParentChild();

        $testChildWithActionUrl = $this -> invokeUserFlowAPIMethod($methodName,
        array(TestCommons::VALID_NODE_ID, TestCommons::VALID_LOCALHOST_PATH, $validParentChildDataTable));
        $this -> assertEquals(TestCommons::VALID_CHILD_ID, $testChildWithActionUrl);

        $testNoChildWithActionUrl = $this -> invokeUserFlowAPIMethod($methodName,
        array(TestCommons::VALID_NODE_ID, TestCommons::VALID_URL_WITHOUT_ID, $validParentChildDataTable));
        $this->assertEquals(false, $testNoChildWithActionUrl);

    }

    function test_getDomain_validInputs_success() {
        $methodName = 'getDomain';

        $testValidUrl = $this -> invokeUserFlowAPIMethod($methodName, array(
            TestCommons::VALID_URL_WITHOUT_ID));
        $this -> assertEquals(TestCommons::EXPECTED_URL_DOMAIN, $testValidUrl);

        $testValidHttpUrl = $this -> invokeUserFlowAPIMethod($methodName, array(
            TestCommons::VALID_HTTP_URL_WITH_ID));
        $this -> assertEquals(TestCommons::EXPECTED_HTTP_URL_DOMAIN, $testValidHttpUrl);
    }

    function test_getUrlSegment_validInputs_success() {
        $methodName = 'getUrlSegment';

        $testValidUrl = $this -> invokeUserFlowAPIMethod($methodName, array(
            TestCommons::VALID_URL_WITHOUT_ID,));
        $this -> assertEquals(TestCommons::EXPECTED_URL_SEGMENT, $testValidUrl);

        $testValidUrlWithoutSegment = $this->invokeUserFlowAPIMethod($methodName, array(
            TestCommons::VALID_LOCALHOST_URL));
        $this -> assertEquals(TestCommons::EXPECTED_EMPTY_SEGMENT, $testValidUrlWithoutSegment);

        $testValidStrangeUrl = $this -> invokeUserFlowAPIMethod($methodName, array(
            TestCommons::VALID_STRANGE_URL));
        $this -> assertEquals(TestCommons::EXPECTED_STRANGE_SEGMENT, $testValidStrangeUrl);
    }

    function test_retrieveEntryId_validInputs_success() {
        $methodName = 'retrieveEntryId';
        $testValidEntryUrls = $this -> invokeUserFlowAPIMethod($methodName, array(
            TestCommons::VALID_LOCALHOST_URL,
            TestCommons::VALID_ENTRY_URLS
        ));
        $this -> assertEquals(TestCommons::VALID_NODE_ID, $testValidEntryUrls);

        $testEmptyEntryUrl = $this -> invokeUserFlowAPIMethod($methodName, array(
            "",
            TestCommons::VALID_ENTRY_URLS
        ));
        $this -> assertEquals(false, $testEmptyEntryUrl);
    }

    function test_updateMetrics_validInputs_success() {
        $methodName = 'updateMetrics';

        $validDataTable = TestCommons::constructDataTableWithSingleNode();

        $testValidDataTable = $this -> invokeUserFlowAPIMethod($methodName,
            array(TestCommons::VALID_NODE_ID,
                $validDataTable,
                TestCommons::VALID_INT_AVERAGE_TIME));

        $testRow = $testValidDataTable->getRowFromLabel(TestCommons::VALID_NODE_ID);

        $testUpdatedAvgTime = $testRow->getColumn(Commons::AVERAGE_TIME_KEY);
        $this->assertEquals(TestCommons::EXPECTED_UPDATE_METRICS_AVERAGE_TIME,
            $testUpdatedAvgTime);

        $testUpdatedVisitorCount = $testRow -> getColumn(Commons::VISITOR_KEY);
        $this -> assertEquals(TestCommons::EXPECTED_UPDATE_METRICS_VISIT_COUNT,
            $testUpdatedVisitorCount);
    }

    function test_updateTimeOnly_validInputs_success()
    {
        $methodName = 'updateTimeOnly';

        $validDataTable = TestCommons::constructDataTableWithSingleNode();

        $testValidDataTable = $this->invokeUserFlowAPIMethod($methodName,
            array(TestCommons::VALID_NODE_ID,
                $validDataTable,
                TestCommons::VALID_INT_AVERAGE_TIME));
        $testRow = $testValidDataTable->getRowFromLabel(TestCommons::VALID_NODE_ID);

        $testUpdatedAvgTime = $testRow->getColumn(Commons::AVERAGE_TIME_KEY);
        $this->assertEquals(TestCommons::EXPECTED_UPDATE_TIME_AVERAGE_TIME,
            $testUpdatedAvgTime);

        $testUpdatedVisitorCount = $testRow -> getColumn(Commons::VISITOR_KEY);
        $this -> assertEquals(TestCommons::VALID_VISIT_COUNT, $testUpdatedVisitorCount);
    }

    function test_stripId_validUrlString_success()
    {
        $methodName = 'stripId';
        $testValidUrlWithId = $this->invokeUserFlowAPIMethod(
            $methodName, array(TestCommons::VALID_URL_WITH_ID));
        $this -> assertEquals(TestCommons::EXPECTED_URL_STRIPPED, $testValidUrlWithId);

        $testValidUrlWithoutId = $this->invokeUserFlowAPIMethod(
            $methodName, array(TestCommons::VALID_URL_WITHOUT_ID));
        $this -> assertEquals(TestCommons::EXPECTED_URL_STRIPPED, $testValidUrlWithoutId);

        $testValidHttpUrlWithId = $this->invokeUserFlowAPIMethod(
            $methodName, array(TestCommons::VALID_HTTP_URL_WITH_ID));
        $this -> assertEquals(TestCommons::EXPECTED_HTTP_URL_STRIPPED, $testValidHttpUrlWithId);

        $testValidLocalhostUrl = $this->invokeUserFlowAPIMethod(
            $methodName, array(TestCommons::VALID_LOCALHOST_URL));
        $this -> assertEquals(TestCommons::EXPECTED_LOCALHOST_URL_STRIPPED, $testValidLocalhostUrl);

        $testValidUrlSegment = $this -> invokeUserFlowAPIMethod(
            $methodName, array(TestCommons::VALID_URL_SEGMENT));
        $this -> assertEquals(TestCommons::EXPECTED_URL_SEGMENT_STRIPPED, $testValidUrlSegment);
    }
}
