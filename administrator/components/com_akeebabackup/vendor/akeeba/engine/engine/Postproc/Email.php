<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Awf\Text\Text;
use Joomla\CMS\Language\Text as JText;
use RuntimeException;

class Email extends Base
{
	public function processPart($localFilepath, $remoteBaseName = null)
	{
		// Retrieve engine configuration data
		$config  = Factory::getConfiguration();
		$address = trim($config->get('engine.postproc.email.address', ''));
		$subject = $config->get('engine.postproc.email.subject', '0');

		// Sanity checks
		if (empty($address))
		{
			throw new BadConfiguration('You have not set up a recipient\'s email address for the backup files');
		}

		// Send the file
		$basename = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;

		Factory::getLog()->info(sprintf("Preparing to email %s to %s", $basename, $address));

		if (empty($subject))
		{
			$subject = "You have a new backup part";

			if (class_exists('\Awf\Text\Text'))
			{
				$subject = Text::_('COM_AKEEBA_COMMON_EMAIL_DEAFULT_SUBJECT');

				if ($subject === 'COM_AKEEBA_COMMON_EMAIL_DEAFULT_SUBJECT')
				{
					$subject = JText::_('COM_AKEEBABACKUP_COMMON_EMAIL_DEAFULT_SUBJECT');
				}
			}
			elseif (class_exists('\Joomla\CMS\Language\Text'))
			{
				$subject = JText::_('COM_AKEEBA_COMMON_EMAIL_DEAFULT_SUBJECT');

				if ($subject === 'COM_AKEEBA_COMMON_EMAIL_DEAFULT_SUBJECT')
				{
					$subject = JText::_('COM_AKEEBABACKUP_COMMON_EMAIL_DEAFULT_SUBJECT');
				}
			}
		}

		$body = "Emailing $basename";

		Factory::getLog()->debug("Subject: $subject");
		Factory::getLog()->debug("Body: $body");

		$result = Platform::getInstance()->send_email($address, $subject, $body, $localFilepath);

		// Return the result
		if ($result !== true)
		{
			// An error occurred
			throw new RuntimeException($result);
		}

		// Return success
		Factory::getLog()->info("Email sent successfully");

		return true;
	}

	protected function makeConnector()
	{
		/**
		 * This method does not use a connector.
		 */
		return;
	}


}
