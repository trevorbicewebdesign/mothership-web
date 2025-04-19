<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace Rsjoomla\Plugin\System\Rsfirewallconsole;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Date\Date as DateHelper;


defined('_JEXEC') or die;

trait HelperFunctions 
{

	protected function askQuestion(string $text)
	{
		$status = (string) readline($text);
		$status = trim($status);
		$status = strtolower($status);

		return (strpos($status,'y') === 0);
	}

	
	protected function getFileTime($file){
		$relative_time = '';
		$file_time = '';

		if ($time = @filemtime(JPATH_SITE.'/'.$file))
		{
			$gmdate = gmdate('Y-m-d H:i:s', $time);
			// workaround to avoid user timezone
			
			// Get now
			$now = new DateHelper('now');
			// Get the difference in seconds between now and the time
			$diff = strtotime($now) - strtotime($gmdate);

			$diff = $diff / 60  / 60 / 24 / 7;

			if ($diff > 4)
			{
				$relative_time = HTMLHelper::_('date', $time, 'Y-m-d H:i:s', false);
			}
			else
			{
				$relative_time = HTMLHelper::_('date.relative', $gmdate);
			}
			
			$file_time = HTMLHelper::_('date', $time, 'Y-m-d H:i:s', false);
		}

		return ['relative' => $relative_time, 'time' => $file_time];
	}
}