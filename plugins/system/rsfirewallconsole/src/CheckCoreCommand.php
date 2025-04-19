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

class CheckCoreCommand extends AbstractCommand
{
	use HelperFunctions;

	protected $symfonyStyle;
	protected static $defaultName = 'rsfirewall:check-core';

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/models/check.php';

		$this->symfonyStyle = new SymfonyStyle($input, $output);
		
		// load the Joomla library language file
		Factory::getApplication()->getLanguage()->load('lib_joomla', JPATH_ADMINISTRATOR);
		Factory::getApplication()->getLanguage()->load('com_rsfirewall', JPATH_ADMINISTRATOR);

		$useLog      	= boolval($input->getOption('log'));
		$restore      	= boolval($input->getOption('restore'));
		$confirmRestore = boolval($input->getOption('confirm'));
		
		$scriptStart = microtime(true);

		try
		{
			$this->symfonyStyle->title(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE'));
			$this->symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_PHP_INFO', ini_get('memory_limit'), ini_get('max_execution_time')));

			// create an 100 % progress bar
			$this->symfonyStyle->progressStart(100); 

			$model = new \RsfirewallModelCheck();

			$output = $this->checkHashes($model);

			$this->symfonyStyle->progressFinish();

			if (!empty($output))
			{
				$this->parseOutput($output, $useLog, $restore, $confirmRestore);
			}
		}
		catch (\Exception $e)
		{
			$this->symfonyStyle->progressFinish();
			$err = $e->getMessage();
			// remove the tags from the error message
			$err = strip_tags($err);

			$this->symfonyStyle->error($err);
			return $e->getCode();
		}

		$time = number_format(microtime(true) - $scriptStart, 2, '.', '');
		
		$this->symfonyStyle->writeln('');
		$this->symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_FINISHED_IN_SECONDS', $time));
		$this->symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_SUCCESS'));

		
		return 0;
	}

	protected function checkHashes($model, $start = 0, $limit = 0)
	{
		static $last_progress;

		// if the limit is not set get
		if (empty($limit))
		{
			$limit  = $model->getConfig()->get('offset', 300);
		}

		$result = $model->checkHashes($start, $limit);

		$output = [];

		if ($result === false)
		{
			throw new \Exception($model->getError());
		}
		else 
		{
			$progress = (100 * $result->fstop) / $result->size;
			$progress = (int) floor($progress);
			
			// must determine the actual steps for this call
			if (is_null($last_progress))
			{
				$last_progress = $progress;
			}
			else 
			{
				$tmp_progress = $progress;
				$progress = $progress - $last_progress;
				$last_progress = $tmp_progress;
			}

			$this->symfonyStyle->progressAdvance($progress);
			// store the output
			$output[] = $result;

			// if is not finished continue scanning
			if ($result->fstop < $result->size) 
			{
				$rest = $this->checkHashes($model, $result->fstop, $limit);
				$output = array_merge($output, $rest);
			}
			else
			{
				// if is finished reset the last progress
				$last_progress = null;
			}	
		}

		return $output;
	}

	protected function parseOutput($output = [], $useLog = false, $restore = false, $confirm = false)
	{
		$wrong_paths = [];
		$missing_paths = [];
		// store the modified files because we will remember the time of the change
		$wrong_paths_log = [];

		// output count
		$count = 0;
		foreach ($output as $result)
		{
			if (!empty($result->wrong))
			{
				$count += count($result->wrong);
			}

			if (!empty($result->missing))
			{
				$count += count($result->missing);
			}
		}

		if ($count > 0)
		{
			$this->symfonyStyle->warning(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_FOUND_FILES', $count));
		}
		else
		{
			$this->symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_NOT_FOUND_FILES'));
		}
		
		foreach ($output as $result)
		{
			// output wrong (modified) files
			if (!empty($result->wrong))
			{
				foreach ($result->wrong as $file)
				{
					$file_time = $this->getFileTime($file);
					
					if (strlen($file_time['relative']))
					{
						$this->symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_WRONG_FILE_AGO', $file, $file_time['relative']));
						$file_log = Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_WRONG_FILE_DATE', $file, $file_time['time']);
					}
					else 
					{
						$this->symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_WRONG_FILE', $file));
						$file_log = $file;
					}
					$wrong_paths[] = $file; 
					$wrong_paths_log[] = $file_log; 
				}
			}

			// output missing files
			if (!empty($result->missing))
			{
				foreach ($result->missing as $file)
				{
					$this->symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_MISSING_FILE', $file));
					$missing_paths[] = $file;
				}
			}
		}

		if ($useLog && (!empty($wrong_paths_log) || !empty($missing_paths)))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/log.php';
			$logger = \RSFirewallLogger::getInstance();
			// use the server timezone
			$logger->setTimezone(false);

			if (!empty($wrong_paths_log))
			{
				// save them with new lines
				$wrong_paths_log = implode("\n", $wrong_paths_log);
							
				$logger->add('critical','INTEGRITY_FILES_MODIFIED', $wrong_paths_log)->save();
			}

			if (!empty($missing_paths))
			{
				// save them with new lines
				$missing_paths = implode("\n", $missing_paths);
							
				$logger->add('critical','INTEGRITY_FILES_MISSING', $missing_paths)->save();
			}
			
			$this->symfonyStyle->newLine();
			$this->symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_FILES_ADDED_TO_LOG'));
		}

		// restore files if specified
		if ($restore && (!empty($wrong_paths) || !empty($missing_paths)))
		{
			// if confirm is specified ask for confirmation
			if ($confirm && !$this->askQuestion(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_FILES_RESTORE_CONFIRMATION')))
			{
				return;
			}

			//downloadOriginalFile
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/models/diff.php';

			$diff = new \RsfirewallModelDiff();
			$all_paths = array_merge($wrong_paths, $missing_paths);
			
			foreach ($all_paths as $path)
			{
				$message = $diff->downloadOriginalFile($path, false);

				if ($message['status'])
				{
					$this->symfonyStyle->success(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_FILE_COPIED_SUCCESS', $path));
				}
				else
				{
					$this->symfonyStyle->error(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_FILE_COPIED_ERROR', $path, $message['message']));
				}
			}
		}
		
	}

	protected function configure(): void
	{
		$this->addOption('log', 'l', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_USE_LOG_OPTION'), '');
		$this->addOption('restore', 'r', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_RESTORE_FILES_OPTION'), '');
		$this->addOption('confirm', 'c', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_CONFIRM_RESTORE_FILES_OPTION'), '');

		$this->setDescription(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_DESCRIPTION'));

		$help_1 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_HELP_1');
		$help_2 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_HELP_2');
		$help_3 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_CHECKCORE_HELP_3');
		$this->setHelp(
			<<<EOF
RSFirewall!
###########

{$help_1}

        php joomla.php rsfirewall:check-core --log=1

{$help_2}

        php joomla.php rsfirewall:check-core --log=1 --restore=1

{$help_3}

        php joomla.php rsfirewall:check-core --log=1 --restore=1 --confirm=1

EOF
		);
	}
}