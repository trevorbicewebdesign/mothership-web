<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers');
$params = $this->params;

$info    = $params->get('info_block_position', 2);
$aInfo1 = ($params->get('show_publish_date') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author'));
$aInfo2 = ($params->get('show_create_date') || $params->get('show_modify_date') || $params->get('show_hits'));
$topInfo = ($aInfo1 && $info != 1) || ($aInfo2 && $info == 0);
$botInfo = ($aInfo1 && $info == 1) || ($aInfo2 && $info != 0);
$icons = $params->get('access-edit') || $params->get('show_print_icon') || $params->get('show_email_icon');

?>

<div id="archive-items">
	<?php foreach ($this->items as $i => $item) : ?>
		<article class="row<?php echo $i % 2; ?>" itemscope itemtype="http://schema.org/Article">

			<?php echo LayoutHelper::render('joomla.content.item_title', array('item' => $item, 'params' => $params, 'title-tag'=>'h2')); ?>

      <?php // Content is generated by content plugin event "onContentAfterTitle" ?>
      <?php echo $item->event->afterDisplayTitle; ?>

	    <!-- Aside -->
	    <?php if ($topInfo || $icons) : ?>
	    <aside class="article-aside clearfix">
	      <?php if ($topInfo): ?>
	      <?php echo LayoutHelper::render('joomla.content.info_block.block', array('item' => $item, 'params' => $params, 'position' => 'above')); ?>
	      <?php endif; ?>
	      
	      <?php if ($icons): ?>
	      <?php echo LayoutHelper::render('joomla.content.icons', array('item' => $item, 'params' => $params)); ?>
	      <?php endif; ?>
	    </aside>  
	    <?php endif; ?>
	    <!-- //Aside -->

      <?php // Content is generated by content plugin event "onContentBeforeDisplay" ?>
      <?php echo $item->event->beforeDisplayContent; ?>

			<?php if ($params->get('show_intro')) :?>
				<div class="intro" itemprop="articleBody"> <?php echo HTMLHelper::_('string.truncateComplex', $item->introtext, $params->get('introtext_limit')); ?> </div>
			<?php endif; ?>

    <!-- footer -->
    <?php if ($botInfo) : ?>
    <footer class="article-footer clearfix">
      <?php echo LayoutHelper::render('joomla.content.info_block.block', array('item' => $item, 'params' => $params, 'position' => 'below')); ?>
    </footer>
    <?php endif; ?>
    <!-- //footer -->

    <?php // Content is generated by content plugin event "onContentAfterDisplay" ?>
    <?php echo $item->event->afterDisplayContent; ?>

		</article>
	<?php endforeach; ?>
</div>

<?php 
$pagesTotal = isset($this->pagination->pagesTotal) ? $this->pagination->pagesTotal : $this->pagination->get('pages.total');
if ($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2 && $pagesTotal > 1)) : ?>
  <nav class="pagination-wrap clearfix">

    <?php if ($this->params->def('show_pagination_results', 1)) : ?>
      <div class="counter">
        <?php echo $this->pagination->getPagesCounter(); ?>
      </div>
    <?php  endif; ?>
        <?php echo $this->pagination->getPagesLinks(); ?>
  </nav>
<?php endif; ?>