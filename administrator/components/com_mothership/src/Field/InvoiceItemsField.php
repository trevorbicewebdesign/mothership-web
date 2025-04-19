<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Custom field to display invoice items in a table layout.
 */
class InvoiceItemsField extends SubformField
{
    protected $type = 'InvoiceItems';

    /**
     * Override the getInput method to include a custom layout.
     */
    protected function getInput()
    {
        $layoutPath = JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/fields/invoiceitems.php';

        if (!file_exists($layoutPath)) {
            return parent::getInput(); // Fallback to default Joomla subform
        }

        $layout = new \Joomla\CMS\Layout\FileLayout('invoiceitems', JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/fields');

        return $layout->render(['field' => $this]);
    }
}
