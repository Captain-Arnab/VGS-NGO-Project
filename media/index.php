<?php
$pageTitle = 'Media Gallery';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $mid = (int) $_GET['delete'];
    $f = $pdo->prepare('SELECT file_path FROM media_gallery WHERE id = ?');
    $f->execute([$mid]);
    delete_upload($f->fetchColumn());
    $pdo->prepare('DELETE FROM media_gallery WHERE id = ?')->execute([$mid]);
    redirect('media/index.php', 'Media deleted.');
}

if (isset($_GET['toggle_website'])) {
    $mid = (int) $_GET['toggle_website'];
    $pdo->prepare('UPDATE media_gallery SET show_on_website = 1 - show_on_website WHERE id = ?')->execute([$mid]);
    redirect('media/index.php', 'Website visibility updated.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    $title = post_string('title', 255);
    $caption = trim($_POST['caption'] ?? '');
    $media_type = $_POST['media_type'] ?? 'Image';
    $show_on_website = isset($_POST['show_on_website']) ? 1 : 0;
    $status = $_POST['status'] ?? 'Active';

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!in_array($media_type, ['Image', 'Video'], true)) {
        $media_type = 'Image';
    }
    $allowed = $media_type === 'Video'
        ? ['mp4', 'webm', 'mov']
        : ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (empty($errors)) {
        try {
            $path = upload_file($_FILES['file'], 'media', $allowed, MEDIA_MAX_UPLOAD_SIZE);
            if (!$path) {
                $errors[] = 'Please select a file (max 5 MB).';
            } else {
                $pdo->prepare('INSERT INTO media_gallery (title, caption, media_type, file_path, file_size, show_on_website, status) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$title, $caption ?: null, $media_type, $path, (int) $_FILES['file']['size'], $show_on_website, $status === 'Inactive' ? 'Inactive' : 'Active']);
                redirect('media/index.php', 'Media uploaded.');
            }
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}
if (!empty($errors)) {
    stash_form_errors($errors);
}

$where = ['1=1'];
$params = [];
if (!empty($_GET['search'])) {
    $where[] = '(title LIKE ? OR caption LIKE ?)';
    $q = '%' . $_GET['search'] . '%';
    $params[] = $q;
    $params[] = $q;
}
if (!empty($_GET['media_type'])) {
    $where[] = 'media_type = ?';
    $params[] = $_GET['media_type'];
}
if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM media_gallery WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$stmt = $pdo->prepare("SELECT * FROM media_gallery WHERE $whereSql ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filterAction = base_url('media/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Title or caption', 'col' => 3],
    ['name' => 'media_type', 'label' => 'Type', 'type' => 'select', 'options' => [''=>'All','Image'=>'Image','Video'=>'Video'], 'col' => 2],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [''=>'All','Active'=>'Active','Inactive'=>'Inactive'], 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Photos and videos for the public website media gallery (max 5 MB each).</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card card-shadow p-4">
            <h5 class="mb-3"><i class="fas fa-cloud-upload-alt me-2 text-success"></i>Upload media</h5>
            <form method="post" enctype="multipart/form-data" class="js-prevent-double">
                <input type="hidden" name="upload_media" value="1">
                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="media_type" class="form-select" id="mediaTypeSelect">
                        <option value="Image">Image (jpg, png, gif, webp)</option>
                        <option value="Video">Video (mp4, webm, mov)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">File <span class="text-danger">*</span> <small class="text-muted">(max 5 MB)</small></label>
                    <input type="file" name="file" class="form-control" required id="mediaFileInput">
                </div>
                <div class="mb-3">
                    <label class="form-label">Caption</label>
                    <textarea name="caption" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="show_on_website" class="form-check-input" id="showWeb" checked>
                    <label class="form-check-label" for="showWeb">Show on public website</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-accent w-100"><span class="btn-text">Upload</span></button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>
        <div class="media-gallery-grid">
            <?php if (empty($rows)): ?>
            <p class="text-muted text-center py-5 w-100">No media yet.</p>
            <?php else: foreach ($rows as $m):
                $url = base_url('uploads/' . $m['file_path']);
                $sizeMb = round($m['file_size'] / 1048576, 2);
            ?>
            <div class="media-gallery-item card-shadow">
                <div class="media-gallery-preview">
                    <?php if ($m['media_type'] === 'Image'): ?>
                    <img src="<?= e($url) ?>" alt="<?= e($m['title']) ?>">
                    <?php else: ?>
                    <video src="<?= e($url) ?>" controls preload="metadata"></video>
                    <?php endif; ?>
                </div>
                <div class="media-gallery-body p-3">
                    <h6 class="mb-1"><?= e($m['title']) ?></h6>
                    <p class="small text-muted mb-2"><?= e($m['media_type']) ?> · <?= $sizeMb ?> MB · <?= format_date($m['created_at']) ?></p>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?= status_badge($m['status']) ?>
                        <?php if ($m['show_on_website']): ?><span class="badge badge-website">Website</span><?php endif; ?>
                    </div>
                    <div class="btn-group btn-group-sm w-100">
                        <a href="<?= e($url) ?>" class="btn btn-light" target="_blank"><i class="fas fa-external-link-alt"></i></a>
                        <a href="?toggle_website=<?= (int) $m['id'] ?>" class="btn btn-light" title="Toggle website"><i class="fas fa-globe"></i></a>
                        <a href="#" class="btn btn-light text-danger" data-delete-url="<?= base_url('media/index.php?delete=' . $m['id']) ?>"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <?php render_pagination($total, $page, $_GET); ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
