<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_breadcrumbs
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;

?>
<nav role="navigation" aria-label="<?php echo htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8'); ?>">
	<ol itemscope itemtype="https://schema.org/BreadcrumbList" class="mod-breadcrumbs breadcrumb">
		<?php if ($params->get('showHere', 1)) : ?>
			<li><?php echo Text::_('MOD_BREADCRUMBS_HERE'); ?>&#160;</li>
		<?php else : ?>
			<li class="active">
				<span class="icon fa fa-home"></span>
			</li>
		<?php endif; ?>

		<?php
		// Get rid of duplicated entries on trail including home page when using multilanguage
		for ($i = 0; $i < $count; $i++) {
			if ($i === 1 && empty($list[$i]->link) && !empty($list[$i - 1]->link) && $list[$i]->link === $list[$i - 1]->link) {
				unset($list[$i]);
			}
		}

		// Find last and penultimate items in breadcrumbs list
		end($list);
		$last_item_key   = key($list);
		prev($list);
		$penult_item_key = key($list);

		$show_last = $params->get('showLast', 1);

		foreach ($list as $key => $item) :
			if ($key !== $last_item_key) :
				if (!empty($item->link)) :
					$breadcrumbItem = '<a itemprop="item" href="' . $item->link . '" class="pathway"><span itemprop="name">' . $item->name . '</span></a>';
				else :
					$breadcrumbItem = '<span itemprop="item"><span itemprop="name">' . $item->name . '</span></span>';
				endif; ?>
				
				<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="mod-breadcrumbs__item breadcrumb-item">
					<?php echo $breadcrumbItem; ?>
					<?php if (!empty($separator)) : ?>
						<span class="divider"><?php echo $separator; ?></span>
					<?php else : ?>
						<span class="divider"></span>
					<?php endif; ?>
					<meta itemprop="position" content="<?php echo $key + 1; ?>">
				</li>

			<?php elseif ($show_last) :
				$breadcrumbItem = !empty($item->link)
					? '<a itemprop="item" href="' . $item->link . '"><span itemprop="name">' . $item->name . '</span></a>'
					: '<span itemprop="item"><span itemprop="name">' . $item->name . '</span></span>'; ?>
				
				<li aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="mod-breadcrumbs__item breadcrumb-item active">
					<?php echo $breadcrumbItem; ?>
					<meta itemprop="position" content="<?php echo $key + 1; ?>">
				</li>
			<?php endif;
		endforeach; ?>
	</ol>
    <?php
    // Structured data as JSON
    $data = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        '@id'             => Uri::root() . '#/schema/BreadcrumbList/' . (int) $module->id,
        'itemListElement' => []
    ];

    // Use an independent counter for positions. E.g. if Heading items in pathway.
    $itemsCounter = 0;

    // If showHome is disabled use the fallback $homeCrumb for startpage at first position.
    if (isset($homeCrumb)) {
        $data['itemListElement'][] = [
                '@type'    => 'ListItem',
                'position' => ++$itemsCounter,
                'name' => $homeCrumb->name,
                'item' => Route::_($homeCrumb->link, true, Route::TLS_IGNORE, true),
        ];
    }

    foreach ($list as $key => $item) {
        // Only add item to JSON if it has a valid link, otherwise skip it.
        if (!empty($item->link)) {
            $data['itemListElement'][] = [
                    '@type'    => 'ListItem',
                    'position' => ++$itemsCounter,
                    'name' => $item->name,
                    'item' => Route::_($item->link, true, Route::TLS_IGNORE, true)
            ];
        } elseif ($key === $last_item_key) {
            // Add the last item (current page) to JSON, but without a link.
            // Google accepts items without a URL only as the current page.
            $data['itemListElement'][] = [
                    '@type'    => 'ListItem',
                    'position' => ++$itemsCounter,
                    'name' => $item->name,
            ];
        }
    }

    if ($itemsCounter) {
        /** @var WebAssetManager $wa */
        $wa = $app->getDocument()->getWebAssetManager();
        $prettyPrint = JDEBUG ? JSON_PRETTY_PRINT : 0;
        $wa->addInline(
            'script',
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | $prettyPrint),
            ['name' => 'inline.mod_breadcrumbs-schemaorg'],
            ['type' => 'application/ld+json']
        );
    }
    ?>
</nav>
