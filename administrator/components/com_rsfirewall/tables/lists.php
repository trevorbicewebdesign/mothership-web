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
use Joomla\CMS\Language\Text;

class RsfirewallTableLists extends Table
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	public $id;
	public $ip;
	public $type;
	public $reason;
	public $date;
	public $published = 1;
		
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__rsfirewall_lists', 'id', $db);
	}
	
	public function check()
	{
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);
		
		// Remove whitespace
		$this->ip = str_replace(' ', '', trim($this->ip));
		
		// See if there's already an entry in the db with the same details.
		$query->select('type')
			  ->from($this->getTableName())
			  ->where($db->qn('ip').' = '.$db->q($this->ip));
		if ($this->id) {
			$query->where($db->qn('id').' != '.$db->q($this->id));
		}
		$db->setQuery($query);
		
		$type = $db->loadResult();
		if (!is_null($type)) {
			$this->setError(Text::sprintf('COM_RSFIREWALL_IP_ALREADY_IN_DB', $this->ip, Text::_('COM_RSFIREWALL_LIST_TYPE_'.$type)));
			return false;
		}
		
		if ($this->isRange()) {
			// Check if it's any of these ranges. These shouldn't be used as they will block everyone.
			$disallowed = array(
				'*.*.*.*',
				'0.0.0.0/0',
				'0.0.0.0/1',
				'0.0.0.0-127.255.255.255',
				'0.0.0.0-255.255.255.255'
			);
			if (in_array($this->ip, $disallowed)) {
				$this->setError(Text::_('COM_RSFIREWALL_IP_MASK_ERROR'));
				return false;
			}
		} else {			
			// Check if we're attempting to ban server's IP		
			if ($this->ip == Factory::getApplication()->input->server->get('SERVER_ADDR', '', 'string') && $this->isBlacklist()) {
				$this->setError(Text::_('COM_RSFIREWALL_IP_SERVER_ERROR'));
				return false;
			}
			
			// Make sure IP is valid
			try {
				require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/ip/ip.php';
				$class = new RSFirewallIP($this->ip);
				
				// And check if it matches any of the current entries from the db
				// Done only in the administration section to prevent flooding when autoban is enabled.
				$app = Factory::getApplication();
				if ($app->isClient('administrator') && $app->input->getCmd('option') == 'com_rsfirewall') {
					$query->clear();
					$query->select($db->qn('ip'))
						  ->from($this->getTableName())
						  ->where('('.$db->qn('ip').' LIKE '.$db->q('%*%').' OR '.$db->qn('ip').' LIKE '.$db->q('%/%').' OR '.$db->qn('ip').' LIKE '.$db->q('%-%').')')
						  ->where($db->qn('type').' = '.$db->q($this->type));
					$db->setQuery($query);
					if ($entries = $db->loadColumn()) {
						foreach ($entries as $entry) {
							try {
								if ($class->match($entry)) {
									$this->setError(Text::sprintf('COM_RSFIREWALL_IP_FOUND_IN_RANGE', $this->ip, $entry));
									return false;
								}
							} catch (Exception $e) {
								continue;
							}
						}
					}
				}
			} catch (Exception $e) {
				$this->setError($e->getMessage());
				return false;
			}
		}
		
		return true;
	}
	
	protected function isBlacklist() {
		return !$this->type;
	}
	
	protected function isWhitelist() {
		return $this->type;
	}
	
	protected function isRange() {
		$range = &$this->ip;
		return strpos($range, '*') !== false || strpos($range, '-') !== false || strpos($range, '/') !== false;
	}
	
	public function store($updateNulls = false) {
		if (!$this->id) {
			$this->date = Factory::getDate()->toSql();
		}
		
		return parent::store($updateNulls);
	}
}