<?php


namespace Piwik\Plugins\UserFlow;


use Piwik\View;

class Controller extends \Piwik\Plugin\Controller
{
    public function getUserFlowTree()
    {
        $CUSTOM_VIZ = true;

        if ($CUSTOM_VIZ) {
            // Customised visualization
            $view = new View("@UserFlow/index.twig");
            $view->summary = $this -> getSummary();
            $view->chart = $this->renderChart();
        } else {
            // Normal Data Table -- used for debugging
            $view = \Piwik\ViewDataTable\Factory::build(
                $defaultType = 'table', // the visualization type
                $apiAction = 'UserFlow.getUserFlowTree',
                $controllerMethod = 'UserFlow.getUserFlowTree'
            );
            $view->config->columns_to_display = array_merge(array('label', 'step', 'url', 'domain', 'nb_visits', 'average_time', 'parent_id'));
        }

        return $view->render();
    }
    public function getSummary() {
        $view = \Piwik\ViewDataTable\Factory::build(
            $defaultType = 'table', // the visualization type
            $apiAction = 'UserFlow.getSummary',
            $controllerMethod = 'UserFlow.getSummary'
            );
        $view->config->columns_to_display = array_merge(array('step', 'label', 'nb_visits','average_time'));
        $view->config->show_visualization_only = true;
        return $view -> render();
    }

    public function renderChart()
    {
        $view = new View("@UserFlow/_userflowViz.twig");
        $data = \Piwik\API\Request::processRequest('UserFlow.getUserFlowTree');
        $view->data = $this -> convertToPHParray($data);
        return $view -> render();
    }

    private function convertToPHParray($dataTable) {
        $resultArray = [];
        $keys = $dataTable -> getColumns();
        foreach($dataTable -> getRows() as $node) {
            $nodeArray = [];
            foreach ($keys as $key) {
                $nodeArray[$key] = $node -> getColumn($key);
            }
            array_push($resultArray, $nodeArray);
        }
        return $resultArray;

    }

}