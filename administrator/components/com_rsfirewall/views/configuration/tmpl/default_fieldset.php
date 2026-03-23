<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

// set description if required
if (!empty($this->fieldset->description)) { ?>
	<div class="alert alert-info"><i class="icon-lightbulb icon-lamp"></i> <?php echo Text::_($this->fieldset->description); ?></div>
<?php } ?>
<?php
foreach ($this->fields as $field)
{
	echo $this->form->renderField($field->fieldname);
}