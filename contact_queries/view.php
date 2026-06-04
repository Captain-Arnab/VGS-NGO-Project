<?php
$pageTitle = 'View Contact Query';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$errors = [];

try {
    $stmt = $pdo->prepare('SELECT * FROM contact_queries WHERE id = ?');
    $stmt->execute([$id]);
    $query = $stmt->fetch();
} catch (PDOException $e) {
    redirect('contact_queries/index.php', null, 'Contact queries table not found. Run the database migration.');
}

if (!$query) {
    redirect('contact_queries/index.php', null, 'Query not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'New';
    $allowed = ['New', 'Read', 'Replied', 'Closed'];
    if (!in_array($status, $allowed, true)) {
        $errors[] = 'Invalid status.';
    }
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if (empty($errors)) {
        $pdo->prepare('UPDATE contact_queries SET status = ?, admin_notes = ? WHERE id = ?')
            ->execute([$status, $adminNotes ?: null, $id]);
        if ($status === 'Read' && $query['status'] === 'New') {
            // already updated
        }
        redirect('contact_queries/view.php?id=' . $id, 'Query updated.');
    }
    stash_form_errors($errors);
    $query = array_merge($query, $_POST);
}

if ($query['status'] === 'New' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $pdo->prepare("UPDATE contact_queries SET status = 'Read' WHERE id = ? AND status = 'New'")->execute([$id]);
    $query['status'] = 'Read';
}

$q = $query;
?>

<div class="page-header-row">
    <a href="<?= base_url('contact_queries/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back to list</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-shadow p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h4 class="mb-1"><?= e($q['name']) ?></h4>
                    <p class="text-muted mb-0"><?= format_date($q['created_at'], 'd M Y, g:i A') ?></p>
                </div>
                <?= status_badge($q['status']) ?>
            </div>
            <dl class="row mb-0">
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9"><a href="mailto:<?= e($q['email']) ?>"><?= e($q['email']) ?></a></dd>
                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9"><?= $q['phone'] ? '<a href="tel:' . e(preg_replace('/\s+/', '', $q['phone'])) . '">' . e($q['phone']) . '</a>' : '—' ?></dd>
                <dt class="col-sm-3">Subject</dt>
                <dd class="col-sm-9"><?= e($q['subject']) ?></dd>
                <dt class="col-sm-3">Message</dt>
                <dd class="col-sm-9"><div class="border rounded p-3 bg-light" style="white-space:pre-wrap"><?= e($q['message']) ?></div></dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-shadow p-4">
            <h5 class="mb-3">Update status</h5>
            <form method="post" class="js-prevent-double">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['New', 'Read', 'Replied', 'Closed'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($q['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin notes (internal)</label>
                    <textarea name="admin_notes" class="form-control" rows="4" placeholder="Follow-up notes, response summary…"><?= e($q['admin_notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-accent"><span class="btn-text">Save</span></button>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
