<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;

defined('_JEXEC') or die;

/** @var array $displayData */
$invoice  = $displayData['invoice'];
$account  = $displayData['account'] ?? null;
$client   = $displayData['client'] ?? null;
$business = $displayData['business'] ?? null;
$items    = $invoice->items ?? [];


// if task=invoice.preview then root path is / otherwise Uri::root();

$task = Factory::getApplication()->input->getCmd('task');

$root_path = ($task === 'previewPdf')
    ? '/'
    : Uri::root();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo htmlspecialchars($invoice->number); ?></title>
    <style>

  body {
    font-family: 'Open Sans', sans-serif;
    font-size: 10pt;
    color: #3A3A3A;
    margin: 20mm auto;
    width:188mm;
}

.header {

    margin-bottom: 8mm;
}

.logo-company-block {

    margin-bottom: 5mm;
}

.company-info,
.client-info {
    font-size: 8pt;
    line-height: 1.5;
}

.header-right h1 {
    margin: 0 0 4mm 0;
    font-size: 20pt;
    color: #539CCD;
}

h1 {
    font-family: 'Open Sans Light', 'Open Sans', sans-serif;
    color: #539CCD;
    white-space: nowrap;
}

.invoice-meta p {
    margin: 1mm 0;
    font-size: 9pt;
}

.section {
    margin-top: 10mm;
}

.account-heading {
    font-size: 13pt;
    font-weight: bold;
    margin: 6mm 0 4mm;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4mm;
}

.items-table th,
.items-table td {
    padding: 4px 6px;
}

.items-table th {
    background: #FFFFFF;
    text-align: left;
    border-bottom: 1px solid #ccc;
}

.total {
    text-align: right;
    margin-top: 6mm;
    font-size: 12pt;
    font-weight: bold;
    color: #539CCD;
}

.footer {
    font-size: 9pt;
    font-weight: normal;
    margin-top: 10mm;
    line-height: 1.4;
}

.final-company-info {
    text-align: center;
    margin-top: 16mm;
    font-size: 10pt;
    color: #444;
    line-height: 1.4;
}

    </style>
</head>
<body>

<table class="header" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8mm;">
    <tr valign="top">
        <td style="width: 50%;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="vertical-align: top; padding-left: 10px;">
                        <div class="company-info" style="text-align: left;">
                            <table cellpadding="0" cellspacing="0">
                                <tr valign="top">
                                    <td style="text-align: right; vertical-align: top; padding-right: 12px;">
                                        <?php if (!empty($business['company_name'])): ?>
                                            <?php
                                                // Split "Trevor Bice Webdesign" into two parts
                                                $companyName = $business['company_name'];
                                                $nameParts = explode(' ', $companyName, 3);
                                                $firstName = $nameParts[0] ?? '';
                                                $lastName = $nameParts[1] ?? '';
                                                $webdesign = strtoupper($nameParts[2] ?? '');
                                            ?>
                                            <span style="font-size:10pt; color:#000;line-height:8pt;text-transform:uppercase;"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></span><br>
                                            <span style="font-size:16pt; font-weight:bold;line-height:8pt; letter-spacing:2px;"><?php echo htmlspecialchars($webdesign); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: left; font-size:7pt; color:#666; vertical-align: top;">
                                        <?php if (!empty($business['company_address_1'])): ?>
                                            <div><?php echo nl2br(htmlspecialchars($business['company_address_1'])); ?></div>
                                        <?php endif; ?>
                                        <?php
                                            $city = $business['company_city'] ?? '';
                                            $state = $business['company_state'] ?? '';
                                            $zip = $business['company_zip'] ?? '';
                                            $addressLine = trim($city . ($state ? ', ' . $state : '') . ($zip ? ' ' . $zip : ''));
                                        ?>
                                        <?php if ($addressLine): ?>
                                            <div><?php echo htmlspecialchars($addressLine); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($business['company_phone'])): ?>
                                            <div><?php echo htmlspecialchars($business['company_phone']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <br/><br/><br/>

            <div class="client-info" style="margin-top: 0;line-height:1.2em;font-size:12pt;font-weight:300;">
                <p style="margin:0px;"><?php echo htmlspecialchars($client->name ?? $invoice->client_name ?? ''); ?></p>
                <?php if (!empty($client->address_1)): ?>
                    <p style="margin:0px;"><?php echo htmlspecialchars($client->address_1); ?></p>
                <?php endif; ?>
                <?php if (!empty($client->address_2)): ?>
                    <p style="margin:0px;"><?php echo htmlspecialchars($client->address_2); ?></p>
                <?php endif; ?>
                <?php if (!empty($client->city) || !empty($client->state) || !empty($client->zip)): ?>
                    <p style="margin:0px;">
                        <?php echo htmlspecialchars($client->city ?? ''); ?>
                        <?php echo !empty($client->state) ? ', ' . htmlspecialchars($client->state) : ''; ?>
                        <?php echo htmlspecialchars($client->zip ?? ''); ?>
                    </p>
                <?php endif; ?>
            </div>
        </td>
        <td style="width: 50%; vertical-align: top; text-align: right;">
            <div class="header-right">
                <h1 style="font-weight:300;letter-spacing:1pt;">Invoice of Services</h1>
                <div class="invoice-meta" style="text-align: right;">
                    <p><strong>Invoice Number: </strong> #<?php echo htmlspecialchars($invoice->number); ?></p>
                    <p><strong>Invoice Status:</strong> <?php echo htmlspecialchars(InvoiceHelper::getStatus($invoice->status)); ?></p>
                    <p><strong>Invoice Due:</strong> <?php echo htmlspecialchars($invoice->due_date ?? '—'); ?></p>
                </div>
            </div>
        </td>
    </tr>
</table>

<img src="<?php echo $root_path; ?>plugins/mothership-invoice-pdf/tbwebdesign/assets/images/custom-hr.jpg" alt="Separator" style="width: 100%; height: 1px; margin: 0px;" />
<div class="section account-heading">
    <?php echo htmlspecialchars($account->name ?? ''); ?><br/>
    <h6 style="margin:0px;padding:0px;margin-top:2mm;"><?php echo htmlspecialchars($invoice->title ?? ''); ?></h6>
</div>

<img src="<?php echo $root_path; ?>plugins/mothership-invoice-pdf/tbwebdesign/assets/images/custom-hr.jpg" alt="Separator" style="width: 100%; height: 1px; margin: 0px" />

<?php if(!empty($invoice->summary)): ?>
    <div class="section invoice-summary">
        <?php echo $invoice->summary ?? ''; ?>
    </div>
    <pagebreak />
<?php endif; ?>

<div class="section">
    <table class="items-table">
        <thead>
            <tr style="background-color: #f5f5f5;">
                <th style="width: 16px;"></th>
                <th>SERVICES RENDERED</th>
                <th style="width: 50px;text-align:right;">Hours</th>
                <th style="width: 50px;text-align:right;">Rate</th>
                <th style="width: 70px;text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 0; ?>
        <?php foreach ($items as $item): ?>
            <?php
                $rowStyle = ($i % 2 === 0)
                    ? 'background-color: #ffffff;'
                    : 'background-color: #f5f5f5;';
                $i++;
            ?>
            <tr style="<?php echo $rowStyle; ?>">
                <td><img src="<?php echo $root_path; ?>plugins/mothership-invoice-pdf/tbwebdesign/assets/images/invoice-bullet.png" alt="•" style="width:16px;height:auto;" /></td>
                <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                <td style="text-align: right;"><?php echo number_format((float)($item['quantity'] ?? 0), 2); ?></td>
                <td style="text-align: right;">$<?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                <td style="text-align: right;">$<?php echo number_format((float)($item['subtotal'] ?? 0), 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 6mm;">
    <tr valign="bottom">
        <td style="text-align: left; vertical-align: bottom;">
            <div class="footer">
                <strong>Have Questions? Get in touch —</strong><br>
                <?php if (!empty($business['company_email'])) : ?>
                    <?php echo htmlspecialchars($business['company_email']); ?>
                <?php endif; ?>
                <?php if (!empty($business['company_email']) && !empty($business['company_phone'])) : ?>
                    &nbsp;|&nbsp;
                <?php endif; ?>
                <?php if (!empty($business['company_phone'])) : ?>
                    <?php echo htmlspecialchars($business['company_phone']); ?>
                <?php endif; ?>
            </div>
        </td>
        <td style="text-align: right; vertical-align: bottom;">
            <div class="total">
                AMOUNT DUE: $<?php echo number_format((float)($invoice->total ?? 0), 2); ?><br>
                <span style="font-size: 10pt; font-weight: normal; color: #777;">Due upon receipt of invoice</span>
            </div>
        </td>
    </tr>
</table>

<?php if(!empty($invoice->notes)): ?>
<pagebreak />

<div class="section invoice-notes">
<?php echo $invoice->notes ?? ''; ?>
</div>
<?php endif; ?>

</body>
</html>
