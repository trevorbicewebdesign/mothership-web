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

class RsfirewallControllerDiff extends BaseController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		if (!Factory::getUser()->authorise('check.run', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
	}

	public function download()
	{
		$app       = Factory::getApplication();
		$model     = $this->getModel('diff');
		$localFile = $app->input->get('localFile', '', 'path');

		$model->downloadOriginalFile($localFile);

		$app->close();
	}
}