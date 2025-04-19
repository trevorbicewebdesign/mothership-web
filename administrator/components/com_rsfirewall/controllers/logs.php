<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsfirewallControllerLogs extends AdminController
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$user = Factory::getUser();
		if (!$user->authorise('logs.view', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
	}
	
	public function getModel($name = 'Log', $prefix = 'RsfirewallModel', $config = array('ignore_request' => true)) {
		return parent::getModel($name, $prefix, $config);
	}
	
	public function truncate() {
		$this->checkToken();
		
		$model = $this->getModel();
		$model->truncate();
		
		$this->setRedirect('index.php?option=com_rsfirewall&view=logs', Text::_('COM_RSFIREWALL_LOG_EMPTIED'));
	}
	
	public function download() {
		$this->checkToken();
		
		$model 		= $this->getModel('Logs');
		$app		= Factory::getApplication();
		$document 	= $app->getDocument();
		try {
			if (is_callable(array($document, 'setMimeEncoding')))
			{
				$document->setMimeEncoding('text/csv');
			}
			
			@ob_end_clean();
			
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: public');
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Description: File Transfer');
			header('Content-Disposition: attachment; filename="rsfirewall_logs_'.Factory::getDate()->format('Y-m-d-H-i', true).'.csv"');
			
			$model->toCSV();
			
			$app->close();
		} catch (Exception $e) {
			$app->enqueueMessage($e->getMessage(), 'error');
			$this->setRedirect('index.php?option=com_rsfirewall&view=logs');
		}
	}
	
	public function addToBlacklist() {
		$this->addToList(0);
	}
	public function addToWhitelist() {
		$this->addToList(1);
	}
	
	public function blockAjax() {
		$id = Factory::getApplication()->input->getInt('id');

		// Grab IPs from the database
		$data = $this->getModel()->prepareData(array($id));
		
		// Build response
		$response = new stdClass();
		$response->type 	= 0;
		$response->result 	= true;
		
		if ($data) {
			$model = $this->getModel('list');
			$entry = array(
				'type' 	=> 0,
				'ip' 	=> trim($data[0]),
			);
			if (!$model->save($entry)) {
				$response->result = false;
				$response->error = $model->getError();
			} else {
				$response->listId = $model->getState($model->getName() . '.id');
			}
		}
		
		$this->showResponse(true, $response);
		
	}
	
	public function unBlockAjax() {
		$listId = Factory::getApplication()->input->getInt('listId');
		$model  = $this->getModel('list');
		
		$response = new stdClass();
		$response->type 	= 1;
		$response->result 	= true;
		if (!$model->delete($listId)) {
			$response->result = false;
			$response->error = Text::_('COM_RSFIREWALL_ERROR_UNBLOCK');
		}
		
		$this->showResponse(true, $response);
	}
	
	public function addToList($type) {
		$app 	= Factory::getApplication();
		$cid 	= $app->input->get('cid', array(), 'array');

		// Grab IPs from the database
		$data = $this->getModel()->prepareData($cid);
		
		$added = 0;
		foreach ($data as $ip) {
			$model = $this->getModel('list');

			$entry = array(
				'type' 	=> $type,
				'ip' 	=> trim($ip),
			); 
			
			if (!$model->save($entry)) {
				$app->enqueueMessage($model->getError(), 'error');
			} else {
				$added++;
			}
		
		}
		
		$this->setMessage(Text::sprintf('COM_RSFIREWALL_ADD_FROM_LOG_ITEM_SAVED_OK', $added));
		$this->setRedirect('index.php?option=com_rsfirewall&view=logs');
	}

	public function getStatistics()
	{
		$model = $this->getModel('Logs');
		$data  = $model->getBlockedIps();

		if (empty($data))
		{
			$data = new stdClass();
		}

		$this->showResponse(true, $data);
	}

	protected function showResponse($success, $data=null) {
		// set JSON encoding
		Factory::getApplication()->getDocument()->setMimeEncoding('application/json');
		
		// compute the response
		$response = new stdClass();
		$response->success = $success;
		if ($data) {
			$response->data = $data;
		}
		
		// show the response
		echo json_encode($response);
		
		// close
		Factory::getApplication()->close();
	}
}