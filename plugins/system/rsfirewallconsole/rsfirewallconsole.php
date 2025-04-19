<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;
use Joomla\DI\Container;
use Rsjoomla\Plugin\System\Rsfirewallconsole\DownloadgeoipCommand;
use Rsjoomla\Plugin\System\Rsfirewallconsole\CheckCoreCommand;
use Rsjoomla\Plugin\System\Rsfirewallconsole\CheckMalwareCommand;
use Rsjoomla\Plugin\System\Rsfirewallconsole\CheckFoldersCommand;
use Rsjoomla\Plugin\System\Rsfirewallconsole\CheckFilesCommand;

class plgSystemRsfirewallconsole extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;

	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents::BEFORE_EXECUTE => 'registerCommand',
		];
	}

	public function registerCommand(ApplicationEvent $event): void
	{
		if (file_exists(JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/config.php'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/config.php';
		}
		else
		{
			return;
		}

		/* Download GeoIp database command */
		$serviceId = 'rsfirewall:download-geoip';

		Factory::getContainer()->share(
			$serviceId,
			function (Container $container) {
				// do stuff to create command class and return it
				return new DownloadgeoipCommand();
			},
			true
		);

		Factory::getContainer()->get(WritableLoaderInterface::class)->add(DownloadgeoipCommand::getDefaultName(), $serviceId);

		/* Check core files integrity command */
		$serviceId = 'rsfirewall:check-core';

		Factory::getContainer()->share(
			$serviceId,
			function (Container $container) {
				// do stuff to create command class and return it
				return new CheckCoreCommand();
			},
			true
		);

		Factory::getContainer()->get(WritableLoaderInterface::class)->add(CheckCoreCommand::getDefaultName(), $serviceId);

		/* Check common malware command */
		$serviceId = 'rsfirewall:check-malware';

		Factory::getContainer()->share(
			$serviceId,
			function (Container $container) {
				// do stuff to create command class and return it
				return new CheckMalwareCommand();
			},
			true
		);

		Factory::getContainer()->get(WritableLoaderInterface::class)->add(CheckMalwareCommand::getDefaultName(), $serviceId);

		/* Files & Folders command available only on linux OS */
		if (substr(PHP_OS, 0, 3) != 'WIN')
		{
			/* Check folders permissions command */
			$serviceId = 'rsfirewall:check-folders';

			Factory::getContainer()->share(
				$serviceId,
				function (Container $container) {
					// do stuff to create command class and return it
					return new CheckFoldersCommand();
				},
				true
			);

			Factory::getContainer()->get(WritableLoaderInterface::class)->add(CheckFoldersCommand::getDefaultName(), $serviceId);

			/* Check files permissions command */
			$serviceId = 'rsfirewall:check-files';

			Factory::getContainer()->share(
				$serviceId,
				function (Container $container) {
					// do stuff to create command class and return it
					return new CheckFilesCommand();
				},
				true
			);

			Factory::getContainer()->get(WritableLoaderInterface::class)->add(CheckFilesCommand::getDefaultName(), $serviceId);
		}
	}
}