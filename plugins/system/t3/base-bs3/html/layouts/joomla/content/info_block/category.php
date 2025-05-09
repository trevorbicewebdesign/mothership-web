<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$item = $displayData['item'];
$title = $this->escape($item->category_title);
if (!isset($item->catslug)) {
	$item->catslug = $item->category_alias ? ($item->catid.':'.$item->category_alias) : $item->catid;
}
?>
			<dd class="category-name hasTooltip" title="<?php echo Text::sprintf('COM_CONTENT_CATEGORY', ''); ?>">
				<i class="fa fa-folder-open"></i>
				<?php if ($displayData['params']->get('link_category') && $item->catslug) : ?>
					<?php echo HTMLHelper::_('link', Route::_(ContentHelperRoute::getCategoryRoute($item->catslug)), '<span itemprop="genre">'.$title.'</span>'); ?>
				<?php else : ?>
					<span itemprop="genre"><?php echo $title ?></span>
				<?php endif; ?>
			</dd>