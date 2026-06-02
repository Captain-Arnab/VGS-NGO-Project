<?php
$pageTitle = 'Add Case Study';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$case = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM case_studies WHERE id = ?');
    $stmt->execute([$id]);
    $case = $stmt->fetch();
    if (!$case) {
        redirect('case_studies/index.php', null, 'Case study not found.');
    }
    $pageTitle = 'Edit Case Study';
}

$beneficiaries = $pdo->query('SELECT id, name FROM beneficiaries ORDER BY name')->fetchAll();
$campaigns = $pdo->query('SELECT id, title FROM campaigns ORDER BY title')->fetchAll();
$categories = ['Education', 'Medical', 'Food', 'Shelter', 'Employment', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post_string('title', 255);
    $summary = trim($_POST['summary'] ?? '');
    $subject_name = post_string('subject_name', 150);
    $category = $_POST['category'] ?? 'Other';
    $status = $_POST['status'] ?? 'Active';
    $beneficiary_id = !empty($_POST['beneficiary_id']) ? (int) $_POST['beneficiary_id'] : null;
    $campaign_id = !empty($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;
    $started_at = $_POST['started_at'] ?: null;
    $closed_at = $_POST['closed_at'] ?: null;

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!in_array($category, $categories, true)) {
        $category = 'Other';
    }
    if (!in_array($status, ['Active', 'Inactive'], true)) {
        $status = 'Active';
    }
    if ($status === 'Inactive' && !$closed_at) {
        $closed_at = date('Y-m-d');
    }
    if ($status === 'Active') {
        $closed_at = null;
    }

    if (empty($errors)) {
        $slug = slugify($title);
        $checkSlug = $pdo->prepare('SELECT id FROM case_studies WHERE slug = ? AND id != ?');
        $checkSlug->execute([$slug, $id ?: 0]);
        if ($checkSlug->fetch()) {
            $slug .= '-' . time();
        }

        $featured = $case['featured_image'] ?? null;
        try {
            if (!empty($_FILES['featured_image']['name'])) {
                $featured = upload_file($_FILES['featured_image'], 'case_studies', ['jpg', 'jpeg', 'png', 'webp']);
                if ($id && !empty($case['featured_image'])) {
                    delete_upload($case['featured_image']);
                }
            }
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }

        if (empty($errors)) {
            if ($id) {
                $pdo->prepare('UPDATE case_studies SET title=?, slug=?, summary=?, subject_name=?, category=?, status=?, beneficiary_id=?, campaign_id=?, started_at=?, closed_at=?, featured_image=? WHERE id=?')
                    ->execute([$title, $slug, $summary ?: null, $subject_name ?: null, $category, $status, $beneficiary_id, $campaign_id, $started_at, $closed_at, $featured, $id]);
            } else {
                $pdo->prepare('INSERT INTO case_studies (title, slug, summary, subject_name, category, status, beneficiary_id, campaign_id, started_at, closed_at, featured_image) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$title, $slug, $summary ?: null, $subject_name ?: null, $category, $status, $beneficiary_id, $campaign_id, $started_at, $closed_at, $featured]);
                $id = (int) $pdo->lastInsertId();
            }
            redirect('case_studies/view.php?id=' . $id, $id ? 'Case study updated.' : 'Case study created. Add milestones on the detail page.');
        }
    }
    stash_form_errors($errors);
    $case = $_POST;
}

$c = $case ?? ['status' => 'Active', 'category' => 'Education', 'started_at' => date('Y-m-d')];
?>

<div class="page-header-row">
    <a href="<?= base_url($id ? 'case_studies/view.php?id=' . $id : 'case_studies/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card p-4">
    <form method="post" enctype="multipart/form-data" class="js-prevent-double">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required value="<?= e($c['title'] ?? '') ?>" placeholder="e.g. Helping a child complete school">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="Active" <?= ($c['status'] ?? '') === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($c['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive (closed / past)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Subject / person helped</label>
                <input type="text" name="subject_name" class="form-control" value="<?= e($c['subject_name'] ?? '') ?>" placeholder="e.g. Arjun Singh">
            </div>
            <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= ($c['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Linked beneficiary</label>
                <select name="beneficiary_id" class="form-select select2">
                    <option value="">None</option>
                    <?php foreach ($beneficiaries as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= (int)($c['beneficiary_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Linked campaign</label>
                <select name="campaign_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($campaigns as $cam): ?>
                    <option value="<?= $cam['id'] ?>" <?= (int)($c['campaign_id'] ?? 0) === (int)$cam['id'] ? 'selected' : '' ?>><?= e($cam['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Started on</label>
                <input type="text" name="started_at" class="form-control flatpickr" value="<?= e($c['started_at'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Closed on (if inactive)</label>
                <input type="text" name="closed_at" class="form-control flatpickr" value="<?= e($c['closed_at'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Featured image</label>
                <input type="file" name="featured_image" class="form-control" accept="image/*">
            </div>
            <div class="col-12">
                <label class="form-label">Summary</label>
                <textarea name="summary" class="form-control" rows="4" placeholder="Brief overview of the case and outcome goals"><?= e($c['summary'] ?? '') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text"><?= $id ? 'Update' : 'Create' ?> Case Study</span></button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
