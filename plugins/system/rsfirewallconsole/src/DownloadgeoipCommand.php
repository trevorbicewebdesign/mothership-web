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

class DownloadgeoipCommand extends AbstractCommand
{
	protected static $defaultName = 'rsfirewall:download-geoip';

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/models/configuration.php';

		Factory::getApplication()->getLanguage()->load('com_rsfirewall', JPATH_ADMINISTRATOR);

		$symfonyStyle = new SymfonyStyle($input, $output);

		$license_key = $input->getOption('key');
		$scriptStart = microtime(true);

		try
		{
			$symfonyStyle->title(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_DOWNLOAD_GEOIP_TITLE'));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_PHP_INFO', ini_get('memory_limit'), ini_get('max_execution_time')));

			if (!$license_key)
			{
				$db = Factory::getDbo();
				$query = $db->getQuery(true)
					->select($db->qn('value'))
					->from($db->qn('#__rsfirewall_configuration'))
					->where($db->qn('name') . ' = ' . $db->q('maxmind_license_key'));

				$license_key = (string) $db->setQuery($query)->loadResult();

				// make it bool
				if (!strlen($license_key))
				{
					$license_key = false;
				}
			}

			if (!$license_key)
			{
				throw new \Exception(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_LICENSE_KEY_ERROR'));
			}

			$model = new \RsfirewallModelConfiguration();
			
			$result = $model->downloadGeoIPDatabase($license_key, false);
			
			if (!$result['success'])
			{
				throw new \Exception(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_ERROR', $result['message']));
			}
		}
		catch (\Exception $e)
		{
			$symfonyStyle->error($e->getMessage());
			return $e->getCode();
		}

		$time = number_format(microtime(true) - $scriptStart, 2, '.', '');

		$symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFIREWALLCONSOLE_FINISHED_IN_SECONDS', $time));
		$symfonyStyle->success(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_DOWNLOAD_SUCCESS'));

		return 0;
	}

	protected function configure(): void
	{
		$this->addOption('key', 'k', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_KEY_OPTION'), '');
		$this->setDescription(Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_DOWNLOAD_GEOIP_DESCRIPTION'));

		$help_1 = Text::_('PLG_SYSTEM_RSFIREWALLCONSOLE_COMMAND_DOWNLOAD_GEOIP_HELP_1');
		$this->setHelp(
			<<<EOF
RSFirewall!
###########

{$help_1}

        php joomla.php rsfirewall:download-geoip --key=xxxxxxx

EOF
		);
	}
}