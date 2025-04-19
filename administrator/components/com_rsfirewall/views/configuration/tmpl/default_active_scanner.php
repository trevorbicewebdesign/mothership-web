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
if (isset($this->fieldset->description) && !empty($this->fieldset->description)) { ?>
	<div class="alert alert-info"><i class="icon-lightbulb icon-lamp"></i> <?php echo Text::_($this->fieldset->description); ?></div>
<?php } ?>
<?php if (!$this->config->get('active_scanner_status')) { ?>
    <div class="com-rsfirewall-not-ok-text"><p><i class="icon-cancel"></i> <?php echo Text::_('COM_RSFIREWALL_ACTIVE_SCANNER_IS_DISABLED'); ?></p></div>
<?php } else { ?>
	<div class="com-rsfirewall-ok-text"><p><i class="icon-checkmark"></i> <?php echo Text::_('COM_RSFIREWALL_ACTIVE_SCANNER_IS_ENABLED'); ?></p></div>
<?php } ?>
<?php
foreach ($this->fields as $field)
{
	echo $this->form->renderField($field->fieldname);
}