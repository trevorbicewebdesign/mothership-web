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
use Joomla\CMS\Toolbar\ToolbarHelper;

class RsfirewallViewException extends HtmlView
{
	protected $form;
	protected $item;
	protected $field;
	protected $ip;
	
	public function display($tpl = null)
	{
		$user = Factory::getUser();
		if (!$user->authorise('exceptions.manage', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');
		$this->ip	= $this->get('IP');

		Factory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolBar();
		
		parent::display($tpl);
	}
	
	protected function addToolBar()
	{
		RSFirewallToolbarHelper::addToolbar('exceptions');

		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');
		
		$layout = $this->getLayout();
		switch ($layout)
		{
			case 'edit':
				ToolbarHelper::title($this->item->id ? Text::_('COM_RSFIREWALL_EDITING_EXCEPTION') : Text::_('COM_RSFIREWALL_ADDING_NEW_EXCEPTION'), 'rsfirewall');

				ToolbarHelper::apply('exception.apply');
				ToolbarHelper::save('exception.save');
				ToolbarHelper::save2new('exception.save2new');
				ToolbarHelper::save2copy('exception.save2copy');
				ToolbarHelper::cancel('exception.cancel');
			break;
			
			case 'bulk':
				ToolbarHelper::save('exception.bulksave');
				ToolbarHelper::cancel('exception.cancel');
			break;
		}
	}
}