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

class RsfirewallViewLists extends HtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $geoip;

	public $filterForm;
	public $activeFilters;

	public function display($tpl = null)
	{
		if (!Factory::getUser()->authorise('lists.manage', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->addToolBar();

		$this->state 		 = $this->get('State');
		$this->items 		 = $this->get('Items');
		$this->pagination 	 = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		// Load GeoIP helper class
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/geoip/geoip.php';
		$this->geoip = RSFirewallGeoIP::getInstance();
		
		parent::display($tpl);
	}
	
	protected function addToolBar()
	{
		RSFirewallToolbarHelper::addToolbar('lists');

		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');
		
		ToolbarHelper::addNew('list.add');
		ToolbarHelper::addNew('list.bulkadd', Text::_('COM_RSFIREWALL_BULK_ADD'));

		if (version_compare(JVERSION, '4.0', '>='))
		{
			$this->createButtons();
		}
		else
		{
			ToolbarHelper::editList('list.edit');
			ToolbarHelper::divider();
			ToolbarHelper::publish('lists.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('lists.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			ToolbarHelper::divider();
			ToolbarHelper::deleteList('COM_RSFIREWALL_CONFIRM_DELETE', 'lists.delete');
		}

		ToolbarHelper::custom('lists.download', 'download', 'download', Text::_('COM_RSFIREWALL_DOWNLOAD_LISTS'), false);
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

		$childBar->edit('list.edit')->listCheck(true);
		$childBar->publish('lists.publish')->listCheck(true);
		$childBar->unpublish('lists.unpublish')->listCheck(true);
		$childBar->delete('lists.delete')->message('COM_RSFIREWALL_CONFIRM_DELETE')->listCheck(true);
	}
}