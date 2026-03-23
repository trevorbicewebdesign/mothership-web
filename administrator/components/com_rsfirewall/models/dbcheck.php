<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class RsfirewallModelDbcheck extends BaseDatabaseModel
{
	public function getIsSupported()
	{
		return (strpos(Factory::getApplication()->get('dbtype'), 'mysql') !== false && $this->getTables());
	}
	
	public function getTables() {
		static $cache;
		if (is_null($cache)) {
			$db = $this->getDbo();
			$db->setQuery("SHOW TABLE STATUS");
			$tables = $db->loadObjectList();
			foreach ($tables as $i => $table)
			{
				if (!isset($table->Engine) || $table->Engine != 'MyISAM')
				{
					unset($tables[$i]);
				}
			}
			
			$cache = array_values($tables);
		}
		
		return $cache;
	}
	
	public function optimizeTables()
	{
		$app 	= Factory::getApplication();
		$db 	= $this->getDbo();
		$table 	= $app->input->get('table', '', 'raw');
		$return = array(
			'optimize' => '',
			'repair' => ''
		);
		
		try {
			// Optimize
			$db->setQuery("OPTIMIZE TABLE ".$db->qn($table));
			$result = $db->loadObject();
			$return['optimize'] = $result->Msg_text;
		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
		
		try {
			// Repair
			$db->setQuery("REPAIR TABLE ".$db->qn($table));
			$result = $db->loadObject();
			$return['repair'] = $result->Msg_text;
		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
		
		return $return;
	}
}