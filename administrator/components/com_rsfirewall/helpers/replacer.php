<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

abstract class RSFirewallReplacer
{
	protected static $emails = false;

	public static function addCaptcha(&$buffer)
	{
		if (!class_exists('RSFirewallCaptcha'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/captcha.php';
		}

		try
		{
			$captcha = new RSFirewallCaptcha();
			$data = $captcha->getImage();

			if (!$data)
			{
				throw new Exception(Text::_('COM_RSFIREWALL_CAPTCHA_IMAGE_COULD_NOT_BE_GENERATED'));
			}

			// Load the Text field
			$field = FormHelper::loadFieldType('text');
			$form = new Form('rsfirewalldummyform');
			$field->setForm($form);

			// Prepare the XML
			$xml = new SimpleXMLElement('<field name="rsf_backend_captcha" autocomplete="off" type="text" label="COM_RSFIREWALL_CAPTCHA_SECURITY" hint="COM_RSFIREWALL_PLEASE_ENTER_THE_IMAGE_CODE" />');

			// Setup the field
			$field->setup($xml, '');

			// Render the image
			$image = '<img src="data:image/jpeg;base64,' . $data . '" alt="Captcha" />';

			if (version_compare(JVERSION, '4.0', '>='))
			{
				// Joomla! 4
				$find       = '<div class="form-group">';
				$position   = strrpos($buffer, $find);

				if ($position !== false)
				{
					$image = str_replace('<img ', '<img style="height: auto; max-height: auto;" ', $image);

					$html = '<div class="form-group text-center">' . $image . '</div>';
					$html .= str_replace('control-group', 'form-group', $field->renderField());

					$buffer = substr_replace($buffer, $html . $find, $position, strlen($find));
					return true;
				}
			}
			else
			{
				// Joomla! 3
				$find       = '<div class="control-group">';
				$position   = strrpos($buffer, $find);

				if ($position !== false)
				{
					$html = '<div class="control-group center">' . $image . '</div>';
					$html .= $field->renderField(array('class' => 'center'));

					$buffer = substr_replace($buffer, $html . $find, $position, strlen($find));
					return true;
				}
			}
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		return false;
	}
}