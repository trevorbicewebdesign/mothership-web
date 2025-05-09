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

?>
			<dd class="published hasTooltip" title="<?php echo Text::sprintf('COM_CONTENT_PUBLISHED_DATE_ON', ''); ?>">
				<i class="fa fa-calendar"></i>
				<time datetime="<?php echo HTMLHelper::_('date', $displayData['item']->publish_up, 'c'); ?>" itemprop="datePublished">
					<?php echo HTMLHelper::_('date', $displayData['item']->publish_up, Text::_('DATE_FORMAT_LC3')); ?>
          <meta  itemprop="datePublished" content="<?php echo HTMLHelper::_('date', $displayData['item']->publish_up, 'c'); ?>" />
          <meta  itemprop="dateModified" content="<?php echo HTMLHelper::_('date', $displayData['item']->publish_up, 'c'); ?>" />
				</time>
			</dd>
