<?php

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Layout\FileLayout;

/**
 * Mothership Invoice PDF plugin: Trevor Bice Webdesign template
 */
class PlgMothershipInvoicePdfTbwebdesign extends CMSPlugin
{
    /**
     * Render the invoice HTML that will be passed to mPDF.
     *
     * @param array $data [
     *   'invoice'  => object,
     *   'client'   => object,
     *   'account'  => object,
     *   'business' => array|object
     * ]
     *
     * @return string HTML output
     */
    public function renderInvoicePdf(array $data): string
    {
        // Look for layouts/invoice.php inside this plugin
        $basePath = __DIR__ . '/layout';

        $layout = new FileLayout('pdf', $basePath);

        return $layout->render($data);
    }
}
