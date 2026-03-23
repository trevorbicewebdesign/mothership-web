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
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

class RsfirewallViewList extends HtmlView
{
	protected $form;
	protected $item;
	protected $ip;
	
	public function display($tpl = null)
	{
		if (!Factory::getUser()->authorise('lists.manage', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$layout = $this->getLayout();
		switch ($layout)
		{
			case 'edit':				
				$this->form	 = $this->get('Form');
				$this->item	 = $this->get('Item');
				$this->ip	 = $this->get('Ip'); 
			break;
			
			case 'bulk':
				$this->form	= $this->get('Form');
				$this->ip	= $this->get('Ip'); 
			break;
		}

		Factory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolBar();
		
		parent::display($tpl);
	}
	
	protected function addToolBar()
	{
		RSFirewallToolbarHelper::addToolbar('lists');

		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');
		
		$layout = $this->getLayout();
		switch ($layout)
		{
			case 'edit':
				ToolbarHelper::title($this->item->id ? Text::sprintf('COM_RSFIREWALL_EDITING_IP', $this->escape($this->item->ip)) : Text::_('COM_RSFIREWALL_ADDING_NEW_IP'), 'rsfirewall');

				ToolbarHelper::apply('list.apply');
				ToolbarHelper::save('list.save');
				ToolbarHelper::save2new('list.save2new');
				ToolbarHelper::save2copy('list.save2copy');
				ToolbarHelper::cancel('list.cancel');
			break;
			
			case 'bulk':
				ToolbarHelper::title(Text::_('COM_RSFIREWALL_ADDING_NEW_IP_BULK'), 'rsfirewall');

				ToolbarHelper::save('list.bulksave');
				ToolbarHelper::cancel('list.cancel');
			break;
		}
	}
}