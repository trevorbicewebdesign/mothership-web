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

class RsfirewallControllerException extends FormController
{
	protected function allowAdd($data = array())
	{
		return Factory::getUser()->authorise('exceptions.manage', 'com_rsfirewall');
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		return Factory::getUser()->authorise('exceptions.manage', 'com_rsfirewall');
	}
}