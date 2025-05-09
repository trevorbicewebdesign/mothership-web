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

class RsfirewallModelList extends AdminModel
{
	public function getTable($name = 'Lists', $prefix = 'RsfirewallTable', $options = array())
	{
		return Table::getInstance($name, $prefix, $options);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		$app 	= Factory::getApplication();
		$input 	= $app->input;
		$type	= $input->get('layout') == 'bulk' ? 'list_bulk' : 'list';
		
		// Get the form.
		$form = $this->loadForm('com_rsfirewall.'.$type, $type, array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_rsfirewall.edit.list.data', array());

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