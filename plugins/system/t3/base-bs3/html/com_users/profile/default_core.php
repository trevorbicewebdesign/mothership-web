<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>

<fieldset id="users-profile-core">
	<legend>
		<?php echo Text::_('COM_USERS_PROFILE_CORE_LEGEND'); ?>
	</legend>
	<dl class="dl-horizontal">
		<dt>
			<?php echo Text::_('COM_USERS_PROFILE_NAME_LABEL'); ?>
		</dt>
		<dd>
			<?php echo $this->data->name; ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_USERS_PROFILE_USERNAME_LABEL'); ?>
		</dt>
		<dd>
			<?php echo htmlspecialchars($this->data->username, ENT_COMPAT, 'UTF-8'); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_USERS_PROFILE_REGISTERED_DATE_LABEL'); ?>
		</dt>
		<dd>
			<?php echo HTMLHelper::_('date', $this->data->registerDate, Text::_('DATE_FORMAT_LC1')); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_USERS_PROFILE_LAST_VISITED_DATE_LABEL'); ?>
		</dt>

		<?php if ($this->data->lastvisitDate != $this->db->getNullDate()) : ?>
			<dd>
				<?php echo HTMLHelper::_('date', $this->data->lastvisitDate, Text::_('DATE_FORMAT_LC1')); ?>
			</dd>
		<?php else : ?>
			<dd>
				<?php echo Text::_('COM_USERS_PROFILE_NEVER_VISITED'); ?>
			</dd>
		<?php endif; ?>

	</dl>
</fieldset>
