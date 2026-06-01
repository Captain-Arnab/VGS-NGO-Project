<?php
$pageTitle = 'Beneficiary Detail';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$stmt = $pdo->prepare('
    SELECT b.*, c.title AS campaign_title, e.name AS event_name
    FROM beneficiaries b
    LEFT JOIN campaigns c ON c.id = b.campaign_id
    LEFT JOIN events e ON e.id = b.event_id
    WHERE b.id = ?
');
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b) {
    redirect('beneficiaries/index.php', null, 'Not found.');
}
$pageTitle = $b['name'];
?>

<div class="page-header-row">
    <a href="<?= base_url('beneficiaries/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="<?= base_url('beneficiaries/create.php?id=' . $id) ?>" class="btn btn-accent btn-sm">Edit</a>
</div>

<div class="card card-shadow p-4">
    <h3><?= e($b['name']) ?></h3>
    <p class="text-muted"><?= e($b['aid_category']) ?> · <?= e($b['gender'] ?? '') ?> · Age <?= e($b['age'] ?? '—') ?></p>
    <hr>
    <div class="row g-3">
        <div class="col-md-6"><strong>Phone:</strong> <?= e($b['phone'] ?? '—') ?></div>
        <div class="col-md-6"><strong>Family Size:</strong> <?= e($b['family_size'] ?? '—') ?></div>
        <div class="col-12"><strong>Address:</strong><br><?= nl2br(e($b['address'] ?? '—')) ?></div>
        <div class="col-md-6"><strong>Campaign:</strong> <?= e($b['campaign_title'] ?? '—') ?></div>
        <div class="col-md-6"><strong>Event:</strong> <?= e($b['event_name'] ?? '—') ?></div>
        <div class="col-md-6"><strong>Added:</strong> <?= format_date($b['created_at']) ?></div>
        <div class="col-md-6">
            <?php if ($b['document_path']): ?>
            <a href="<?= base_url('uploads/' . e($b['document_path'])) ?>" class="btn btn-light btn-sm" target="_blank"><i class="fas fa-file-download me-1"></i> Download Document</a>
            <?php endif; ?>
        </div>
        <?php if ($b['notes']): ?>
        <div class="col-12"><strong>Notes:</strong><p class="text-muted mb-0"><?= nl2br(e($b['notes'])) ?></p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
