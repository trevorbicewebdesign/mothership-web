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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;
use Joomla\CMS\Date\Date;

class RsfirewallModelCheck extends BaseDatabaseModel
{
	const HASHES_DIR = '/components/com_rsfirewall/assets/hashes/';
	const SIGS_DIR = '/components/com_rsfirewall/assets/sigs/';
	const DICTIONARY = '/components/com_rsfirewall/assets/dictionary/passwords.txt';
	const CHUNK_SIZE = 2048;

	protected $count 	= 0;
	protected $folders 	= array();
	protected $files 	= array();
	protected $limit 	= 0;

	protected $ignored = array();

	protected $log = false;

	public function __construct($config = array()) {
		parent::__construct($config);

		// Enable logging
		if ($this->getConfig()->get('log_system_check') && is_writable(Factory::getApplication()->get('log_path'))) {
			$this->log = true;
		}
	}

	protected function addLogEntry($data, $error=false) {
		if (!$this->log) {
			return false;
		}

		static $path;
		if (!$path) {
			$path = Factory::getApplication()->get('log_path').'/rsfirewall.log';
		}
		$prepend = gmdate('Y-m-d H:i:s ');
		if ($error) {
			$prepend .= '** ERROR ** ';
		}
		return file_put_contents($path, $prepend.$data."\n", FILE_APPEND);
	}

	public function getConfig() {
		return RSFirewallConfig::getInstance();
	}

	protected function connect($url, $caching = true)
	{
		$cache = Factory::getCache('com_rsfirewall');
		$cache->setCaching($caching);

		try
		{
			$response = $cache->get(array('RsfirewallModelCheck', 'connectCache'), array($url));
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}

		return $response;
	}

	public static function connectCache($url)
	{
		$response = HttpFactory::getHttp()->get($url, array(), 30);

		return (object) array(
			'code' => $response->code,
			'body' => $response->body,
			'headers' => $response->headers,
		);
	}

	public function getCurrentJoomlaVersion()
	{
		return (new Version())->getShortVersion();
	}

	protected function _loadPasswords() {
		static $passwords;
		if (is_null($passwords)) {
			$passwords = array();
			if ($contents = file_get_contents(JPATH_ADMINISTRATOR.self::DICTIONARY)) {
				$passwords = $this->explode($contents);
			}
		}

		return $passwords;
	}

	protected function explode($string) {
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return explode("\n", $string);
	}

	protected function checkWeakPassword($original) {
		$passwords = $this->_loadPasswords();
		foreach ($passwords as $password) {
			if ($original == $password)
				return $password;
		}

		return false;
	}

	protected function isWindows() {
		static $result = null;
		if (!is_bool($result)) {
			$result = substr(PHP_OS, 0, 3) == 'WIN';
		}
		return $result;
	}

	public function getIsWindows() {
		return $this->isWindows();
	}

	public function checkJoomlaVersion() {
		$this->addLogEntry('System check started.');

		$code 	 = $this->getConfig()->get('code');
		$current = $this->getCurrentJoomlaVersion();
		$url 	 = 'http://www.rsjoomla.com/index.php?option=com_rsfirewall_kb&task=version&version=joomla&current='.urlencode($current).'&code='.urlencode($code);

		// could not connect
		if (!($response = $this->connect($url))) {
			return false;
		}

		// error response code
		if ($response->code != 200) {
			if (isset($response->headers) && is_array($response->headers) && isset($response->headers['Reason'])) {
				$this->setError(strip_tags($response->headers['Reason']));
				return false;
			}
			$this->setError(Text::sprintf('COM_RSFIREWALL_HTTP_ERROR_RESPONSE_CODE', $response->code));
			return false;
		}

		$latest = $response->body;

		return array($current, $latest, version_compare($current, $latest, '>='));
	}

	public function checkRSFirewallVersion() {
		$code 	 = $this->getConfig()->get('code');
		$current = $this->getCurrentJoomlaVersion();
		$version = new RSFirewallVersion();
		$url 	 = 'http://www.rsjoomla.com/index.php?option=com_rsfirewall_kb&task=version&version=firewall&current='.urlencode($current).'&firewall='.urlencode((string) $version).'&code='.urlencode($code);

		// could not connect
		if (!($response = $this->connect($url))) {
			return false;
		}

		// error response code
		if ($response->code != 200) {
			if (isset($response->headers) && is_array($response->headers) && isset($response->headers['Reason'])) {
				$this->setError(strip_tags($response->headers['Reason']));
				return false;
			}
			$this->setError(Text::sprintf('COM_RSFIREWALL_HTTP_ERROR_RESPONSE_CODE', $response->code));
			return false;
		}

		$current = (string) $version;
		$latest  = $response->body;

		return array($current, $latest, version_compare($current, $latest, '>='));
	}

	public function checkSQLPassword()
	{
		return $this->checkWeakPassword(Factory::getApplication()->get('password'));
	}

	public function hasAdminUser() {
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);

		$query->select($db->qn('id'))
			  ->from($db->qn('#__users'))
			  ->where($db->qn('username').'='.$db->q('admin'))
			  ->where($db->qn('block').'='.$db->q('0'));

		$db->setQuery($query);
		return $db->loadResult();
	}

	public function hasFTPPassword() {
		return Factory::getApplication()->get('ftp_pass') != '';
	}

	public function isSEFEnabled() {
		return Factory::getApplication()->get('sef') > 0;
	}

	public function buildConfiguration($overwrite = array())
	{
		$oldConfig = new Registry(new JConfig());

		if ($overwrite)
		{
			foreach ($overwrite as $key => $value)
			{
				$oldConfig->set($key, $value);
			}
		}

		return $oldConfig->toString('PHP', array('class' => 'JConfig', 'closingtag' => false));
	}

	protected function getArrayString($a)
	{
		$s = 'array(';
		$i = 0;

		foreach ($a as $k => $v)
		{
			$s .= ($i) ? ', ' : '';
			$s .= '"' . $k . '" => ';

			if (is_array($v) || is_object($v))
			{
				$s .= $this->getArrayString((array) $v);
			}
			else
			{
				$s .= '"' . addslashes($v) . '"';
			}

			$i++;
		}

		$s .= ')';

		return $s;
	}
	
	public function getDisableFunctions()
	{
		return array(
			'system',
			'shell_exec',
			'passthru',
			'exec',
			'popen',
			'proc_open'
		);
	}

	public function isConfigurationModified() {
		$reflector 	= new ReflectionClass('JConfig');
		$config 	= $reflector->getFileName();

		$contents 		= file_get_contents($config);
		$configuration 	= $this->buildConfiguration();

		if ($contents != $configuration) {
			$contents = explode("\n", $contents);
			$configuration = explode("\n", $configuration);
			$diff  = array_diff($contents, $configuration);

			return $diff;
		} else {
			return false;
		}
	}

	public function getPHPini()
	{
		$contents = array(
			'expose_php=Off',
			'allow_url_include=Off',
			'disable_functions=' . implode(', ', $this->getDisableFunctions())
		);

		return implode("\r\n", $contents);
	}

	protected function getAdminUsers() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/users.php';

		return RSFirewallUsersHelper::getAdminUsers();
	}

	public function getSessionLifetime() {
		return Factory::getApplication()->get('lifetime');
	}

	public function getTemporaryFolder() {
		return Factory::getApplication()->get('tmp_path');
	}

	public function getLogFolder() {
		return Factory::getApplication()->get('log_path');
	}

	public function getServerSoftware() {
		if (preg_match('#IIS/([\d.]*)#', $_SERVER['SERVER_SOFTWARE'])) {
			return 'iis';
		}

		return 'apache';
	}

	public function getFiles($folder, $recurse=false, $sort=true, $fullpath=true, $ignore=array()) {
		if (!is_dir($folder)) {
			$this->addLogEntry("[getFiles] $folder is not a valid folder!", true);

			$this->setError("$folder is not a valid folder!");
			return false;
		}

		$arr = array();

		try {
			$handle = @opendir($folder);
			while (($file = readdir($handle)) !== false) {
				if ($file != '.' && $file != '..' && !in_array($file, $ignore)) {
					$dir = $folder . DIRECTORY_SEPARATOR . $file;
					if (is_file($dir)) {
						if ($fullpath) {
							$arr[] = $dir;
						} else {
							$arr[] = $file;
						}
					} elseif (is_dir($dir) && $recurse) {
						$arr = array_merge($arr, $this->getFiles($dir, $recurse, $sort, $fullpath, $ignore));
					}
				}
			}
			closedir($handle);
		}
		catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
		if ($sort) {
			asort($arr);
		}
		return $arr;
	}

	public function getFolders($folder, $recurse=false, $sort=true, $fullpath=true) {
		if (!is_dir($folder)) {
			$this->addLogEntry("[getFolders] $folder is not a valid folder!", true);

			$this->setError(Text::sprintf('COM_RSFIREWALL_FOLDER_IS_NOT_A_VALID_FOLDER', $folder));
			return false;
		}

		$arr = array();

		try {
			$handle = @opendir($folder);
			if ($handle) {
				while (($file = readdir($handle)) !== false) {
					if ($file != '.' && $file != '..') {
						$dir = $folder . DIRECTORY_SEPARATOR . $file;
						if (is_dir($dir)) {
							if ($fullpath) {
								$arr[] = $dir;
							} else {
								$arr[] = $file;
							}
							if ($recurse) {
								$arr = array_merge($arr, $this->getFolders($dir, $recurse, $sort, $fullpath));
							}
						}
					}
				}
				closedir($handle);
			} else {
				$this->setError(Text::sprintf('COM_RSFIREWALL_FOLDER_CANNOT_BE_OPENED', $folder));
				return false;
			}
		}
		catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}

		if ($sort) {
			asort($arr);
		}

		return $arr;
	}

	protected function getParent($path) {
		$parts   = explode(DIRECTORY_SEPARATOR, $path);
		array_pop($parts);

		return implode(DIRECTORY_SEPARATOR, $parts);
	}

	protected function getAdjacentFolder($folder) {
		// one level up
		$parent = $this->getParent($folder);
		$folders = $this->getFolders($parent, false, false, true);
		if ($this->ignored['folders']) {
			// remove ignored folders
			$folders = array_diff($folders, $this->ignored['folders']);
			// renumber indexes
			$folders = array_merge(array(), $folders);
		}
		if ($folders !== false) {
			if (($pos = array_search($folder, $folders)) !== false) {
				if (isset($folders[$pos+1])) {
					return $folders[$pos+1];
				} else {
					if ($parent == JPATH_SITE || $parent == '/') {
						// this means that there are no more folders left in the Joomla! installation
						// so we're done here
						return false;
					}

					// up again
					return $this->getAdjacentFolder($parent);
				}
			}
		} else {
			return false;
		}
	}

	protected function getAdjacentFolderFiles($folder) {
		if ($folder == JPATH_SITE) {
			return false;
		}

		// one level up
		$parent = $this->getParent($folder);
		$folders = $this->getFolders($parent, false, false, true);

		if ($this->ignored['folders']) {
			// remove ignored folders
			$folders = array_diff($folders, $this->ignored['folders']);
			// renumber indexes
			$folders = array_merge(array(), $folders);
		}
		if ($folders !== false) {
			if (($pos = array_search($folder, $folders)) !== false) {
				if (isset($folders[$pos+1])) {
					return $folders[$pos+1];
				} else {

					if (!$this->addFiles($parent, false)) {
						return false;
					}

					if ($parent == JPATH_SITE || $parent == '/') {
						// this means that there are no more folders left in the Joomla! installation
						// so we're done here
						return false;
					}

					// up again
					return $this->getAdjacentFolderFiles($parent);
				}
			}
		} else {
			return false;
		}
	}

	public function getFoldersLimit($folder) {
		if (!is_dir($folder)) {
			$this->setError(Text::sprintf('COM_RSFIREWALL_FOLDER_IS_NOT_A_VALID_FOLDER', $folder));
			return false;
		}

		try {
			$handle = @opendir($folder);
			if ($handle) {
				if (!is_link($folder)) {
					while (($file = readdir($handle)) !== false) {
						// check the limit
						if (count($this->folders) >= $this->limit) {
							$this->addLogEntry("[getFoldersLimit] Limit '{$this->limit}' reached!");

							return true;
						}
						$dir = $folder . DIRECTORY_SEPARATOR . $file;
						if ($file != '.' && $file != '..' && is_dir($dir)) {
							// is it ignored? if so, continue
							if (in_array($dir, $this->ignored['folders'])) {
								$this->addLogEntry("[getFoldersLimit] Skipping '$dir' because it's ignored.");

								continue;
							}

							$this->addLogEntry("[getFoldersLimit] Adding '$dir' to array.");

							$this->folders[] = $dir;
							$this->getFoldersLimit($dir);
							return true;
						}
					}
				}
				closedir($handle);
			} else {
				$this->addLogEntry("[getFoldersLimit] Error opening $folder!");
			}

			// try to find the next folder
			if (($dir = $this->getAdjacentFolder($folder)) !== false) {
				$this->addLogEntry("[getFoldersLimit] Adding adjacent '$dir' to array.");

				$this->folders[] = $dir;
				$this->getFoldersLimit($dir);
			}
		}
		catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
	}

	public function getFilesLimit($startfile) {
		if (is_file($startfile)) {
			$folder = dirname($startfile);
			$scan_subdirs = false;
		} else {
			$folder = $startfile;
			$scan_subdirs = true;
		}

		$this->addLogEntry("[getFilesLimit] Reading from '$startfile'");

		try {
			$handle = @opendir($folder);
			if ($handle) {
				if (!is_link($folder)) {
					if ($scan_subdirs) {
						while (($file = readdir($handle)) !== false) {
							$path = $folder . DIRECTORY_SEPARATOR . $file;
							if ($file != '.' && $file != '..' && is_dir($path)) {
								// is it ignored? if so, continue
								if (in_array($path, $this->ignored['folders'])) {
									continue;
								}

								$this->getFilesLimit($path);
								return true;
							}
						}
					}
				}
				closedir($handle);

				if (!$this->addFiles($folder, is_file($startfile) ? $startfile : false)) {
					return true;
				}
			} else {
				$this->addLogEntry("[getFilesLimit] Error opening $folder!");
			}

			// done here, try to find the next folder to parse
			if (($dir = $this->getAdjacentFolderFiles($folder)) !== false) {
				$this->getFilesLimit($dir);
			}
		}
		catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
	}

	protected function addFiles($folder, $skip_until=false) {
		$handle = @opendir($folder);
		if ($handle) {
			$passed = false;

			// no more subdirectories here, search for files
			while (($file = readdir($handle)) !== false) {
				$path = $folder . DIRECTORY_SEPARATOR . $file;
				if ($file != '.' && $file != '..' && is_file($path)) {
					// is it ignored? if so, continue
					if (in_array($path, $this->ignored['files'])) {
						$this->addLogEntry("[addFiles] Skipping '$path' because it's ignored.");

						continue;
					}

					if ($skip_until !== false) {
						if (!$passed && $path == $skip_until) {
							$passed = true;
							continue;
						}

						if (!$passed) {
							continue;
						}
					}

					if (count($this->files) >= $this->limit) {
						$this->addLogEntry("[addFiles] Limit '{$this->limit}' reached!");

						return false;
					}

					$this->addLogEntry("[addFiles] Adding '$path' to array.");

					$this->files[] = $path;
				}
			}
			closedir($handle);

			return true;
		}
	}

	public function getAccessFile() {
		static $software;
		if (!$software) {
			$software = $this->getServerSoftware();
		}

		switch ($software) {
			case 'apache':
				return '.htaccess';
			break;

			case 'iis':
				return 'web.config';
			break;
		}
	}

	public function getDefaultAccessFile() {
		static $software;
		if (!$software) {
			$software = $this->getServerSoftware();
		}

		switch ($software) {
			case 'apache':
				return 'htaccess.txt';
			break;

			case 'iis':
				return 'web.config.txt';
			break;
		}
	}

	public function hasHtaccess() {
		$file = $this->getAccessFile();
		if (file_exists(JPATH_SITE.'/'.$file)) {
			return true;
		}

		return false;
	}

	public function checkPHPVersion()
	{
		// According to https://www.php.net/supported-versions.php
		$phpSupportData = array(
			'5.4' => '2015-09-03',
			'5.5' => '2016-07-21',
			'5.6' => '2018-12-31',
			'7.0' => '2019-01-10',
			'7.1' => '2019-12-01',
			'7.2' => '2020-11-30',
			'7.3' => '2021-12-06',
			'7.4' => '2022-11-28',
			'8.0' => '2023-11-26',
			'8.1' => '2023-11-25',
			'8.2' => '2024-12-31',
			'8.3' => '2025-12-31',
			'8.4' => '2026-12-31',
		);

		$supportStatus = array('status' => true, 'message' => Text::sprintf('COM_RSFIREWALL_PHP_YOU_ARE_RUNNING', PHP_VERSION));

		// Check the PHP version's support status using the minor version
		$activePhpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

		if (isset($phpSupportData[$activePhpVersion]))
		{
			// First check if the version has reached end of support
			$today           = new Date();
			$phpEndOfSupport = new Date($phpSupportData[$activePhpVersion]);

			if ($today > $phpEndOfSupport)
			{
				$supportStatus = array('status' => false, 'message' => Text::sprintf('COM_RSFIREWALL_PHP_YOU_ARE_RUNNING_WRONG', PHP_VERSION, $phpEndOfSupport->format('Y-m-d')));
			}
		}

		return $supportStatus;
	}

	public function checkGoogleSafeBrowsing(){
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/google-safe-browsing.php';

		$check = RSFirewallGoogleSafeBrowsing::getInstance();
		return $check->check();

	}

	public function checkGoogleWebRisk(){
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/google-web-risk.php';

		$check = RSFirewallGoogleWebRisk::getInstance();
		return $check->check();

	}

	public function getINI($name) {
		return ini_get($name);
	}

	public function compareINI($name, $against='1') {
		return $this->getINI($name) == $against;
	}

	protected function getHash($version) {
		$path = JPATH_ADMINISTRATOR.self::HASHES_DIR.$version.'.csv';

		if (!file_exists($path)) {
			// Attempt to download the new hashes

			// Make sure we have a valid code before continuing
			$code = $this->getConfig()->get('code');
			if (!$code || strlen($code) != 20) {
				throw new Exception(Text::_('COM_RSFIREWALL_CODE_FOR_HASHES'));
			}

			$url = 'http://www.rsjoomla.com/index.php?'.http_build_query(array(
				'option' 	=> 'com_rsfirewall_kb',
				'task'	 	=> 'gethash',
				'site'		=> Uri::root(),
				'code'	 	=> $code,
				'version' 	=> $version
			));

			// Connect to grab hashes (no caching)
			if (!($response = $this->connect($url, false))) {
				return false;
			}

			// Error code?
			if ($response->code != 200) {
				if (isset($response->headers) && is_array($response->headers) && isset($response->headers['Reason'])) {
					$reason = is_array($response->headers['Reason']) ? implode('', $response->headers['Reason']) : $response->headers['Reason'];
					throw new Exception(strip_tags($reason));
				}
				throw new Exception(Text::sprintf('COM_RSFIREWALL_HTTP_ERROR_RESPONSE_CODE', $response->code));
			}

			if (!File::write($path, $response->body)) {
				throw new Exception(Text::sprintf('COM_RSFIREWALL_COULD_NOT_WRITE_HASH_FILE', $path));
			}

			// Let's find out if we need to add the hashes to the database
			$db 	= Factory::getDbo();
			$query 	= $db->getQuery(true);

			$query->select('*')
				  ->from($db->qn('#__rsfirewall_hashes'))
				  ->where($db->qn('file').'='.$db->q('index.php'))
				  ->where($db->qn('type').'='.$db->q($version));
			if (!$db->setQuery($query)->loadObject()) {
				$files = array(
					'plugins/user/joomla/joomla.php',
					'plugins/authentication/joomla/joomla.php',
					'index.php',
					'administrator/index.php'
				);
				$count = 0;

				if ($handle = @fopen($path, 'r')) {
					while (($data = fgetcsv($handle, self::CHUNK_SIZE, ',')) !== false && $count < 4) {
						list($file_path, $file_hash) = $data;

						if (in_array($file_path, $files)) {
							$query->clear()
								  ->insert($db->qn('#__rsfirewall_hashes'))
								  ->set($db->qn('file').'='.$db->q($file_path))
								  ->set($db->qn('hash').'='.$db->q($file_hash))
								  ->set($db->qn('type').'='.$db->q($version));

							$db->setQuery($query)->execute();
							$count++;
						}
					}
					fclose($handle);
				} else {
					throw new Exception(Text::sprintf('COM_RSFIREWALL_COULD_NOT_READ_HASH_FILE', $path));
				}
			}
		}

		return $path;
	}

	protected function getMemoryLimitInBytes() {
		$memory_limit = $this->getINI('memory_limit');
		switch (substr($memory_limit, -1)) {
			case 'K':
				$memory_limit = (int) $memory_limit * 1024;
			break;

			case 'M':
				$memory_limit = (int) $memory_limit * 1024 * 1024;
			break;

			case 'G':
				$memory_limit = (int) $memory_limit * 1024 * 1024 * 1024;
			break;
		}
		return $memory_limit;
	}

	protected function getIgnoredHashedFiles() {
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);

		$query->select($db->qn('file'))
			  ->select($db->qn('hash'))
			  ->select($db->qn('flag'))
			  ->select($db->qn('type'))
			  ->from($db->qn('#__rsfirewall_hashes'))
              ->where('('. $db->qn('type') . '=' . $db->q('ignore') . ' OR ' . $db->qn('type') . ' = ' . $db->q($this->getCurrentJoomlaVersion()) . ')');
		$db->setQuery($query);

		$results = $db->loadObjectList('file');
		
		$ignored = array(
			'administrator/language/en-GB/en-GB.com_associations.ini',
			'administrator/language/en-GB/en-GB.com_associations.sys.ini',
			'administrator/language/en-GB/en-GB.com_banners.ini',
			'administrator/language/en-GB/en-GB.com_banners.sys.ini',
			'administrator/language/en-GB/en-GB.com_contact.ini',
			'administrator/language/en-GB/en-GB.com_contact.sys.ini',
			'administrator/language/en-GB/en-GB.com_contenthistory.ini',
			'administrator/language/en-GB/en-GB.com_contenthistory.sys.ini',
			'administrator/language/en-GB/en-GB.com_fields.ini',
			'administrator/language/en-GB/en-GB.com_fields.sys.ini',
			'administrator/language/en-GB/en-GB.com_finder.ini',
			'administrator/language/en-GB/en-GB.com_finder.sys.ini',
			'administrator/language/en-GB/en-GB.com_newsfeeds.ini',
			'administrator/language/en-GB/en-GB.com_newsfeeds.sys.ini',
			'administrator/language/en-GB/en-GB.com_search.ini',
			'administrator/language/en-GB/en-GB.com_search.sys.ini',
			'administrator/language/en-GB/en-GB.mod_feed.ini',
			'administrator/language/en-GB/en-GB.mod_feed.sys.ini',
			'administrator/language/en-GB/en-GB.mod_latest.ini',
			'administrator/language/en-GB/en-GB.mod_latest.sys.ini',
			'administrator/language/en-GB/en-GB.mod_logged.ini',
			'administrator/language/en-GB/en-GB.mod_logged.sys.ini',
			'administrator/language/en-GB/en-GB.mod_multilangstatus.ini',
			'administrator/language/en-GB/en-GB.mod_multilangstatus.sys.ini',
			'administrator/language/en-GB/en-GB.mod_popular.ini',
			'administrator/language/en-GB/en-GB.mod_popular.sys.ini',
			'administrator/language/en-GB/en-GB.mod_sampledata.ini',
			'administrator/language/en-GB/en-GB.mod_sampledata.sys.ini',
			'administrator/language/en-GB/en-GB.mod_version.ini',
			'administrator/language/en-GB/en-GB.mod_version.sys.ini',
			'administrator/language/en-GB/en-GB.plg_authentication_cookie.ini',
			'administrator/language/en-GB/en-GB.plg_authentication_cookie.sys.ini',
			'administrator/language/en-GB/en-GB.plg_authentication_gmail.ini',
			'administrator/language/en-GB/en-GB.plg_authentication_gmail.sys.ini',
			'administrator/language/en-GB/en-GB.plg_authentication_ldap.ini',
			'administrator/language/en-GB/en-GB.plg_authentication_ldap.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_contact.ini',
			'administrator/language/en-GB/en-GB.plg_content_contact.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_emailcloak.ini',
			'administrator/language/en-GB/en-GB.plg_content_emailcloak.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_finder.ini',
			'administrator/language/en-GB/en-GB.plg_content_finder.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_joomla.ini',
			'administrator/language/en-GB/en-GB.plg_content_joomla.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_loadmodule.ini',
			'administrator/language/en-GB/en-GB.plg_content_loadmodule.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_pagebreak.ini',
			'administrator/language/en-GB/en-GB.plg_content_pagebreak.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_pagenavigation.ini',
			'administrator/language/en-GB/en-GB.plg_content_pagenavigation.sys.ini',
			'administrator/language/en-GB/en-GB.plg_content_vote.ini',
			'administrator/language/en-GB/en-GB.plg_content_vote.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_article.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_article.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_contact.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_contact.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_fields.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_fields.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_image.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_image.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_menu.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_menu.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_module.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_module.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_pagebreak.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_pagebreak.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_readmore.ini',
			'administrator/language/en-GB/en-GB.plg_editors-xtd_readmore.sys.ini',
			'administrator/language/en-GB/en-GB.plg_editors_tinymce.ini',
			'administrator/language/en-GB/en-GB.plg_editors_tinymce.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_calendar.ini',
			'administrator/language/en-GB/en-GB.plg_fields_calendar.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_checkboxes.ini',
			'administrator/language/en-GB/en-GB.plg_fields_checkboxes.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_color.ini',
			'administrator/language/en-GB/en-GB.plg_fields_color.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_editor.ini',
			'administrator/language/en-GB/en-GB.plg_fields_editor.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_imagelist.ini',
			'administrator/language/en-GB/en-GB.plg_fields_imagelist.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_integer.ini',
			'administrator/language/en-GB/en-GB.plg_fields_integer.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_list.ini',
			'administrator/language/en-GB/en-GB.plg_fields_list.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_media.ini',
			'administrator/language/en-GB/en-GB.plg_fields_media.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_radio.ini',
			'administrator/language/en-GB/en-GB.plg_fields_radio.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_sql.ini',
			'administrator/language/en-GB/en-GB.plg_fields_sql.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_text.ini',
			'administrator/language/en-GB/en-GB.plg_fields_text.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_textarea.ini',
			'administrator/language/en-GB/en-GB.plg_fields_textarea.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_url.ini',
			'administrator/language/en-GB/en-GB.plg_fields_url.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_user.ini',
			'administrator/language/en-GB/en-GB.plg_fields_user.sys.ini',
			'administrator/language/en-GB/en-GB.plg_fields_usergrouplist.ini',
			'administrator/language/en-GB/en-GB.plg_fields_usergrouplist.sys.ini',
			'administrator/language/en-GB/en-GB.plg_finder_categories.ini',
			'administrator/language/en-GB/en-GB.plg_finder_categories.sys.ini',
			'administrator/language/en-GB/en-GB.plg_finder_contacts.ini',
			'administrator/language/en-GB/en-GB.plg_finder_contacts.sys.ini',
			'administrator/language/en-GB/en-GB.plg_finder_content.ini',
			'administrator/language/en-GB/en-GB.plg_finder_content.sys.ini',
			'administrator/language/en-GB/en-GB.plg_finder_newsfeeds.ini',
			'administrator/language/en-GB/en-GB.plg_finder_newsfeeds.sys.ini',
			'administrator/language/en-GB/en-GB.plg_finder_tags.ini',
			'administrator/language/en-GB/en-GB.plg_finder_tags.sys.ini',
			'administrator/language/en-GB/en-GB.plg_sampledata_blog.ini',
			'administrator/language/en-GB/en-GB.plg_sampledata_blog.sys.ini',
			'administrator/language/en-GB/en-GB.plg_search_categories.ini',
			'administrator/language/en-GB/en-GB.plg_search_categories.sys.ini',
			'administrator/language/en-GB/en-GB.plg_search_contacts.ini',
			'administrator/language/en-GB/en-GB.plg_search_contacts.sys.ini',
			'administrator/language/en-GB/en-GB.plg_search_content.ini',
			'administrator/language/en-GB/en-GB.plg_search_content.sys.ini',
			'administrator/language/en-GB/en-GB.plg_search_newsfeeds.ini',
			'administrator/language/en-GB/en-GB.plg_search_newsfeeds.sys.ini',
			'administrator/language/en-GB/en-GB.plg_search_tags.ini',
			'administrator/language/en-GB/en-GB.plg_search_tags.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_debug.ini',
			'administrator/language/en-GB/en-GB.plg_system_debug.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_fields.ini',
			'administrator/language/en-GB/en-GB.plg_system_fields.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_highlight.ini',
			'administrator/language/en-GB/en-GB.plg_system_highlight.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_languagecode.ini',
			'administrator/language/en-GB/en-GB.plg_system_languagecode.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_p3p.ini',
			'administrator/language/en-GB/en-GB.plg_system_p3p.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_sef.ini',
			'administrator/language/en-GB/en-GB.plg_system_sef.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_stats.ini',
			'administrator/language/en-GB/en-GB.plg_system_stats.sys.ini',
			'administrator/language/en-GB/en-GB.plg_system_updatenotification.ini',
			'administrator/language/en-GB/en-GB.plg_system_updatenotification.sys.ini',
			'administrator/language/en-GB/en-GB.plg_twofactorauth_totp.ini',
			'administrator/language/en-GB/en-GB.plg_twofactorauth_totp.sys.ini',
			'administrator/language/en-GB/en-GB.plg_twofactorauth_yubikey.ini',
			'administrator/language/en-GB/en-GB.plg_twofactorauth_yubikey.sys.ini',
			'administrator/language/en-GB/en-GB.plg_user_contactcreator.ini',
			'administrator/language/en-GB/en-GB.plg_user_contactcreator.sys.ini',
			'administrator/language/en-GB/en-GB.plg_user_profile.ini',
			'administrator/language/en-GB/en-GB.plg_user_profile.sys.ini',
			'language/en-GB/en-GB.com_contact.ini',
			'language/en-GB/en-GB.com_finder.ini',
			'language/en-GB/en-GB.com_newsfeeds.ini',
			'language/en-GB/en-GB.com_search.ini',
			'language/en-GB/en-GB.mod_articles_archive.ini',
			'language/en-GB/en-GB.mod_articles_archive.sys.ini',
			'language/en-GB/en-GB.mod_articles_categories.ini',
			'language/en-GB/en-GB.mod_articles_categories.sys.ini',
			'language/en-GB/en-GB.mod_articles_category.ini',
			'language/en-GB/en-GB.mod_articles_category.sys.ini',
			'language/en-GB/en-GB.mod_articles_latest.ini',
			'language/en-GB/en-GB.mod_articles_latest.sys.ini',
			'language/en-GB/en-GB.mod_articles_news.ini',
			'language/en-GB/en-GB.mod_articles_news.sys.ini',
			'language/en-GB/en-GB.mod_articles_popular.ini',
			'language/en-GB/en-GB.mod_articles_popular.sys.ini',
			'language/en-GB/en-GB.mod_banners.ini',
			'language/en-GB/en-GB.mod_banners.sys.ini',
			'language/en-GB/en-GB.mod_feed.ini',
			'language/en-GB/en-GB.mod_feed.sys.ini',
			'language/en-GB/en-GB.mod_finder.ini',
			'language/en-GB/en-GB.mod_finder.sys.ini',
			'language/en-GB/en-GB.mod_footer.ini',
			'language/en-GB/en-GB.mod_footer.sys.ini',
			'language/en-GB/en-GB.mod_random_image.ini',
			'language/en-GB/en-GB.mod_random_image.sys.ini',
			'language/en-GB/en-GB.mod_related_items.ini',
			'language/en-GB/en-GB.mod_related_items.sys.ini',
			'language/en-GB/en-GB.mod_search.ini',
			'language/en-GB/en-GB.mod_search.sys.ini',
			'language/en-GB/en-GB.mod_stats.ini',
			'language/en-GB/en-GB.mod_stats.sys.ini',
			'language/en-GB/en-GB.mod_tags_popular.ini',
			'language/en-GB/en-GB.mod_tags_popular.sys.ini',
			'language/en-GB/en-GB.mod_tags_similar.ini',
			'language/en-GB/en-GB.mod_tags_similar.sys.ini',
			'language/en-GB/en-GB.mod_users_latest.ini',
			'language/en-GB/en-GB.mod_users_latest.sys.ini',
			'language/en-GB/en-GB.mod_whosonline.ini',
			'language/en-GB/en-GB.mod_whosonline.sys.ini',
			'language/en-GB/en-GB.mod_wrapper.ini',
			'language/en-GB/en-GB.mod_wrapper.sys.ini'
		);
		
		foreach ($ignored as $file)
		{
			if (!file_exists(JPATH_SITE . '/' . $file))
			{
				$results[$file] = (object) array(
					'file' => $file,
					'hash' => '',
					'flag' => 'M',
					'type' => 'ignore'
				);
			}
		}
		
		return $results;
	}

	protected function _getIgnored() {
		if (empty($this->ignored)) {
			$this->ignored	= array(
				'folders' => array(),
				'files'   => array()
			);
			$db 	= $this->getDbo();
			$query 	= $db->getQuery(true);

			$query->select('*')
				  ->from($db->qn('#__rsfirewall_ignored'))
				  ->where($db->qn('type').'='.$db->q('ignore_folder').' OR '.$db->qn('type').'='.$db->q('ignore_file'));
			$db->setQuery($query);
			$results = $db->loadObjectList();
			foreach ($results as $result) {
				$this->ignored[$result->type == 'ignore_folder' ? 'folders' : 'files'][] = $result->path;
			}
		}
	}

	protected function getOptionalFolders() {
		return $this->getConfig()->get('optional_folders');
	}

	public function isAlpha() {
		return (new Version())->isInDevelopmentState();
	}

	public function checkHashes($start=0, $limit=300) {
		// version information
		$version = $this->getCurrentJoomlaVersion();

		// Below stable?
		if ($this->isAlpha()) {
			$this->setError(Text::sprintf('COM_RSFIREWALL_NO_HASHES_FOR_ALPHA', $version));
			return false;
		}

		try {
			if ($hash_file = $this->getHash($version)) {
				if ($handle = @fopen($hash_file, 'r')) {
					// set pointer to last value
					fseek($handle, $start);

					$result				= new stdClass();
					$result->wrong 		= array(); // files with wrong checksums
					$result->missing 	= array(); // files missing
					$result->fstop		= 0; // the pointer (bytes) where the scanning stopped
					$result->size		= filesize($hash_file); // the file size so that we can compute the progress
					$result->ignored    = array();

					$ignored_files 		= $this->getIgnoredHashedFiles();
					$ignored_folders 	= $this->getOptionalFolders();

					// memory variables
					$memory_limit = $this->getMemoryLimitInBytes();
					$memory_usage = memory_get_usage();

					// read data
					while (($data = fgetcsv($handle, self::CHUNK_SIZE, ',')) !== false && $limit > 0) {
						list($file_path, $file_hash) = $data;
						$full_path = JPATH_SITE.'/'.$file_path;

						// is it an optional folder, that might have been uninstalled?
						$parts = explode('/', $file_path);
						// this removes the filename
						array_pop($parts);
						// we do this so that subfolders are ignored as well
						while ($parts) {
							$folder = implode('/', $parts);
							if (in_array($folder, $ignored_folders) && !is_dir(JPATH_SITE.'/'.$folder)) {
								continue 2;
							}
							array_pop($parts);
						}

						// get the new hash
						if (isset($ignored_files[$file_path])) {
							// if there's an M flag this means the file should be missing
							if ($ignored_files[$file_path]->flag == 'M') {
								// we check if the file is indeed missing...
								if (!is_file($full_path)) {
									// ... and skip the hash checks
									continue;
								} // ... because if it isn't, we need to check it since the administrator might have put it back after he noticed it was missing
							} else {
								// grab the hash from the file found in the database
								$file_hash = $ignored_files[$file_path]->hash;
							}
							if ($ignored_files[$file_path]->type == 'ignore') {
								$result->ignored[] = $file_path;
							}
						}

						if (file_exists($full_path)) {
							$file_size = filesize($full_path);

							// let's hope the file can be read
							if ($memory_usage + $file_size < $memory_limit) {
								// does this file have a wrong checksum ?
								if (md5_file($full_path) != $file_hash) {
									$result->wrong[] = $file_path;

									// refresh this
									$memory_usage = memory_get_usage();
								}
							}
						} else {
							$result->missing[] = $file_path;

							// refresh this
							$memory_usage = memory_get_usage();
						}

						$limit--;
					}

					// get the current pointer
					$result->fstop = ftell($handle);
					// we're done, close
					fclose($handle);

					return $result;
				} else {
					$this->setError(Text::sprintf('COM_RSFIREWALL_COULD_NOT_READ_HASH_FILE', $hash_file));
					return false;
				}
			}
		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}

		$this->setError(Text::sprintf('COM_RSFIREWALL_NO_HASHES_FOUND', $version));
		return false;
	}

	public function checkPermissions($path) {
		if (!is_readable($path)) {
			return false;
		}

		return substr(decoct(@fileperms($path)),-3);
	}

	public function setOffsetLimit($limit) {
		$this->limit = $limit;
	}

	public function getFoldersRecursive($folder) {
		// cache the ignored items
		$this->_getIgnored();

		$result = $this->getFoldersLimit($folder);
		// something has gone wrong, tell the controller to throw an error message
		if ($result === false) {
			return false;
		}

		if ($this->folders) {
			// found folders...
			return $this->folders;
		} else {
			// this most likely means we've reached the end
			return true;
		}
	}

	public function getFilesRecursive($startfile) {
		// cache the ignored items
		$this->_getIgnored();

		$this->files = array();
		$result = $this->getFilesLimit($startfile);
		// something has gone wrong, tell the controller to throw an error message
		if ($result === false) {
			return false;
		}

		$root = JPATH_SITE;
		// workaround to grab the correct root
		if ($root == '') {
			$root = '/';
		}

		// This is an exceptional case when all files are ignored from the root.
		if (!$this->files && dirname($startfile) == $root) {
			$this->files = array($this->getLastFile($root));
		}

		// found files
		return $this->files;
	}

	public function _loadSignatures()
	{
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);

		$query->select('*')
			  ->from($db->qn('#__rsfirewall_signatures'));

		$db->setQuery($query);
		$signatures = $db->loadObjectList();

		foreach ($signatures as $signature)
		{
			$signature->signature = base64_decode($signature->signature);
		}
		
		// Load MD5 signatures
		$file = JPATH_ADMINISTRATOR . self::SIGS_DIR . '/php.csv';
		
		if (file_exists($file) && is_readable($file) && $this->getConfig()->get('check_md5'))
		{
			$lines = file($file, FILE_IGNORE_NEW_LINES);
			foreach ($lines as $line)
			{
				list($hash, $desc) = explode(',', $line);
				$signatures[] = (object) array(
					'signature' => $hash,
					'type' 		=> 'md5',
					'reason' 	=> $desc
				);
			}
		}
		
		return $signatures;
	}

	protected function readableFilesize($bytes, $decimals = 2) {
		$size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}

	public function checkSignatures($file, $filename = null)
	{
		static $signatures;
		if (!is_array($signatures)) {
			$signatures = $this->_loadSignatures();
		}

		if (empty($signatures))
		{
			throw new Exception (Text::_('COM_RSFIREWALL_NO_MALWARE_SIGNATURES'));
		}

		if ($filename === null)
		{
			$filename = $this->basename($file);
		}
		
		$ext = strtolower(File::getExt($filename));

		if ($ext == 'php')
		{
			if (!is_readable($file))
			{
				$this->addLogEntry("[checkSignatures] Error reading '$file'.", true);

				$this->setError(Text::sprintf('COM_RSFIREWALL_COULD_NOT_READ_FILE', $file));
				return false;
			}

			$bytes = filesize($file);

			// More than 512 kb
			if ($bytes >= 524288) {
				$this->addLogEntry("[checkSignatures] File '$file' is {$this->readableFilesize($bytes)}.", true);

				$this->setError(Text::sprintf('COM_RSFIREWALL_BIG_FILE_PLEASE_SKIP', $file, $this->readableFilesize($bytes)));
				return false;
			}

			$this->addLogEntry("[checkSignatures] Opening '$file' ({$this->readableFilesize($bytes)}) for reading.");

			$contents = file_get_contents($file);
			$md5 = md5($contents);
		}
		
		$basename 	= $filename;
		$dirname	= dirname($file);

		foreach ($signatures as $signature)
		{
			if (strpos($signature->type, 'regex') === 0 && $ext == 'php')
			{
				$flags = str_replace('regex', '', $signature->type);
				if (preg_match('#'.$signature->signature.'#'.$flags, $contents, $match))
				{
					$this->addLogEntry("[checkSignatures] Malware found ({$signature->reason})");
					return array('match' => $match[0], 'reason' => $signature->reason);
				}
			}
			elseif ($signature->type == 'filename')
			{
				if (preg_match('#'.$signature->signature.'#i', $basename, $match))
				{
					$this->addLogEntry("[checkSignatures] Malware found ({$signature->reason})");
					return array('match' => $match[0], 'reason' => $signature->reason);
				}
			}
			elseif ($signature->type == 'md5' && $ext == 'php')
			{
				if ($signature->signature === $md5)
				{
					$this->addLogEntry("[checkSignatures] Malware found ({$signature->reason})");
					return array('match' => $signature->signature, 'reason' => $signature->reason);
				}
			}
		}

		if ($ext == 'php')
		{
			// Checking for base64 inside index.php
			if (in_array(strtolower($basename), array('index.php', 'home.php'))) {
				if (preg_match('#base64\_decode\((.*?)\)#is', $contents, $match)) {
					$this->addLogEntry("[checkSignatures] Malware found (".Text::_('COM_RSFIREWALL_BASE64_IN_FILE').")");

					return array('match' => $match[0], 'reason' => Text::_('COM_RSFIREWALL_BASE64_IN_FILE'));
				}
			}

			// Check if there are php files in root
			if ($dirname == JPATH_SITE) {
				if (!in_array($basename, array('index.php', 'configuration.php'))) {
					$this->addLogEntry("[checkSignatures] Malware found (".Text::_('COM_RSFIREWALL_SUSPICIOUS_FILE_IN_ROOT').")");

					return array('match' => $basename, 'reason' => Text::_('COM_RSFIREWALL_SUSPICIOUS_FILE_IN_ROOT'));
				}
			}

			// Check if there are php files in the /images folder
			if (strpos($dirname, JPATH_SITE.DIRECTORY_SEPARATOR.'images') === 0) {
				$this->addLogEntry("[checkSignatures] Malware found (".Text::sprintf('COM_RSFIREWALL_SUSPICIOUS_FILE_IN_FOLDER', 'images').")");

				return array('match' => $basename, 'reason' => Text::sprintf('COM_RSFIREWALL_SUSPICIOUS_FILE_IN_FOLDER', 'images'));
			}

			$folders = array(
				// site view
				'components',
				'templates',
				'plugins',
				'modules',
				'language',

				// admin view
				'administrator'.DIRECTORY_SEPARATOR.'components',
				'administrator'.DIRECTORY_SEPARATOR.'templates',
				'administrator'.DIRECTORY_SEPARATOR.'modules',
				'administrator'.DIRECTORY_SEPARATOR.'language');

				foreach ($folders as $folder) {
					if ($dirname == JPATH_SITE.DIRECTORY_SEPARATOR.$folder) {
						$this->addLogEntry("[checkSignatures] Malware found (".Text::sprintf('COM_RSFIREWALL_SUSPICIOUS_FILE_IN_FOLDER', $folder).")");

						return array('match' => $basename, 'reason' => Text::sprintf('COM_RSFIREWALL_SUSPICIOUS_FILE_IN_FOLDER', $folder));
					}
				}
		}
		else
		{
			if (preg_match('/\.php\.[a-z]{3,4}$/i', $basename))
			{
				return array('match' => $basename, 'reason' => Text::_('COM_RSFIREWALL_SUSPICIOUS_PHP_DOUBLE_EXTENSION_FILE'));
			}
			if (substr($basename, 0, 1) == ' ')
			{
				return array('match' => $basename, 'reason' => Text::_('COM_RSFIREWALL_SUSPICIOUS_SPACE_FILE'));
			}

			$ignoredDotFiles = $this->getDotFiles();
			if (substr($basename, 0, 1) == '.' && !in_array(strtolower($basename), $ignoredDotFiles) && $ext != 'yml')
			{
				return array('match' => $basename, 'reason' => Text::_('COM_RSFIREWALL_SUSPICIOUS_HIDDEN_FILE'));
			}
		}

		$this->addLogEntry("[checkSignatures] File $basename appears to be clean. Moving on to next...");

		return false;
	}

	private function getDotFiles()
	{
		$ignoredDotFiles = (array) array_filter($this->getConfig()->get('dot_files', array(), true));
		$ignoredDotFiles = array_merge($ignoredDotFiles, array('.htaccess', '.htpasswd', '.htusers', '.htgroups'));

		return $ignoredDotFiles;
	}
	
	protected function basename($filename)
	{
		$parts = explode(DIRECTORY_SEPARATOR, $filename);
		return end($parts);
	}

	public function getLastFile($root) {
		static $last_file;

		if (!$last_file) {
			// cache the ignored items
			$this->_getIgnored();

			$files = $this->getFiles($root, false, false);
			// must remove ignored files
			if ($this->ignored['files']) {
				// remove ignored files
				$files = array_diff($files, $this->ignored['files']);
				// renumber indexes
				$files = array_merge(array(), $files);
			}
			$last_file = end($files);
			// this shouldn't happen
			if (!$files) {
				$last_file = $root.DIRECTORY_SEPARATOR.'index.php';
			}
		}

		return $last_file;
	}

	public function getOffset() {
		return RSFirewallConfig::getInstance()->get('offset');
	}

	public function saveGrade() {
		$grade = Factory::getApplication()->input->get('grade', '', 'int');

		$this->getConfig()->set('grade', $grade);

		$this->getConfig()->set('system_check_last_run', Factory::getDate()->toSql(true));

		$this->addLogEntry("System check finished: $grade");
	}
}