<?php
$pageTitle = 'Donor Profile';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$stmt = $pdo->prepare('SELECT * FROM donors WHERE id = ?');
$stmt->execute([$id]);
$donor = $stmt->fetch();
if (!$donor) {
    redirect('donors/index.php', null, 'Donor not found.');
}

$totalStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM donations WHERE donor_id = ?');
$totalStmt->execute([$id]);
$totalDonated = (float) $totalStmt->fetchColumn();

$donations = $pdo->prepare("
    SELECT d.*, c.title AS campaign_title
    FROM donations d
    LEFT JOIN campaigns c ON c.id = d.campaign_id
    WHERE d.donor_id = ?
    ORDER BY d.donation_date DESC
");
$donations->execute([$id]);
$history = $donations->fetchAll();
$pageTitle = $donor['name'];
?>

<div class="page-header-row">
    <a href="<?= base_url('donors/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="d-flex gap-2">
        <a href="<?= base_url('donations/create.php?donor_id=' . $id) ?>" class="btn btn-accent btn-sm">Record Donation</a>
        <a href="<?= base_url('donors/create.php?id=' . $id) ?>" class="btn btn-light btn-sm">Edit</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-shadow profile-card">
            <div class="mb-3">
                <div class="stat-icon mx-auto" style="width:80px;height:80px;font-size:2rem;background:rgba(46,204,113,0.15);color:var(--accent)">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            <h4><?= e($donor['name']) ?></h4>
            <p class="text-muted mb-2"><?= e($donor['donor_type']) ?> · <?= e($donor['category']) ?></p>
            <?php if ($donor['company_name']): ?><p><strong>Company:</strong> <?= e($donor['company_name']) ?></p><?php endif; ?>
            <hr>
            <p class="mb-1"><i class="fas fa-envelope me-2 text-muted"></i><?= e($donor['email'] ?? '—') ?></p>
            <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i><?= e($donor['phone'] ?? '—') ?></p>
            <p class="mb-1"><i class="fas fa-id-card me-2 text-muted"></i><?= e($donor['pan_number'] ?? '—') ?></p>
            <p class="small text-muted mt-3"><?= nl2br(e($donor['address'] ?? '')) ?></p>
            <div class="summary-bar mt-3 text-center">
                Total Donated<br>
                <span class="fs-4 text-success"><?= format_currency($totalDonated) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-shadow table-card">
            <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-0">Donation History</h5></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Amount</th><th>Campaign</th><th>Payment</th><th>Purpose</th></tr></thead>
                    <tbody>
                    <?php if (empty($history)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No donations recorded.</td></tr>
                    <?php else: foreach ($history as $h): ?>
                    <tr>
                        <td><?= format_date($h['donation_date']) ?></td>
                        <td class="fw-semibold text-success"><?= format_currency((float)$h['amount']) ?></td>
                        <td><?= e($h['campaign_title'] ?? '—') ?></td>
                        <td><?= e($h['payment_mode']) ?></td>
                        <td><?= e($h['purpose'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($donor['notes']): ?>
        <div class="card card-shadow mt-4 p-4">
            <h6>Notes</h6>
            <p class="mb-0 text-muted"><?= nl2br(e($donor['notes'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
