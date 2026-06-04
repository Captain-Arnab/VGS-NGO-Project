<?php
$pageTitle = 'Add Homepage Banner';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$banner = null;
$errors = [];

$gradientPresets = [
    'linear-gradient(135deg, #002157 0%, #001540 100%)' => 'Navy (Primary)',
    'linear-gradient(135deg, #f4811f 0%, #d96a0a 100%)' => 'Saffron (Accent)',
    'linear-gradient(135deg, #439539 0%, #2d6825 100%)' => 'Green (Growth)',
    'linear-gradient(135deg, #002157 0%, #439539 100%)' => 'Navy to Green',
    'linear-gradient(135deg, #f4811f 0%, #439539 100%)' => 'Saffron to Green',
    'linear-gradient(135deg, #002157 0%, #f4811f 100%)' => 'Navy to Saffron',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM homepage_banners WHERE id = ?');
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if (!$banner) {
        redirect('banners/index.php', null, 'Banner not found.');
    }
    $pageTitle = 'Edit Homepage Banner';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post_string('title', 255);
    $subtitle = post_string('subtitle', 255);
    $description = trim($_POST['description'] ?? '');
    $button1_text = post_string('button1_text', 100);
    $button1_link = post_string('button1_link', 255);
    $button2_text = post_string('button2_text', 100);
    $button2_link = post_string('button2_link', 255);
    $bg_gradient = post_string('bg_gradient', 255);
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        $errors[] = 'Title is required.';
    }

    $background = $banner['background_image'] ?? null;
    try {
        if (!empty($_FILES['background_image']['name'])) {
            if ($background) {
                delete_upload($background);
            }
            $background = upload_file($_FILES['background_image'], 'banners', ['jpg', 'jpeg', 'png', 'webp']);
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare('UPDATE homepage_banners SET title=?, subtitle=?, description=?, button1_text=?, button1_link=?, button2_text=?, button2_link=?, background_image=?, bg_gradient=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$title, $subtitle ?: null, $description ?: null, $button1_text ?: null, $button1_link ?: null, $button2_text ?: null, $button2_link ?: null, $background, $bg_gradient ?: null, $sort_order, $is_active, $id]);
            redirect('banners/index.php', 'Banner updated.');
        } else {
            $pdo->prepare('INSERT INTO homepage_banners (title, subtitle, description, button1_text, button1_link, button2_text, button2_link, background_image, bg_gradient, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$title, $subtitle ?: null, $description ?: null, $button1_text ?: null, $button1_link ?: null, $button2_text ?: null, $button2_link ?: null, $background, $bg_gradient ?: null, $sort_order, $is_active]);
            redirect('banners/index.php', 'Banner created.');
        }
    }
    stash_form_errors($errors);
    $banner = array_merge($banner ?? [], $_POST);
    $banner['is_active'] = $is_active;
}

$b = $banner ?? [
    'button1_text' => 'Learn More',
    'button1_link' => '#welcome',
    'button2_text' => 'Join Us',
    'button2_link' => '#volunteer',
    'bg_gradient' => 'linear-gradient(135deg, #002157 0%, #001540 100%)',
    'sort_order' => 0,
    'is_active' => 1,
];
?>

<div class="page-header-row">
    <a href="<?= base_url('banners/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card p-4">
    <form method="post" enctype="multipart/form-data" class="js-prevent-double">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required value="<?= e($b['title'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Sort order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int) ($b['sort_order'] ?? 0) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Subtitle</label>
                <input type="text" name="subtitle" class="form-control" value="<?= e($b['subtitle'] ?? '') ?>" placeholder="Indira Mahila Mandali">
            </div>
            <div class="col-md-6">
                <label class="form-label">Background gradient</label>
                <select name="bg_gradient" class="form-select">
                    <?php foreach ($gradientPresets as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($b['bg_gradient'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Used when no background image is uploaded.</small>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($b['description'] ?? '') ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 1 text</label>
                <input type="text" name="button1_text" class="form-control" value="<?= e($b['button1_text'] ?? 'Learn More') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 1 link</label>
                <input type="text" name="button1_link" class="form-control" value="<?= e($b['button1_link'] ?? '#welcome') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 2 text</label>
                <input type="text" name="button2_text" class="form-control" value="<?= e($b['button2_text'] ?? 'Join Us') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 2 link</label>
                <input type="text" name="button2_link" class="form-control" value="<?= e($b['button2_link'] ?? '#volunteer') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Background image (optional)</label>
                <input type="file" name="background_image" class="form-control" accept="image/*">
                <small class="text-muted">Recommended size: <strong>1920 × 720 px</strong>. Replaces the gradient placeholder on the homepage.</small>
                <?php if (!empty($b['background_image'])): ?>
                <img src="<?= base_url('uploads/' . e($b['background_image'])) ?>" class="thumb-sm mt-2" alt="">
                <?php endif; ?>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check mb-3">
                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= !empty($b['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active (visible on website)</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text">Save banner</span></button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
