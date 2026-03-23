<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<div class="table-responsive">
<table class="table table-striped">
<thead>
	<tr>
		<th width="1%" nowrap="nowrap"><?php echo Text::_('#'); ?></th>
		<th style="width:1%" class="text-center">
			<?php echo HTMLHelper::_('grid.checkall'); ?>
		</th>
		<th width="1%"><?php echo Text::_('COM_RSFIREWALL_FILES_MODIFIED_DATE'); ?></th>
		<th><?php echo Text::_('COM_RSFIREWALL_FILES_FILE_PATH'); ?></th>
		<th><?php echo Text::_('COM_RSFIREWALL_ORIGINAL_HASH'); ?></th>
		<th><?php echo Text::_('COM_RSFIREWALL_MODIFIED_HASH'); ?></th>
	</tr>
</thead>
<?php foreach ($this->files as $i => $file) { ?>
<tr>
	<td width="1%" nowrap="nowrap"><?php echo $i+1; ?></td>
	<td width="1%"><?php echo HTMLHelper::_('grid.id', $i, $file->id); ?></td>
	<td width="1%"><?php echo HTMLHelper::_('date', $file->date, 'Y-m-d H:i:s'); ?></td>
	<td><?php echo $this->escape($file->path); ?></td>
	<td width="1%"><?php echo $this->escape($file->hash); ?></td>
	<td width="1%" class="com-rsfirewall-level-high"><?php echo $file->modified_hash; ?></td>
</tr>
<?php } ?>
</table>
</div>
<button type="button" class="btn btn-primary" data-rsfirewall-task="acceptModifiedFiles"><?php echo Text::_('COM_RSFIREWALL_ACCEPT_CHANGES'); ?></button>
