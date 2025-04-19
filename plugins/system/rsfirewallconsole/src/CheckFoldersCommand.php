<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace Rsjoomla\Plugin\System\Rsfirewallconsole;

use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class CheckFoldersCommand extends AbstractCommand
{
	use HelperFunctions;

	protected $symfonyStyle;
	protected static $defaultName = 'rsfirewall:check-folders';
	protected $folders;
	protected $folder_permissions 	 = 755;

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/models/check.php';

		$this->symfonyStyle = new SymfonyStyle($input, $output);
		
		// load the Joomla library language file
		Factory::getApplication()->getLanguage()->load('lib_joomla', JPATH_ADMINISTRATOR);
		Factory::getApplication()->getLanguage()->load('com_rsfirewall', JPATH_ADMINISTRATOR);

		$useLog     = boolval($input->getOption('log'));
		$fix      	= boolval($input->getOption('fix'));
		$confirmFix = boolval($input->getOption('confirm'));
		
		$scriptStart = microtime(true);

		try
		{
			
			$this->symfonyStyle->title(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS'));
			$this->symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_PHP_INFO', ini_get('memory_limit'), ini_get('max_execution_time')));

			// Check if Xdebug is enabled and try to disable it
			if (extension_loaded('xdebug'))
			{
				$this->symfonyStyle->warning(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_XDEBUG_ENABLED'));
				$this->symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_XDEBUG_TRY_DISABLE'));
				// try and disable it, if no stop the script
				if (function_exists('xdebug_disable'))
				{
					xdebug_disable();
					$this->symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_XDEBUG_DISABLE_SUCCESS'));
				}
				else 
				{
					throw new \Exception(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_XDEBUG_DISABLE_FAIL'));
				}
			}

			
			$model = new \RsfirewallModelCheck();

			// output a message that states we are scanning the instalation for all the folders
			$this->symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_SCANNING'));

			// count all folders so that we can create the progress bar
			$num_folders = $this->numAllFolders($model);

			// output the number of folders we found
			$this->symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_FOUND_NR_FOLDERS', $num_folders));


			// create the progress bar
			$this->symfonyStyle->progressStart($num_folders); 

			$output = $this->checkPermissions($model);

			$this->symfonyStyle->progressFinish();

			if (!empty($output))
			{
				$this->parseOutput($output, $useLog, $fix, $confirmFix);
			}
			else
			{
				$this->symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_NOT_FOUND_FOLDERS'));
			}
		}
		catch (\Exception $e)
		{
			$err = $e->getMessage();
			// remove the tags from the error message
			$err = strip_tags($err);

			$this->symfonyStyle->error($err);
			return $e->getCode();
		}

		$time = number_format(microtime(true) - $scriptStart, 2, '.', '');

		$this->symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_FINISHED_IN_SECONDS', $time));
		$this->symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_SUCCESS'));

		
		return 0;
	}

	protected function numAllFolders($model, $start = 'none')
	{
		$num_folders = 0;

		$root = JPATH_SITE;
		// workaround to grab the correct root
		if ($root == '') {
			$root = '/';
		}

		// if the start is none use the root
		$start = $start == 'none' ? $root : $start;

		// get all directories from the instalation
		$this->folders = $this->getDirContents($start);

		$num_folders = count($this->folders);

		return $num_folders;
	}

	protected function getDirContents($dir, &$results = []) {
		$files = @scandir($dir);
		if ($files === false)
		{
			return $results;
		}
	
		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path)) {
				continue;
			} 
			
			if ($value != "." && $value != "..") {
				$this->getDirContents($path, $results);
				$results[] = $path;
			}
		}
	
		return $results;
	}

	protected function checkPermissions($model, $start = 0, $limit = 0)
	{
		
		$output = [];

		// if the limit is not set get
		if (empty($limit))
		{
			$limit  = $model->getConfig()->get('offset', 300);
		}

		if (!empty($this->folders) && isset($this->folders[$start]))
		{
			$check_folders = array_slice($this->folders, $start, $limit);

			$progress = count($check_folders);
			$this->symfonyStyle->progressAdvance($progress);

			foreach ($check_folders as $folder)
			{
				if (($perms = $model->checkPermissions($folder)) > $this->folder_permissions) {
					$tmp 		= new \stdClass();
					$tmp->path  = substr_replace($folder, '', 0, strlen(JPATH_SITE.DIRECTORY_SEPARATOR));
					$tmp->perms = $perms;

					$output[] = $tmp;
				}
			}
			
			$next = $start + $limit;
			if (isset($this->folders[$next]))
			{
				$rest = $this->checkPermissions($model, $next, $limit);
				$output = array_merge($output, $rest);
			}
		}
		

		return $output;
	}

	protected function parseOutput($output = [], $useLog = false, $fix = false, $confirm = false)
	{
		$problems = [];
		$paths = [];
		
		$this->symfonyStyle->warning(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_FOUND_FOLDERS', count($output)));

		foreach ($output as $folder)
		{
			// store in paths for fixing later
			$paths[] = $folder->path;

			// init the log array
			$log = [];

			// path
			$log[] = Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_PATH', $folder->path);

			// perms found
			$log[] = Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_PERMS', $folder->perms);

			$cli_message = implode("\n", $log);

			// show the output of the file
			$this->symfonyStyle->writeln($cli_message);
			$this->symfonyStyle->writeln('');
	
			$problems[] = implode("\n", $log); 
		}

		if ($useLog && !empty($problems))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/log.php';
			$logger = \RSFirewallLogger::getInstance();
			// use the server timezone
			$logger->setTimezone(false);

			if (!empty($problems))
			{
				// save them with new lines
				$problems = implode("\n\n", $problems);
							
				$logger->add('critical','FOLDERS_PERMISSIONS_FOUND', $problems)->save();
			}
			
			$this->symfonyStyle->newLine();
			$this->symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_ADDED_TO_LOG'));
		}

		// restore files if specified
		if ($fix && !empty($paths))
		{
			// if confirm is specified ask for confirmation
			if ($confirm && !$this->askQuestion(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_FIX_CONFIRMATION')))
			{
				return;
			}

			//downloadOriginalFile
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/models/fix.php';

			$fix = new \RsfirewallModelFix();

			$fix_results = $fix->setPermissions($paths, $this->folder_permissions);
			
			foreach ($fix_results as $i => $fix_result)
			{
				if ($fix_result)
				{
					$this->symfonyStyle->success(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_FIX_SUCCESS', $paths[$i], $this->folder_permissions));
				}
				else
				{
					$this->symfonyStyle->error(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_FIX_ERROR', $paths[$i]));
				}
			}
		}
		
	}

	protected function configure(): void
	{
		$this->addOption('log', 'l', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_USE_LOG_OPTION'), '');
		$this->addOption('fix', 'f', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_FIX_FOLDERS_OPTION'), '');
		$this->addOption('confirm', 'c', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_CONFIRM_FIX_FOLDERS_OPTION'), '');
		$this->setDescription(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_DESCRIPTION'));

		$help_1 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_HELP_1');
		$help_2 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_HELP_2');
		$help_3 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFOLDERS_HELP_3');
		$this->setHelp(
			<<<EOF
RSFirewall!
###########

{$help_1}

        php joomla.php rsfirewall:check-folders --log=1

{$help_2}

        php joomla.php rsfirewall:check-folders --log=1 --fix=1

{$help_3}

        php joomla.php rsfirewall:check-folders --log=1 --fix=1 --confirm=1

EOF
		);
	}
}