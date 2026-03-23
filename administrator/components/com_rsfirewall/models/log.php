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

class RsfirewallModelLog extends AdminModel
{
	public function getTable($name = 'Logs', $prefix = 'RsfirewallTable', $options = array())
	{
		return Table::getInstance($name, $prefix, $options);
	}
	
	public function getForm($data = array(), $loadData = true) {
		// Get the form.
		$form = $this->loadForm('com_rsfirewall.log', 'log', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}
	
	protected function loadFormData() {
		// Check the session for previously entered form data.
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_rsfirewall.edit.log.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}
	
	public function truncate() {
		Factory::getDbo()->truncateTable('#__rsfirewall_logs');

		require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/log.php';
		RSFirewallLogger::getInstance()->add('critical', 'LOG_EMPTIED')->save();
	}
	
	public function prepareData($ids) {
		$table = $this->getTable();
		
		$data = array();
		foreach ($ids as $id) {
			if ($table->load($id)) {
				$data[] = $table->ip;
			}
		}
		
		return array_unique($data);
	}
}