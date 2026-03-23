<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Version;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsfirewallViewRsfirewall extends HtmlView
{
	protected $buttons;
	protected $canViewLogs;
	protected $lastLogs;
	protected $logNum;
	protected $lastMonthLogs;
	protected $files;
	protected $renderMap;
	// version info
	protected $version;
	protected $code;

	public function display($tpl = null)
	{
		$this->addToolBar();
		if (!PluginHelper::isEnabled('system', 'rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('COM_RSFIREWALL_WARNING_PLUGIN_DISABLED'), 'notice');
		}

		$this->version     = (string) new RSFirewallVersion;
		$this->jversion    = new Version();
		$this->canViewLogs = Factory::getUser()->authorise('logs.view', 'com_rsfirewall');
		$this->code        = $this->get('code');
		$this->files       = $this->get('modifiedFiles');
		$this->renderMap   = $this->renderMap();

		if ($this->canViewLogs)
		{
			$this->logNum        = $this->get('logOverviewNum');
			$this->lastLogs      = $this->get('lastLogs');
			$this->lastMonthLogs = $this->get('lastMonthLogs');
		}

		// Load GeoIP helper class
		require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/geoip/geoip.php';
		$this->geoip = RSFirewallGeoIP::getInstance();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');

		RSFirewallToolbarHelper::addToolbar();
	}

	protected function renderMap()
	{
		return ($this->get('CountryBlocking') && $this->get('GeoIPStatus'));
	}
}