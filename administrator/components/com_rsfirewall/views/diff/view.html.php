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

class RsfirewallViewDiff extends HtmlView
{
	public function display($tpl = null)
	{
		$user = Factory::getUser();
		if (!$user->authorise('check.run', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		try
		{
			// Get local file properties
			$this->localFilename 	= $this->get('LocalFilename');
			$this->local  			= $this->get('LocalFile');
			$this->localTime  		= $this->get('LocalTime');
			
			// Get remote file properties
			$this->remoteFilename 	= $this->get('RemoteFilename');
			$this->remote 			= $this->get('RemoteFile');
			
			// Get file without root path
			$this->filename = $this->get('File');
			
			$this->hashId = $this->get('hashId');
			
			parent::display($tpl);
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}
}