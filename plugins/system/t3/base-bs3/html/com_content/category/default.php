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

if (!class_exists('ContentHelperRoute')) {
  if (version_compare(JVERSION, '4', 'ge')) {
    abstract class ContentHelperRoute extends \Joomla\Component\Content\Site\Helper\RouteHelper
    {
    };
  } else {
    JLoader::register('ContentHelperRoute', $com_path . '/helpers/route.php');
  }
}

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers');
if (version_compare(JVERSION, '4', 'lt')) {
  HTMLHelper::_('behavior.caption');
}
?>
<div class="category-list<?php echo $this->pageclass_sfx; ?>">

  <?php
  $this->subtemplatename = 'articles';
  echo LayoutHelper::render('joomla.content.category_default', $this);
  ?>

</div>