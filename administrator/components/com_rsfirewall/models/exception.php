<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

class RsfirewallModelException extends AdminModel
{
	public function getTable($name = 'Exceptions', $prefix = 'RsfirewallTable', $options = array())
	{
		return Table::getInstance($name, $prefix, $options);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsfirewall.exception', 'exception', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_rsfirewall.edit.exception.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
	
	public function getIP()
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/ip/ip.php';
		
		return RSFirewallIP::get();
	}
}