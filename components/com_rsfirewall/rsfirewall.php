<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

$app = Factory::getApplication();

require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/adapter.php';
require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php';
require_once JPATH_COMPONENT.'/controller.php';
	
$controller	= BaseController::getInstance('RSFirewall');

$task = $app->input->get('task');

$controller->execute($task);
$controller->redirect();