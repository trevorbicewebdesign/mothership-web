<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

class RsfirewallModelLogs extends ListModel
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'logs.level', 'logs.date', 'logs.ip', 'logs.user_id', 'logs.username', 'logs.page', 'logs.referer', 'blocked_status', 'level', 'country_code'
			);
		}

		parent::__construct($config);
	}

	protected function getListQuery()
	{
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);

		// get filtering states
		$search 		= $this->getState('filter.search');
		$level 			= $this->getState('filter.level');
		$blocked_status = $this->getState('filter.blocked_status');
		$country_code   = $this->getState('filter.country_code');

		$query	->select($db->qn('logs').'.*')
				->select($db->qn('#__rsfirewall_lists').'.'.$db->qn('type'))
				->select($db->qn('#__rsfirewall_lists').'.'.$db->qn('id', 'listId'))
				->from($db->qn('#__rsfirewall_logs', 'logs'))
				->join('LEFT', $db->qn('#__rsfirewall_lists').' ON ('.$db->qn('logs').'.'.$db->qn('ip').' = '.$db->qn('#__rsfirewall_lists').'.'.$db->qn('ip').')');
		// search
		if ($search != '') {
			$search = $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);
			$like 	= array();
			$like[] = $db->qn('logs.ip').' LIKE '.$search;
			$like[] = $db->qn('logs.user_id').' LIKE '.$search;
			$like[] = $db->qn('logs.username').' LIKE '.$search;
			$like[] = $db->qn('logs.page').' LIKE '.$search;
			$like[] = $db->qn('logs.referer').' LIKE '.$search;
			$query->where('('.implode(' OR ', $like).')');
		}
		// level
		if ($level != '') {
			$query->where($db->qn('logs.level').'='.$db->q($level));
		}

		if ($blocked_status) {
			switch ($blocked_status)
			{
				// Blocked
				case 1:
					$query->where($db->qn('#__rsfirewall_lists.id').' IS NOT NULL')
						->where($db->qn('type').' = '.$db->q(0));
					break;

				// Not blocked
				case -1:
					$query->where($db->qn('#__rsfirewall_lists.id').' IS NULL');
					break;
			}
		}

		if ($country_code && ($ips = $this->getIpsByCountry($country_code)))
		{
			$query->where($db->qn('logs.ip') . ' IN (' . implode(',', $db->q($ips)) . ')');
		}

		// order by
		$query->order($db->escape($this->getState('list.ordering', 'logs.date')).' '.$db->escape($this->getState('list.direction', 'desc')));

		return $query;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.level',  $this->getUserStateFromRequest($this->context.'.filter.level',  'filter_level'));
		$this->setState('filter.blocked_status', $this->getUserStateFromRequest($this->context.'.filter.blocked_status',  'filter_blocked_status', null, 'int'));
		$this->setState('filter.country_code', $this->getUserStateFromRequest($this->context.'.filter.country_code',  'filter_country_code'));

		// List state information.
		parent::populateState('logs.date', 'desc');
	}

	public function toCSV() {
		// Get Dbo
		$db = $this->getDbo();

		// Populate state so filters and ordering is available.
		$this->populateState();

		// Get results
		$results = $db->setQuery($this->getListQuery())->loadAssocList();

		// Error on no results
		if (!$results) {
			throw new Exception(Text::_('COM_RSFIREWALL_NOT_ENOUGH_RESULTS_TO_OUTPUT'));
		}

		// Load GeoIP helper class
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/geoip/geoip.php';
		$geoip = RSFirewallGeoIP::getInstance();

		$out = @fopen('php://output', 'w');

		if (!is_resource($out)) {
			throw new Exception(Text::_('COM_RSFIREWALL_COULD_NOT_OPEN_PHP_OUTPUT'));
		}

		// Get CSV headers
		$columns = array(
			Text::_('COM_RSFIREWALL_ALERT_LEVEL'),
			Text::_('COM_RSFIREWALL_LOG_DATE_EVENT'),
			Text::_('COM_RSFIREWALL_LOG_IP_ADDRESS'),
			Text::_('COM_RSFIREWALL_LOG_USER_ID'),
			Text::_('COM_RSFIREWALL_LOG_USERNAME'),
			Text::_('COM_RSFIREWALL_LOG_PAGE'),
			Text::_('COM_RSFIREWALL_LOG_REFERER'),
			Text::_('COM_RSFIREWALL_LOG_DESCRIPTION'),
			Text::_('COM_RSFIREWALL_LOG_DEBUG_VARIABLES')
		);

		// Write CSV headers
		if (fputcsv($out, $columns, ',', '"') === false) {
			throw new Exception(Text::_('COM_RSFIREWALL_COULD_NOT_WRITE_PHP_OUTPUT'));
		}

		foreach ($results as $result) {
			// Prettify results
			$result['level'] = Text::_('COM_RSFIREWALL_LEVEL_'.$result['level']);
			$result['date']  = HTMLHelper::_('date', $result['date'], 'Y-m-d H:i:s');
			$result['code']  = Text::_('COM_RSFIREWALL_EVENT_'.$result['code']);

			// Add country code if available
			if ($country = $geoip->getCountryCode($result['ip'])) {
				$result['ip'] = sprintf('(%s) %s', $country, $result['ip']);
			}

			// Remove unneeded headers
			unset($result['type']);
			unset($result['listId']);
			unset($result['id']);

			// Write CSV row
			if (fputcsv($out, $result, ',', '"') === false) {
				throw new Exception(Text::_('COM_RSFIREWALL_COULD_NOT_WRITE_PHP_OUTPUT'));
			}
		}

		fclose($out);
	}

	public function getIpsByCountry($cc)
	{
		$prepared = array();
		$db 	  = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('ip'))
			->from($db->qn('#__rsfirewall_logs'))
			->group($db->qn('ip'));

		if ($ips = $db->setQuery($query)->loadColumn())
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/geoip/geoip.php';
			$geoip = RSFirewallGeoIP::getInstance();

			foreach ($ips as $ip)
			{
				if (strtolower($geoip->getCountryCode($ip)) === $cc)
				{
					$prepared[] = $ip;
				}
			}
		}

		return $prepared;
	}

	public function getBlockedIps()
	{
		$db 	= $this->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT('.$db->qn('ip').') AS num')
			->select($db->qn('ip'))
			->from($db->qn('#__rsfirewall_logs'))
			->group($db->qn('ip'));
		$db->setQuery($query);
		$results = $db->loadObjectList();

		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/geoip/geoip.php';
		$geoip = RSFirewallGeoIP::getInstance();

		$prepared = array();
		foreach ($results as $result)
		{
			$cc = strtolower($geoip->getCountryCode($result->ip));

			if (empty($prepared[$cc]))
			{
				$prepared[$cc] = $result->num;
			}
			else
			{
				$prepared[$cc] += $result->num;
			}
		}
		unset($results);

		return $prepared;
	}

	public function getFilterForm($data = array(), $loadData = true)
	{
		$form = parent::getFilterForm($data, $loadData);

		if ($form)
		{
			// Load model
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/models/configuration.php';
			$model = new RsfirewallModelConfiguration();

			// Get info on GeoIP
			$info = $model->getGeoIPInfo();

			// Does it work?
			if ($info->works)
			{
				// Let's populate the dropdown
				$field      = $form->getField('country_code', 'filter');
				$prepared   = array_keys($this->getBlockedIps());

				sort($prepared);

				foreach ($prepared as $country)
				{
					$field->addOption(strtoupper($country), array('value' => $country));
				}
			}
			else
			{
				$form->removeField('country_code', 'filter');
			}
		}

		return $form;
	}
}