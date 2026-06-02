<?php
$pageTitle = 'Case Study';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$stmt = $pdo->prepare("
    SELECT cs.*, b.name AS beneficiary_name, c.title AS campaign_title
    FROM case_studies cs
    LEFT JOIN beneficiaries b ON b.id = cs.beneficiary_id
    LEFT JOIN campaigns c ON c.id = cs.campaign_id
    WHERE cs.id = ?
");
$stmt->execute([$id]);
$cs = $stmt->fetch();
if (!$cs) {
    redirect('case_studies/index.php', null, 'Case study not found.');
}
$pageTitle = $cs['title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_milestone') {
        $mDate = $_POST['milestone_date'] ?? '';
        $mTitle = post_string('milestone_title', 255);
        $mDesc = trim($_POST['milestone_description'] ?? '');
        if ($mDate && $mTitle !== '') {
            $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) FROM case_study_milestones WHERE case_study_id = ?');
            $max->execute([$id]);
            $order = (int) $max->fetchColumn() + 1;
            $pdo->prepare('INSERT INTO case_study_milestones (case_study_id, milestone_date, title, description, sort_order) VALUES (?,?,?,?,?)')
                ->execute([$id, $mDate, $mTitle, $mDesc ?: null, $order]);
            redirect('case_studies/view.php?id=' . $id, 'Milestone added.');
        }
        stash_form_errors(['Milestone date and title are required.']);
    }
    if ($action === 'delete_milestone') {
        $mid = (int) ($_POST['milestone_id'] ?? 0);
        $pdo->prepare('DELETE FROM case_study_milestones WHERE id = ? AND case_study_id = ?')->execute([$mid, $id]);
        redirect('case_studies/view.php?id=' . $id, 'Milestone removed.');
    }
    if ($action === 'close_case') {
        $pdo->prepare("UPDATE case_studies SET status='Inactive', closed_at=COALESCE(closed_at, CURDATE()) WHERE id=?")->execute([$id]);
        redirect('case_studies/view.php?id=' . $id, 'Case marked as inactive.');
    }
    if ($action === 'reopen_case') {
        $pdo->prepare("UPDATE case_studies SET status='Active', closed_at=NULL, reopened_at=NOW() WHERE id=?")->execute([$id]);
        redirect('case_studies/view.php?id=' . $id, 'Case reopened and set to active.');
    }
}

$stmt->execute([$id]);
$cs = $stmt->fetch();

$milestones = $pdo->prepare('SELECT * FROM case_study_milestones WHERE case_study_id = ? ORDER BY milestone_date ASC, sort_order ASC');
$milestones->execute([$id]);
$milestones = $milestones->fetchAll();
?>

<div class="page-header-row flex-wrap gap-2">
    <a href="<?= base_url('case_studies/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back to list</a>
    <div class="ms-auto d-flex flex-wrap gap-2">
        <a href="<?= base_url('case_studies/create.php?id=' . $id) ?>" class="btn btn-light btn-sm"><i class="fas fa-pen me-1"></i> Edit</a>
        <?php if ($cs['status'] === 'Active'): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Mark this case as inactive?');">
            <input type="hidden" name="action" value="close_case">
            <button type="submit" class="btn btn-light btn-sm"><i class="fas fa-archive me-1"></i> Mark inactive</button>
        </form>
        <?php else: ?>
        <form method="post" class="d-inline">
            <input type="hidden" name="action" value="reopen_case">
            <button type="submit" class="btn btn-accent btn-sm"><i class="fas fa-rotate-left me-1"></i> Reopen case</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-shadow p-4 case-study-sidebar">
            <?php if ($cs['featured_image']): ?>
            <img src="<?= base_url('uploads/' . e($cs['featured_image'])) ?>" alt="" class="case-study-featured w-100 rounded mb-3">
            <?php endif; ?>
            <h3 class="mb-2"><?= e($cs['title']) ?></h3>
            <p class="mb-2"><?= status_badge($cs['status']) ?></p>
            <?php if ($cs['reopened_at']): ?>
            <p class="small text-muted mb-2"><i class="fas fa-rotate-left me-1"></i> Reopened <?= format_date($cs['reopened_at'], 'd M Y H:i') ?></p>
            <?php endif; ?>
            <dl class="case-meta-list mb-0">
                <dt>Subject</dt><dd><?= e($cs['subject_name'] ?? '—') ?></dd>
                <dt>Category</dt><dd><?= e($cs['category']) ?></dd>
                <dt>Started</dt><dd><?= format_date($cs['started_at']) ?></dd>
                <dt>Closed</dt><dd><?= format_date($cs['closed_at']) ?></dd>
                <dt>Beneficiary</dt><dd><?= e($cs['beneficiary_name'] ?? '—') ?></dd>
                <dt>Campaign</dt><dd><?= e($cs['campaign_title'] ?? '—') ?></dd>
            </dl>
        </div>
        <div class="card card-shadow p-4 mt-4">
            <h5 class="mb-3"><i class="fas fa-align-left me-2 text-success"></i>Summary</h5>
            <p class="text-muted mb-0"><?= $cs['summary'] ? nl2br(e($cs['summary'])) : '<em>No summary yet.</em>' ?></p>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-shadow p-4">
            <h5 class="mb-4"><i class="fas fa-route me-2 text-success"></i>Impact roadmap</h5>
            <?php if (empty($milestones)): ?>
            <p class="text-muted">No milestones yet. Add the first step below.</p>
            <?php else: ?>
            <div class="case-timeline">
                <?php foreach ($milestones as $i => $m): ?>
                <div class="case-timeline-item">
                    <div class="case-timeline-marker"><?= $i + 1 ?></div>
                    <div class="case-timeline-content card-shadow-sm">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="case-timeline-date"><?= format_date($m['milestone_date']) ?></span>
                                <h6 class="mb-1 mt-1"><?= e($m['title']) ?></h6>
                                <?php if ($m['description']): ?>
                                <p class="text-muted small mb-0"><?= nl2br(e($m['description'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <form method="post" class="flex-shrink-0" onsubmit="return confirm('Remove this milestone?');">
                                <input type="hidden" name="action" value="delete_milestone">
                                <input type="hidden" name="milestone_id" value="<?= (int) $m['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-light text-danger border-0" title="Delete"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card card-shadow p-4 mt-4">
            <h5 class="mb-3"><i class="fas fa-plus-circle me-2 text-success"></i>Add milestone</h5>
            <form method="post" class="row g-3 js-prevent-double">
                <input type="hidden" name="action" value="add_milestone">
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="text" name="milestone_date" class="form-control flatpickr" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Title</label>
                    <input type="text" name="milestone_title" class="form-control" required placeholder="What happened?">
                </div>
                <div class="col-12">
                    <label class="form-label">Details (how it was handled)</label>
                    <textarea name="milestone_description" class="form-control" rows="3" placeholder="Describe actions taken, people involved, outcomes…"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-accent btn-sm"><span class="btn-text">Add to roadmap</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
