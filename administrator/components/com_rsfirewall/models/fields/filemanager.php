<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class JFormFieldFileManager extends FormField
{
	public $type = 'FileManager';

	protected function getInput()
	{
		$html  = '';
		
		// textarea
		$columns = $this->element['cols'] ? ' cols="' . (int) $this->element['cols'] . '"' : '';
		$rows = $this->element['rows'] ? ' rows="' . (int) $this->element['rows'] . '"' : '';
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		
		// file manager
		$allowfolders 	= !empty($this->element['allowfolders']) ? 1 : 0;
		$allowfiles 	= !empty($this->element['allowfiles']) ? 1 : 0;

		// Do we have an array?
		$value = is_array($this->value) ? implode("\n", $this->value) : $this->value;
		
		$html .= '<div class="com-rsfirewall-file-manager-box">'."\n";
		$html .= '<button type="button" data-name="' . $this->escape($this->fieldname) . '" data-allowfolders="' . $allowfolders . '" data-allowfiles="' . $allowfiles . '" class="com-rsfirewall-filemanager btn btn-secondary">'.Text::_($this->element['button']).'</button>'."\n";
		$html .= '<span class="com-rsfirewall-clear"></span>';
		$html .= '<textarea name="'.$this->name.'" id="'.$this->id.'"'.$columns.$rows.$class.$disabled.'>'.$this->escape($value).'</textarea>'."\n";
		$html .= '</div>'."\n";
		
		return $html;
	}
	
	protected function escape($string) {
		return htmlentities($string, ENT_COMPAT, 'utf-8');
	}
}