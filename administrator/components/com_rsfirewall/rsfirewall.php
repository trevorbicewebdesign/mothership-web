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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// App
$app = Factory::getApplication();

// ACL Check
if (!Factory::getUser()->authorise('core.manage', 'com_rsfirewall'))
{
	$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
	$app->redirect('index.php');
	return false;
}

require_once __DIR__ . '/helpers/adapter.php';
require_once __DIR__ . '/helpers/toolbar.php';
require_once __DIR__ . '/helpers/version.php';
require_once __DIR__ . '/helpers/config.php';
require_once __DIR__ . '/controller.php';

// Load stylesheet
HTMLHelper::_('stylesheet', 'com_rsfirewall/style.css', array('relative' => true, 'version' => 'auto'));

if (version_compare(JVERSION, '5.0', '>='))
{
	HTMLHelper::_('stylesheet', 'com_rsfirewall/style50.css', array('relative' => true, 'version' => 'auto'));
}
if (version_compare(JVERSION, '4.0', '>='))
{
	HTMLHelper::_('stylesheet', 'com_rsfirewall/style40.css', array('relative' => true, 'version' => 'auto'));
}
else
{
	HTMLHelper::_('stylesheet', 'com_rsfirewall/style30.css', array('relative' => true, 'version' => 'auto'));
}

// Load jQuery
HTMLHelper::_('jquery.framework');

// Load our scripts
HTMLHelper::_('script', 'com_rsfirewall/rsfirewall.js', array('relative' => true, 'version' => 'auto'));

// load language, english first
$lang = Factory::getLanguage();
$lang->load('com_rsfirewall', JPATH_ADMINISTRATOR);
// load the frontend language
// this language file contains some event log translations
// it's usually loaded by the System Plugin, but if it's disabled, we need to load it here
$lang->load('com_rsfirewall', JPATH_SITE);

$controller	= BaseController::getInstance('Rsfirewall');

$task = $app->input->get('task');

$controller->execute($task);
$controller->redirect();