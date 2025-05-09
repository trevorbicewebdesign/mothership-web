<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class plgSystemRsfirewall extends CMSPlugin
{
	protected $config;
	protected $ip;
	protected $referer;
	protected $agent = '';
	protected $reason = '';
	protected $files;
	protected $logger;
	protected $option;

	public function __construct($subject, $config = array()) {
		parent::__construct($subject, $config);

		// try to load the configuration class
		if ($this->_autoLoadClass('RSFirewallConfig')) {
			try
			{
				$this->config = RSFirewallConfig::getInstance();
			}
			catch (Exception $e)
			{
				$this->config = false;
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				return;
			}
		}

		// load the frontend language
		Factory::getLanguage()->load('com_rsfirewall', JPATH_SITE);

		Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsfirewall/tables');

		$input = Factory::getApplication()->input;

		$this->agent   = $input->server->get('HTTP_USER_AGENT', '', 'raw'); // user agent
		$this->referer = $input->server->get('HTTP_REFERER', '', 'raw');
		$this->ip 	   = $this->getIP(); // ip address

		if ($this->_autoLoadClass('RSFirewallLogger')) {
			$this->logger = RSFirewallLogger::getInstance();
		}

	}

	protected function getOption() {
		return Factory::getApplication()->input->get('option');
	}

	protected function _autoLoadClass($class) {
		// class already has been loaded
		if (class_exists($class)) {
			return true;
		}

		$path = JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers';

		// try to load
		switch ($class) {
			case 'RSFirewallConfig':
				if (file_exists($path.'/config.php')) {
					require_once $path.'/config.php';
					return true;
				}
				return false;
				break;

			case 'RSFirewallLogger':
				if (file_exists($path.'/log.php')) {
					require_once $path.'/log.php';
					return true;
				}
				return false;
				break;

			case 'RSFirewallXSSHelper':
				if (file_exists($path.'/xss.php')) {
					require_once $path.'/xss.php';
					return true;
				}
				return false;
				break;

			case 'RSFirewallSnapshot':
				if (file_exists($path.'/snapshot.php')) {
					require_once $path.'/snapshot.php';
					return true;
				}
				return false;
				break;

			case 'RSFirewallReplacer':
				if (file_exists($path.'/replacer.php')) {
					require_once $path.'/replacer.php';
					return true;
				}
				return false;
				break;

			case 'RSFirewallCaptcha':
				if (file_exists($path.'/captcha.php')) {
					require_once $path.'/captcha.php';
					return true;
				}
				return false;
				break;
		}

		return false;
	}

	protected function getIP() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/ip/ip.php';

		return RSFirewallIP::get();
	}

	protected function isBlacklisted() {
		$result = $this->isListed(0);

		if ($result && $this->config->get('enable_extra_logging')) {
			$this->logger->add('low', 'IP_BLOCKED');
		}

		return $result;
	}

	protected function isWhitelisted() {
		return $this->isListed(1);
	}

	protected function isListed($type) {
		static $cache = array();

		if (!isset($cache[$type])) {
			$db	 	= Factory::getDbo();
			$query 	= $db->getQuery(true);

			// defaults to false
			$cache[$type] = false;

			$query->select($db->qn('ip'))
				->select($db->qn('reason'))
				->from('#__rsfirewall_lists')
				->where('('.$db->qn('ip').'='.$db->q($this->ip).' OR '.$db->qn('ip').' LIKE '.$db->q('%*%').' OR '.$db->qn('ip').' LIKE '.$db->q('%/%').' OR '.$db->qn('ip').' LIKE '.$db->q('%-%').')')
				->where($db->qn('type').'='.$db->q($type))
				->where($db->qn('published').'='.$db->q(1));
			$db->setQuery($query);

			if ($results = $db->loadObjectList()) {
				try {
					$class = new RSFirewallIP($this->ip);
					foreach ($results as $result) {
						try {
							if ($class->match($result->ip)) {
								// found a match
								$cache[$type] = true;
								// cache the reason
								$this->reason = $result->reason;
								break;
							}
						} catch (Exception $e) {
							continue;
						}
					}
				} catch (Exception $e) {}
			}
		}

		return $cache[$type];
	}

	protected function isGeoIPBlacklisted() {
		$ip   				= $this->ip;
		$blocked_countries 	= array_filter($this->config->get('blocked_countries'));
		$session 			= Factory::getSession();

		// no countries blocked, no need to continue logic
		if (empty($blocked_countries)) {
			return false;
		}

		// result already cached in the session so grab it to avoid reparsing the database
		$code = '';

		$data = $session->get('com_rsfirewall.geoip', array());
		if (!is_array($data))
		{
			$data = array();
		}
		if (!isset($data[$ip]))
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/geoip/geoip.php';

			try
			{
				$code = $data[$ip] = RSFirewallGeoIP::getInstance()->getCountryCode($ip);
				$session->set('com_rsfirewall.geoip', $data);
			} catch (Exception $e) {}
		}
		else
		{
			$code = $data[$ip];
		}

		if ($code != '' && in_array($code, $blocked_countries)) {
			if ($this->config->get('enable_extra_logging')) {
				$this->logger->add('low', 'GEOIP_BLOCKED', Text::sprintf('COM_RSFIREWALL_COUNTRY_CODE', $code));
			}

			$this->reason = Text::sprintf('COM_RSFIREWALL_YOUR_COUNTRY_HAS_BEEN_BLACKLISTED', $code);
			return true;
		} else {
			return false;
		}
	}

	protected function isUserAgentBlacklisted() {
		$agent = $this->agent;

		$verify_agents = $this->config->get('verify_agents');

		// set the reason
		$this->reason = Text::_('COM_RSFIREWALL_MALWARE_DETECTED');

		// empty user agents are not allowed!
		if (in_array('empty', $verify_agents) && (empty($agent) || $agent == '-')) {
			if ($this->config->get('enable_extra_logging')) {
				$this->logger->add('low', 'EMPTY_USER_AGENT');
			}

			return true;
		}

		// perl user agent - but allow w3c validator
		if (in_array('perl', $verify_agents) && preg_match('#libwww-perl#', $agent) && !preg_match('#^W3C-checklink#', $agent)) {
			if ($this->config->get('enable_extra_logging')) {
				$this->logger->add('low', 'PERL_USER_AGENT');
			}

			return true;
		}

		// curl user agent
		if (in_array('curl', $verify_agents) && preg_match('#curl#', $agent)) {
			if ($this->config->get('enable_extra_logging')) {
				$this->logger->add('low', 'CURL_USER_AGENT');
			}

			return true;
		}

		// Java user agent
		if (in_array('java', $verify_agents) && preg_match('#^Java#', $agent)) {
			if ($this->config->get('enable_extra_logging')) {
				$this->logger->add('low', 'JAVA_USER_AGENT');
			}

			return true;
		}

		// Mozilla impersonator
		if (in_array('mozilla', $verify_agents))
		{
			$patterns = array('#^Mozilla\/5\.0$#', '#^Mozilla$#');
			foreach ($patterns as $i => $pattern)
			{
				if (preg_match($pattern, $agent, $match))
				{
					if ($this->config->get('enable_extra_logging'))
					{
						$this->logger->add('low', 'MOZILLA_USER_AGENT');
					}

					return true;
				}
			}
		}
		
		// Really dangerous user agents here
		$patterns = array('#c0li\.m0de\.0n#', '#<\?(.*)\?>#');
		foreach ($patterns as $i => $pattern)
		{
			if (preg_match($pattern, $agent, $match))
			{
				$this->logger->add('medium', 'DANGEROUS_USER_AGENT', Text::sprintf('COM_RSFIREWALL_USER_AGENT', $agent));

				return true;
			}
		}

		// unset here
		$this->reason = '';

		return false;
	}
	
	// Joomla! < 3.6.4 user registration injection
	protected function isUserRegistrationInjection()
	{
		if ($this->option == 'com_users' && Factory::getApplication()->input->getCmd('task') == 'user.register')
		{
			// Set the reason
			$this->reason = Text::_('COM_RSFIREWALL_MALWARE_DETECTED');
			$this->logger->add('medium', 'UNAUTHORIZED_USER_REGISTRATION_ATTEMPT');
			return true;
		}
		
		return false;
	}

	protected function isSourcererInjection()
	{
		if (!empty($_GET))
		{
			$string = strtolower(urldecode(http_build_query($_GET)));
			if (strpos($string, '{source}') !== false || strpos($string, '[[?php') !== false)
			{
				// Set the reason
				$this->reason = Text::_('COM_RSFIREWALL_PHP_CODE_DETECTED');
				$this->logger->add('high', 'SOURCERER_INJECTION_ATTEMPT');
				return true;
			}
		}

		if (!empty($_POST))
		{
			$string = strtolower(urldecode(http_build_query($_POST)));
			if (($this->option === 'com_finder' || $this->option === 'com_search') && (strpos($string, '{source}') !== false || strpos($string, '[[?php') !== false))
			{
				// Set the reason
				$this->reason = Text::_('COM_RSFIREWALL_PHP_CODE_DETECTED');
				$this->logger->add('high', 'SOURCERER_INJECTION_ATTEMPT');
				return true;
			}
		}

		return false;
	}

	// Joomla! 1.5 - 3.4.5 session injection protection
	protected function isSessionInjection() {
		// Set the reason
		$this->reason = Text::_('COM_RSFIREWALL_MALWARE_DETECTED');

		$input = Factory::getApplication()->input;
		if ($value = $input->server->get('HTTP_X_FORWARDED_FOR', '', 'raw')) {
			if (filter_var($value, FILTER_VALIDATE_IP) === false && strpos($value, '{') !== false && strpos($value, '}') !== false) {
				$this->logger->add('medium', 'SESSION_INJECTION_ATTEMPT', $value);
				return true;
			}
		}

		$agent 	 = $this->agent;
		$pattern = '#JSimplepieFactory|JDatabaseDriver|disconnectHandlers|JFactory|assert|sanitize|feed\_url|preg\_|cache\_name\_function#i';
		if (preg_match($pattern, $agent, $match)) {
			$this->logger->add('medium', 'DANGEROUS_USER_AGENT', Text::sprintf('COM_RSFIREWALL_USER_AGENT', $agent));
			return true;
		}

		$this->reason = '';
		return false;
	}

	protected function isRefererBlacklisted() {
		// No point going forward if no referer is found.
		if (!strlen($this->referer)) {
			return false;
		}

		// If we haven't set any domains to be blocked, exit.
		$deny_referers = $this->config->get('deny_referer', array(), true);
		if (!$deny_referers) {
			return false;
		}

		try {
			// Try to parse the referer
			$referer = Uri::getInstance($this->referer);
			$host 	 = strtolower($referer->getHost());

			if (!strlen($host)) {
				return false;
			}

			foreach ($deny_referers as $denied_referer) {
				$denied_host = strtolower($denied_referer);

				if (!strlen($denied_host)) {
					continue;
				}

				// Check for wildcards
				if (strpos($denied_host, '*') !== false) {
					$parts = explode('*', $denied_host);
					array_walk($parts, array($this, 'pregQuoteArray'));

					$pattern = '/^'.implode('.*', $parts).'$/is';
					$deny = preg_match($pattern, $host);
				} else {
					$deny = $denied_host == $host;
				}

				if ($deny) {
					if ($this->config->get('enable_extra_logging')) {
						$this->logger->add('low', 'REFERER_BLOCKED', $host);
					}
					// set the reason
					$this->reason = Text::_('COM_RSFIREWALL_REFERER_BLOCKED');
					return true;
				}
			}
		} catch (Exception $e) {}

		return false;
	}

	protected function pregQuoteArray(&$item, $key) {
		$item = preg_quote($item, '/');
	}

	protected function createView($options=array())
	{
		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', JPATH_SITE.'/components/com_rsfirewall');
		}

		$options['base_path'] 	= JPATH_SITE.'/components/com_rsfirewall';
		$options['layout'] 		= 'default';

		$view = new HtmlView($options);

		// Allow view to be overrided by the template
		$view->addTemplatePath(JPATH_THEMES . '/' . Factory::getApplication()->getTemplate() . '/html/com_rsfirewall/' . $view->getName());

		return $view;
	}

	protected function showForbiddenMessage($count=true) {
		if ($this->isBot()) {
			return;
		}

		$this->logger->save();

		// no passing through here
		header('HTTP/1.1 403 Forbidden');

		$view = $this->createView(array(
			'name' => 'forbidden',
			'base_path' => JPATH_SITE.'/components/com_rsfirewall'
		));
		$view->reason 	= $this->reason;
		$view->display();

		if ($this->config->get('enable_autoban') && $count) {
			$this->countAutoban(Text::_('COM_RSFIREWALL_AUTOBANNED_REPEAT_OFFENDER'));
		}

		Factory::getApplication()->close();
	}

	protected function showBackendLogin() {
		if (!headers_sent())
		{
			header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
			header('Pragma: no-cache');
			header('Content-Type: text/html; charset=utf-8');
		}

		$input = Factory::getApplication()->input;
		$view  = $this->createView(array(
			'name' => 'login',
			'base_path' => JPATH_SITE.'/components/com_rsfirewall'
		));
		$view->password_sent = $input->get('rsf_backend_password', '', 'raw');
		$view->display();

		Factory::getApplication()->close();
	}

	protected function addOffender() {
		$table = Table::getInstance('Offenders', 'RsfirewallTable');
		$table->ip = $this->ip;
		$table->store();
	}

	protected function getNumOffenders() {
		$db 	= Factory::getDbo();
		$query  = $db->getQuery(true);

		$query->select($db->qn('id'))
			->from('#__rsfirewall_offenders')
			->where($db->qn('ip').'='.$db->q($this->ip));
		$db->setQuery($query);
		$db->execute();

		return $db->getNumRows();
	}

	protected function countAutoban($reason) {
		$this->addOffender();
		if ($this->getNumOffenders() >= $this->config->get('autoban_attempts')) {
			$this->clearOffenders();

			$this->ban($reason);
		}
	}

	protected function ban($reason) {
		$table = Table::getInstance('Lists', 'RsfirewallTable');
		$table->bind(array(
			'ip' 		=> $this->ip,
			'type' 		=> 0,
			'published' => 1,
			'reason' 	=> $reason
		));
		if ($table->check()) {
			$table->store();
		} else {
			Factory::getApplication()->enqueueMessage($table->getError(), 'warning');
		}
	}

	protected function clearOffenders() {
		$db 	= Factory::getDbo();
		$query  = $db->getQuery(true);

		$query->delete()
			->from('#__rsfirewall_offenders')
			->where($db->qn('ip').'='.$db->q($this->ip));
		$db->setQuery($query);
		$db->execute();
	}

	public function onUserLogin($response, $options = array()) {
		// Config has not been loaded
		if (!$this->config)
		{
			return false;
		}
		if (Factory::getApplication()->isClient('administrator') && $response['status'] == Authentication::STATUS_SUCCESS && $response['type'] == 'Joomla' && !$this->isWhitelisted()) {
			$this->clearOffenders();
		}
	}

	public function onUserLoginFailure($response)
	{
		// Config has not been loaded
		if (!$this->config)
		{
			return false;
		}
		// run only in the backend
		// & only if failed login
		// & just with the Joomla! authentication
		// & if the IP is NOT in the whitelist
		if (Factory::getApplication()->isClient('administrator') && $response['status'] !== Authentication::STATUS_SUCCESS && $response['type'] == 'Joomla' && !$this->isWhitelisted())
		{
			// run only if the config has been loaded
			// & the Active Scanner is enabled
			if ($this->config && $this->config->get('active_scanner_status'))
			{
				// count attempts for captcha
				if ($this->config->get('enable_backend_captcha'))
				{
					$session  = Factory::getSession();
					$attempts = (int) $session->get('com_rsfirewall.login_attempts', 0);
					$session->set('com_rsfirewall.login_attempts', $attempts+1);
				}

				// count for automatic banning
				if ($this->config->get('enable_autoban_login')) {
					$this->countAutoban(Text::_('COM_RSFIREWALL_AUTOBANNED_TOO_MANY_LOGIN_ATTEMPTS'));
				}

				// Capture login attempts is enabled
				if ($this->config->get('capture_backend_login')) {
					$db 	= Factory::getDbo();
					$query 	= $db->getQuery(true);

					$username = $response['username'];
					$password = $response['password'];

					// check if user exists
					$query->select($db->qn('id'))
						->from('#__users')
						->where($db->qn('username').' LIKE '.$db->q($db->escape($username, true), false));
					$db->setQuery($query);
					if ($db->loadResult()) {
						// found username
						$event = 'BACKEND_LOGIN_ATTEMPT_KNOWN';
					} else {
						// didn't find username
						$event = 'BACKEND_LOGIN_ATTEMPT_UNKNOWN';
					}

					$details = Text::sprintf('COM_RSFIREWALL_EVENT_BACKEND_LOGIN_ATTEMPT_USERNAME_DETAILS', $username);
					if ($this->config->get('capture_backend_password')) {
						$details .= "\n";
						$details .= Text::sprintf('COM_RSFIREWALL_EVENT_BACKEND_LOGIN_ATTEMPT_PASSWORD_DETAILS', $password);
					}

					$this->logger->add('medium', $event, $details)->save();
				}
			}
		}
	}

	protected function isLFI($uri) {
		if (preg_match('#\.\/#is', $uri, $match)) {
			return array(
				'match' => $match[0],
				'uri'	=> $uri
			);
		}
		return false;
	}

	protected function isRFI($uri) {
		static $exceptions;
		if (!is_array($exceptions)) {
			$exceptions = array();
			// attempt to remove instances of our website from the URL...
			$domain = Uri::getInstance()->getHost();
			$exceptions[] = 'http://'.$domain;
			$exceptions[] = 'https://'.$domain;
			if (($pos = strpos($domain, 'www.')) !== false)
			{
				$exceptions[] = 'http://'.substr_replace($domain, '', $pos, strlen('www.'));
				$exceptions[] = 'https://'.substr_replace($domain, '', $pos, strlen('www.'));
			}
			else
			{
				$exceptions[] = 'http://www.'.$domain;
				$exceptions[] = 'https://www.'.$domain;
			}
			// also remove blank entries that do not pose a threat
			$exceptions[] = 'http://&';
			$exceptions[] = 'https://&';
		}

		$uri = str_replace($exceptions, '', $uri);

		if (preg_match('#=https?:\/\/.*#is', $uri, $match)) {
			return array(
				'match' => $match[0],
				'uri'	=> $uri
			);
		}
		return false;
	}

	protected function isSQLi($uri)
	{
		try
		{
			$patterns = array(
				'#(name_const\()#is',
				'#[\d\W](union select|union join|union distinct|information_schema|name_const\()[\d\W]#is',
				'/and 1=1--/is',
			);
			foreach ($patterns as $pattern)
			{
				if (preg_match($pattern, $uri, $match))
				{
					throw new Exception($match[0]);
				}
			}

			// check for SQL operations with a table name in the URI
			// or with #__ in the URI
			if (preg_match('#[\d\W](union|union select|insert|from|where|concat|into|cast|truncate|select|delete|having)[\d\W]#is', $uri, $match) && (preg_match('/'.preg_quote(Factory::getDbo()->getPrefix()).'/', $uri, $match) || preg_match('/#__/', $uri, $match)))
			{
				throw new Exception($match[0]);
			}
		}
		catch (Exception $e)
		{
			return array(
				'match' => $e->getMessage(),
				'uri' => $uri
			);
		}

		return false;
	}

	protected function isXSS($uri) {
		if (preg_match('#<[^>]*\w*\"?[^>]*>#is', $uri, $match)) {
			return array(
				'match' => $match[0],
				'uri'	=> $uri
			);
		}

		return false;
	}

	protected function filterXSS(&$array) {
		if ($this->_autoLoadClass('RSFirewallXSSHelper')) {
			RSFirewallXSSHelper::filter($array);
		}
	}

	protected function findNullcode($uri) {
		return strpos($uri, "\0") !== false;
	}

	protected function isException() {
		// this is the default exception object
		$default_exception = (object) array(
			'php' => 1,
			'sql' => 1,
			'js' => 1,
			'uploads' => 1
		);
		// built-in exceptions
		$app 		= Factory::getApplication();
		$options 	= array('com_config', 'com_rsfirewall', 'com_rsform', 'com_rsseo', 'com_installer', 'com_plugins', 'com_templates', 'com_modules', 'com_advancedmodules', 'com_content');
		if ($app->isClient('administrator') && in_array($this->option, $options)) {
			return $default_exception;
		}
		// find the first match
		$db 	= Factory::getDbo();
		$query 	= $db->getQuery(true);

		$uri = Uri::getInstance();
		$url = $uri->toString();

		$query->select('*')
			->from('#__rsfirewall_exceptions')
			->where($db->qn('published').'='.$db->q(1));
		$db->setQuery($query);
		$results = $db->loadObjectList();
		foreach ($results as $result) {
			switch ($result->type) {
				case 'ua':
					if ($result->regex) {
						if (preg_match('/'.$result->match.'/', $this->agent)) {
							return $result;
						}
					} else {
						if ($result->match == $this->agent) {
							return $result;
						}
					}
					break;

				case 'url':
					if ($result->regex) {
						if (preg_match('/'.$result->match.'/', $url)) {
							return $result;
						}
					} else {
						if (Uri::root().$result->match == $url) {
							return $result;
						}
					}
					break;

				case 'com':
					if ($result->regex) {
						if (preg_match('/'.$result->match.'/', $this->option)) {
							return $result;
						}
					} else {
						if ($result->match == $this->option) {
							return $result;
						}
					}
					break;
			}
		}

		return false;
	}


	protected function _decodeData($data) {
		$result = array();

		if (is_array($data[0])) {
			foreach ($data[0] as $k => $v) {
				$result[$k] = $this->_decodeData(
					array(
						$data[0][$k],
						$data[1][$k],
						$data[2][$k],
						$data[3][$k],
						$data[4][$k]
					)
				);
			}
			return $result;
		}

		$this->files[] = (object) array(
			'name' => $data[0],
			'type' => $data[1],
			'tmp_name' => $data[2],
			'error' => $data[3],
			'size' => $data[4]
		);
	}

	protected function _decodeFiles() {
		$this->files = array();
		foreach ($_FILES as $k => $v) {
			$this->_decodeData(
				array(
					$v['name'],
					$v['type'],
					$v['tmp_name'],
					$v['error'],
					$v['size']
				)
			);
		}

		$results = $this->files;
		unset($this->files);

		return $results;
	}

	protected function getExtension($filename) {
		$parts 	= explode('.', $filename, 2);
		$ext	= '';
		if (count($parts) == 2) {
			$file = $parts[0];
			$ext  = $parts[1];
			// check for multiple extensions
			if (strpos($file, '.') !== false) {
				$parts = explode('.', $file);
				$last  = end($parts);
				if (strlen($last) <= 4) {
					$ext = $last.'.'.$ext;
				}
			}
		}

		return strtolower($ext);
	}

	protected function isMalwareUpload($file) {
		static $model = null;
		if (is_null($model)) {
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsfirewall/models');
            $model = BaseDatabaseModel::getInstance('Check', 'RsfirewallModel', array(
                'option' => 'com_rsfirewall',
                'table_path' => JPATH_ADMINISTRATOR.'/components/com_rsfirewall/tables'
            ));
		}

		return $model->checkSignatures($file->tmp_name, $file->name);
	}

	protected function isBot() {
		static $result;

		if (is_null($result)) {
			try {
				// Initialize caching
				$cache = Factory::getCache('plg_system_rsfirewall');
				$cache->setCaching(true);

				// Get the hostname address
				$hostname 	= $cache->get(array('plgSystemRSFirewall', 'getHostByAddr'), array($this->ip));
				$isGoogle 	= substr($hostname, -14) == '.googlebot.com';
				$isMSN 		= substr($hostname, -15) == '.search.msn.com';
				// Check if it's a search engine domain
				if ($isGoogle || $isMSN) {
					$ip 	= $cache->get(array('plgSystemRSFirewall', 'getAddrByHost'), array($hostname));
					$result = $this->ip === $ip;
				} else {
					$result = false;
				}
			} catch (Exception $e) {
				$result = false;
			}
		}

		return $result;
	}

	public static function getHostByAddr($ip) {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/Net/DNS2.php';

		$resolver = new Net_DNS2_Resolver(array(
			'nameservers' => array(
				'8.8.8.8', '8.8.4.4' // Google DNS
			),
			'timeout' => 2
		));
		$result = $resolver->query($ip, 'PTR');
		if ($result && isset($result->answer[0]->ptrdname)) {
			return $result->answer[0]->ptrdname;
		}

		return $ip;
	}

	public static function getAddrByHost($ip) {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/Net/DNS2.php';

		$resolver = new Net_DNS2_Resolver(array(
			'nameservers' => array(
				'8.8.8.8', '8.8.4.4' // Google DNS
			),
			'timeout' => 2
		));
		$result = $resolver->query($ip, 'A');
		if ($result && isset($result->answer[0]->address)) {
			return $result->answer[0]->address;
		}

		return false;
	}

	public static function isSpam($ip) {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/spamcheck.php';

		$spam = new RSFirewallSpamCheck($ip);
		return $spam->isListed();
	}

	public function clearLogHistory() {
		$days = (int) $this->config->get('log_history');
		if ($days > 0) {
			$date = Factory::getDate();
			$date->modify('-' . $days . ' days');

			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete('#__rsfirewall_logs')
				->where($db->qn('date') . ' < ' . $db->q($date->toSql()));
			$db->setQuery($query);
			$db->execute();
		}
	}
	
	public function clearOldOffenders() {
		$date = Factory::getDate();
		$date->modify('-3 days');

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->delete('#__rsfirewall_offenders')
			->where($db->qn('date') . ' < ' . $db->q($date->toSql()));
		$db->setQuery($query);
		$db->execute();
	}

	public function onAfterRoute()
	{
		$app   = Factory::getApplication();
		$user  = Factory::getUser();
		$input = $app->input;

		// the config has not been loaded...
		if (!$this->config)
		{
			return;
		}

		$this->option = $this->getOption(); // component

		// user is not logged in
		// & only running in the /administrator section
		// & we have captcha enabled
		if ($app->isClient('administrator') && $user->get('guest') && $this->config->get('enable_backend_captcha') && !$this->isWhitelisted())
		{
			// check if we're attempting to login
			if ($input->get('option') == 'com_login' && $input->get('username', '', 'string') && $input->get('passwd', '', 'string'))
			{
				$code = Factory::getSession()->get('com_rsfirewall.backend_captcha');
				$sent = $input->get('rsf_backend_captcha');

				if ($code !== $sent)
				{
					$app->enqueueMessage(Text::_('COM_RSFIREWALL_CAPTCHA_CODE_NOT_CORRECT'), 'warning');
					// clear the password
					$_POST['passwd'] = '';
				}
			}
		}

		$monitor_users = $this->config->get('monitor_users');
		if (is_array($monitor_users))
		{
			$monitor_users = array_filter($monitor_users);
		}
		else
		{
			$monitor_users = array();
		}

		$isUserManager = $app->isClient('administrator') && $input->get('option') == 'com_users' && $input->get('view') == 'user';

		if ($isUserManager && in_array($input->getInt('id'), $monitor_users))
		{
			$app->enqueueMessage(Text::_('COM_RSFIREWALL_THIS_USER_CANNOT_BE_EDITED_BECAUSE_IT_IS_PROTECTED'), 'warning');
		}

		if ($this->config->get('disable_new_admin_users'))
		{
			if ($isUserManager)
			{
				$app->enqueueMessage(Text::sprintf('COM_RSFIREWALL_DISABLE_CREATION_OF_NEW_ADMINS_WARNING', Text::_('COM_RSFIREWALL_DISABLE_CREATION_OF_NEW_ADMINS')), 'warning');
			}
			require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/users.php';

			// get the current admin users
			$users = RSFirewallUsersHelper::getAdminUsers();
			$admin_users = array();
			foreach ($users as $user)
			{
				$admin_users[] = $user->id;
			}
			unset($users);

			$lockdown_users = $this->config->get('admin_users');
			if (is_array($lockdown_users))
			{
				$lockdown_users = array_filter($lockdown_users);
			}
			else
			{
				$lockdown_users = array();
			}

			// these are the only users that should be in the database
			if ($lockdown_users)
			{
				// we must have some users or else we'll end up leaving no admins
				if ($diff_users = array_diff($admin_users, $lockdown_users))
				{
					$db 	= Factory::getDbo();
					$query 	= $db->getQuery(true);

					$query->select('username')
						->from('#__users')
						->where($db->qn('id') . ' IN (' . implode(',', $diff_users) . ')');
					$db->setQuery($query);
					$usernames = $db->loadColumn();
					$this->logger->add('critical', 'NEW_ADMINS_CREATED', implode(',', $usernames))->save();

					$query->clear();
					$query->delete('#__users')
						->where($db->qn('id').' IN ('.implode(',',$diff_users).')');
					$db->setQuery($query);
					$db->execute();

				}
			}
		}

		// let's see if lockdown options are enabled
		if ($this->config->get('disable_installer')) {
			// are we in the standard installer (and not requesting an ajax call)
			// or the akeeba one ?
			if (($this->option == 'com_installer' && $input->get('task', '', 'string') != 'update.ajax') || ($this->option == 'com_akeeba' && $input->get('view') == 'installer')) {
				$this->reason = Text::_('COM_RSFIREWALL_ACCESS_TO_INSTALLER_DISABLED');
				$this->showForbiddenMessage(false);
			}
		}

		// monitor core files
		// + monitor protected files
		// monitor users
		$jversion = new Version();
		$db 	= Factory::getDbo();
		$query 	= $db->getQuery(true);

		$query->select('*')->from('#__rsfirewall_hashes');
		if ($this->config->get('monitor_core')) {
			$query->where('('.$db->qn('type').'='.$db->q($jversion->getShortVersion()).' OR '.$db->qn('type').'='.$db->q('protect').')');
		} else {
			$query->where($db->qn('type').'='.$db->q('protect'));
		}
		$query->where('(' . $db->qn('flag') . '!=' . $db->q('C') . ' OR ' . $db->qn('flag') . ' IS NULL)');
		$db->setQuery($query);
		if ($files = $db->loadObjectList()) {
			foreach ($files as $file) {
				if ($file->type == 'protect') {
					$path = $file->file;
				} else {
					$path = JPATH_SITE.'/'.$file->file;
				}
				if (file_exists($path) && is_readable($path)) {
					if ($file->hash != md5_file($path)) {
						$table = Table::getInstance('Hashes', 'RsfirewallTable');
						$table->bind($file);
						$table->flag = 'C';
						$table->date = Factory::getDate()->toSql();
						$table->store();

						$this->logger->add('critical', $file->type == 'protect' ? 'PROTECTED_FILES_MODIFIED' : 'CORE_FILES_MODIFIED', $path)->save();
					}
				}
			}
		}

		if ($users = $this->config->get('monitor_users')) {
			if ($this->_autoLoadClass('RSFirewallSnapshot')) {
				$snapshots = RSFirewallSnapshot::get('protect');
				foreach ($users as $user_id) {
					// this user isn't selected
					if (!isset($snapshots[$user_id])) {
						continue;
					}

					// assign the snapshot
					$snapshot = $snapshots[$user_id];

					// get the user from the database - this way we avoid the JUser error showing up if the user doesn't exist
					$query 	= $db->getQuery(true);
					$query->select('*')
						->from($db->qn('#__users'))
						->where($db->qn('id').' = '.$db->q($user_id));
					$user = $db->setQuery($query)->loadObject();

					// if the user exists and the snapshot information doesn't match
					// OR if the user doesn't exist
					// replace with the snapshot information
					if (empty($user) || ($modified = RSFirewallSnapshot::modified($user, $snapshot))) {
						RSFirewallSnapshot::replace($snapshot, !empty($user));

						if (empty($user)) {
							$debug = Text::sprintf('COM_RSFIREWALL_MISSING_USER', $snapshot->username);
						} else {
							$debug = Text::sprintf('COM_RSFIREWALL_MODIFIED_DATA', $snapshot->username, $modified['key'], $modified['snapshot'], $modified['value']);
						}

						$this->logger->add('critical', 'PROTECTED_USER_CHANGE_ATTEMPT', $debug)->save();
					}
				}
			}
		}

		// only perform the checks if it's not whitelisted
		if (!$this->isWhitelisted()) {
			// check if IP is blacklisted
			// or country is blacklisted
			// or the user agent has been blacklisted
			if ($this->isBlacklisted() || $this->isGeoIPBlacklisted() || $this->isUserAgentBlacklisted() || $this->isRefererBlacklisted()) {
				// show the message
				$this->showForbiddenMessage(false);
			}
			
			if ($this->isUserRegistrationInjection())
			{
				$this->showForbiddenMessage();
			}

			if ($this->isSessionInjection())
			{
				// Stop session hijacking
				$session = Factory::getSession();

				// Just to be on the safe side in case destroying doesn't work.
				$session->set('session.client.forwarded', null);
				$session->set('session.client.browser', null);

				// Destroy it
				$session->destroy();
				$this->showForbiddenMessage();
			}

			if ($this->isSourcererInjection())
			{
				$this->showForbiddenMessage();
			}

			if ($app->isClient('administrator') && $this->config->get('backend_password_enabled') && $this->config->get('backend_password'))
			{
				$use_parameter 	= $this->config->get('backend_password_use_parameter');
				$session 		= Factory::getSession();

				if ($use_parameter)
				{
					if (!$session->get('com_rsfirewall.logged_in'))
					{
						$input 		= Factory::getApplication()->input;
						$parameter 	= $this->config->get('backend_password_parameter');
						$password   = null;

						if (strlen($parameter))
						{
							$password = $input->get->get($parameter, null, 'raw');
						}
						else
						{
							foreach ($input->get->getArray() as $var => $value)
							{
								if ($value === '')
								{
									$password = $var;

									break;
								}
							}
						}

						// if the password is not set add a permanent redirect to the site root
						if (is_null($password))
						{
							$this->logger->add('medium', 'BACKEND_LOGIN_ERROR_NOT_SET');
							$this->logger->save();

							if ($this->config->get('enable_autoban_login'))
							{
								$this->countAutoban(Text::_('COM_RSFIREWALL_AUTOBANNED_TOO_MANY_BACKEND_PASSWORD_ATTEMPTS'));
							}

							$app->redirect(Uri::root(), 301);
						}
						else // password has been sent
						{
							if (md5($password) == $this->config->get('backend_password'))
							{
								$session->set('com_rsfirewall.logged_in', 1);
							}
							else
							{
								if ($this->config->get('enable_autoban_login'))
								{
									$this->countAutoban(Text::_('COM_RSFIREWALL_AUTOBANNED_TOO_MANY_BACKEND_PASSWORD_ATTEMPTS'));
								}

								$this->logger->add('medium', 'BACKEND_LOGIN_ERROR');
							}
							$this->logger->save();
						}

						// and the user is not logged in...
						if (!$session->get('com_rsfirewall.logged_in'))
						{
							$app->redirect(Uri::root(), 301);
						}
					}
				}
				else
				{
					if (!$session->get('com_rsfirewall.logged_in'))
					{
						$input = Factory::getApplication()->input;
						// password has been sent
						if ($password = $input->get('rsf_backend_password', '', 'raw'))
						{
							if (md5($password) == $this->config->get('backend_password'))
							{
								$session->set('com_rsfirewall.logged_in', 1);
								$this->logger->add('low', 'BACKEND_LOGIN_OK');
							}
							else
							{
								if ($this->config->get('enable_autoban_login'))
								{
									$this->countAutoban(Text::_('COM_RSFIREWALL_AUTOBANNED_TOO_MANY_BACKEND_PASSWORD_ATTEMPTS'));
								}

								$this->logger->add('medium', 'BACKEND_LOGIN_ERROR');
							}
							$this->logger->save();
						}

						// and the user is not logged in...
						if (!$session->get('com_rsfirewall.logged_in'))
						{
							$this->showBackendLogin();
						}
					}
				}
			}

			// check protections, if enabled
			if ($this->config->get('active_scanner_status')) {
				// are we in the backend and is the protection on ?
				// or are we in the frontend
				if (($app->isClient('administrator') && $this->config->get('active_scanner_status_backend')) || $app->isClient('site')) {
					// get if it's an exception
					$exception = $this->isException();
					// build URI out of $_GET and $_POST to compare easier
					$uri = array(
						'get' => urldecode(http_build_query($_GET)),
						'post' => urldecode(http_build_query($_POST))
					);

					if ((int) $this->config->get('abusive_ips') && !empty($uri['post']) && $app->isClient('site')) {
						// Initialize caching
						$cache = Factory::getCache('plg_system_rsfirewall');
						$cache->setCaching(true);
						$cache->setLifeTime(1440);

						if ($result = $cache->get(array('plgSystemRSFirewall', 'isSpam'), array($this->ip))) {
							$this->logger->add('medium', 'DNSBL_LISTED', "({$result->dnsbl}) {$result->reason}");
							$this->reason = Text::_('COM_RSFIREWALL_DNSBL_LISTED');
							$this->showForbiddenMessage();
						}
					}

					$enable_php_for = $this->config->get('enable_php_for');
					$enable_sql_for = $this->config->get('enable_sql_for');
					$enable_js_for 	= $this->config->get('enable_js_for');

					// php
					if (!$exception || !$exception->php) {
						// lfi is enabled
						if ($this->config->get('lfi')) {
							// $_GET is filtered
							// or $_POST is filtered
							if ((in_array('get', $enable_php_for) && ($result = $this->isLFI($uri['get']))) || (in_array('post', $enable_php_for) && ($result = $this->isLFI($uri['post'])))) {
								$details  = Text::sprintf('COM_RSFIREWALL_URI', $result['uri'])."\n";
								$details .= Text::sprintf('COM_RSFIREWALL_MATCH', $result['match']);
								$this->logger->add('medium', 'LFI_ATTEMPTED', $details);
								$this->reason = Text::_('COM_RSFIREWALL_EVENT_LFI_ATTEMPTED');
								$this->showForbiddenMessage();
							}
						}

						// rfi is enabled
						if ($this->config->get('rfi')) {
							// $_GET is filtered
							// or $_POST is filtered
							if ((in_array('get', $enable_php_for) && ($result = $this->isRFI($uri['get']))) || (in_array('post', $enable_php_for) && ($result = $this->isRFI($uri['post'])))) {
								$details  = Text::sprintf('COM_RSFIREWALL_URI', $result['uri'])."\n";
								$details .= Text::sprintf('COM_RSFIREWALL_MATCH', $result['match']);
								$this->logger->add('medium', 'RFI_ATTEMPTED', $details);
								$this->reason = Text::_('COM_RSFIREWALL_EVENT_RFI_ATTEMPTED');
								$this->showForbiddenMessage();
							}
						}

						// nullcode should never be found...
						if ($this->findNullcode($uri['get']) || $this->findNullcode($uri['post'])) {
							$this->logger->add('low', 'NULLCODE_IN_URI');
							$this->reason = Text::_('COM_RSFIREWALL_EVENT_NULLCODE_IN_URI');
							$this->showForbiddenMessage();
						}
					}

					// sql
					if (!$exception || !$exception->sql) {
						// if $_GET is filtered or $_POST is filtered
						if ((in_array('get', $enable_sql_for) && ($result = $this->isSQLi($uri['get']))) || (in_array('post', $enable_sql_for) && ($result = $this->isSQLi($uri['post'])))) {
							$details  = Text::sprintf('COM_RSFIREWALL_URI', $result['uri'])."\n";
							$details .= Text::sprintf('COM_RSFIREWALL_MATCH', $result['match']);
							$this->logger->add('medium', 'SQLI_ATTEMPTED', $details);
							$this->reason = Text::_('COM_RSFIREWALL_EVENT_SQLI_ATTEMPTED');
							$this->showForbiddenMessage();
						}
					}

					// js
					if (!$exception || !$exception->js) {
						if ((in_array('get', $enable_js_for) && ($result = $this->isXSS($uri['get']))) || (in_array('post', $enable_js_for) && ($result = $this->isXSS($uri['post'])))) {
							// if we don't filter, just drop the connection
							if (!$this->config->get('filter_js')) {
								$details  = Text::sprintf('COM_RSFIREWALL_URI', $result['uri'])."\n";
								$details .= Text::sprintf('COM_RSFIREWALL_MATCH', $result['match']);
								$this->logger->add('medium', 'XSS_ATTEMPTED', $details);
								$this->reason = Text::_('COM_RSFIREWALL_EVENT_XSS_ATTEMPTED');
								$this->showForbiddenMessage();
							}

							// filter $_GET
							if (in_array('get', $enable_js_for)) {
								$this->filterXSS($_GET);
							}

							// filter $_POST
							if (in_array('post', $enable_js_for)) {
								$this->filterXSS($_POST);
							}
						}
					}

					// uploads
					if (!$exception || !$exception->uploads) {
						if ($_FILES) {
							$verify_upload 				  = $this->config->get('verify_upload');
							$verify_multiple_exts 		  = $this->config->get('verify_multiple_exts');
							$verify_upload_blacklist_exts = $this->config->get('verify_upload_blacklist_exts', array(), true);
							$filter_uploads				  = $this->config->get('filter_uploads');

							// compact $_FILES in a friendlier array
							$files = $this->_decodeFiles();
							foreach ($files as $file) {
								if ($file->tmp_name && file_exists($file->tmp_name)) {
									$ext = $this->getExtension($file->name);
									// has extension
									if ($ext != '') {
										if ($verify_multiple_exts && strpos($ext, '.') !== false) {

											// verify parts
											$parts = explode('.', $ext);
											$multi = true;
											foreach ($parts as $part) {
												if (is_numeric($part) || strlen($part) > 4) {
													$multi = false;
													break;
												}
											}

											if ($ext == 'tar.gz')
                                            {
                                                $multi = false;
                                            }

											if ($multi) {
												if (!$filter_uploads) {
													$this->logger->add('low', 'UPLOAD_MULTIPLE_EXTS_ERROR', $file->name);
													$this->reason = Text::_('COM_RSFIREWALL_EVENT_UPLOAD_MULTIPLE_EXTS_ERROR');
													$this->showForbiddenMessage();
												}
												@unlink($file->tmp_name);
												continue;
											}
										}
										if ($verify_upload_blacklist_exts && in_array($ext, $verify_upload_blacklist_exts)) {
											if (!$filter_uploads) {
												$this->logger->add('medium', 'UPLOAD_EXTENSION_ERROR', $file->name);
												$this->reason = Text::_('COM_RSFIREWALL_EVENT_UPLOAD_EXTENSION_ERROR');
												$this->showForbiddenMessage();
											}
											@unlink($file->tmp_name);
											continue;
										}
										if ($verify_upload && $ext == 'php') {
											if ($match = $this->isMalwareUpload($file)) {
												if (!$filter_uploads) {
													$this->logger->add('medium', 'UPLOAD_SHELL', $file->name.' / '.$match['reason']);
													$this->reason = Text::_('COM_RSFIREWALL_EVENT_UPLOAD_SHELL');
													$this->showForbiddenMessage();
												}

												@unlink($file->tmp_name);
												continue;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if ($app->isClient('administrator')) {
			$this->clearLogHistory();
			$this->clearOldOffenders();
		}
	}

	public function onAfterDispatch()
	{
		// Config has not been loaded
		if (!$this->config)
		{
			return false;
		}

		$doc = Factory::getApplication()->getDocument();
		// Remove generator
		if ($this->config->get('verify_generator'))
		{
			$doc->setGenerator('');
		}

		$app = Factory::getApplication();
		// No point in going further
		if ($app->isClient('site'))
		{
			return false;
		}

		if ($doc->getType() !== 'html')
		{
			return false;
		}

		if ($this->config->get('check_user_password') && $this->option == 'com_users' && $app->input->get('view') == 'user' && $app->input->get('layout') == 'edit')
		{
			Text::script('COM_RSFIREWALL_PASSWORD_INFO');
			Text::script('COM_RSFIREWALL_PLEASE_TYPE_PASSWORD');

			Text::script('COM_RSFIREWALL_PASSWORD_MIN_LENGTH');
			Text::script('COM_RSFIREWALL_PASSWORD_MIN_INTEGERS');
			Text::script('COM_RSFIREWALL_PASSWORD_MIN_SYMBOLS');
			Text::script('COM_RSFIREWALL_PASSWORD_MIN_UPPERCASE');
			Text::script('COM_RSFIREWALL_PASSWORD_MIN_LOWERCASE');

			HTMLHelper::_('script', 'com_rsfirewall/password.js', array('relative' => true, 'version' => 'auto'));

			// get and set the global user parameters
			$params = ComponentHelper::getParams('com_users');
			$options = array(
				'minLength'     => $params->get('minimum_length'),
				'minIntegers'   => $params->get('minimum_integers'),
				'minSymbols'    => $params->get('minimum_symbols'),
				'minUppercase'  => $params->get('minimum_uppercase'),
				'minLowercase'  => $params->get('minimum_lowercase', 0),
			);

			$doc->addScriptDeclaration('RSFirewallPassword.userOptions = '.json_encode($options));
		}

		// Captcha is enabled in the backend, not in whitelist
		if (!$this->isWhitelisted() && $this->config->get('enable_backend_captcha') && Factory::getUser()->get('guest') && $this->_autoLoadClass('RSFirewallReplacer'))
		{
			$buffer = $doc->getBuffer('component');

			if (RSFirewallReplacer::addCaptcha($buffer))
			{
				$doc->setBuffer($buffer, array('type' => 'component', 'name' => null, 'title' => null));
			}
			else
			{
				// Disable it, template not supported
				$this->config->set('enable_backend_captcha', 0);
			}
		}
	}

	public function onBeforeApiRoute()
	{
		if (!$this->config)
		{
			return;
		}

		if (!$this->isWhitelisted())
		{
			// check if IP is blacklisted
			// or country is blacklisted
			// or the user agent has been blacklisted
			if ($this->isBlacklisted() || $this->isGeoIPBlacklisted() || $this->isUserAgentBlacklisted() || $this->isRefererBlacklisted())
			{
				// show the message
				$this->showForbiddenMessage(false);
			}

			if ($this->isApiInjection())
			{
				$this->logger->add('high', 'UNAUTHORIZED_API_REQUEST');
				$this->reason = Text::_('COM_RSFIREWALL_UNAUTHORIZED_API_MESSAGE');
				$this->showForbiddenMessage();
			}
		}
	}

	protected function isApiInjection()
	{
		return isset($_REQUEST['public']);
	}
}