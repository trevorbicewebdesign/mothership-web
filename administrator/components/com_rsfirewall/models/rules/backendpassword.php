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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class JFormRuleBackendpassword extends FormRule
{
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
		// If the field is empty and not required, the field is valid.
		$required = ((string) $element['required'] === 'true' || (string) $element['required'] === 'required');

		$minimumLength = 6;

		if (!$required && empty($value))
		{
			return true;
		}

		$valueLength = strlen($value);

		// We don't allow white space inside passwords
		$valueTrim = trim($value);

		// Set a variable to check if any errors are made in password
		$validPassword = true;

		if (strlen($valueTrim) !== $valueLength)
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_RSFIREWALL_MSG_SPACES_IN_PASSWORD'),
				'warning'
			);

			$validPassword = false;
		}

		if (strlen((string) $value) < $minimumLength)
		{
			Factory::getApplication()->enqueueMessage(
				Text::plural('COM_RSFIREWALL_MSG_PASSWORD_TOO_SHORT_N', $minimumLength),
				'warning'
			);

			$validPassword = false;
		}

		return $validPassword;
	}
}