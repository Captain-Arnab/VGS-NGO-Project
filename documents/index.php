<?php
$pageTitle = 'Documents';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $did = (int) $_GET['delete'];
    $f = $pdo->prepare('SELECT file_path FROM ngo_documents WHERE id = ?');
    $f->execute([$did]);
    delete_upload($f->fetchColumn());
    $pdo->prepare('DELETE FROM ngo_documents WHERE id = ?')->execute([$did]);
    redirect('documents/index.php', 'Document deleted.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    $title = post_string('title', 255);
    $category = $_POST['category'] ?? 'Other';
    $description = trim($_POST['description'] ?? '');
    $uploaded_by = post_string('uploaded_by', 100);

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    try {
        $path = upload_file($_FILES['file'], 'documents', ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png']);
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $pdo->prepare('INSERT INTO ngo_documents (title, category, file_path, file_type, file_size, uploaded_by, description) VALUES (?,?,?,?,?,?,?)')
            ->execute([$title, $category, $path, $ext, (int)$_FILES['file']['size'], $uploaded_by ?: null, $description ?: null]);
        redirect('documents/index.php', 'Document uploaded.');
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }
}

$where = ['1=1'];
$params = [];
if (!empty($_GET['search'])) {
    $where[] = 'title LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['category'])) {
    $where[] = 'category = ?';
    $params[] = $_GET['category'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $_GET['date_to'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ngo_documents WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$stmt = $pdo->prepare("SELECT * FROM ngo_documents WHERE $whereSql ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

function doc_icon(string $type): string {
    $map = ['pdf'=>'fa-file-pdf text-danger','doc'=>'fa-file-word text-primary','docx'=>'fa-file-word text-primary','xls'=>'fa-file-excel text-success','xlsx'=>'fa-file-excel text-success','ppt'=>'fa-file-powerpoint text-warning','pptx'=>'fa-file-powerpoint text-warning','jpg'=>'fa-file-image text-info','png'=>'fa-file-image text-info'];
    return $map[strtolower($type)] ?? 'fa-file text-muted';
}

$filterAction = base_url('documents/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Title', 'col' => 3],
    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => ['Legal'=>'Legal','Financial'=>'Financial','Annual Report'=>'Annual Report','Project Report'=>'Project Report','Policy'=>'Policy','Certificate'=>'Certificate','Other'=>'Other'], 'col' => 3],
    ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">NGO legal, financial, and project documents.</p>
</div>

<?php if (!empty($errors)) { stash_form_errors($errors); } ?>

<div class="card card-shadow mb-4">
    <div class="card-header bg-white border-0 pt-3 px-4">
        <a class="text-decoration-none fw-semibold" data-bs-toggle="collapse" href="#uploadCollapse">
            <i class="fas fa-upload me-2 text-success"></i> Upload New Document
        </a>
    </div>
    <div class="collapse show" id="uploadCollapse">
        <div class="card-body pt-0">
            <form method="post" enctype="multipart/form-data" class="js-prevent-double">
                <input type="hidden" name="upload_doc" value="1">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required></div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <?php foreach (['Legal','Financial','Annual Report','Project Report','Policy','Certificate','Other'] as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Uploaded By</label><input type="text" name="uploaded_by" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-4">
                        <label class="form-label">File (max 10MB)</label>
                        <input type="file" name="file" class="form-control file-upload-preview" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.png">
                        <div class="file-preview"></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-accent mt-3"><span class="btn-text">Upload</span></button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th></th><th>Title</th><th>Category</th><th>Uploaded By</th><th>Date</th><th>Size</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted py-5">No documents.</td></tr>
            <?php else: foreach ($rows as $row): ?>
            <tr>
                <td><i class="fas <?= doc_icon($row['file_type'] ?? '') ?> fa-lg"></i></td>
                <td class="fw-semibold"><?= e($row['title']) ?></td>
                <td><?= e($row['category']) ?></td>
                <td><?= e($row['uploaded_by'] ?? '—') ?></td>
                <td><?= format_date($row['created_at']) ?></td>
                <td><?= $row['file_size'] ? round($row['file_size']/1024, 1) . ' KB' : '—' ?></td>
                <td>
                    <a href="<?= base_url('uploads/' . e($row['file_path'])) ?>" class="btn btn-sm btn-light" download><i class="fas fa-download"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('documents/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
