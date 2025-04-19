<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

class RsfirewallControllerConfiguration extends BaseController
{
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$user = Factory::getUser();
		if (!$user->authorise('core.admin', 'com_rsfirewall')) {
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->registerTask('apply', 'save');
	}

	public function clearPassword()
	{
		$this->checkToken('get');

		$config = RSFirewallConfig::getInstance();
		$config->set('backend_password_enabled', 0);
		$config->set('backend_password', '');

		$this->setMessage(Text::_('COM_RSFIREWALL_BACKEND_PASSWORD_HAS_BEEN_CLEARED_SUCCESSFULLY'), 'success');
		$this->setRedirect('index.php?option=com_rsfirewall&view=configuration');
	}
	
	public function cancel() {
		$this->checkToken();
		
		$this->setRedirect('index.php?option=com_rsfirewall');
	}
	
	public function export() {
		$this->checkToken();
		
		$model 		= $this->getModel('configuration');
		$document 	= Factory::getApplication()->getDocument();
		
		if (is_callable(array($document, 'setMimeEncoding'))) {
			$document->setMimeEncoding('application/json');
		}
		
		@ob_end_clean();
		
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: public');
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="configuration_'.Uri::getInstance()->getHost().'.json"');
		
		echo $model->toJSON();
		
		Factory::getApplication()->close();
	}

	public function downloadGeoIPDatabase(){
		$model 		= $this->getModel('Configuration');

		echo json_encode($model->downloadGeoIPDatabase(Factory::getApplication()->input->getString('license_key')));

		jexit();
	}


	public function save() {
		$this->checkToken();
		
		$app   = Factory::getApplication();
		$data  = $app->input->get('jform', array(), 'array');
		$model = $this->getModel('configuration');
		$form  = $model->getForm();
		
		// Validate the posted data.
		$return = $model->validate($form, $data);
		
		// Check for validation errors.
		if ($return === false) {
			// Get the validation messages.
			$errors	= $model->getErrors();
			
			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
				if ($errors[$i] instanceof Exception) {
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				} else {
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Redirect back to the edit screen.
			$this->setRedirect('index.php?option=com_rsfirewall&view=configuration');
			return false;
		}
		
		$data = $return;
		
		if (!$model->save($data)) {
			$this->setMessage($model->getError(), 'error');
		} else {
			$this->setMessage(Text::_('COM_RSFIREWALL_CONFIGURATION_SAVED'));
		}
		
		$task = $this->getTask();
		if ($task == 'save') {
			$this->setRedirect('index.php?option=com_rsfirewall');
		} elseif ($task == 'apply') {
			$this->setRedirect('index.php?option=com_rsfirewall&view=configuration');
		}
	}
}