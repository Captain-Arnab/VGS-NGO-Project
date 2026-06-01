<?php
$pageTitle = 'Campaign Detail';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = ?');
$stmt->execute([$id]);
$campaign = $stmt->fetch();
if (!$campaign) {
    redirect('campaigns/index.php', null, 'Campaign not found.');
}
$pageTitle = $campaign['title'];

$pct = $campaign['goal_amount'] > 0 ? min(100, round(($campaign['raised_amount'] / $campaign['goal_amount']) * 100)) : 0;

$donations = $pdo->prepare("
    SELECT d.amount, d.donation_date, dn.name AS donor_name
    FROM donations d JOIN donors dn ON dn.id = d.donor_id
    WHERE d.campaign_id = ? ORDER BY d.donation_date DESC LIMIT 20
");
$donations->execute([$id]);

$events = $pdo->prepare('SELECT id, name, event_date, status FROM events WHERE campaign_id = ? ORDER BY event_date DESC');
$events->execute([$id]);

$beneficiaries = $pdo->prepare('SELECT id, name, aid_category, created_at FROM beneficiaries WHERE campaign_id = ? ORDER BY created_at DESC');
$beneficiaries->execute([$id]);
?>

<div class="page-header-row">
    <a href="<?= base_url('campaigns/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="<?= base_url('campaigns/create.php?id=' . $id) ?>" class="btn btn-accent btn-sm">Edit</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-shadow p-4">
            <?php if ($campaign['banner_image']): ?>
            <img src="<?= base_url('uploads/' . e($campaign['banner_image'])) ?>" class="w-100 rounded mb-3" style="max-height:220px;object-fit:cover" alt="">
            <?php endif; ?>
            <h3><?= e($campaign['title']) ?></h3>
            <p class="text-muted"><?= nl2br(e($campaign['description'] ?? '')) ?></p>
            <p><?= format_date($campaign['start_date']) ?> — <?= format_date($campaign['end_date']) ?></p>
            <?= status_badge($campaign['status'] === 'Active' ? 'Active' : 'Upcoming') ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow p-4">
            <h5>Fundraising Progress</h5>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-success fw-bold fs-4"><?= format_currency((float)$campaign['raised_amount']) ?></span>
                <span class="text-muted">of <?= format_currency((float)$campaign['goal_amount']) ?></span>
            </div>
            <div class="progress progress-campaign mb-2" style="height:12px">
                <div class="progress-bar" style="width:<?= $pct ?>%"></div>
            </div>
            <p class="mb-0 text-center fw-semibold"><?= $pct ?>% achieved</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-shadow table-card">
            <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-0">Linked Donations</h5></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Donor</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($donations->fetchAll() as $d): ?>
                    <tr>
                        <td><?= e($d['donor_name']) ?></td>
                        <td class="text-success"><?= format_currency((float)$d['amount']) ?></td>
                        <td><?= format_date($d['donation_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-shadow p-4">
            <h6>Linked Events</h6>
            <ul class="list-unstyled mb-0">
            <?php foreach ($events->fetchAll() as $ev): ?>
            <li class="mb-2"><a href="<?= base_url('events/view.php?id=' . $ev['id']) ?>"><?= e($ev['name']) ?></a><br><small class="text-muted"><?= format_date($ev['event_date']) ?></small></li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-shadow p-4">
            <h6>Beneficiaries</h6>
            <ul class="list-unstyled mb-0">
            <?php foreach ($beneficiaries->fetchAll() as $b): ?>
            <li class="mb-2"><a href="<?= base_url('beneficiaries/view.php?id=' . $b['id']) ?>"><?= e($b['name']) ?></a><br><small><?= e($b['aid_category']) ?></small></li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
