<?php
$pageTitle = 'Volunteers';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $vid = (int) $_GET['delete'];
    $photo = $pdo->prepare('SELECT profile_photo FROM volunteers WHERE id = ?');
    $photo->execute([$vid]);
    $p = $photo->fetchColumn();
    delete_upload($p);
    $pdo->prepare('DELETE FROM volunteers WHERE id = ?')->execute([$vid]);
    redirect('volunteers/index.php', 'Volunteer removed.');
}

$where = ['1=1'];
$params = [];
if (!empty($_GET['search'])) {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $s = '%' . $_GET['search'] . '%';
    $params[] = $s;
    $params[] = $s;
}
if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['gender'])) {
    $where[] = 'gender = ?';
    $params[] = $_GET['gender'];
}
if (!empty($_GET['availability'])) {
    $where[] = 'availability = ?';
    $params[] = $_GET['availability'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'joined_date >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'joined_date <= ?';
    $params[] = $_GET['date_to'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM volunteers WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE $whereSql ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filterAction = base_url('volunteers/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Name or email', 'col' => 3],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Active'=>'Active','Inactive'=>'Inactive','Pending'=>'Pending'], 'col' => 2],
    ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Male'=>'Male','Female'=>'Female','Other'=>'Other'], 'col' => 2],
    ['name' => 'availability', 'label' => 'Availability', 'type' => 'select', 'options' => ['Full-time'=>'Full-time','Part-time'=>'Part-time','Weekends Only'=>'Weekends Only'], 'col' => 2],
    ['name' => 'date_from', 'label' => 'Joined From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'Joined To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Manage your volunteer network.</p>
    <a href="<?= base_url('volunteers/register.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Register Volunteer</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>#</th><th>Photo</th><th>Name</th><th>Email</th><th>Phone</th><th>Skills</th><th>Availability</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="text-center text-muted py-5">No volunteers found.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($rows as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td>
                    <?php if ($row['profile_photo']): ?>
                    <img src="<?= base_url('uploads/' . e($row['profile_photo'])) ?>" class="thumb-sm" alt="">
                    <?php else: ?>
                    <div class="thumb-placeholder"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </td>
                <td class="fw-semibold"><?= e($row['name']) ?></td>
                <td><?= e($row['email'] ?? '—') ?></td>
                <td><?= e($row['phone'] ?? '—') ?></td>
                <td><?= e(mb_strimwidth($row['skills'] ?? '—', 0, 40, '…')) ?></td>
                <td><?= e($row['availability'] ?? '—') ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td><?= format_date($row['joined_date']) ?></td>
                <td>
                    <a href="<?= base_url('volunteers/register.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('volunteers/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
