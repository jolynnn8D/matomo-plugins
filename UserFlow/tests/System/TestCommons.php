<?php

namespace Piwik\Plugins\UserFlow\tests\System;


use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Plugins\UserFlow\Commons;

class TestCommons
{
    const VALID_URL_WITH_ID = 'www.example.com/index/25';
    const VALID_URL_WITHOUT_ID = 'www.example.com/index';
    const VALID_HTTP_URL_WITH_ID = 'http://www.example.com/25';
    const VALID_STRANGE_URL = 'www.example.com/index/page?=25';
    const VALID_LOCALHOST_PATH = 'localhost:3000/dashboard/tab1/25';
    const VALID_LOCALHOST_URL = 'localhost:3000';
    const VALID_URL_SEGMENT = '/dashboard/tab1/25';
    const VALID_ENTRY_URLS =  array(TestCommons::VALID_LOCALHOST_URL => TestCommons::VALID_NODE_ID);


    const EXPECTED_URL_STRIPPED = 'www.example.com/index';
    const EXPECTED_HTTP_URL_STRIPPED = 'http://www.example.com';
    const EXPECTED_LOCALHOST_URL_STRIPPED = 'localhost:3000';
    const EXPECTED_URL_SEGMENT_STRIPPED = '/dashboard/tab1';

    const EXPECTED_URL_DOMAIN = 'www.example.com';
    const EXPECTED_HTTP_URL_DOMAIN = 'http://www.example.com';
    const EXPECTED_URL_SEGMENT = '/index';
    const EXPECTED_EMPTY_SEGMENT = '';
    const EXPECTED_STRANGE_SEGMENT = '/index/page?=25';


    const VALID_NODE_ID = 50;
    const VALID_VISIT_COUNT = 10;
    const VALID_FLOAT_AVERAGE_TIME = 9.5;
    const VALID_INT_AVERAGE_TIME = 15;
    const VALID_CHILDREN = array(1,3);
    const VALID_CHILD_ID = 1;

    const ZERO_VISIT_COUNT = 0;

    const EXPECTED_UPDATE_TIME_AVERAGE_TIME = 11;
    const EXPECTED_UPDATE_METRICS_AVERAGE_TIME = 10;
    const EXPECTED_UPDATE_METRICS_VISIT_COUNT = 11;

    const EXPECTED_SINGLE_NODE_DEPTH = 0;
    const EXPECTED_TREE_DEPTH = 2;

    public static function constructDataTreeWithVaryingDepth() {
        $rootNode = TestCommons::constructNode(
            TestCommons::VALID_NODE_ID,
            TestCommons::VALID_VISIT_COUNT,
            TestCommons::VALID_FLOAT_AVERAGE_TIME,
            "",
            "",
            0,
            TestCommons::VALID_CHILDREN
        );

        $firstLeftChild = TestCommons::constructNode(
            1,
            TestCommons::VALID_VISIT_COUNT,
            TestCommons::VALID_FLOAT_AVERAGE_TIME,
            "",
            "",
            TestCommons::VALID_NODE_ID,
            array(2)
        );

        $secondLeftChild = TestCommons::constructNode(
            2,
            TestCommons::VALID_VISIT_COUNT,
            TestCommons::VALID_FLOAT_AVERAGE_TIME,
            "",
            "",
            1
        );
        $rightChild = TestCommons::constructNode(
            3,
            TestCommons::VALID_VISIT_COUNT,
            TestCommons::VALID_FLOAT_AVERAGE_TIME,
            "",
            "",
            TestCommons::VALID_NODE_ID
        );
        return new DataTableStub(array($rootNode, $firstLeftChild, $secondLeftChild, $rightChild));
    }

    public static function constructDataTableWithParentChild() {
        $parentNode = TestCommons::constructNode(
            TestCommons::VALID_NODE_ID,
            TestCommons::VALID_VISIT_COUNT,
            TestCommons::VALID_FLOAT_AVERAGE_TIME,
            TestCommons::VALID_LOCALHOST_URL,
            "",
            0,
            TestCommons::VALID_CHILDREN
        );
        $childNode = TestCommons::constructNode(
            TestCommons::VALID_CHILD_ID,
            TestCommons::VALID_VISIT_COUNT,
            TestCommons::VALID_INT_AVERAGE_TIME,
            TestCommons::VALID_LOCALHOST_URL,
            TestCommons::VALID_URL_SEGMENT
        );
        return new DataTableStub(array($parentNode, $childNode));
    }

    public static function constructDataTableWithSingleNode() {
        $validRow = TestCommons::constructNode(
            TestCommons::VALID_NODE_ID,
            TestCommons::VALID_VISIT_COUNT,
            TestCommons::VALID_FLOAT_AVERAGE_TIME);
        $validDataTable = new DataTableStub(array($validRow));
        return $validDataTable;
    }

    public static function constructNode($id, $visits, $averageTime, $domain = "", $url = "", $parentId = 0, $children = []) {
        $node = new Row(array(
            Row::COLUMNS => array(
                'label' => $id,
                Commons::VISITOR_KEY => $visits,
                Commons::AVERAGE_TIME_KEY => $averageTime,
                Commons::URL_KEY => $url,
                'domain' => $domain,
                'parent_id' => $parentId,
                'children' => $children,
            )));
        return $node;
    }


}