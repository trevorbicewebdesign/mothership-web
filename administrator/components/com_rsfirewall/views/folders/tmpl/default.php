<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('script', 'com_rsfirewall/filemanager.js', array('relative' => true, 'version' => 'auto'));

$params = array();

if ($this->name)
{
	$params['name'] = $this->name;
}

if ($this->allowFolders)
{
	$params['allowfolders'] = 1;
}

if ($this->allowFiles)
{
	$params['allowfiles'] = 1;
}

if ($params)
{
	$params = '&' . http_build_query($params);
}
else
{
	$params = '';
}

$canAdd = $this->name && ($this->allowFiles || $this->allowFolders);
?>
<div id="com-rsfirewall-explorer">
	<p>
		<?php if ($canAdd) { ?>
		<button data-name="<?php echo htmlspecialchars($this->name); ?>" class="com-rsfirewall-add-file btn btn-primary"><?php echo Text::_('COM_RSFIREWALL_ADD_SELECTED_ITEMS'); ?></button>
		<?php } ?>
		<button class="com-rsfirewall-window-close btn btn-secondary"><?php echo Text::_('COM_RSFIREWALL_CLOSE_FILE_MANAGER'); ?></button>
	</p>
	<div id="com-rsfirewall-explorer-header">
		<strong><?php echo Text::_('COM_RSFIREWALL_CURRENT_LOCATION'); ?></strong>
		<?php foreach ($this->elements as $element) { ?>
			<a href="<?php echo Route::_('index.php?option=com_rsfirewall&view=folders&tmpl=component'.$params.'&folder='.urlencode($element->fullpath)); ?>"><?php echo $this->escape($element->name); ?></a> <?php echo DIRECTORY_SEPARATOR; ?>
		<?php } ?>
	</div>
	<br/>
	<table class="com-rsfirewall-striped">
		<tr>
			<th></th>
			<th><?php echo Text::_('COM_RSFIREWALL_FOLDERS_OR_FILES'); ?></th>
			<th><?php echo Text::_('COM_RSFIREWALL_PERMISSIONS'); ?></th>
			<th><?php echo Text::_('COM_RSFIREWALL_SIZE'); ?></th>
		</tr>
		<?php if ($this->previous) { ?>
		<tr>
			<td nowrap class="com-rsfirewall-w-1">
				<?php if ($this->allowFolders) { ?>
					<input type="checkbox" disabled="disabled" />
				<?php } ?>
			</td>
			<td>
				<i class="icon-folder"></i>
				<a href="<?php echo Route::_('index.php?option=com_rsfirewall&view=folders&tmpl=component'.$params.'&folder='.urlencode($this->previous)); ?>">..</a>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<?php } ?>
		<?php foreach ($this->folders as $folder => $data) { ?>
			<?php $fullpath = $this->path.DIRECTORY_SEPARATOR.$folder; ?>
			<tr>
                <td nowrap class="com-rsfirewall-w-1">
					<?php if ($this->allowFolders) { ?>
					<input type="checkbox" name="cid[]" value="<?php echo $this->escape($fullpath); ?>" />
					<?php } ?>
				</td>
				<td>
					<i class="icon-folder"></i>
					<a href="<?php echo Route::_('index.php?option=com_rsfirewall&view=folders&tmpl=component'.$params.'&folder='.urlencode($fullpath)); ?>"><?php echo $this->escape($folder); ?></a>
				</td>
				<td><?php echo $data['octal']?> (<?php echo $data['full']?>)</td>
				<td>&nbsp;</td>
			</tr>
		<?php } ?>
		
		<?php
		$i = 0;
		foreach ($this->files as $file => $data) { ?>
			<?php $fullpath = $this->path.DIRECTORY_SEPARATOR.$file; ?>
			<tr>
                <td nowrap class="com-rsfirewall-w-1">
					<?php if ($this->allowFiles) { ?>
						<input type="checkbox" id="file<?php echo $i; ?>" name="cid[]" value="<?php echo $this->escape($fullpath); ?>" />
					<?php } ?>
				</td>
				<td>
                    <i class="icon-file"></i>
					<label for="file<?php echo $i; ?>"><?php echo $this->escape($file); ?></label>
				</td>
				<td><?php echo $data['octal']?> (<?php echo $data['full']?>)</td>
				<td><?php echo $data['filesize']?></td>
			</tr>
		<?php 
			$i++;
		} 
		?>
	</table>
	<p>
	<?php if ($canAdd) { ?>
		<button data-name="<?php echo htmlspecialchars($this->name); ?>" class="com-rsfirewall-add-file btn btn-primary"><?php echo Text::_('COM_RSFIREWALL_ADD_SELECTED_ITEMS'); ?></button>
	<?php } ?>
	<button class="com-rsfirewall-window-close btn btn-secondary"><?php echo Text::_('COM_RSFIREWALL_CLOSE_FILE_MANAGER'); ?></button>
	</p>
</div>