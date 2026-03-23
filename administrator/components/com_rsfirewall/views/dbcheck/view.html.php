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

class RsfirewallViewDbcheck extends HtmlView
{
	protected $supported;
	protected $tables;
	
	public function display($tpl = null)
	{
		if (!Factory::getUser()->authorise('dbcheck.run', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->addToolBar();
		
		$this->supported = $this->get('IsSupported');
		$this->tables 	 = $this->get('Tables');

		$this->request_timeout = RSFirewallConfig::getInstance()->get('request_timeout');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');

		RSFirewallToolbarHelper::addToolbar('dbcheck');
	}
	
	protected function _convert($b)
	{
		if ($b < 1)
		{
			return '0.00';
		}

		return number_format($b/1024, 2, '.', ' ');
	}
}