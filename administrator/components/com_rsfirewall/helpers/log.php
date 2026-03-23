<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Mail\MailHelper;

class RSFirewallLogger {
	protected $table;
	protected $root;
	protected $config;
	protected $emails;
	protected $mailfrom;
	protected $fromname;
	protected $bound = false;
	protected $tz = true;
	
	public function __construct() {
		$config = Factory::getConfig();
		
		$this->table = Table::getInstance('Logs', 'RsfirewallTable');
		$cli = function_exists('php_sapi_name') && php_sapi_name() === 'cli';

		if ($cli)
		{
			$uri = 'cli';
			$root = 'cli';
		}
		else
		{
			try
			{
				$uri = Uri::getInstance()->toString();
				$root = Uri::root();
			}
			catch (Exception $e)
			{
				$uri = $root = '-';
			}
		}

		$this->table->bind(array(
			'date' 		=> Factory::getDate()->toSql(),
			'ip' 		=> $this->getIP(),
			'user_id' 	=> Factory::getUser()->get('id'),
			'username' 	=> Factory::getUser()->get('username'),
			'page'		=> $uri,
			'referer' 	=> isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''
		));
		
		$this->root 	= $root;
		$this->config	= RSFirewallConfig::getInstance();
		$this->emails	= $this->config->get('log_emails', array(), true);
		$this->mailfrom = $config->get('mailfrom');
		$this->fromname = $config->get('fromname');
	}
	
	public static function getInstance() {
		static $initialized = false;
		if (!$initialized) {
			// load language
			Factory::getLanguage()->load('com_rsfirewall', JPATH_ADMINISTRATOR);
			// set table path
			Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsfirewall/tables');
			// load config class if not already loaded
			if (!class_exists('RSFirewallConfig')) {
				require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php';
			}
			
			// don't call this again
			$initialized = true;
		}
		// always create a new instance to allow subsequent calls to grab the correct details
		$inst = new RSFirewallLogger();
		return $inst;
	}
	
	protected function getIP() {
		require_once __DIR__ . '/ip/ip.php';
		
		return RSFirewallIP::get();
	}
	
	public function add($level='low', $code=1, $debug_variables=null) {
		$this->table->bind(array(
			'level' => $level,
			'code' => $code,
			'debug_variables' => $debug_variables
		));
		
		$this->bound = true;
		
		return $this;
	}

	public function setTimezone($tz)
	{
		$this->tz = $tz;
	}
	
	protected function escape($string) {
		$string = (string) $string;
		return htmlentities($string, ENT_COMPAT, 'utf-8');
	}
	
	protected function showDate($date) {
		return HTMLHelper::_('date', $date, 'Y-m-d H:i:s', $this->tz);
	}
	
	public function save() {
		// save to db
		if ($this->bound) {
			$this->bound = false;
			
			$this->table->store();

			// if this level is higher or equal to the configured minimum level
			if (in_array($this->table->level, $this->config->get('log_alert_level'))) {
				// send the email alert
				$this->sendAlert();
			}
		}
	}
	
	protected function sendAlert()
	{
		$lang = Factory::getLanguage();
		// When SEF and Language Filter is enabled, the language could have untranslated strings
		if (!$lang->hasKey('COM_RSFIREWALL_WEBSITE'))
		{
			// Load the backend language
			$lang->load('com_rsfirewall', JPATH_ADMINISTRATOR);
		}

		$subject = Text::sprintf('COM_RSFIREWALL_LOG_EMAIL_SUBJECT',
			Text::_('COM_RSFIREWALL_LEVEL_'.$this->table->level),
			$this->escape($this->root),
			$this->escape($this->table->ip)
		);
		
		$body =  '<html>'."\n"
				.'<body>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_WEBSITE').':</strong> <a href="'.$this->escape($this->root).'">'.$this->escape($this->root).'</a></p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_PAGE').':</strong> '.$this->escape($this->table->page).'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_REFERER').':</strong> '.($this->table->referer ? $this->escape($this->table->referer) : '<em>'.Text::_('COM_RSFIREWALL_NO_REFERER').'</em>').'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_DESCRIPTION').':</strong> '.Text::_('COM_RSFIREWALL_EVENT_'.$this->table->code).'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_DEBUG_VARIABLES').':</strong> '.nl2br($this->escape($this->table->debug_variables)).'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_ALERT_LEVEL').':</strong> '.Text::_('COM_RSFIREWALL_LEVEL_'.$this->table->level).'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_DATE_EVENT').':</strong> '.$this->showDate($this->table->date).'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_IP_ADDRESS').':</strong> '.$this->escape($this->table->ip).'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_USER_ID').':</strong> '.$this->escape($this->table->user_id).'</p>'."\n"
				.'<p><strong>'.Text::_('COM_RSFIREWALL_LOG_USERNAME').':</strong> '.$this->escape($this->table->username).'</p>'."\n"
				.'<small>'.Text::_('COM_RSFIREWALL_EMAIL_NOTICE').'</small>'."\n"
				.'</body>'."\n"
				.'</html>';
				
		// sent so far
		$sent = (int) $this->config->get('log_emails_count');
		// limit per hour
		$limit = $this->config->get('log_hour_limit');
		// after the hour we're allowed to send
		$after = $this->config->get('log_emails_send_after');
		// now
		$dateNow = Factory::getDate();
		$now = $dateNow->toUnix();
		
		// are we allowed to send?
		if ($now > $after)
		{
			// do we have emails set?
			if ($this->emails)
			{
				// loop through emails and attempt sending
				foreach ($this->emails as $email)
				{
					$email = trim($email);
					if (MailHelper::isEmailAddress($email) && $sent < $limit)
					{
						try
						{
							Factory::getMailer()->sendMail($this->mailfrom, $this->fromname, $email, $subject, $body, true);

							// increment number of sent emails
							$sent++;
						}
						catch (Exception $e)
						{
							Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
						}
					}
				}
				
				// reached the limit?
				if ($sent >= $limit)
				{
					// allow to send in the next hour
					$nextDate = Factory::getDate()->setTime((int) $dateNow->hour + 1, 0);
					$next_after = $nextDate->toUnix();
					$this->config->set('log_emails_send_after', $next_after);
					$this->config->set('log_emails_count', 0);
				}
				else
				{
					$this->config->set('log_emails_count', $sent);
				}
			}
		}
	}
}