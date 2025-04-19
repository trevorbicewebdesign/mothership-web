<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

class com_rsfirewallInstallerScript
{
	public function install($parent)
    {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php';
		
		$user 	= \Joomla\CMS\Factory::getUser();
		$config = RSFirewallConfig::getInstance();
		
		// this is the first time we've installed RSFirewall! so we need to setup an email here
		$config->set('log_emails', $user->get('email'));
	}
	
	public function uninstall($parent)
    {
		// get a new installer
		$plg_installer = new \Joomla\CMS\Installer\Installer();

		// get the database object
		$db 	= \Joomla\CMS\Factory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select($db->qn('extension_id'))
			  ->from($db->qn('#__extensions'))
			  ->where($db->qn('element').'='.$db->q('rsfirewall'))
			  ->where($db->qn('folder').'='.$db->q('system'))
			  ->where($db->qn('type').'='.$db->q('plugin'));
		$db->setQuery($query);
		if ($extension_id = $db->loadResult()) {
			$plg_installer->uninstall('plugin', $extension_id);
		}
		
		$query->clear();
		$query->select($db->qn('extension_id'))
			  ->from($db->qn('#__extensions'))
			  ->where($db->qn('element').'='.$db->q('rsfirewall'))
			  ->where($db->qn('folder').'='.$db->q('installer'))
			  ->where($db->qn('type').'='.$db->q('plugin'));
		$db->setQuery($query);
		if ($extension_id = $db->loadResult()) {
			$plg_installer->uninstall('plugin', $extension_id);
		}

		$query->clear();
		$query->select($db->qn('extension_id'))
			  ->from($db->qn('#__extensions'))
			  ->where($db->qn('element').'='.$db->q('mod_rsfirewall'))
			  ->where($db->qn('client_id').'='.$db->q('1'))
			  ->where($db->qn('type').'='.$db->q('module'));
		$db->setQuery($query);
		if ($extension_id = $db->loadResult()) {
			$plg_installer->uninstall('module', $extension_id);
		}

		$query->clear();
		$query->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('element').'='.$db->q('rsfirewallconsole'))
			->where($db->qn('folder').'='.$db->q('system'))
			->where($db->qn('type').'='.$db->q('plugin'));
		$db->setQuery($query);
		if ($extension_id = $db->loadResult()) {
			$plg_installer->uninstall('plugin', $extension_id);
		}
	}
	
	public function preflight($type, $parent)
	{
		try
		{
			$minJoomla = '3.9.0';
			$minPHP = '5.4.0';

			if (version_compare(PHP_VERSION, $minPHP, '<'))
			{
				throw new Exception(sprintf('You have a very old PHP version and RSFirewall! requires at least %s; please ask your hosting provider to upgrade to a newer version of PHP.', $minPHP));
			}

			if (!class_exists('\\Joomla\\CMS\\Version'))
			{
				throw new Exception(sprintf('Please upgrade to at least Joomla! %s before continuing!', $minJoomla));
			}

			$jversion = new \Joomla\CMS\Version;
			if (!$jversion->isCompatible($minJoomla))
			{
				throw new Exception(sprintf('Please upgrade to at least Joomla! %s before continuing!', $minJoomla));
			}
		}
		catch (Exception $e)
		{
			if (class_exists('\Joomla\CMS\Factory'))
			{
				$app = \Joomla\CMS\Factory::getApplication();
			}
            elseif (class_exists('JFactory'))
			{
				$app = JFactory::getApplication();
			}

			if (!empty($app))
			{
				$app->enqueueMessage($e->getMessage(), 'error');
			}
			return false;
		}
		
		return true;
	}
	
	public function postflight($type, $parent)
    {
		if ($type == 'uninstall')
        {
			return true;
		}
		
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/config.php';
		
		$source = $parent->getParent()->getPath('source');
		
		// Get a new installer
		$installer = new \Joomla\CMS\Installer\Installer();
		
		$messages = array(
			'plg_rsfirewall' => false,
			'plg_installer'  => false,
			'mod_rsfirewall' => false
		);
		
		$db = \Joomla\CMS\Factory::getDbo();

		if ($installer->install($source.'/other/plg_rsfirewall')) {
			$query = $db->getQuery(true);
			$query->update('#__extensions')
				  ->set($db->qn('enabled').'='.$db->q(1))
				  ->set($db->qn('ordering').'='.$db->q('-999'))
				  ->where($db->qn('element').'='.$db->q('rsfirewall'))
				  ->where($db->qn('type').'='.$db->q('plugin'))
				  ->where($db->qn('folder').'='.$db->q('system'));
			$db->setQuery($query);
			$db->execute();
			
			$messages['plg_rsfirewall'] = true;
		}
		
		// Get a new installer
		$installer = new \Joomla\CMS\Installer\Installer();
		
		if ($installer->install($source.'/other/plg_installer')) {
			$query = $db->getQuery(true);
			$query->update('#__extensions')
				  ->set($db->qn('enabled').'='.$db->q(1))
				  ->where($db->qn('element').'='.$db->q('rsfirewall'))
				  ->where($db->qn('type').'='.$db->q('plugin'))
				  ->where($db->qn('folder').'='.$db->q('installer'));
			$db->setQuery($query);
			$db->execute();
			
			$messages['plg_installer'] = true;
		}
		
		if (version_compare(JVERSION, '4.0', '>=')) {
			if ($plg_id = $installer->install($source.'/other/plg_console'))
			{
				$query = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where($db->qn('element') . ' = ' . $db->q('rsfirewallconsole'))
					->where($db->qn('type') . ' = ' . $db->q('plugin'))
					->where($db->qn('folder') . ' = ' . $db->q('system'));
				$messages['plg_console'] = $db->setQuery($query)->loadResult();
            }
		}

		if ($installer->install($source.'/other/mod_rsfirewall')) {
            if ($type === 'install')
            {
	            $query = $db->getQuery(true);
	            $query->select('id')
		            ->from('#__modules')
		            ->where($db->qn('module').'='.$db->q('mod_rsfirewall'))
		            ->where($db->qn('client_id').'='.$db->q(1))
		            ->where($db->qn('position').'='.$db->q(''));
	            $db->setQuery($query);
	            if ($moduleid = $db->loadResult()) {
		            $query->clear();
		            $query->update('#__modules')
			            ->set($db->qn('published').'='.$db->q(1))
			            ->set($db->qn('position').'='.$db->q('cpanel'))
			            ->set($db->qn('ordering').'='.$db->q(1))
			            ->where($db->qn('id').'='.$db->q($moduleid));
		            $db->setQuery($query);
		            $db->execute();

		            $query->clear();
                    $query->delete('#__modules_menu')
                        ->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
                    $db->setQuery($query)->execute();

		            $query->clear();
		            $query->insert('#__modules_menu')
			            ->columns(array('moduleid', 'menuid'))
			            ->values("$moduleid, 0");
		            $db->setQuery($query);
		            $db->execute();
	            }
            }
			
			$messages['mod_rsfirewall'] = true;
		}
		
		// show message
		$this->showInstallMessage($messages, $type);
		
		if ($type != 'update') {
			$this->removeSignatures();
			return true;
		}

		// Let's run this, something went wrong with the Joomla! install
		$tables = $db->getTableList();
		$mandatoryTables = array('configuration', 'exceptions', 'hashes', 'ignored', 'lists', 'logs', 'offenders', 'signatures', 'snapshots');
		foreach ($mandatoryTables as $mandatoryTable)
		if (!in_array($db->getPrefix() . 'rsfirewall_' . $mandatoryTable, $tables))
		{
			$this->runSQL($source, $mandatoryTable . '.sql');
		}
		
		// change date
		$columns = $db->getTableColumns('#__rsfirewall_logs');
		if ($columns['date'] == 'int') {
			$db->setQuery("ALTER TABLE ".$db->qn('#__rsfirewall_logs')." CHANGE ".$db->qn('date')." ".$db->qn('date')." VARCHAR(255) NOT NULL");
			$db->execute();
			
			// convert the date
			$query = $db->getQuery(true);
			$query->update('#__rsfirewall_logs')
				  ->set($db->qn('date')."=FROM_UNIXTIME(".$db->qn('date').")");
			$db->setQuery($query);
			$db->execute();
			
			// change the column type
			$db->setQuery("ALTER TABLE ".$db->qn('#__rsfirewall_logs')." CHANGE ".$db->qn('date')." ".$db->qn('date')." DATETIME NOT NULL");
			$db->execute();
		}
		
		// userid changed to user_id
		if (isset($columns['userid'])) {
			$db->setQuery("ALTER TABLE ".$db->qn('#__rsfirewall_logs')." CHANGE ".$db->qn('userid')." ".$db->qn('user_id')." INT(11) NOT NULL");
			$db->execute();
		}
		// add referer column
		if (!isset($columns['referer'])) {
			$db->setQuery("ALTER TABLE ".$db->qn('#__rsfirewall_logs')." ADD ".$db->qn('referer')." TEXT NOT NULL AFTER ".$db->qn('page'));
			$db->execute();
		}
		
		// change type column
		$columns = $db->getTableColumns('#__rsfirewall_snapshots');
		if (strpos($columns['type'], 'enum') !== false) {
			$db->setQuery("ALTER TABLE ".$db->qn('#__rsfirewall_snapshots')." CHANGE ".$db->qn('type')." ".$db->qn('type')." VARCHAR(16) NOT NULL");
			$db->execute();
		}
		
		// change date
		$columns = $db->getTableColumns('#__rsfirewall_hashes');
		if ($columns['date'] == 'int') {
			$db->setQuery("ALTER TABLE ".$db->qn('#__rsfirewall_hashes')." CHANGE ".$db->qn('date')." ".$db->qn('date')." VARCHAR(255) NOT NULL");
			$db->execute();
			
			// convert the date
			$query = $db->getQuery(true);
			$query->update('#__rsfirewall_hashes')
				  ->set($db->qn('date')."=FROM_UNIXTIME(".$db->qn('date').")");
			$db->setQuery($query);
			$db->execute();
			
			// change the column type
			$db->setQuery("ALTER TABLE ".$db->qn('#__rsfirewall_hashes')." CHANGE ".$db->qn('date')." ".$db->qn('date')." DATETIME NOT NULL");
			$db->execute();
		}
		
		// add the missing config data
		$this->runSQL($source, 'configuration.data.sql');

		// Some dot files need to be hardcoded
		$config = RSFirewallConfig::getInstance();
		$dot_files = $config->get('dot_files', array(), true);
		$dot_files = array_filter($dot_files);
		$save = false;
		foreach (array('.htaccess', '.htpasswd', '.htusers', '.htgroups') as $file)
		{
			$pos = array_search($file, $dot_files);
			if ($pos !== false)
			{
				unset($dot_files[$pos]);

				$save = true;
			}
		}
		if ($save)
		{
			$config->set('dot_files', $dot_files);
		}
		
		// ignore files and folders
		$query = $db->getQuery(true);
		$query->select('*')
			  ->from('#__rsfirewall_ignored')
			  ->where($db->qn('type').'='.$db->q('ignore_files_folders'));
		$db->setQuery($query);
		if ($results = $db->loadObjectList()) {
			$query->clear();
			foreach ($results as $result) {
				if (is_file($result->path)) {
					$result->type = 'ignore_file';
				} elseif (is_dir($result->path)) {
					$result->type = 'ignore_folder';
				}
				
				$query->update('#__rsfirewall_ignored')
					  ->set($db->qn('type').'='.$db->q($result->type))
					  ->where($db->qn('path').'='.$db->q($result->path));
				$db->setQuery($query)->execute();
				$query->clear();
			}
		}
		
		// admin_users should not be empty...
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/users.php';
		// get the current admin users
		$users = RSFirewallUsersHelper::getAdminUsers();
		$admin_users = array();
		foreach ($users as $user) {
			$admin_users[] = $user->id;
		}
		
		$config = RSFirewallConfig::getInstance();
		$config->set('admin_users', $admin_users);
		
		// 2.7.0 update
		
		// lists
		$columns = $db->getTableColumns('#__rsfirewall_lists', false);
		if ($columns['ip']->Key == 'UNI') {
			$db->setQuery('ALTER TABLE #__rsfirewall_lists DROP INDEX ip');
			$db->execute();
		}
		
		if ($columns['ip']->Type != 'varchar(255)') {
			$db->setQuery('ALTER TABLE #__rsfirewall_lists CHANGE `ip` `ip` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ');
			$db->execute();
		}

		if ($columns['reason']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_lists` CHANGE `reason` `reason` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}
		
		// logs
		$columns = $db->getTableColumns('#__rsfirewall_logs', false);
		if ($columns['ip']->Type != 'varchar(255)') {
			$db->setQuery('ALTER TABLE #__rsfirewall_logs CHANGE `ip` `ip` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL');
			$db->execute();
		}
		
		if (!$columns['ip']->Key) {
			$db->setQuery('ALTER TABLE #__rsfirewall_logs ADD INDEX(`ip`); ');
			$db->execute();
			$db->setQuery('ALTER TABLE #__rsfirewall_lists ADD INDEX(`ip`); ');
			$db->execute();
		}

		if ($columns['username']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_logs` CHANGE `username` `username` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}

		if ($columns['page']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_logs` CHANGE `page` `page` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}

		if ($columns['referer']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_logs` CHANGE `referer` `referer` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}

		if ($columns['referer']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_logs` CHANGE `debug_variables` `debug_variables` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}
		
		// offenders
		$columns = $db->getTableColumns('#__rsfirewall_offenders', false);
		if ($columns['ip']->Type != 'varchar(255)') {
			$db->setQuery('ALTER TABLE #__rsfirewall_offenders CHANGE `ip` `ip` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL');
			$db->execute();
		}

		// exceptions
		$columns = $db->getTableColumns('#__rsfirewall_exceptions', false);
		if ($columns['reason']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_exceptions` CHANGE `reason` `reason` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}

		// hashes
		$columns = $db->getTableColumns('#__rsfirewall_hashes', false);
		if ($columns['flag']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_hashes` CHANGE `flag` `flag` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}

		if ($columns['date']->Null === 'NO')
		{
			$db->setQuery("ALTER TABLE `#__rsfirewall_hashes` CHANGE `date` `date` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			$db->execute();
		}

		// add hashes
		$this->runSQL($source, 'hashes.data.sql');

		// remove duplicates
		$query = $db->getQuery(true);
		$query->select('COUNT(' . $db->qn('type')  . ') AS ' . $db->qn('found'))
			->select($db->qn('file'))
			->select($db->qn('type'))
			->from($db->qn('#__rsfirewall_hashes'))
			->where($db->qn('type') . ' LIKE ' . $db->q('%.%.%'))
			->group($db->qn('type'))
			->group($db->qn('file'))
			->having($db->qn('found') . ' > 1');

		if ($results = $db->setQuery($query)->loadObjectList())
		{
			foreach ($results as $result)
			{
				$query = $db->getQuery(true);
				$query->delete('#__rsfirewall_hashes')
					->where($db->qn('file') . ' = ' . $db->q($result->file))
					->where($db->qn('type') . ' = ' . $db->q($result->type))
					->order($db->qn('id') . ' ' . $db->escape('ASC'))
					->setLimit($result->found - 1);

				$db->setQuery($query)->execute();
			}
		}

		// Remove old versions
		list($major, $minor, $patch) = explode('.', JVERSION, 3);
		if (strpos($patch, '-') !== false)
		{
			$tmp = explode('-', $patch, 2);
			$patch = reset($tmp);
		}

		$query = $db->getQuery(true)
			->delete($db->qn('#__rsfirewall_hashes'))
			->where($db->qn('type') . ' LIKE ' . $db->q('%.%.%'))
			->where("CONCAT(
			LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(" . $db->qn('type') . ", '.', 1), '.', -1), 2, '0'),
			LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(" . $db->qn('type') . ", '.', 2), '.', -1), 2, '0'),
			LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(" . $db->qn('type') . ", '.', 3), '.', -1), 2, '0')
		) < CONCAT(LPAD(" . $db->q($major) . ", 2, '0'), LPAD(" . $db->q($minor) . ", 2, '0'), LPAD(" . $db->q($patch) . ", 2, '0'))");
		$db->setQuery($query)->execute();

		// add signatures
		$this->runSQL($source, 'signatures.data.sql');
		
		$this->removeSignatures();
	}
	
	protected function removeSignatures()
	{
		// There you go hosting providers, scan this.
		$files = array(
			JPATH_ADMINISTRATOR.'/components/com_rsfirewall/sql/mysql/signatures.data.sql',
			JPATH_ADMINISTRATOR. '/components/com_rsfirewall/helpers/geolite2/vendor/composer/ca-bundle/src/CaBundle.php'
		);

		foreach ($files as $file)
		{
			if (file_exists($file))
			{
				\Joomla\CMS\Filesystem\File::delete($file);
			}
		}
	}
	
	protected function runSQL($source, $file)
	{
		$db 	= \Joomla\CMS\Factory::getDbo();
		$driver = 'mysql';
		
		$sqlfile = $source . '/admin/sql/' . $driver . '/' . $file;
		
		if (file_exists($sqlfile))
		{
			$buffer = file_get_contents($sqlfile);
			if ($buffer !== false)
			{
				if ($queries = $db->splitSql($buffer))
				{
					foreach ($queries as $query)
					{
						$db->setQuery($query)->execute();
					}
				}
			}
		}
	}
	
	protected function explode($string) {
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return explode("\n", $string);
	}
	
	protected function showInstallMessage($messages=array(), $type = 'install') {
		$ip = \Joomla\Utilities\IpHelper::getIp();
?>
<style type="text/css">
.version-history {
	margin: 0 0 2em 0;
	padding: 0;
	list-style-type: none;
}
.version-history > li {
	margin: 0 0 0.5em 0;
	padding: 0 0 0 4em;
}

.version,
.version-new,
.version-fixed,
.version-upgraded {
	float: left;
	font-size: 0.8em;
	margin-left: -4.9em;
	width: 4.5em;
	color: white;
	text-align: center;
	font-weight: bold;
	text-transform: uppercase;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
}

.version {
	background: #000;
}

.version-new {
	background: #7dc35b;
}
.version-fixed {
	background: #e9a130;
}
.version-upgraded {
	background: #61b3de;
}

.install-ok {
	background: #7dc35b;
	color: #fff;
	padding: 3px;
}

.install-not-ok {
	background: #E9452F;
	color: #fff;
	padding: 3px;
}
</style>
	<div>
		<!-- until Watchful fix their code this part has to be written like this; they're emulating the backend in a frontend request and template overrides don't work because 'isis' doesn't exist in the frontend, so any calls that use the /media folder will throw an error -->
		<p><img src="<?php echo \Joomla\CMS\Uri\Uri::root(true) . '/media/com_rsfirewall/images/rsfirewall-box.png'; ?>" alt="RSFirewall!" /></p>
			<p>System Plugin ...
				<?php if ($messages['plg_rsfirewall']) { ?>
				<strong class="install-ok">Installed</strong>
				<?php } else { ?>
				<strong class="install-not-ok">Error installing!</strong>
				<?php } ?>
			</p>
			<p>Installer Plugin ...
				<?php if ($messages['plg_installer']) { ?>
				<strong class="install-ok">Installed</strong>
				<?php } else { ?>
				<strong class="install-not-ok">Error installing!</strong>
				<?php } ?>
			</p>
			 <?php
			if (isset($messages['plg_console']))
			{
				?>
				<p>System - RSFirewall! CLI Plugin ...
					<?php if ($messages['plg_console']) { ?>
						<b class="install-ok">Installed</b> <a href="index.php?option=com_plugins&task=plugin.edit&extension_id=<?php echo $messages['plg_console']; ?>" class="btn btn-secondary text-white">Edit Plugin</a> Please enable manually if you wish to use the CLI.
					<?php } else { ?>
						<b class="install-not-ok">Error installing!</b>
					<?php } ?>
				</p>
				<?php
			}
			?>
			<p>RSFirewall! Control Panel Module ...
				<?php if ($messages['mod_rsfirewall']) { ?>
				<strong class="install-ok">Installed</strong>
				<?php } else { ?>
				<strong class="install-not-ok">Error installing!</strong>
				<?php } ?>
			</p>
			<h2>Changelog v3.1.5</h2>
			<ul class="version-history">
                <li><span class="version-new">New</span> Button to clear the Backend Password.</li>
                <li><span class="version-new">New</span> Protection against Sourcerer &lt; 11.0.0 vulnerability.</li>
                <li><span class="version-upgraded">Upg</span> Backend Password instructions when using 'Use as parameter'.</li>
			</ul>
			<?php
			if ($type === 'install')
			{
				?>
				<div class="alert alert-warning">
                    <p>Your IP is currently detected as <strong><?php echo htmlspecialchars($ip, ENT_COMPAT, 'utf-8'); ?></strong>. If this does not match your current IP:</p>
                    <ul>
                        <li>Go to Global Configuration &mdash; Server and set 'Behind Load Balancer' to 'Yes', or:</li>
                        <li>Go to Firewall Configuration &mdash; Active Scanner &mdash; Grab IP from Proxy Headers and select which PHP headers forward your current IP address. You can select them one by one until your correct IP address is reported. If in doubt, contact your hosting provider for more information. You can Google &quot;what is my ip address&quot; to find out what your real IP address is.
                        </li>
                    </ul>
				</div>
				<?php
			}
			?>
			<p>
				<a class="btn btn-primary btn-large btn-lg text-white" href="index.php?option=com_rsfirewall">Start using RSFirewall!</a>
				<a class="btn btn-secondary text-white" href="https://www.rsjoomla.com/support/documentation/rsfirewall-user-guide.html" target="_blank">Read the RSFirewall! User Guide</a>
				<a class="btn btn-secondary text-white" href="https://www.rsjoomla.com/support.html" target="_blank">Get Support!</a>
			</p>
	</div>
		<?php
	}
}