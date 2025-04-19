<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

?>
<style>
    .mt-4 {
        margin-top: 1.5rem;
    }
</style>
<h1>Payments</h1>
<table class="table paymentsTable" id="paymentsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Account</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Fee Amount</th>
            <th>Payment Method</th>
            <th>Transaction Id</th>
            <th>Invoices</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->payments)) : ?>
            <tr>
                <td colspan="7">No payments found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->payments as $payment) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=payment&id=' . $payment->id); ?>"><?php echo $payment->id; ?></a></td>
                <td><?php echo $payment->account_name; ?></td>
                <td>$<?php echo number_format($payment->amount, 2); ?></td>
                <td><?php echo $payment->status; ?></td>
                <td>$<?php echo number_format($payment->fee_amount, 2); ?></td>
                <td>
                    <?php 
                    $plugin = \Joomla\CMS\Plugin\PluginHelper::getPlugin('mothership-payment', $payment->payment_method);
                    $pluginParams = new \Joomla\Registry\Registry($plugin->params);
                    echo $pluginParams->get('display_name');
                    ?>
                </td>
                <td><?php echo $payment->transaction_id; ?></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $payment->invoice_ids); ?>" ><?php echo $payment->invoice_ids; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="card mt-4">
  <div class="card-header">
    Payment Status Legend
  </div>
  <div class="card-body">
    <ul class="mb-0">
      <li><strong>Pending</strong>: Payment is awaiting confirmation.</li>
      <li><strong>Completed</strong>: Payment was successful.</li>
      <li><strong>Failed</strong>: Payment failed to process.</li>
      <li><strong>Cancelled</strong>: Payment was cancelled.</li>
      <li><strong>Refunded</strong>: Payment was returned to the payer.</li>
    </ul>
  </div>
</div>