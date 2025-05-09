<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_breadcrumbs
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

if (version_compare(JVERSION, '3.0', 'ge')) {	
	HTMLHelper::_('bootstrap.tooltip');
}
$moduleclass_sfx = $params->get('moduleclass_sfx','');
?>

<ol class="breadcrumb <?php echo $moduleclass_sfx; ?>">
	<?php
	if ($params->get('showHere', 1)) {
		echo '<li class="active">' . Text::_('MOD_BREADCRUMBS_HERE') . '&#160;</li>';
	} else {
		echo '<li class="active"><span class="hasTooltip"><i class="fa fa-map-marker" data-toggle="tooltip" title="' . Text::_('MOD_BREADCRUMBS_HERE') . '"></i></span></li>';
	}

	// Get rid of duplicated entries on trail including home page when using multilanguage
	for ($i = 0; $i < $count; $i++) {
		if ($i === 1 && !empty($list[$i]->link) && !empty($list[$i - 1]->link) && $list[$i]->link == $list[$i - 1]->link) {
			unset($list[$i]);
		}
	}
	// Find last and penultimate items in breadcrumbs list
	end($list);
	$last_item_key = key($list);
	prev($list);
	$penult_item_key = key($list);

	// Generate the trail
	foreach ($list as $key => $item) :
		// Make a link if not the last item in the breadcrumbs
		$show_last = $params->get('showLast', 1);
	
		if ($key != $last_item_key) {
			// Render all but last item - along with separator
			echo '<li>';
			if (!empty($item->link)) {
				echo '<a href="' . $item->link . '" class="pathway">' . $item->name . '</a>';
			} else {
				echo '<span>' . $item->name . '</span>';
			}

			if ((($key != $penult_item_key) || $show_last) && !empty($separator)) {
				echo '<span class="divider">' . $separator . '</span>';
			}

			echo '</li>';
		} elseif ($show_last) {
			// Render last item if reqd.
			echo '<li>';
			echo '<span>' . $item->name . '</span>';
			echo '</li>';
		}
	endforeach; ?>
</ol>
