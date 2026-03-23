<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsfirewallViewFile extends HtmlView
{
	public function display($tpl = null) {
		$user = Factory::getUser();
		if (!$user->authorise('check.run', 'com_rsfirewall')) {
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		try {
			// Get file
			$this->filename 	= $this->get('Filename');
			$this->contents  	= $this->get('Contents');
			$this->status		= $this->get('Status');
			$this->time			= $this->get('Time');
			
			parent::display($tpl);
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}
}