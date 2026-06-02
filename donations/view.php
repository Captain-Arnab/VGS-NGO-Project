<?php
$pageTitle = 'Donation Detail';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/invoice_helpers.php';

$id = get_int('id');
$donation = fetch_donation_for_invoice($pdo, $id);
if (!$donation) {
    redirect('donations/index.php', null, 'Donation not found.');
}
$pageTitle = 'Donation #' . $id;
?>

<div class="page-header-row flex-wrap gap-2">
    <a href="<?= base_url('donations/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= base_url('donations/invoice.php?id=' . $id) ?>" class="btn btn-accent btn-sm" target="_blank"><i class="fas fa-file-invoice me-1"></i> View / Print Invoice</a>
        <a href="<?= base_url('donations/create.php?id=' . $id) ?>" class="btn btn-light btn-sm"><i class="fas fa-pen me-1"></i> Edit</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-shadow p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h3 class="mb-1"><?= format_currency((float) $donation['amount']) ?></h3>
                    <p class="text-muted mb-0"><?= format_date($donation['donation_date']) ?> · <?= e($donation['payment_mode']) ?></p>
                </div>
                <span class="badge bg-light text-dark fs-6"><?= e($donation['invoice_number']) ?></span>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-6"><strong>Donor</strong><br><?= e($donation['donor_name']) ?></div>
                <div class="col-md-6"><strong>Donor type</strong><br><?= e($donation['donor_type'] ?? '—') ?></div>
                <div class="col-md-6"><strong>Email</strong><br><?= e($donation['donor_email'] ?? '—') ?></div>
                <div class="col-md-6"><strong>Phone</strong><br><?= e($donation['donor_phone'] ?? '—') ?></div>
                <div class="col-md-6"><strong>Campaign</strong><br><?= e($donation['campaign_title'] ?? 'General fund') ?></div>
                <div class="col-md-6"><strong>Reference</strong><br><?= e($donation['reference_number'] ?? '—') ?></div>
                <div class="col-md-6"><strong>Purpose</strong><br><?= e($donation['purpose'] ?? '—') ?></div>
                <div class="col-md-6"><strong>Recorded</strong><br><?= format_date($donation['created_at'], 'd M Y H:i') ?></div>
                <?php if ($donation['notes']): ?>
                <div class="col-12"><strong>Notes</strong><p class="text-muted mb-0"><?= nl2br(e($donation['notes'])) ?></p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow p-4 text-center">
            <i class="fas fa-file-invoice fa-3x text-success mb-3"></i>
            <h5>Donation receipt</h5>
            <p class="text-muted small">Official invoice for this contribution. Print or save as PDF from the invoice page.</p>
            <a href="<?= base_url('donations/invoice.php?id=' . $id) ?>" class="btn btn-accent w-100" target="_blank"><i class="fas fa-download me-1"></i> Open invoice</a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
