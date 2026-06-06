<?php
$pageTitle = 'Blog';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $bid = (int) $_GET['delete'];
    $img = $pdo->prepare('SELECT featured_image FROM blogs WHERE id = ?');
    $img->execute([$bid]);
    delete_upload($img->fetchColumn());
    $pdo->prepare('DELETE FROM blogs WHERE id = ?')->execute([$bid]);
    redirect('blogs/index.php', 'Post deleted.');
}

$where = ['1=1'];
$params = [];
if (!empty($_GET['search'])) {
    $where[] = 'title LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
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
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$stmt = $pdo->prepare("SELECT * FROM blogs WHERE $whereSql ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filterAction = base_url('blogs/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Title', 'col' => 3],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Draft'=>'Draft','Published'=>'Published','Archived'=>'Archived'], 'col' => 2],
    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => ['News'=>'News','Story'=>'Story','Announcement'=>'Announcement','Campaign'=>'Campaign','Other'=>'Other'], 'col' => 2],
    ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <div>
        <p class="text-muted mb-0">News, stories, and announcements. Posts with status <strong>Published</strong> appear on the public website blog page.</p>
    </div>
    <a href="<?= base_url('blogs/create.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Add New Post</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>#</th><th>Image</th><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">No posts.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($rows as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?php if ($row['featured_image']): ?><img src="<?= base_url('uploads/' . e($row['featured_image'])) ?>" class="thumb-sm" alt=""><?php else: ?><div class="thumb-placeholder"><i class="fas fa-image"></i></div><?php endif; ?></td>
                <td class="fw-semibold"><?= e($row['title']) ?></td>
                <td><?= e($row['author'] ?? '—') ?></td>
                <td><?= e($row['category']) ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td><?= format_date($row['published_at']) ?></td>
                <td>
                    <a href="<?= base_url('blogs/create.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('blogs/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
