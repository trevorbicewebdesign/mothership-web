<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;

// logged in user
$user = Factory::getUser();

if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php'))
{
	return false;
}

require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php';

try
{
	$config = RSFirewallConfig::getInstance();
}
catch (Exception $e)
{
	Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
	return false;
}

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsfirewall/models');

if (version_compare(JVERSION, '4.0', '<'))
{
	$model = BaseDatabaseModel::getInstance('RSFirewall', 'RsfirewallModel', array(
		'option' => 'com_rsfirewall',
		'table_path' => JPATH_ADMINISTRATOR.'/components/com_rsfirewall/tables'
	));
}
else
{
	$model = Factory::getApplication()->bootComponent('com_rsfirewall')->getMVCFactory()->createModel('Rsfirewall', 'RsfirewallModel', array('ignore_request' => true));
}

if ($model && $user->authorise('core.admin', 'com_rsfirewall')) {
	// load the frontend language
	// this language file contains some event log translations
	Factory::getLanguage()->load('com_rsfirewall', JPATH_SITE);

    HTMLHelper::_('stylesheet', 'mod_rsfirewall/style.css', array('relative' => true, 'version' => 'auto'));

	// Load jQuery
    HTMLHelper::_('jquery.framework');

    HTMLHelper::_('script', 'com_rsfirewall/rsfirewall.js', array('relative' => true, 'version' => 'auto'));
    HTMLHelper::_('script', 'mod_rsfirewall/rsfirewall.js', array('relative' => true, 'version' => 'auto'));

	$logs = array();
	$showMap = false;
	if ($user->authorise('logs.view', 'com_rsfirewall'))
	{
		$logs 	= $model->getLastLogs();
		$logNum = $model->getLogOverviewNum();
		$showMap = $model->getCountryBlocking() && $model->getGeoIPStatus();
	}

	if (!$params->get('show_map', 1))
	{
		$showMap = false;
	}

	if ($showMap)
	{
		HTMLHelper::_('stylesheet', 'com_rsfirewall/jqvmap.css', array('relative' => true, 'version' => 'auto'));

		HTMLHelper::_('script', 'com_rsfirewall/jquery.vmap.min.js', array('relative' => true, 'version' => 'auto'));
		HTMLHelper::_('script', 'com_rsfirewall/jquery.vmap.world.js', array('relative' => true, 'version' => 'auto'));
		HTMLHelper::_('script', 'com_rsfirewall/vmap.js', array('relative' => true, 'version' => 'auto'));
	}

	$showGrade 		= $params->get('show_grade', 1);
	$showVersions 	= $params->get('show_version_check', 1);
	$showLogs 		= $params->get('show_logs', 1);

	$grade = $config->get('grade');
	if (!$grade) {
		$color = '#000';
	}
	elseif ($grade <= 75) {
		$color = '#ED7A53';
	} elseif ($grade <= 90) {
		$color = '#88BBC8';
	} elseif ($grade <= 100) {
		$color = '#9FC569';
	}
	
	// Load GeoIP helper class
	require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/geoip/geoip.php';
	$geoip = RSFirewallGeoIP::getInstance();
	
	require ModuleHelper::getLayoutPath('mod_rsfirewall');
}