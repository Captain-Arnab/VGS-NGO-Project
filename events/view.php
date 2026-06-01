<?php
$pageTitle = 'Event Detail';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$stmt = $pdo->prepare('SELECT e.*, c.title AS campaign_title FROM events e LEFT JOIN campaigns c ON c.id = e.campaign_id WHERE e.id = ?');
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) {
    redirect('events/index.php', null, 'Event not found.');
}
$pageTitle = $event['name'];

$organisers = $pdo->prepare('SELECT * FROM event_organisers WHERE event_id = ?');
$organisers->execute([$id]);
$organisers = $organisers->fetchAll();

$participants = $pdo->prepare('SELECT * FROM event_participants WHERE event_id = ?');
$participants->execute([$id]);
$participants = $participants->fetchAll();
?>

<div class="page-header-row">
    <a href="<?= base_url('events/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="<?= base_url('events/create.php?id=' . $id) ?>" class="btn btn-accent btn-sm">Edit</a>
</div>

<div class="card card-shadow p-4 mb-4">
    <div class="row">
        <?php if ($event['banner_image']): ?>
        <div class="col-md-4"><img src="<?= base_url('uploads/' . e($event['banner_image'])) ?>" class="w-100 rounded" alt=""></div>
        <?php endif; ?>
        <div class="col">
            <h3><?= e($event['name']) ?></h3>
            <?= status_badge($event['status']) ?>
            <span class="badge bg-light text-dark ms-2"><?= e($event['event_type']) ?></span>
            <p class="mt-3 mb-1"><i class="fas fa-map-marker-alt me-2 text-muted"></i><?= e($event['location'] ?? 'TBA') ?></p>
            <p class="mb-1"><i class="far fa-calendar me-2 text-muted"></i><?= format_date($event['event_date']) ?> at <?= $event['event_time'] ? date('h:i A', strtotime($event['event_time'])) : '—' ?></p>
            <?php if ($event['maps_link']): ?><p><a href="<?= e($event['maps_link']) ?>" target="_blank" rel="noopener">View on Maps</a></p><?php endif; ?>
            <?php if ($event['campaign_title']): ?><p class="text-muted">Campaign: <?= e($event['campaign_title']) ?></p><?php endif; ?>
            <p class="text-muted mt-3"><?= nl2br(e($event['description'] ?? '')) ?></p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-shadow table-card">
            <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-0">Organisers (<?= count($organisers) ?>)</h5></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Name</th><th>Role</th><th>Phone</th><th>Email</th></tr></thead>
                    <tbody>
                    <?php if (empty($organisers)): ?>
                    <tr><td colspan="4" class="text-muted text-center">No organisers.</td></tr>
                    <?php else: foreach ($organisers as $o): ?>
                    <tr>
                        <td><?= e($o['name']) ?></td>
                        <td><?= e($o['role'] ?? '—') ?></td>
                        <td><?= e($o['phone'] ?? '—') ?></td>
                        <td><?= e($o['email'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-shadow table-card">
            <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-0">Participants (<?= count($participants) ?>)</h5></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Doc</th></tr></thead>
                    <tbody>
                    <?php if (empty($participants)): ?>
                    <tr><td colspan="4" class="text-muted text-center">No participants.</td></tr>
                    <?php else: foreach ($participants as $p): ?>
                    <tr>
                        <td><?= e($p['name']) ?></td>
                        <td><?= e($p['email'] ?? '—') ?></td>
                        <td><?= e($p['phone'] ?? '—') ?></td>
                        <td><?php if ($p['document_path']): ?><a href="<?= base_url('uploads/' . e($p['document_path'])) ?>" target="_blank"><i class="fas fa-file"></i></a><?php else: ?>—<?php endif; ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
