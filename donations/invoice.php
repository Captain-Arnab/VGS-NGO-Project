<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/invoice_helpers.php';
require_once dirname(__DIR__) . '/includes/site_settings.php';

require_login();

$id = get_int('id');
$donation = fetch_donation_for_invoice($pdo, $id);
if (!$donation) {
    die('Donation not found.');
}

$print = isset($_GET['print']);
$orgName = get_setting('org_name', ORG_NAME);
$orgTagline = get_setting('org_tagline', ORG_TAGLINE);
$logo = org_logo_file_uri();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= e($donation['invoice_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; color: #002147; margin: 0; padding: 24px; background: #f0f2f7; }
        .invoice-wrap { max-width: 840px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,33,71,.1); }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #F58220; padding-bottom: 20px; margin-bottom: 24px; gap: 12px; }
        .org-name { font-size: 22px; font-weight: 700; color: #002147; margin: 0; }
        .org-meta { font-size: 12px; color: #5e7185; margin-top: 8px; line-height: 1.6; }
        .org-logo { max-height: 56px; max-width: 150px; margin-bottom: 8px; object-fit: contain; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; font-size: 28px; color: #002147; }
        .invoice-title p { margin: 4px 0 0; color: #5e7185; }
        table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e8eef2; }
        th { background: #f4f7fb; font-size: 12px; text-transform: uppercase; color: #5e7185; }
        .amount-row td { font-size: 18px; font-weight: 700; color: #138808; border-bottom: none; }
        .bill-to { margin: 20px 0; }
        .bill-to strong { display: block; margin-bottom: 8px; }
        .footer-note { margin-top: 32px; padding-top: 16px; border-top: 1px dashed #d5dee6; font-size: 12px; color: #5e7185; text-align: center; }
        .toolbar { max-width: 800px; margin: 0 auto 16px; display: flex; gap: 8px; }
        .toolbar button, .toolbar a { font-family: inherit; padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-print { background: #F58220; color: #fff; }
        .btn-back { background: #fff; color: #002147; border: 1px solid #d5dee6; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .invoice-wrap { box-shadow: none; padding: 20px; }
        }
    </style>
</head>
<body>
<?php if (!$print): ?>
<div class="toolbar">
    <a href="<?= base_url('donations/view.php?id=' . $id) ?>" class="btn-back">← Back to donation</a>
    <button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button>
</div>
<?php endif; ?>

<div class="invoice-wrap">
    <div class="invoice-header">
        <div>
            <?php if ($logo): ?><img src="<?= $logo ?>" alt="" class="org-logo"><?php endif; ?>
            <p class="org-name"><?= e($orgName) ?></p>
            <div class="org-meta">
                <?= e($orgTagline) ?><br>
                <?= nl2br(e(ORG_ADDRESS)) ?><br>
                <?= e(ORG_EMAIL) ?> · <?= e(ORG_PHONE) ?>
            </div>
        </div>
        <div class="invoice-title">
            <h1>INVOICE</h1>
            <p><strong><?= e($donation['invoice_number']) ?></strong></p>
            <p>Date: <?= format_date($donation['donation_date']) ?></p>
        </div>
    </div>

    <div class="bill-to">
        <strong>Received from</strong>
        <?= e($donation['donor_name']) ?><br>
        <?php if ($donation['donor_email']): ?><?= e($donation['donor_email']) ?><br><?php endif; ?>
        <?php if ($donation['donor_phone']): ?><?= e($donation['donor_phone']) ?><br><?php endif; ?>
        <?php if ($donation['donor_address']): ?><?= nl2br(e($donation['donor_address'])) ?><?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Payment mode</th>
                <th>Reference</th>
                <th style="text-align:right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Donation<?= $donation['campaign_title'] ? ' — ' . e($donation['campaign_title']) : '' ?>
                    <?php if ($donation['purpose']): ?><br><small style="color:#5e7185"><?= e($donation['purpose']) ?></small><?php endif; ?>
                </td>
                <td><?= e($donation['payment_mode']) ?></td>
                <td><?= e($donation['reference_number'] ?? '—') ?></td>
                <td style="text-align:right"><?= format_currency((float) $donation['amount']) ?></td>
            </tr>
            <tr class="amount-row">
                <td colspan="3" style="text-align:right">Total (<?= e($donation['currency']) ?>)</td>
                <td style="text-align:right"><?= format_currency((float) $donation['amount']) ?></td>
            </tr>
        </tbody>
    </table>

    <p class="footer-note">
        Thank you for your generous support. This document serves as an official receipt for your donation.<br>
        Donation ID: #<?= (int) $donation['id'] ?> · Generated <?= date('d M Y') ?>
    </p>
</div>
<?php if ($print): ?>
<script>window.onload = function () { window.print(); };</script>
<?php endif; ?>
</body>
</html>
