<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class RsfirewallTableSnapshots extends Table
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	public $id = null;
	
	public $user_id = null;
	public $snapshot = null;
	public $type = null;
		
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(& $db) {
		parent::__construct('#__rsfirewall_snapshots', 'id', $db);
	}
}