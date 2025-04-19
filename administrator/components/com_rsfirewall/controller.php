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
use Joomla\CMS\Language\Text;

class RsfirewallController extends BaseController
{
	public function acceptModifiedFiles()
	{
		$this->checkToken();
		
		$input = Factory::getApplication()->input;
		$cid   = $input->get('cid', array(), 'array');
		$cid = array_map('intval', $cid);

		if ($cid)
		{
			$model = $this->getModel('rsfirewall');
			$model->acceptModifiedFiles($cid);

			$this->setMessage(Text::_('COM_RSFIREWALL_HASH_CHANGED_SUCCESS'));
		}
		
		$this->setRedirect('index.php?option=com_rsfirewall');
	}
	
	protected function showResponse($success, $data=null)
	{
		$app 		= Factory::getApplication();
		$document 	= $app->getDocument();
		
		// set JSON encoding
		$document->setMimeEncoding('application/json');
		
		// compute the response
		$response = new stdClass();
		$response->success = $success;
		if ($data) {
			$response->data = $data;
		}
		
		// show the response
		echo json_encode($response);
		
		// close
		$app->close();
	}
	
	public function getLatestJoomlaVersion()
	{
		$model 		= $this->getModel('check');
		$data  		= new stdClass();
		$success 	= true;

		if (!($result = $model->checkJoomlaVersion()))
		{
			$success = false;
			$data->message = $model->getError();
		}
		else
		{
			list($current, $latest, $is_latest) = $result;
			$data->current = $current;
			$data->latest = $latest;
			$data->is_latest = $is_latest;

			if ($model->isAlpha())
			{
				$data->is_latest = false;
				$data->message = Text::sprintf('COM_RSFIREWALL_JOOMLA_VERSION_ALPHA', $current);
			}
			elseif (version_compare($current, '4.0', '<'))
			{
				$data->is_latest = false;
				$data->message = Text::sprintf('COM_RSFIREWALL_JOOMLA_VERSION_3', $current);
			}
			elseif ($is_latest)
			{
				$data->message = Text::sprintf('COM_RSFIREWALL_JOOMLA_VERSION_OK', $current);
			}
			else
			{
				$data->message = Text::sprintf('COM_RSFIREWALL_JOOMLA_VERSION_NOT_OK', $current, $latest);
				$data->details = Text::_('COM_RSFIREWALL_JOOMLA_VERSION_DETAILS');
			}
		}

		$this->showResponse($success, $data);
	}
	
	public function getLatestFirewallVersion()
	{
		$model = $this->getModel('check');
		$data  = new stdClass();
		if ($response = $model->checkRSFirewallVersion())
		{
			$success = true;
			list($data->current, $data->latest, $data->is_latest) = $response;
		}
		else
		{
			// error
			$success = false;
			$data->message = $model->getError();
		}
		
		$this->showResponse($success, $data);
	}
}