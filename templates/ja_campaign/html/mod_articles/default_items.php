<?php
/** @var array $items */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\FileLayout;

if (!$items) {
    return;
}
?>

<div class="container py-5">
    <div class="row g-4">
        <?php foreach ($items as $item) : ?>
            <div class="col-md-4" style="margin-bottom: 20px;">
                <div class="card h-100 text-center feature-card">
                    <div class="card-body">
                        <?php
                        // Optionally use a custom field called 'icon' if defined
                        $icon = '';
                        foreach ($item->jcfields ?? [] as $field) {
                            if ($field->name === 'icon') {
                                $icon = $field->value;
                                break;
                            }
                        }

                        // Extract first <p> from introtext
                        $firstParagraph = '';
                        if (!empty($item->introtext)) {
                            $dom = new DOMDocument();
                            libxml_use_internal_errors(true); // Suppress warnings for bad HTML
                            $dom->loadHTML(mb_convert_encoding($item->introtext, 'HTML-ENTITIES', 'UTF-8'));
                            libxml_clear_errors();
                            $paragraphs = $dom->getElementsByTagName('p');
                            if ($paragraphs->length > 0) {
                                $firstParagraph = $dom->saveHTML($paragraphs->item(0));
                            }
                        }
                        ?>
                        <div class="feature-icon"><?php echo htmlspecialchars($icon ?: '✨', ENT_QUOTES, 'UTF-8'); ?></div>

                        <h5 class="card-title">
                            <a href="<?php echo Route::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid)); ?>">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h5>

                        <p class="card-text"><?php echo $firstParagraph; ?></p>

                        <!-- Read More -->
                        <a href="<?php echo Route::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid)); ?>" class="btn btn-primary">Read More</a>
                            
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
