<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package Installation
 */
namespace Piwik\Plugins\Installation;

use Piwik\Piwik;
use Piwik\Common;
use Piwik\Translate;

/**
 *
 * @package Installation
 */
class Installation extends \Piwik\Plugin
{
    protected $installationControllerName = '\\Piwik\\Plugins\\Installation\\Controller';

    /**
     * @see Piwik_Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        $hooks = array(
            'FrontController.NoConfigurationFile'  => 'dispatch',
            'FrontController.badConfigurationFile' => 'dispatch',
            'AdminMenu.add'                        => 'addMenu',
            'AssetManager.getStylesheetFiles'      => 'getStylesheetFiles',
        );
        return $hooks;
    }

    public function setControllerToLoad($newControllerName)
    {
        $this->installationControllerName = $newControllerName;
    }

    protected function getInstallationController()
    {
        return new $this->installationControllerName();
    }

    /**
     * @param \Exception|null $exception
     */
    public function dispatch($exception = null)
    {
        if ($exception) {
            $message = $exception->getMessage();
        } else {
            $message = '';
        }

        Translate::getInstance()->loadCoreTranslation();

        Piwik_PostEvent('Installation.startInstallation', array($this));

        $step = Common::getRequestVar('action', 'welcome', 'string');
        $controller = $this->getInstallationController();
        $isActionWhiteListed = in_array($step, array( 'saveLanguage', 'getBaseCss'));
        if (in_array($step, array_keys($controller->getInstallationSteps()))
            || $isActionWhiteListed
        ) {
            $controller->$step($message);
        } else {
            Piwik::exitWithErrorMessage(Piwik_Translate('Installation_NoConfigFound'));
        }

        exit;
    }

    /**
     * Adds the 'System Check' admin page if the user is the super user.
     */
    public function addMenu()
    {
        Piwik_AddAdminMenu('Installation_SystemCheck',
            array('module' => 'Installation', 'action' => 'systemCheckPage'),
            $addIf = Piwik::isUserIsSuperUser(),
            $order = 15);
    }

    /**
     * Adds CSS files to list of CSS files for asset manager.
     */
    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/Installation/stylesheets/systemCheckPage.less";
    }
}
