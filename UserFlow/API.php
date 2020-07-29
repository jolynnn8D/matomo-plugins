<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\UserFlow;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Metrics;
use Piwik\Site;
use Piwik\Plugins\UserFlow\Commons;

/**
 * API for plugin UserFlow
 *
 * @method static \Piwik\Plugins\UserFlow\API getInstance()
 */
class API extends \Piwik\Plugin\API
{

    public $MAX_ACTION_STEPS = 30;
    public $MAX_VISITORS = 100;
    public $GROUPED_NODES = true;

    /**
     * Returns a DataTable summarising the most commonly used workflow. Most commonly used workflow is defined by the
     * workflow with the greatest number of visitors along the path, and given two workflows with similar number of
     * visitors, the longest workflow will be chosen. Each row in the DataTable represents a single step in the workflow.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getSummary($idSite, $period, $date, $segment = false) {
        $dataTable = \Piwik\API\Request::processRequest('UserFlow.getUserFlowTree');
        $dataTable -> filter('Sort', array('step', 'asc', $naturalSort = false));

        $result = new DataTable();
        $nodeToTrack = null;
        foreach ($dataTable -> getRows() as $node) {
            $level = $node -> getColumn('step');
            if ($level != 0) {
                break;
            }
            if ($nodeToTrack == null) {
                $nodeToTrack = $node;
            } else {
                $nodeToTrack = $this->searchLongerPath($node, $nodeToTrack, $dataTable);
            }

        }
        if ($nodeToTrack != null) {
            $result = $this->traceWorkflow($nodeToTrack, $result, $dataTable);
        }
        return $result;
    }

    /**
     * Trace the most commonly used workflow in a particular tree by choosing the child of each node with the most
     * number of visitors. If two children have the same number of visitors, a search of the depth occurs and the
     * one with a higher depth is chosen.
     * @param Row $node The node to start tracing a workflow from.
     * @param DataTable $workflowDataTable Constructed DataTable from the most commonly used workflow.
     * @param DataTable $dataTable DataTable of the user flow tree.
     * @return DataTable
     */
    private function traceWorkflow($node, $workflowDataTable, $dataTable) {
        $step = 1;
        $currNode = $node;
        $newRow = $this -> createRowFromNode($step, $currNode);
        $workflowDataTable->addRow($newRow);
        $step++;
        $children = $currNode -> getColumn('children');
        while(!empty($children)) {
            $highestVisits = 0;
            foreach($children as $child) {
                $childRow = $dataTable -> getRowFromLabel($child);
                $visits = $childRow -> getColumn(Commons::VISITOR_KEY);
                if ($visits > $highestVisits) {
                    $highestVisits = $visits;
                    $currNode = $childRow;
                } else if ($visits == $highestVisits) {
                    $currNode = $this -> searchLongerPath($currNode, $childRow, $dataTable);
                }
            }
            $newRow = $this -> createRowFromNode($step, $currNode);
            $workflowDataTable->addRow($newRow);
            $step++;
            $children = $currNode -> getColumn('children');
        }

        return $workflowDataTable;
    }

    /**
     * Given two nodes, return the node with a higher depth (longer route to the end).
     * @param Row $firstNode First node to compare
     * @param Row $secondNode Second node to compare
     * @param DataTable $data DataTable of the user flow tree.
     * @return Row
     */
    private function searchLongerPath($firstNode, $secondNode, $data) {
        $firstNodeLength = $this->searchDepth($firstNode, 0, $data);
        $secondNodeLength = $this->searchDepth($secondNode, 0, $data);
        if ($firstNodeLength > $secondNodeLength) {
            return $firstNode;
        } else {
            return $secondNode;
        }
    }

    /**
     * Recursively search for the longest path starting from a node, DFS.
     * @param Row $node Node to start searching from.
     * @param integer $depth Current depth.
     * @param DataTable $data DataTable of the user flow tree.
     * @return integer
     */
    private function searchDepth($node, $depth, $data) {
        $children = $node -> getColumn('children');
        if (empty($children)) {
            return $depth;
        } else {
            $maxDepth = $depth;
            foreach($children as $child) {
                $childRow = $data -> getRowFromLabel($child);
                $childDepth = $this->searchDepth($childRow, $depth + 1, $data);
                if ($childDepth > $maxDepth) {
                    $maxDepth = $childDepth;
                }
            }
            return $maxDepth;
        }
    }

    /**
     * Creates a Row in the DataTable which represents a single step in the most common workflow.
     * @param integer $step Represents the nth step of the workflow.
     * @param Row $node Node in the user flow that is part of the most common workflow.
     * @return Row
     */
    private function createRowFromNode($step, $node) {
        $url = $node -> getColumn(Commons::URL_KEY);
        $domain = $node -> getColumn(Commons::DOMAIN_KEY);
        $visitors = $node -> getColumn(Commons::VISITOR_KEY);
        $timeSpent = $node -> getColumn(Commons::AVERAGE_TIME_KEY);
        $newRow = new Row(array(
            Row::COLUMNS => array(
                'label' => $domain . $url,
                'step' => $step,
                Commons::VISITOR_KEY => $visitors,
                Commons::AVERAGE_TIME_KEY => $timeSpent)));
        return $newRow;
    }

    /**
     * Returns a DataTable of nodes which represents the User Flow Tree. This report requests for visitor data from the
     * Live plugin. Each row in the DataTable represents a node in the tree, containing information like : URL,
     * Number of Visitors, Average time spent, Parent Node ID and Children Nodes IDs.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getUserFlowTree($idSite, $period, $date, $segment = false)
    {
        $data = \Piwik\API\Request::processRequest('Live.getLastVisitsDetails', array(
            'idSite' => $idSite,
            'period' => $period,
            'date' => $date,
            'segment' => $segment,
            'countVisitorsToFetch' => $this->MAX_VISITORS,
        ));

        //Entry URL to node ID mapping
        $entryUrls = [];

        $result = new DataTable();

        foreach ($data -> getRows() as $visitRow) {
            $actions = $visitRow -> getColumn(Commons::ACTION_KEY);
            $entryAction = $actions[0];

            // Retrieve entry ID from URL mapping
            $processedEntry = $this->processEntryNode($entryAction, $entryUrls, $result);
            $result = $processedEntry['result']; //Updated metrics for entry node
            $parentId = $processedEntry['actionId']; //Retrieved ID for entry node
            $entryUrls = $processedEntry['entryUrls']; //Update URL mapping

            $actionNum = 0;
            for ($action = 1; $action < sizeof($actions); $action++) {
                if ($actionNum >= $this->MAX_ACTION_STEPS) {
                    break;
                }
                $currAction = $actions[$action];
                if ($currAction['type'] == 'action') {
                    $actionNum++;
                    $processedAction = $this->processAction($currAction, $result, $parentId, $action, false);
                    $result = $processedAction['result'];
                    $parentId = $processedAction['actionId'];
                }
            }

        }
        return $result;
    }

    /**
     * Processes specifically entry actions (first step of a list of actions).
     * The entry action is different from the rest of the actions because the only information on hand is the
     * entry URL. To find the root of the user tree, the URL to ID mapping for entry nodes need to be accessed
     * to find the root of the desired user flow.
     * @param $entryAction
     * @param $entryUrls
     * @param $result
     * @return array
     */
    private function processEntryNode($entryAction, $entryUrls, $result) {
        $processedEntry = $this -> processAction($entryAction, $result, 0, 0, true, $entryUrls);
        return $processedEntry;
    }

    /**
     * Processes in a single action made by a visitor, and updates the user flow tree accordingly.
     * If there was a similar sequence of actions made by a previous visitor, the said sequence's number of visits
     * and average time spent will be updated accordingly. If the sequence does not exist, a new child will
     * be created to indicate a new sequence.
     * The function works slightly differently if the action is the first action made by the visitor (entry), where
     * the entry point (by node ID) of the user flow tree will have to be retrieved from a URL to ID
     * mapping that has been initiated previously.
     * @param array $action An array of the details of a single action made by the visitor, provided by Live plugin.
     * @param DataTable $result The DataTable to update.
     * @param integer $parentId Unique ID of the node of the action that precedes this action.
     * @param integer $step  Indicates the n-th number of steps this action is on.
     * @param bool $isEntry Whether this action is an entry action.
     * @param array $entryUrls URL to ID mapping for entry nodes.
     * @return array Contains three results - Updated DataTable, ID of the node, and updated entry URL to ID mapping
     */
    private function processAction($action, $result, $parentId, $step, $isEntry, $entryUrls = []) {
        $actionUrl = $this->stripId($action[Commons::URL_KEY]);
        $actionTime = 0;
        if (array_key_exists(Commons::TIME_KEY, $action)){
            $actionTime = $action[Commons::TIME_KEY];
        }
        if ($isEntry) {
            $actionId = $this -> retrieveEntryId($actionUrl, $entryUrls);
        } else {
            $actionId = $this -> retrieveActionId($parentId, $actionUrl, $result);
        }

        if ($actionId == false) {
            $node = $this -> initialiseNode($actionUrl, $parentId, $step, $actionTime);
            $actionId = $node -> getColumn('label');
            $result -> addRow($node);
            if ($isEntry) {
                $entryUrls[$actionUrl] = $actionId;
            } else {
                $result = $this -> addParent($parentId, $actionId, $result);
            }
        } else if ($actionId == $parentId) {
            // Counts refreshes or changes in URL ID to be the same step
            $result = $this -> updateTimeOnly($actionId, $result, $actionTime);
        } else {
            $result = $this -> updateMetrics($actionId, $result, $actionTime);
        }
        return array("result" => $result, "actionId" => $actionId, "entryUrls" => $entryUrls);
    }

    /**
     * Retrieves the ID of the entry node (first action step) based on an array of URL to ID mapping.
     * If the mapping does not exist, return false.
     * @param String $entryUrl URL of the entry action.
     * @param array $entryUrls URL to ID mapping for entry nodes.
     * @return bool|integer
     */
    private function retrieveEntryId($entryUrl, $entryUrls) {
        if (array_key_exists($entryUrl, $entryUrls)) {
            return $entryUrls[$entryUrl];
        } else {
            return false;
        }
    }

    /**
     * Searches the parent node to see if it contains a child node that corresponds to the input URL and returns the
     * ID of the child node.
     * If such a node does not exist, return false.
     * @param integer $parentId Unique ID of the parent node.
     * @param String $actionUrl URL on which the action occurred.
     * @param DataTable $result The DataTable to search in.
     * @return bool|integer
     */
    private function retrieveActionId($parentId, $actionUrl, $result) {
        $parentRow = $result -> getRowFromLabel($parentId);
        $parentUrl = $this -> stripId($parentRow -> getColumn(Commons::URL_KEY));
        $parentDomain = $parentRow -> getColumn(Commons::DOMAIN_KEY);
        if ($this->GROUPED_NODES && $actionUrl ==  $parentDomain . $parentUrl) {
            return $parentId;
        }
        $children = $parentRow -> getColumn('children');
        foreach($children as $child) {
            $childRow = $result -> getRowFromLabel($child);
            if ($childRow == false) { continue; }
            $childUrl = $childRow -> getColumn(Commons::URL_KEY);
            $childDomain = $childRow -> getColumn(Commons::DOMAIN_KEY);
            if ($childDomain . $childUrl == $actionUrl) {
                return $child;
            }
        }
        return false;
    }

    /**
     * Updates the 'children' property of a node when a new node is created and assigned to it as a child.
     * Only the ID of the child node is stored.
     * @param integer $parentId Unique ID of the parent node.
     * @param integer $childId Unique ID of the child to be added to the parent node.
     * @param DataTable $result The DataTable to update.
     * @return DataTable
     */
    private function addParent($parentId, $childId, $result) {
        $parentRow = $result -> getRowFromLabel($parentId);
        $children = $parentRow -> getColumn('children');
        array_push($children, $childId);
        $parentRow -> setColumn('children', $children);
        return $result;
    }

    /**
     * Creates a new node representing a step in a user flow.
     * All nodes must have a unique ID, and a parent node (unless it is an entry node, then the parent ID will be
     * set to 0). The URL of the step is also broken down into domain and path, i.e www.example.com/shopping/checkout
     * will be split into 'www.example.com', and '/shopping/checkout'. This is to allow the URL to be displayed
     * neatly in the UI later on (and also if there are any functions to be written specifically for cross-domain
     * actions).
     * @param String $url URL on which the action occurred.
     * @param integer $parentId Unique ID of the parent node.
     * @param integer $step Indicates the n-th number of steps this action is on.
     * @param integer $timeSpent Time spent on the action by the visitor.
     * @return Row
     */
    private function initialiseNode($url, $parentId, $step, $timeSpent) {
        static $id = 1;
        $node = new Row(array(
            Row::COLUMNS => array(
                'label' => $id,
                'step' => $step,
                'domain' => $this->getDomain($url),
                Commons::URL_KEY => $this->getUrlSegment($url),
                Commons::VISITOR_KEY => 1,
                Commons::AVERAGE_TIME_KEY => $timeSpent,
                'parent_id' => $parentId,
                'children' => [])));
        $id += 1;
        return $node;
    }

    /**
     * Retrieves the domain of the url, i.e www.example.com/page returns www.example.com
     * @param string $url The full url.
     * @return string Domain portion of the url.
     */
    private function getDomain($url) {
        $domainMatches = [];
        preg_match(Commons::DOMAIN_REGEX, $url, $domainMatches);
        return $domainMatches[0];
    }

    /**
     * Retrieves a segment of the url without the domain, i.e www.example.com/page/index returns '/page/index'
     * @param string $url The full url.
     * @return string End segment of the url.
     */
    private function getUrlSegment($url) {
        return preg_split(Commons::DOMAIN_REGEX, $url)[1];
    }
    /**
     * Updates the number of visits as well as the average time spent on a webpage.
     * @param integer $actionId The unique ID of the node to update the data of.
     * @param DataTable $result The DataTable to update.
     * @param integer $actionTime Time spent on the action by the visitor.
     * @return DataTable
     */
    private function updateMetrics($actionId, $result, $actionTime)
    {
        $resultRow = $result->getRowFromLabel($actionId);
        $counter = $resultRow->getColumn(Commons::VISITOR_KEY);
        $resultRow->setColumn(Commons::VISITOR_KEY, $counter + 1);

        $avgTime = $resultRow -> getColumn(Commons::AVERAGE_TIME_KEY);
        $newAvgTime = ($avgTime * $counter + $actionTime) / ($counter + 1);
        $resultRow -> setColumn(Commons::AVERAGE_TIME_KEY, $newAvgTime);
        return $result;
    }

    /**
     * Updates the average time spent on a webpage by different visitors without updating the number of visits.
     * This is only called when a visitor refreshes the webpage or the ID of the webpage is changed.
     * e.g www.example.com/index/15 -> www.example.com/index/27
     * @param integer $actionId The unique ID of the node to update the data of.
     * @param DataTable $result The DataTable to update.
     * @param integer $actionTime Time spent on the action by the visitor.
     * @return DataTable
     */
    private function updateTimeOnly($actionId, $result, $actionTime) {
        $resultRow = $result->getRowFromLabel($actionId);
        $avgTime = $resultRow -> getColumn(Commons::AVERAGE_TIME_KEY);
        $visitors = $resultRow -> getColumn(Commons::VISITOR_KEY);
        $newAvgTime = ($avgTime * $visitors + $actionTime) / $visitors;
        $resultRow -> setColumn(Commons::AVERAGE_TIME_KEY, $newAvgTime);

        return $result;
    }

    /**
     * Strips the last segment of the path if it's numerical. Applied such that the same webpage with varied ID
     * data will be considered as an action on one webpage.
     * e.g www.example.com/index/27 will be stripped to www.example.com/index
     * @param String $url
     * @return mixed|string
     */
    private function stripId($url) {
        $output_array = [];
        preg_match(Commons::LAST_SEGMENT_REGEX, $url, $output_array);
        if (sizeof($output_array) > 0) {
            $lastSegment = $output_array[0];
            if (is_numeric($lastSegment)) {
                // An un-elegant way to remove the ID from the URL as well as the preceding backlash
                // Need to improve the regex if possible
                $urlWithoutLastSegment = preg_split(Commons::LAST_SEGMENT_REGEX, $url)[0];
                return substr($urlWithoutLastSegment, 0, -1);
            }
        }
        return $url;
    }
}
