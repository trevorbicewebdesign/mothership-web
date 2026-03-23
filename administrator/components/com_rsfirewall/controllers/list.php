<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsfirewallControllerList extends FormController
{
	protected function allowAdd($data = array())
	{
		return Factory::getUser()->authorise('lists.manage', 'com_rsfirewall');
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		return Factory::getUser()->authorise('lists.manage', 'com_rsfirewall');
	}
	
	public function bulkAdd()
	{
		$this->setRedirect('index.php?option=com_rsfirewall&view=list&layout=bulk');
	}
	
	public function bulkSave()
	{
		$this->checkToken();
		
		$app 	= Factory::getApplication();
		$input	= $app->input;
		$model 	= $this->getModel('list');
		
		$data = $input->get('jform', '', 'array');
		$ips  = isset($data['ips']) ? $data['ips'] : '';
		$ips  = $this->explode($ips);
		
		unset($data['ips']);
		$added = 0;
		foreach ($ips as $ip) {
			$data['ip'] = trim($ip);
			
			if (!$data['ip']) {
				continue;
			}
			
			if (!$model->save($data)) {
				$app->enqueueMessage($model->getError(), 'error');
			} else {
				$added++;
			}
		}
		
		$this->setMessage(Text::sprintf('COM_RSFIREWALL_BULK_ITEM_SAVED_OK', $added));
		$this->setRedirect('index.php?option=com_rsfirewall&view=lists');
	}
	
	protected function explode($string)
	{
		return explode("\n", str_replace(array("\r\n", "\r"), "\n", $string));
	}
}