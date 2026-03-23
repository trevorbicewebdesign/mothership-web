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
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

class RsfirewallViewExceptions extends HtmlView
{
	protected $items;
	protected $pagination;
	protected $state;

	public $filterForm;
	public $activeFilters;
	
	public function display($tpl=null)
	{
		if (!Factory::getUser()->authorise('exceptions.manage', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->addToolBar();
		
		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state 		= $this->get('State');

		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		parent::display($tpl);
	}
	
	protected function addToolBar()
	{
		RSFirewallToolbarHelper::addToolbar('exceptions');

		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');

		ToolbarHelper::addNew('exception.add');

		if (version_compare(JVERSION, '4.0', '>='))
		{
			$this->createButtons();
		}
		else
		{
			ToolbarHelper::editList('exception.edit');
			ToolbarHelper::divider();
			ToolbarHelper::publish('exceptions.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('exceptions.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			ToolbarHelper::divider();
			ToolbarHelper::deleteList('COM_RSFIREWALL_CONFIRM_DELETE', 'exceptions.delete');
		}

		ToolbarHelper::custom('exceptions.download', 'download', 'download', Text::_('COM_RSFIREWALL_DOWNLOAD_EXCEPTIONS'), false);
	}

	private function createButtons()
	{
		$toolbar = Toolbar::getInstance();
		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('fa fa-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();

		$childBar->edit('exception.edit')->listCheck(true);
		$childBar->publish('exceptions.publish')->listCheck(true);
		$childBar->unpublish('exceptions.unpublish')->listCheck(true);
		$childBar->delete('exceptions.delete')->message('COM_RSFIREWALL_CONFIRM_DELETE')->listCheck(true);
	}
}