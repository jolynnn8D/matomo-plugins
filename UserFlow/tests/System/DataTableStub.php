<?php


namespace Piwik\Plugins\UserFlow\tests\System;


class DataTableStub
{
    public $nodes;

    public function __construct($nodeArray) {
        foreach($nodeArray as $node) {
            $id = $node -> getColumn('label');
            $this -> nodes[$id] = $node;
        }
    }

    public function getRowFromLabel($actionId) {
        if (array_key_exists($actionId, $this -> nodes)) {
            return ($this -> nodes)[$actionId];
        } else {
            return false;
        }
    }
}