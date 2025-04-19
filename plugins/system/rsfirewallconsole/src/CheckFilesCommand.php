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

class CheckFilesCommand extends AbstractCommand
{
	use HelperFunctions;

	protected $symfonyStyle;
	protected static $defaultName = 'rsfirewall:check-files';
	protected $files;
	protected $files_permissions = 644;

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
			
			$this->symfonyStyle->title(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES'));
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

			// output a message that states we are scanning the instalation for all the files
			$this->symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_SCANNING'));

			// count all files so that we can create the progress bar
			$num_files = $this->numAllFiles($model);

			// output the number of files we found
			$this->symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_FOUND_NR_FILES', $num_files));

			// create the progress bar
			$this->symfonyStyle->progressStart($num_files); 

			$output = $this->checkPermissions($model);

			$this->symfonyStyle->progressFinish();

			if (!empty($output))
			{
				$this->parseOutput($output, $useLog, $fix, $confirmFix);
			}
			else
			{
				$this->symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_NOT_FOUND_FILES'));
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
		$this->symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_SUCCESS'));

		
		return 0;
	}

	protected function numAllFiles($model, $start = 'none')
	{
		$num_files = 0;

		$root = JPATH_SITE;
		// workaround to grab the correct root
		if ($root == '') {
			$root = '/';
		}

		// if the start is none use the root
		$start = $start == 'none' ? $root : $start;

		// get all directories from the instalation
		$this->files = $this->getDirContents($start);

		$num_files = count($this->files);

		return $num_files;
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
				$results[] = $path;
			} 
			
			if ($value != "." && $value != "..") {
				$this->getDirContents($path, $results);
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

		if (!empty($this->files) && isset($this->files[$start]))
		{
			$check_files = array_slice($this->files, $start, $limit);

			$progress = count($check_files);
			$this->symfonyStyle->progressAdvance($progress);

			foreach ($check_files as $file)
			{
				if (($perms = $model->checkPermissions($file)) > $this->files_permissions) {
					$tmp 		= new \stdClass();
					$tmp->path  = substr_replace($file, '', 0, strlen(JPATH_SITE.DIRECTORY_SEPARATOR));
					$tmp->perms = $perms;

					$output[] = $tmp;
				}
			}
			
			$next = $start + $limit;
			if (isset($this->files[$next]))
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
		
		$this->symfonyStyle->warning(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_FOUND_FILES', count($output)));

		foreach ($output as $file)
		{
			// store in paths for fixing later
			$paths[] = $file->path;

			// init the log array
			$log = [];

			// path
			$log[] = Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_PATH', $file->path);

			// perms found
			$log[] = Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_PERMS', $file->perms);

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
							
				$logger->add('critical','FILES_PERMISSIONS_FOUND', $problems)->save();
			}
			
			$this->symfonyStyle->newLine();
			$this->symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_ADDED_TO_LOG'));
		}

		// restore files if specified
		if ($fix && !empty($paths))
		{
			// if confirm is specified ask for confirmation
			if ($confirm && !$this->askQuestion(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_FIX_CONFIRMATION')))
			{
				return;
			}

			//downloadOriginalFile
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/models/fix.php';

			$fix = new \RsfirewallModelFix();

			$fix_results = $fix->setPermissions($paths, $this->files_permissions);
			
			foreach ($fix_results as $i => $fix_result)
			{
				if ($fix_result)
				{
					$this->symfonyStyle->success(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_FIX_SUCCESS', $paths[$i], $this->files_permissions));
				}
				else
				{
					$this->symfonyStyle->error(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_FIX_ERROR', $paths[$i]));
				}
			}
		}
		
	}

	protected function configure(): void
	{
		$this->addOption('log', 'l', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_USE_LOG_OPTION'), '');
		$this->addOption('fix', 'f', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_FIX_FILES_OPTION'), '');
		$this->addOption('confirm', 'c', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_CONFIRM_FIX_FILES_OPTION'), '');
		$this->setDescription(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_DESCRIPTION'));

		$help_1 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_HELP_1');
		$help_2 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_HELP_2');
		$help_3 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKFILES_HELP_3');
		$this->setHelp(
			<<<EOF
RSFirewall!
###########

{$help_1}

        php joomla.php rsfirewall:check-files --log=1

{$help_2}

        php joomla.php rsfirewall:check-files --log=1 --fix=1

{$help_3}

        php joomla.php rsfirewall:check-files --log=1 --fix=1 --confirm=1

EOF
		);
	}
}