<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;

class JFormRuleRsfirewallregex extends FormRule
{
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
		if ($input->get('regex'))
		{
			preg_match('/' . $value . '/', '');
			if (preg_last_error() !== PREG_NO_ERROR)
			{
				$element->addAttribute('message', Text::sprintf('COM_RSFIREWALL_EXCEPTION_REGEX_INCORRECT', '/' . $value . '/'));
				return false;
			}
		}

		return true;
	}
}