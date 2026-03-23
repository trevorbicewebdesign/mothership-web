<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

abstract class RSFirewallToolbarHelper
{
	public static function addToolbar($view = '')
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			return;
		}

		$user = Factory::getUser();

		// Load language file (.sys because the toolbar has the same options as the components dropdown)
		Factory::getLanguage()->load('com_rsfirewall.sys', JPATH_ADMINISTRATOR);

		// Add toolbar entries
		JHtmlSidebar::addEntry(Text::_('COM_RSFIREWALL_OVERVIEW'), 'index.php?option=com_rsfirewall', $view == '' || $view == 'rsfirewall');

		if ($user->authorise('check.run', 'com_rsfirewall'))
		{
			JHtmlSidebar::addEntry(Text::_('COM_RSFIREWALL_SYSTEM_CHECK'), 'index.php?option=com_rsfirewall&view=check', $view == 'check');
		}

		if ($user->authorise('dbcheck.run', 'com_rsfirewall'))
		{
			JHtmlSidebar::addEntry(Text::_('COM_RSFIREWALL_DATABASE_CHECK'), 'index.php?option=com_rsfirewall&view=dbcheck', $view == 'dbcheck');
		}

		if ($user->authorise('logs.view', 'com_rsfirewall'))
		{
			JHtmlSidebar::addEntry(Text::_('COM_RSFIREWALL_SYSTEM_LOGS'), 'index.php?option=com_rsfirewall&view=logs', $view == 'logs');
		}

		if ($user->authorise('core.admin', 'com_rsfirewall'))
		{
			JHtmlSidebar::addEntry(Text::_('COM_RSFIREWALL_FIREWALL_CONFIGURATION'), 'index.php?option=com_rsfirewall&view=configuration', $view == 'configuration');
		}

		if ($user->authorise('lists.manage', 'com_rsfirewall'))
		{
			JHtmlSidebar::addEntry(Text::_('COM_RSFIREWALL_LISTS'), 'index.php?option=com_rsfirewall&view=lists', $view == 'lists');
		}

		if ($user->authorise('exceptions.manage', 'com_rsfirewall'))
		{
			JHtmlSidebar::addEntry(Text::_('COM_RSFIREWALL_EXCEPTIONS'), 'index.php?option=com_rsfirewall&view=exceptions', $view == 'exceptions');
		}
	}
}