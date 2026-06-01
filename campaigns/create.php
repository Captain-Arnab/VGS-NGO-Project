<?php
$pageTitle = 'Create Campaign';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$campaign = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = ?');
    $stmt->execute([$id]);
    $campaign = $stmt->fetch();
    if (!$campaign) {
        redirect('campaigns/index.php', null, 'Campaign not found.');
    }
    $pageTitle = 'Edit Campaign';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post_string('title', 255);
    $description = trim($_POST['description'] ?? '');
    $goal_amount = (float) ($_POST['goal_amount'] ?? 0);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $status = $_POST['status'] ?? 'Upcoming';

    if ($title === '') {
        $errors[] = 'Title is required.';
    }

    $banner = $campaign['banner_image'] ?? null;
    try {
        if (!empty($_FILES['banner_image']['name'])) {
            if ($banner) {
                delete_upload($banner);
            }
            $banner = upload_file($_FILES['banner_image'], 'campaigns', ['jpg', 'jpeg', 'png', 'webp']);
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare('UPDATE campaigns SET title=?, description=?, goal_amount=?, start_date=?, end_date=?, banner_image=?, status=? WHERE id=?')
                ->execute([$title, $description ?: null, $goal_amount, $start_date, $end_date, $banner, $status, $id]);
            redirect('campaigns/index.php', 'Campaign updated.');
        } else {
            $pdo->prepare('INSERT INTO campaigns (title, description, goal_amount, start_date, end_date, banner_image, status) VALUES (?,?,?,?,?,?,?)')
                ->execute([$title, $description ?: null, $goal_amount, $start_date, $end_date, $banner, $status]);
            redirect('campaigns/index.php', 'Campaign created.');
        }
    }
    stash_form_errors($errors);
    $campaign = array_merge($campaign ?? [], $_POST);
}

$c = $campaign ?? ['status' => 'Upcoming', 'goal_amount' => 0];
?>

<div class="page-header-row">
    <a href="<?= base_url('campaigns/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card p-4">
    <form method="post" enctype="multipart/form-data" class="js-prevent-double">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required value="<?= e($c['title'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['Upcoming','Active','Completed','Paused'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($c['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e($c['description'] ?? '') ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Goal Amount (₹)</label>
                <input type="number" step="0.01" name="goal_amount" class="form-control" value="<?= e($c['goal_amount'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="text" name="start_date" class="form-control flatpickr" value="<?= e($c['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="text" name="end_date" class="form-control flatpickr" value="<?= e($c['end_date'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Banner Image</label>
                <input type="file" name="banner_image" class="form-control file-upload-preview" accept="image/*">
                <div class="file-preview">
                    <?php if (!empty($c['banner_image'])): ?>
                    <img src="<?= base_url('uploads/' . e($c['banner_image'])) ?>" class="thumb-sm mt-2" alt="">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text"><?= $id ? 'Update' : 'Create' ?> Campaign</span></button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
