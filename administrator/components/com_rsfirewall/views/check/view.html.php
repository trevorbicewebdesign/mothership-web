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
use Joomla\CMS\HTML\HTMLHelper;

class RsfirewallViewCheck extends HtmlView
{
	protected $accessFile;
	protected $defaultAccessFile;
	protected $isWindows;
	protected $offset;
	protected $lastRun;
	
	public function display($tpl = null)
	{
		if (!Factory::getUser()->authorise('check.run', 'com_rsfirewall')) {
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->addToolBar();
		
		// the access file depends on the OS we're in
		$this->accessFile 		 = $this->get('accessFile');
		$this->defaultAccessFile = $this->get('defaultAccessFile');
		
		// on Windows we need to skip a few things
		$this->isWindows = $this->get('isWindows');
		
		// is Xdebug loaded?
		$this->hasXdebug = extension_loaded('xdebug');
		
		$this->offset = $this->get('Offset');
		$this->config = RSFirewallConfig::getInstance();
		
		// Last time the System Check was run
		$this->lastRun = $this->config->get('system_check_last_run');
		
		// Prettify
		if ($this->lastRun) {
			$this->lastRun = HTMLHelper::_('date.relative', $this->lastRun);
		} else {
			$this->lastRun = Text::_('COM_RSFIREWALL_NEVER');
		}

		$this->PHPini = $this->get('PHPini');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		ToolbarHelper::title('RSFirewall!', 'rsfirewall');

		RSFirewallToolbarHelper::addToolbar('check');
	}

	protected function getSystemCheckSteps()
	{
		// Build steps array
		$steps = array('checkJoomlaVersion',
			'checkRSFirewallVersion',
			'checkSQLPassword',
			'checkAdminUser',
			'checkFTPPassword',
			'checkSEFEnabled',
			'checkConfigurationIntegrity',
			'checkSession',
			'checkTemporaryFiles',
			'checkHtaccess',
			'checkBackendPassword');

		if (in_array('safebrowsing', $this->config->get('google_apis')))
		{
			$steps[] = 'checkGoogleSafeBrowsing';
		}

		if (in_array('webrisk', $this->config->get('google_apis')))
		{
			$steps[] = 'checkGoogleWebRisk';
		}

		return $steps;
	}

	protected function getFilesCheckSteps()
	{
		$steps = array();

		$steps[] = 'checkCoreFilesIntegrity';
		if (!$this->isWindows)
		{
			$steps[] = 'checkFolderPermissions';
			$steps[] = 'checkFilePermissions';
		}
		$steps[] = 'checkSignatures';

		return $steps;
	}
}