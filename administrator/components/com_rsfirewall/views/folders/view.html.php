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

class RsfirewallViewFolders extends HtmlView
{	
	protected $elements;
	protected $folders;
	protected $files;
	protected $DS;
	
	protected $allowFolders;
	protected $allowFiles;
	
	public function display( $tpl = null ) {
		$user = Factory::getUser();
		if (!$user->authorise('core.admin', 'com_rsfirewall')) {
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->name		= $this->get('Name');
		$this->elements = $this->get('Elements');
		$this->previous	= $this->get('Previous');
		$this->folders 	= $this->get('Folders');
		$this->files	= $this->get('Files');
		$this->path		= $this->get('Path');
		
		$this->allowFolders = $this->get('allowFolders');
		$this->allowFiles 	= $this->get('allowFiles');
		
		parent::display($tpl);
	}
}