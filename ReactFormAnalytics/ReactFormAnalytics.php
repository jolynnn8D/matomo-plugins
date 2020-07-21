<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ReactFormAnalytics;

class ReactFormAnalytics extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return array(
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
        );
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/ReactFormAnalytics/stylesheets/reactFormAnalytics.less";
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/ReactFormAnalytics/libs/amcharts/core.js';
        $jsFiles[] = 'plugins/ReactFormAnalytics/libs/amcharts/charts.js';
        $jsFiles[] = 'plugins/ReactFormAnalytics/libs/amcharts/animated.js';
        $jsFiles[] = 'plugins/ReactFormAnalytics/javascript/formHeatMap.js';
        $jsFiles[] = 'plugins/ReactFormAnalytics/javascript/formIndex.js';
        $jsFiles[] = 'plugins/ReactFormAnalytics/javascript/formDistribution.js';

    }
}
