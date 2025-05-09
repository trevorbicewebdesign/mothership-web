<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers');
HTMLHelper::addIncludePath(T3_PATH . '/html/com_content');
HTMLHelper::addIncludePath(dirname(dirname(__FILE__)));
if(version_compare(JVERSION, '4','lt')){
  HTMLHelper::_('behavior.caption'); 
}
?>
<div class="archive<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading')) : ?>
		<div class="page-header">
			<h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
		</div>
	<?php endif; ?>

	<form id="adminForm" action="<?php echo Route::_('index.php'); ?>" method="post" class="form-inline">
		<fieldset class="filters">
			<div class="filter-search form-group">
				<?php if ($this->params->get('filter_field') !== 'hide') : ?>
					<div class="form-group">
						<input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->filter); ?>" class="form-control col-sm-2" onchange="document.getElementById('adminForm').submit();"  placeholder="<?php echo Text::_('COM_CONTENT_TITLE_FILTER_LABEL'); ?>"/>
					</div>
				<?php endif; ?>

				<?php echo $this->form->monthField; ?>
				<?php echo $this->form->yearField; ?>
				<?php echo $this->form->limitField; ?>

			</div>
			<button type="submit" class="btn btn-primary"><?php echo Text::_('JGLOBAL_FILTER_BUTTON'); ?></button>
			<input type="hidden" name="view" value="archive"/>
			<input type="hidden" name="option" value="com_content"/>
			<input type="hidden" name="limitstart" value="0"/>
		</fieldset>

		<?php echo $this->loadTemplate('items'); ?>
	</form>
</div>
