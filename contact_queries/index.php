<?php
$pageTitle = 'Contact Queries';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $pdo->prepare('DELETE FROM contact_queries WHERE id = ?')->execute([$id]);
        redirect('contact_queries/index.php', 'Query removed.');
    } catch (PDOException $e) {
        redirect('contact_queries/index.php', null, 'Could not delete query.');
    }
}

$where = ['1=1'];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ?)';
    $s = '%' . $_GET['search'] . '%';
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
}
if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
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

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_queries WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $page = max(1, get_int('page', 1));
    $p = pagination($total, $page);
    $stmt = $pdo->prepare("SELECT * FROM contact_queries WHERE $whereSql ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $newCount = (int) $pdo->query("SELECT COUNT(*) FROM contact_queries WHERE status = 'New'")->fetchColumn();
} catch (PDOException $e) {
    $rows = [];
    $total = 0;
    $p = pagination(0, 1);
    $newCount = 0;
    $tableMissing = true;
}

$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Name, email, subject…', 'col' => 4],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['New' => 'New', 'Read' => 'Read', 'Replied' => 'Replied', 'Closed' => 'Closed'], 'col' => 2],
    ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <div>
        <p class="text-muted mb-0">Messages submitted from the public <strong>Contact Us</strong> page.</p>
        <?php if (!empty($newCount)): ?>
        <span class="badge bg-warning text-dark mt-2"><?= $newCount ?> new</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($tableMissing)): ?>
<div class="alert alert-warning">Run <code>admin/database/migration_contact_queries.sql</code> on your database to enable contact form storage.</div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="text-center text-muted py-5">No contact queries found.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($rows as $row): ?>
            <tr class="<?= $row['status'] === 'New' ? 'table-warning' : '' ?>">
                <td><?= $i++ ?></td>
                <td class="fw-semibold"><?= e($row['name']) ?></td>
                <td><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></td>
                <td><?= e($row['phone'] ?? '—') ?></td>
                <td><?= e($row['subject']) ?></td>
                <td><?= e(mb_strimwidth($row['message'], 0, 60, '…')) ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td><?= format_date($row['created_at'], 'd M Y H:i') ?></td>
                <td>
                    <a href="<?= base_url('contact_queries/view.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light" title="View"><i class="fas fa-eye"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('contact_queries/index.php?delete=' . $row['id']) ?>" title="Delete"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
