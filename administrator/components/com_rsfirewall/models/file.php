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
use Joomla\CMS\HTML\HTMLHelper;

class RsfirewallModelFile extends BaseDatabaseModel
{
	public function getFilename() {
		return Factory::getApplication()->input->getString('file');
	}

	protected function getLocalFilename() {
		return JPATH_SITE.'/'.$this->getFilename();
	}
	
	public function getTime()
	{
		$path = $this->getLocalFilename();

		if (!file_exists($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_FOUND', $path));
		}

		if (!is_readable($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_READABLE', $path));
		}

		if (!is_file($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_NOT_A_FILE', $path));
		}
		
		if ($time = filemtime($path))
		{
			return HTMLHelper::_('date.relative', gmdate('Y-m-d H:i:s', $time));
		}
		
		return '';
	}

	public function getContents() {
		$path = $this->getLocalFilename();

		if (!file_exists($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_FOUND', $path));
		}

		if (!is_readable($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_READABLE', $path));
		}

		if (!is_file($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_NOT_A_FILE', $path));
		}

		return file_get_contents($path);
	}

	public function getStatus() {
		$path = $this->getLocalFilename();

		if (!file_exists($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_FOUND', $path));
		}

		if (!is_readable($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_READABLE', $path));
		}

		if (!is_file($path)) {
			throw new Exception(Text::sprintf('COM_RSFIREWALL_NOT_A_FILE', $path));
		}

		$checkModel = $this->getInstance('Check', 'RsfirewallModel');
		if ($status = $checkModel->checkSignatures($path)) {
			return $status;
		} else {
			if ($error = $checkModel->getError()) {
				throw new Exception($error);
			}

			return false;
		}
	}
}