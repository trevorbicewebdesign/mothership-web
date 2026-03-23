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

class RsfirewallControllerDbcheck extends BaseController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		if (!Factory::getUser()->authorise('dbcheck.run', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
	}
	
	public function optimize()
	{
		$app 	= Factory::getApplication();
		$model 	= $this->getModel('DbCheck');
		
		if (!($result = $model->optimizeTables()))
		{
			echo $model->getError();
		}
		else
		{
			echo Text::sprintf('COM_RSFIREWALL_OPTIMIZE_REPAIR_RESULT', $result['optimize'], $result['repair']);
		}
		
		$app->close();
	}
}