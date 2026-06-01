<?php
$pageTitle = 'Donations';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $row = $pdo->prepare('SELECT campaign_id, amount FROM donations WHERE id = ?');
    $row->execute([$delId]);
    $don = $row->fetch();
    if ($don && $don['campaign_id']) {
        $pdo->prepare('UPDATE campaigns SET raised_amount = GREATEST(0, raised_amount - ?) WHERE id = ?')->execute([$don['amount'], $don['campaign_id']]);
    }
    $pdo->prepare('DELETE FROM donations WHERE id = ?')->execute([$delId]);
    redirect('donations/index.php', 'Donation deleted.');
}

$where = ['1=1'];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = 'dn.name LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['campaign_id'])) {
    $where[] = 'd.campaign_id = ?';
    $params[] = (int) $_GET['campaign_id'];
}
if (!empty($_GET['payment_mode'])) {
    $where[] = 'd.payment_mode = ?';
    $params[] = $_GET['payment_mode'];
}
if (!empty($_GET['amount_min'])) {
    $where[] = 'd.amount >= ?';
    $params[] = (float) $_GET['amount_min'];
}
if (!empty($_GET['amount_max'])) {
    $where[] = 'd.amount <= ?';
    $params[] = (float) $_GET['amount_max'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'd.donation_date >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'd.donation_date <= ?';
    $params[] = $_GET['date_to'];
}

$whereSql = implode(' AND ', $where);
$sumStmt = $pdo->prepare("SELECT COALESCE(SUM(d.amount),0) FROM donations d JOIN donors dn ON dn.id = d.donor_id WHERE $whereSql");
$sumStmt->execute($params);
$filteredTotal = (float) $sumStmt->fetchColumn();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM donations d JOIN donors dn ON dn.id = d.donor_id WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$sql = "SELECT d.*, dn.name AS donor_name, c.title AS campaign_title
        FROM donations d
        JOIN donors dn ON dn.id = d.donor_id
        LEFT JOIN campaigns c ON c.id = d.campaign_id
        WHERE $whereSql
        ORDER BY d.donation_date DESC, d.id DESC
        LIMIT {$p['perPage']} OFFSET {$p['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$campaigns = $pdo->query('SELECT id, title FROM campaigns ORDER BY title')->fetchAll();
$campaignOpts = [];
foreach ($campaigns as $c) {
    $campaignOpts[$c['id']] = $c['title'];
}

$filterAction = base_url('donations/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Donor Name', 'placeholder' => 'Search donor', 'col' => 2],
    ['name' => 'campaign_id', 'label' => 'Campaign', 'type' => 'select', 'options' => $campaignOpts, 'col' => 2],
    ['name' => 'payment_mode', 'label' => 'Payment Mode', 'type' => 'select', 'options' => array_combine(['Cash','Cheque','Bank Transfer','UPI','Online'], ['Cash','Cheque','Bank Transfer','UPI','Online']), 'col' => 2],
    ['name' => 'amount_min', 'label' => 'Min Amount', 'type' => 'number', 'col' => 2],
    ['name' => 'amount_max', 'label' => 'Max Amount', 'type' => 'number', 'col' => 2],
    ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Track all incoming contributions.</p>
    <a href="<?= base_url('donations/create.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Add New Donation</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="summary-bar">
    <i class="fas fa-indian-rupee-sign me-2 text-success"></i>
    Total (filtered): <span class="text-success"><?= format_currency($filteredTotal) ?></span>
    <span class="text-muted fw-normal ms-2">· <?= $total ?> record(s)</span>
</div>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>#</th><th>Donor</th><th>Campaign</th><th>Amount</th><th>Payment</th><th>Date</th><th>Purpose</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">No donations found.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($rows as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= e($row['donor_name']) ?></td>
                <td><?= e($row['campaign_title'] ?? '—') ?></td>
                <td class="fw-semibold text-success"><?= format_currency((float)$row['amount']) ?></td>
                <td><?= e($row['payment_mode']) ?></td>
                <td><?= format_date($row['donation_date']) ?></td>
                <td><?= e($row['purpose'] ?? '—') ?></td>
                <td>
                    <a href="<?= base_url('donations/create.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('donations/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
