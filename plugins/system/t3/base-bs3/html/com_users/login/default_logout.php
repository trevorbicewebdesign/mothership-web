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
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

?>
<div class="logout <?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading')) : ?>
	<div class="page-header">
		<h1>
			<?php echo $this->escape($this->params->get('page_heading')); ?>
		</h1>
	</div>
	<?php endif; ?>

	<?php if (($this->params->get('logoutdescription_show','') == 1 && str_replace(' ', '', $this->params->get('logout_description','')) != '')|| $this->params->get('logout_image') != '') : ?>
	<div class="logout-description">
	<?php endif; ?>

		<?php if ($this->params->get('logoutdescription_show') == 1) : ?>
			<?php echo $this->params->get('logout_description'); ?>
		<?php endif; ?>

		<?php if ($this->params->get('logout_image') != '') : ?>
			<img src="<?php echo $this->escape($this->params->get('logout_image')); ?>" class="thumbnail pull-right logout-image" alt="<?php echo Text::_('COM_USER_LOGOUT_IMAGE_ALT'); ?>"/>
		<?php endif; ?>

	<?php if (($this->params->get('logoutdescription_show','') == 1 && str_replace(' ', '', $this->params->get('logout_description','')) != '')|| $this->params->get('logout_image') != '') : ?>
	</div>
	<?php endif; ?>

	<form action="<?php echo Route::_('index.php?option=com_users&task=user.logout'); ?>" method="post" class="form-horizontal">
		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">
					<span class="fa fa-arrow-left"></span>
					<?php echo Text::_('JLOGOUT'); ?>
				</button>
			</div>
		</div>
    
		<?php if ($this->params->get('logout_redirect_url')) : ?>
			<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('logout_redirect_url', $this->form->getValue('return'))); ?>" />
		<?php else : ?>
			<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('logout_redirect_menuitem', $this->form->getValue('return'))); ?>" />
		<?php endif; ?>
    
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
