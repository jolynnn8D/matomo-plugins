<?php


namespace Piwik\Plugins\ReactFormAnalytics;


use Piwik\View;

class Controller extends \Piwik\Plugin\Controller
{
    public function getForms() {
        $view = new View("@ReactFormAnalytics/index.twig");
        $view->formsDetected = $this -> renderFormUI();
//        $view->formsDetected = $this -> getFormsDetected();
        $view->formDistribution = $this -> getFormDistribution();
        return $view -> render();
    }

    public function getFormsDetected() {
        $data = \Piwik\API\Request::processRequest('ReactFormAnalytics.getFormsDetected');
        return $data;
    }

    public function getFormDistribution() {
        $data = \Piwik\API\Request::processRequest('ReactFormAnalytics.getFormTimeDistribution');
        return $data;
    }

    public function renderFormUI() {
        $view = new View("@ReactFormAnalytics/_formHeatMap.twig");
        $data = \Piwik\API\Request::processRequest('ReactFormAnalytics.getFormsDetected');
        $distributions = \Piwik\API\Request::processRequest('ReactFormAnalytics.getFormTimeDistribution');
        $view->formData = $this -> convertToPHParray($data);
        $view->formNames = $this -> retrieveFormNames($data);
        $view->formDistribution = $this -> convertToPHParray($distributions);
        return $view -> render();
    }

    private function retrieveFormNames($dataTable) {
        $formNames = [];
        foreach($dataTable -> getRows() as $form) {
            $name = $form -> getColumn('label');
            array_push($formNames, $name);
        }
        return $formNames;
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