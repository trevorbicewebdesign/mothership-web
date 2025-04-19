<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

FormHelper::loadFieldClass('text');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\TextField', 'JFormFieldText');
}

class JFormFieldBackendParameter extends TextField
{
	protected function getInput()
	{
		return parent::getInput() . '<div class="alert alert-info" id="backend_password_placeholder_container"><p>' . Text::_('COM_RSFIREWALL_BACKEND_PASSWORD_EXAMPLE_PREFILL') . '<br><strong>' . htmlspecialchars(Uri::root() . 'administrator/?', ENT_QUOTES, 'utf-8') . '<span id="backend_password_placeholder"></span></strong></p></div>';
	}
}