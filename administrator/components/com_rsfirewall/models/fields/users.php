<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\ListField', 'JFormFieldList');
}

class JFormFieldUsers extends ListField
{
	protected $type = 'Users';
	
	protected function getOptions()
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/users.php';
		
		// Initialize variables.
		$options = array();
		
		$users = RSFirewallUsersHelper::getAdminUsers();
		
		foreach ($users as $user)
		{
			// Add the option object to the result set.
			$options[] = (object) array(
				'value' => $user->id,
				'text' => $user->username
			);
		}

		reset($options);
		
		return $options;
	}
}
