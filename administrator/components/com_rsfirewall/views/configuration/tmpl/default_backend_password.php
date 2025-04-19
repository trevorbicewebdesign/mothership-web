<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

// set description if required
if (isset($this->fieldset->description) && !empty($this->fieldset->description)) { ?>
	<div class="alert alert-info"><i class="icon-lightbulb icon-lamp"></i> <?php echo Text::_($this->fieldset->description); ?></div>
<?php } ?>
<?php if (!$this->config->get('backend_password')) { ?>
	<div class="com-rsfirewall-not-ok-text"><p><i class="icon-cancel"></i> <?php echo Text::_('COM_RSFIREWALL_BACKEND_PASSWORD_IS_NOT_SET'); ?></p></div>
<?php } else { ?>
	<div class="com-rsfirewall-ok-text"><p><i class="icon-checkmark"></i> <?php echo Text::_('COM_RSFIREWALL_BACKEND_PASSWORD_IS_SET'); ?><a class="btn btn-danger" href="index.php?option=com_rsfirewall&task=configuration.clearpassword&<?php echo Session::getFormToken(); ?>=1"><?php echo Text::_('COM_RSFIREWALL_BACKEND_PASSWORD_CLEAR'); ?></a></p></div>
<?php } ?>
<?php
foreach ($this->fields as $field)
{
	echo $this->form->renderField($field->fieldname);
}