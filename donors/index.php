<?php
$pageTitle = 'Donors';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM donors WHERE id = ?')->execute([$id]);
    redirect('donors/index.php', 'Donor deleted successfully.');
}

$where = ['1=1'];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = '(d.name LIKE ? OR d.email LIKE ?)';
    $s = '%' . $_GET['search'] . '%';
    $params[] = $s;
    $params[] = $s;
}
if (!empty($_GET['donor_type'])) {
    $where[] = 'd.donor_type = ?';
    $params[] = $_GET['donor_type'];
}
if (!empty($_GET['category'])) {
    $where[] = 'd.category = ?';
    $params[] = $_GET['category'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'DATE(d.created_at) >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'DATE(d.created_at) <= ?';
    $params[] = $_GET['date_to'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM donors d WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$sql = "SELECT d.*, COALESCE(SUM(dn.amount),0) AS total_donated
        FROM donors d
        LEFT JOIN donations dn ON dn.donor_id = d.id
        WHERE $whereSql
        GROUP BY d.id
        ORDER BY d.created_at DESC
        LIMIT {$p['perPage']} OFFSET {$p['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donors = $stmt->fetchAll();

$filterAction = base_url('donors/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Name or email', 'col' => 3],
    ['name' => 'donor_type', 'label' => 'Type', 'type' => 'select', 'options' => ['Individual' => 'Individual', 'Company' => 'Company'], 'col' => 2],
    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => ['Regular' => 'Regular', 'One-time' => 'One-time', 'Corporate' => 'Corporate', 'Anonymous' => 'Anonymous'], 'col' => 2],
    ['name' => 'date_from', 'label' => 'Added From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'Added To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Manage donor records and giving history.</p>
    <a href="<?= base_url('donors/create.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Add New Donor</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th><th>Name</th><th>Type</th><th>Category</th><th>Email</th><th>Phone</th>
                    <th>Total Donated</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($donors)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">No donors found.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($donors as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="fw-semibold"><?= e($row['name']) ?></td>
                <td><?= e($row['donor_type']) ?></td>
                <td><?= e($row['category']) ?></td>
                <td><?= e($row['email'] ?? '—') ?></td>
                <td><?= e($row['phone'] ?? '—') ?></td>
                <td class="text-success fw-semibold"><?= format_currency((float)$row['total_donated']) ?></td>
                <td>
                    <a href="<?= base_url('donors/view.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light" title="View"><i class="fas fa-eye"></i></a>
                    <a href="<?= base_url('donors/create.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light" title="Edit"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('donors/index.php?delete=' . $row['id']) ?>" title="Delete"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
