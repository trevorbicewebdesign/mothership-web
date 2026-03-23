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

class RsfirewallControllerIgnored extends BaseController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$user = Factory::getUser();
		if (!$user->authorise('check.run', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
	}

	public function removeFromIgnored()
	{
		$app       = Factory::getApplication();
		$model     = $this->getModel('ignored');
		$id      = $app->input->get('ignoredFileId', '', 'path');

		$model->remove($id);

		$app->close();
	}
}