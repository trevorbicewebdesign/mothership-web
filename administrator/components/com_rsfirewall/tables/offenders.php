<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

class RsfirewallTableOffenders extends Table
{
	/**
	 * Primary Key
	 *
	 * @public int
	 */
	public $id 	 = null;
	public $ip 	 = null;
	public $date = null;
		
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(& $db) {
		parent::__construct('#__rsfirewall_offenders', 'id', $db);
	}
	
	public function store($updateNulls = false) {
		if (!$this->id) {
			$this->date = Factory::getDate()->toSql();
		}
		
		return parent::store($updateNulls);
	}
}