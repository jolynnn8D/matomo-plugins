<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserFlow;

class UserFlow extends \Piwik\Plugin
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
        $stylesheets[] = "plugins/UserFlow/stylesheets/userFlow.less";
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/UserFlow/libs/amcharts/core.js';
        $jsFiles[] = 'plugins/UserFlow/libs/amcharts/charts.js';
        $jsFiles[] = 'plugins/UserFlow/libs/amcharts/animated.js';
    }

}
