<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package CoreAdminHome
 */
namespace Piwik\Plugins\CoreAdminHome;

use Piwik\DataAccess\ArchiveSelector;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Piwik;
use Piwik\Date;
use Piwik\ScheduledTask;
use Piwik\Db;
use Piwik\ScheduledTime\Daily;

/**
 *
 * @package CoreAdminHome
 */
class CoreAdminHome extends \Piwik\Plugin
{
    /**
     * @see Piwik_Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        return array(
            'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'AssetManager.getJsFiles'         => 'getJsFiles',
            'AdminMenu.add'                   => 'addMenu',
            'TaskScheduler.getScheduledTasks' => 'getScheduledTasks',
        );
    }

    public function getScheduledTasks(&$tasks)
    {
        // general data purge on older archive tables, executed daily
        $purgeArchiveTablesTask = new ScheduledTask ($this,
            'purgeOutdatedArchives',
            null,
            new Daily(),
            ScheduledTask::HIGH_PRIORITY);
        $tasks[] = $purgeArchiveTablesTask;

        // lowest priority since tables should be optimized after they are modified
        $optimizeArchiveTableTask = new ScheduledTask ($this,
            'optimizeArchiveTable',
            null,
            new Daily(),
            ScheduledTask::LOWEST_PRIORITY);
        $tasks[] = $optimizeArchiveTableTask;
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "libs/jquery/themes/base/jquery-ui.css";
        $stylesheets[] = "plugins/CoreAdminHome/stylesheets/menu.less";
        $stylesheets[] = "plugins/Zeitgeist/stylesheets/base.less";
        $stylesheets[] = "plugins/CoreAdminHome/stylesheets/generalSettings.less";
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "libs/jquery/jquery.js";
        $jsFiles[] = "libs/jquery/jquery-ui.js";
        $jsFiles[] = "libs/jquery/jquery.browser.js";
        $jsFiles[] = "libs/javascript/sprintf.js";
        $jsFiles[] = "plugins/Zeitgeist/javascripts/piwikHelper.js";
        $jsFiles[] = "plugins/Zeitgeist/javascripts/ajaxHelper.js";
        $jsFiles[] = "libs/jquery/jquery.history.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/broadcast.js";
        $jsFiles[] = "plugins/CoreAdminHome/javascripts/generalSettings.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/donate.js";
    }

    function addMenu()
    {
        Piwik_AddAdminSubMenu('CoreAdminHome_MenuManage', null, "", Piwik::isUserHasSomeAdminAccess(), $order = 1);
        Piwik_AddAdminSubMenu('CoreAdminHome_MenuDiagnostic', null, "", Piwik::isUserHasSomeAdminAccess(), $order = 6);
        Piwik_AddAdminSubMenu('General_Settings', null, "", Piwik::isUserHasSomeAdminAccess(), $order = 5);
        Piwik_AddAdminSubMenu('General_Settings', 'CoreAdminHome_MenuGeneralSettings',
            array('module' => 'CoreAdminHome', 'action' => 'generalSettings'),
            Piwik::isUserHasSomeAdminAccess(),
            $order = 6);
        Piwik_AddAdminSubMenu('CoreAdminHome_MenuManage', 'CoreAdminHome_TrackingCode',
            array('module' => 'CoreAdminHome', 'action' => 'trackingCodeGenerator'),
            Piwik::isUserHasSomeAdminAccess(),
            $order = 4);
    }

    function purgeOutdatedArchives()
    {
        $archiveTables = ArchiveTableCreator::getTablesArchivesInstalled();
        foreach ($archiveTables as $table) {
            $date = ArchiveTableCreator::getDateFromTableName($table);
            list($month, $year) = explode('_', $date);
            ArchiveSelector::purgeOutdatedArchives(Date::factory("$year-$month-15"));
        }
    }

    function optimizeArchiveTable()
    {
        $archiveTables = ArchiveTableCreator::getTablesArchivesInstalled();
        Db::optimizeTables($archiveTables);
    }
}
