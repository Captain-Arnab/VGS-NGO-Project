<?php
$pageTitle = 'Beneficiaries';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $bid = (int) $_GET['delete'];
    $doc = $pdo->prepare('SELECT document_path FROM beneficiaries WHERE id = ?');
    $doc->execute([$bid]);
    delete_upload($doc->fetchColumn());
    $pdo->prepare('DELETE FROM beneficiaries WHERE id = ?')->execute([$bid]);
    redirect('beneficiaries/index.php', 'Beneficiary removed.');
}

$where = ['1=1'];
$params = [];
if (!empty($_GET['search'])) {
    $where[] = 'b.name LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['aid_category'])) {
    $where[] = 'b.aid_category = ?';
    $params[] = $_GET['aid_category'];
}
if (!empty($_GET['gender'])) {
    $where[] = 'b.gender = ?';
    $params[] = $_GET['gender'];
}
if (!empty($_GET['campaign_id'])) {
    $where[] = 'b.campaign_id = ?';
    $params[] = (int) $_GET['campaign_id'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'DATE(b.created_at) >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'DATE(b.created_at) <= ?';
    $params[] = $_GET['date_to'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM beneficiaries b WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$sql = "SELECT b.*, c.title AS campaign_title
        FROM beneficiaries b
        LEFT JOIN campaigns c ON c.id = b.campaign_id
        WHERE $whereSql ORDER BY b.created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$campaigns = $pdo->query('SELECT id, title FROM campaigns ORDER BY title')->fetchAll();
$campaignOpts = [];
foreach ($campaigns as $c) {
    $campaignOpts[$c['id']] = $c['title'];
}

$filterAction = base_url('beneficiaries/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Name', 'col' => 3],
    ['name' => 'aid_category', 'label' => 'Aid Category', 'type' => 'select', 'options' => ['Food'=>'Food','Education'=>'Education','Medical'=>'Medical','Shelter'=>'Shelter','Employment'=>'Employment','Other'=>'Other'], 'col' => 2],
    ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Male'=>'Male','Female'=>'Female','Other'=>'Other'], 'col' => 2],
    ['name' => 'campaign_id', 'label' => 'Campaign', 'type' => 'select', 'options' => $campaignOpts, 'col' => 2],
    ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">People receiving aid through your programs.</p>
    <a href="<?= base_url('beneficiaries/create.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Add Beneficiary</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>#</th><th>Name</th><th>Age</th><th>Gender</th><th>Aid</th><th>Campaign</th><th>Location</th><th>Added</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="text-center text-muted py-5">No beneficiaries.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($rows as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="fw-semibold"><?= e($row['name']) ?></td>
                <td><?= e($row['age'] ?? '—') ?></td>
                <td><?= e($row['gender'] ?? '—') ?></td>
                <td><?= e($row['aid_category']) ?></td>
                <td><?= e($row['campaign_title'] ?? '—') ?></td>
                <td><?= e(mb_strimwidth($row['address'] ?? '—', 0, 30, '…')) ?></td>
                <td><?= format_date($row['created_at']) ?></td>
                <td>
                    <a href="<?= base_url('beneficiaries/view.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>
                    <a href="<?= base_url('beneficiaries/create.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('beneficiaries/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
