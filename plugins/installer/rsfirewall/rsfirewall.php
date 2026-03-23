<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class plgInstallerRsfirewall extends CMSPlugin
{
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		$uri 	= Uri::getInstance($url);
		$parts 	= explode('/', $uri->getPath());
		
		if ($uri->getHost() == 'www.rsjoomla.com' && in_array('com_rsfirewall', $parts)) {
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php')) {
				return;
			}
			
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/version.php')) {
				return;
			}
			
			// Load our config
			require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php';
			
			// Load our version
			require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/version.php';
			
			// Load language
			Factory::getLanguage()->load('plg_installer_rsfirewall');
			
			// Get the version
			$version = new RSFirewallVersion;
			
			// Get the update code
			$code = RSFirewallConfig::getInstance()->get('code');
			
			// No code added
			if (!strlen($code)) {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_INSTALLER_RSFIREWALL_MISSING_UPDATE_CODE'), 'warning');
				return;
			}
			
			// Code length is incorrect
			if (strlen($code) != 20) {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_INSTALLER_RSFIREWALL_INCORRECT_CODE'), 'warning');
				return;
			}
			
			// Compute the update hash			
			$uri->setVar('hash', md5($code.$version->key));
			$uri->setVar('domain', Uri::getInstance()->getHost());
			$uri->setVar('code', $code);
			$url = $uri->toString();
		}
	}
}
