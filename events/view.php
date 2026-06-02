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

$participants = $pdo->prepare('SELECT * FROM event_participants WHERE event_id = ? ORDER BY name');
$participants->execute([$id]);
$participants = $participants->fetchAll();

$hasPartCertSource = true;
$hasOrgCertSource = true;
try {
    $pdo->query('SELECT certificate_source FROM event_participants LIMIT 1');
} catch (PDOException $e) {
    $hasPartCertSource = false;
}
try {
    $pdo->query('SELECT certificate_source FROM event_organisers LIMIT 1');
} catch (PDOException $e) {
    $hasOrgCertSource = false;
}
?>

<div class="page-header-row flex-wrap gap-2">
    <a href="<?= base_url('events/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="ms-auto d-flex flex-wrap gap-2">
        <a href="<?= base_url('settings.php?tab=certificate') ?>" class="btn btn-light btn-sm"><i class="fas fa-certificate me-1"></i> Certificate template</a>
        <a href="<?= base_url('events/create.php?id=' . $id) ?>" class="btn btn-accent btn-sm">Edit</a>
    </div>
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
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="mb-0">Organisers (<?= count($organisers) ?>)</h5>
                <p class="small text-muted mb-0">Generate from template or upload a certificate file.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Name</th><th>Role</th><th>Certificate</th></tr></thead>
                    <tbody>
                    <?php if (empty($organisers)): ?>
                    <tr><td colspan="3" class="text-muted text-center py-4">No organisers.</td></tr>
                    <?php else: foreach ($organisers as $o): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($o['name']) ?></div>
                            <small class="text-muted"><?= e($o['email'] ?? '') ?></small>
                        </td>
                        <td><?= e($o['role'] ?? 'Organiser') ?></td>
                        <td>
                            <?php $orgCert = $o['certificate_path'] ?? null; ?>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="<?= base_url('events/certificate.php?target_type=organiser&target_id=' . $o['id'] . '&event_id=' . $id) ?>" class="btn btn-sm btn-accent" title="Design certificate"><i class="fas fa-award"></i></a>
                                <?php if ($orgCert): ?>
                                <a href="<?= base_url('uploads/' . e($orgCert)) ?>" class="btn btn-sm btn-light" target="_blank" title="View uploaded"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#upload-org-<?= (int) $o['id'] ?>" title="Upload"><i class="fas fa-upload"></i></button>
                            </div>
                            <div class="collapse mt-2" id="upload-org-<?= (int) $o['id'] ?>">
                                <form method="post" action="<?= base_url('events/certificate-upload.php') ?>" enctype="multipart/form-data" class="d-flex gap-1">
                                    <input type="hidden" name="target_type" value="organiser">
                                    <input type="hidden" name="target_id" value="<?= (int) $o['id'] ?>">
                                    <input type="hidden" name="event_id" value="<?= (int) $id ?>">
                                    <input type="file" name="certificate" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button type="submit" class="btn btn-sm btn-light">OK</button>
                                </form>
                            </div>
                            <?php if ($orgCert): ?>
                            <small class="text-success d-block mt-1"><i class="fas fa-check me-1"></i>Certificate assigned</small>
                            <?php elseif ($hasOrgCertSource && !empty($o['certificate_source'])): ?>
                            <small class="text-muted d-block mt-1"><?= e($o['certificate_source']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-shadow table-card">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="mb-0">Participants (<?= count($participants) ?>)</h5>
                <p class="small text-muted mb-0">Generate from template or upload a certificate file.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Name</th><th>Certificate</th></tr></thead>
                    <tbody>
                    <?php if (empty($participants)): ?>
                    <tr><td colspan="2" class="text-muted text-center py-4">No participants. Add them when editing the event.</td></tr>
                    <?php else: foreach ($participants as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($p['name']) ?></div>
                            <small class="text-muted"><?= e($p['email'] ?? '') ?></small>
                        </td>
                        <td>
                            <?php
                            $partCert = $p['certificate_path'] ?? null;
                            if (!$partCert && (($p['certificate_source'] ?? '') === 'upload') && !empty($p['document_path'])) {
                                $partCert = $p['document_path'];
                            }
                            ?>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="<?= base_url('events/certificate.php?target_type=participant&target_id=' . $p['id'] . '&event_id=' . $id) ?>" class="btn btn-sm btn-accent" title="Design certificate"><i class="fas fa-award"></i></a>
                                <?php if ($partCert): ?>
                                <a href="<?= base_url('uploads/' . e($partCert)) ?>" class="btn btn-sm btn-light" target="_blank" title="View uploaded"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#upload-<?= (int) $p['id'] ?>" title="Upload"><i class="fas fa-upload"></i></button>
                            </div>
                            <div class="collapse mt-2" id="upload-<?= (int) $p['id'] ?>">
                                <form method="post" action="<?= base_url('events/certificate-upload.php') ?>" enctype="multipart/form-data" class="d-flex gap-1">
                                    <input type="hidden" name="target_type" value="participant">
                                    <input type="hidden" name="target_id" value="<?= (int) $p['id'] ?>">
                                    <input type="hidden" name="event_id" value="<?= (int) $id ?>">
                                    <input type="file" name="certificate" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button type="submit" class="btn btn-sm btn-light">OK</button>
                                </form>
                            </div>
                            <?php if ($partCert): ?>
                            <small class="text-success d-block mt-1"><i class="fas fa-check me-1"></i>Certificate assigned</small>
                            <?php elseif ($hasPartCertSource && !empty($p['certificate_source'])): ?>
                            <small class="text-muted d-block mt-1"><?= e($p['certificate_source']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
