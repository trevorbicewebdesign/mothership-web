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

class RsfirewallController extends BaseController
{
	public function display($cachable = false, $urlparams = false)
	{
		Factory::getApplication()->input->set('view', 'rsfirewall');

		parent::display($cachable, $urlparams);
	}
}