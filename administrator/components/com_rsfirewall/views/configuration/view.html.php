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

class RsfirewallViewConfiguration extends HtmlView
{
	protected $tabs;
	protected $field;
	protected $forms;
	protected $fieldsets;
	protected $geoip;
	protected $config;
	protected $ip;

	public function display($tpl = null)
	{
		$user = Factory::getUser();
		if (!$user->authorise('core.admin', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->addToolBar();
		
		// tabs
		$this->tabs = $this->get('RSTabs');
		
		// form
		$this->form		 = $this->get('Form');
		$this->fieldsets = $this->form->getFieldsets();
		
		// GeoIP info
		$this->geoip = $this->get('GeoIPInfo');
		
		// config
		$this->config	= $this->get('Config');
		$this->ip = $this->get('ip');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		RSFirewallToolbarHelper::addToolbar('configuration');

		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');
		
		ToolbarHelper::apply('configuration.apply');
		ToolbarHelper::save('configuration.save');
		ToolbarHelper::cancel('configuration.cancel');
		
		ToolbarHelper::custom('configuration.export', 'download', 'download', Text::_('COM_RSFIREWALL_EXPORT_CONFIGURATION'), false, false);
	}
}