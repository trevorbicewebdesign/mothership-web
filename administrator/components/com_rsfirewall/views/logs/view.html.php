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

class RsfirewallViewLogs extends HtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $levels;

	public $filterForm;
	public $activeFilters;
	
	public function display( $tpl = null )
	{
		if (!Factory::getUser()->authorise('logs.view', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->addToolBar();

		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state 		= $this->get('State');
		$this->levels		= $this->get('Levels');

		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		// Load GeoIP helper class
		require_once JPATH_ADMINISTRATOR.'/components/com_rsfirewall/helpers/geoip/geoip.php';
		$this->geoip = RSFirewallGeoIP::getInstance();
		
		parent::display($tpl);
	}
	
	protected function addToolBar()
	{
		RSFirewallToolbarHelper::addToolbar('logs');

		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');
		
		ToolbarHelper::addNew('logs.addtoblacklist', Text::_('COM_RSFIREWALL_LOG_ADD_BLACKLIST'), true);
		ToolbarHelper::addNew('logs.addtowhitelist', Text::_('COM_RSFIREWALL_LOG_ADD_WHITELIST'), true);
		ToolbarHelper::deleteList('COM_RSFIREWALL_CONFIRM_DELETE', 'logs.delete');
		ToolbarHelper::divider();
		ToolbarHelper::custom('logs.truncate', 'delete', 'delete', Text::_('COM_RSFIREWALL_EMPTY_LOG'), false);
		ToolbarHelper::custom('logs.download', 'download', 'download', Text::_('COM_RSFIREWALL_DOWNLOAD_LOG'), false);
	}
}