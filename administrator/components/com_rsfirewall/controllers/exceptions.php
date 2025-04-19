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
use Joomla\CMS\Uri\Uri;

class RsfirewallControllerExceptions extends AdminController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		if (!Factory::getUser()->authorise('exceptions.manage', 'com_rsfirewall'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect('index.php?option=com_rsfirewall');
		}
		
		$this->registerTask('trash', 'delete');
	}
	
	public function getModel($name = 'Exception', $prefix = 'RsfirewallModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function download()
	{
		$this->checkToken();

		$model 		= $this->getModel('Exceptions');
		$app		= Factory::getApplication();
		$document 	= $app->getDocument();

		try
		{
			if (is_callable(array($document, 'setMimeEncoding')))
			{
				$document->setMimeEncoding('application/json');
			}

			@ob_end_clean();

			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: public');
			header('Content-Type: application/json; charset=utf-8');
			header('Content-Description: File Transfer');
			header('Content-Disposition: attachment; filename="exceptions_'.Uri::getInstance()->getHost().'.json"');

			$model->toJson();

			$app->close();
		}
		catch (Exception $e)
		{
			$app->enqueueMessage($e->getMessage(), 'error');
			$this->setRedirect('index.php?option=com_rsfirewall&view=exceptions');
		}
	}
}