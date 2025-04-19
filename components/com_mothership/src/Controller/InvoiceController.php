<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Mpdf\Mpdf;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;

// Load all enabled payment plugins
PluginHelper::importPlugin('mothership-payment');

class InvoiceController extends BaseController
{


    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', $this->input->getCmd('view', 'invoice'));
        parent::display($cachable, $urlparams);
    }

    public function downloadPdf()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_INVOICE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $model = $this->getModel('Invoice');
        $invoice = $model->getItem($id);

        if (!$invoice) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVOICE_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        // Generate the HTML
        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        $html = $layout->render(['invoice' => $invoice]);



        // Turn off Joomla's output
        ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Invoice-' . $invoice->number . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Generate and output the PDF
        $pdf = new Mpdf();
        $pdf->WriteHTML($html);
        $pdf->Output(null, 'I');

        $app->close();
    }

    public function payment()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_INVOICE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $model = $this->getModel('Invoice');
        $invoice = $model->getItem($id);

        if (!$invoice) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVOICE_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        // Load enabled payment plugins
        $plugins = \Joomla\CMS\Plugin\PluginHelper::getPlugin('mothership-payment');
        $paymentOptions = [];

        foreach ($plugins as $plugin) {
            $params = new \Joomla\Registry\Registry($plugin->params);
            $pluginName = $params->get('display_name') ?: ucfirst(str_replace('mothership-', '', $plugin->element));

            if($plugin->name == 'paypal') {
                $fee_amount = number_format($invoice->total * 0.039 + 0.30, 2);
                $display_fee = " 3.9% + $0.30";
            } else {
                $fee_amount = number_format(0, 2);
                $display_fee = "No Fee";
            }

            $paymentOptions[] = [
                'element' => $plugin->name,
                'name' => $pluginName,
                'fee_amount' => $fee_amount,
                'display_fee' => $display_fee,
            ];

        }

        // Correct way to pass data to the view:
        $view = $this->getView('Invoice', 'html');
        $view->setModel($model, true);
        $view->item = $invoice;
        $view->paymentOptions = $paymentOptions;
        $view->setLayout('payment');
        $view->display();
    }

    protected function getPluginInstance(string $pluginName)
    {
        // Normalize plugin name casing
        $normalized = strtolower($pluginName);

        // Load the plugin group
        PluginHelper::importPlugin('mothership-payment');

        $plugins = PluginHelper::getPlugin('mothership-payment');

        foreach ($plugins as $plugin) {
            if ($plugin->name === $normalized) {
                // Build expected class name, e.g., PlgMothershippaymentPaypal
                $className = 'PlgMothershipPayment' . ucfirst($plugin->name);
       
                if (!class_exists($className)) {
                    throw new \RuntimeException("Plugin class '$className' not found.");
                }

                // Instantiate and return
                $dispatcher = Factory::getApplication()->getDispatcher();
                return new $className($dispatcher, (array) $plugin);
            }
        }

        throw new \RuntimeException("Payment plugin '$pluginName' not found or not enabled. 1 ".json_encode($plugins));
    }

    public function processPayment()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $invoiceId = $input->getCmd('id');
        $paymentMethod = $input->getCmd('payment_method');

        

        if (!$invoiceId || !$paymentMethod) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_REQUEST'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoiceId, false));
            return;
        }

        // Load the invoice
        $invoiceModel = $this->getModel('Invoice');
        $invoice = $invoiceModel->getItem($invoiceId);

        // Create the payment record
        $payment = Factory::getApplication()
            ->bootComponent('com_mothership')
            ->getMVCFactory()
            ->createTable('Payment', 'MothershipTable');
        $payment->client_id = $invoice->client_id;
        $payment->account_id = $invoice->account_id;
        $payment->amount = $invoice->total;
        $payment->status = 1; // Pending
        $payment->payment_method = $paymentMethod;
        $payment->payment_date = Factory::getDate()->toSql();
        $payment->created = Factory::getDate()->toSql();

        if (!$payment->store()) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_SAVE_FAILED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoiceId, false));
            return;
        }

        // Create the invoice payment record
        $invoicePayment = Factory::getApplication()
            ->bootComponent('com_mothership')
            ->getMVCFactory()
            ->createTable('InvoicePayment', 'MothershipTable');
        $invoicePayment->invoice_id = $invoiceId;
        $invoicePayment->payment_id = $payment->id;
        $invoicePayment->applied_amount = $invoice->total;
        if (!$invoicePayment->store()) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_SAVE_FAILED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoiceId, false));
            return;
        }

        // Invoke the plugin to process
        try {
            $plugin = $this->getPluginInstance($paymentMethod);

            if (!method_exists($plugin, 'initiate')) {
                throw new \RuntimeException("Plugin '{$paymentMethod}' cannot be initiated.");
            }

            return $plugin->initiate($payment, $invoice); // Plugin handles redirect or rendering
        } catch (\Exception $e) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PAYMENT_PROCESSING_FAILED', $e->getMessage()), 'error');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=invoice&id={$invoiceId}&task=invoice.payment", false));
            return;
        }
    }

}
