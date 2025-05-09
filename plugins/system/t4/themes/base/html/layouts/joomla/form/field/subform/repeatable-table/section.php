<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Make thing clear
 *
 * @var JForm   $form       The form instance for render the section
 * @var string  $basegroup  The base group name
 * @var string  $group      Current group name
 * @var array   $buttons    Array of the buttons that will be rendered
 */
extract($displayData);

?>

<tr
	class="subform-repeatable-group"
	data-base-name="<?php echo $basegroup; ?>"
	data-group="<?php echo $group; ?>"
>
	<?php foreach ($form->getGroup('') as $field) : ?>
	<td data-column="<?php echo strip_tags($field->label); ?>">
		<?php echo $field->renderField(array('hiddenLabel' => true)); ?>
	</td>
	<?php endforeach; ?>
	<?php if (!empty($buttons)) : ?>
	<td>
		<div class="btn-group">
			<?php if (!empty($buttons['add'])) : ?>
				<a class="btn btn-mini button btn-success group-add" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
					<span class="icon-plus" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
			<?php if (!empty($buttons['remove'])) : ?>
				<a class="btn btn-mini button btn-danger group-remove" aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>">
					<span class="icon-minus" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
			<?php if (!empty($buttons['move'])) : ?>
				<a class="btn btn-mini button btn-primary group-move" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
					<span class="icon-move" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		</div>
	</td>
	<?php endif; ?>
</tr>
