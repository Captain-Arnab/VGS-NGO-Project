<?php
$pageTitle = 'Campaigns';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $cid = (int) $_GET['delete'];
    $img = $pdo->prepare('SELECT banner_image FROM campaigns WHERE id = ?');
    $img->execute([$cid]);
    delete_upload($img->fetchColumn());
    $pdo->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$cid]);
    redirect('campaigns/index.php', 'Campaign deleted.');
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
if (!empty($_GET['date_from'])) {
    $where[] = 'start_date >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'end_date <= ?';
    $params[] = $_GET['date_to'];
}
if (!empty($_GET['goal_min'])) {
    $where[] = 'goal_amount >= ?';
    $params[] = (float) $_GET['goal_min'];
}
if (!empty($_GET['goal_max'])) {
    $where[] = 'goal_amount <= ?';
    $params[] = (float) $_GET['goal_max'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE $whereSql ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filterAction = base_url('campaigns/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Title', 'col' => 3],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Upcoming'=>'Upcoming','Active'=>'Active','Completed'=>'Completed','Paused'=>'Paused'], 'col' => 2],
    ['name' => 'date_from', 'label' => 'Start From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'End To', 'type' => 'date', 'col' => 2],
    ['name' => 'goal_min', 'label' => 'Min Goal', 'type' => 'number', 'col' => 2],
    ['name' => 'goal_max', 'label' => 'Max Goal', 'type' => 'number', 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Fundraising campaigns and progress tracking.</p>
    <a href="<?= base_url('campaigns/create.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Create Campaign</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>#</th><th>Banner</th><th>Title</th><th>Goal</th><th>Raised</th><th>Progress</th><th>Start</th><th>End</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="text-center text-muted py-5">No campaigns.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($rows as $row):
                    $pct = $row['goal_amount'] > 0 ? min(100, round(($row['raised_amount'] / $row['goal_amount']) * 100)) : 0;
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td>
                    <?php if ($row['banner_image']): ?>
                    <img src="<?= base_url('uploads/' . e($row['banner_image'])) ?>" class="thumb-sm" alt="">
                    <?php else: ?><div class="thumb-placeholder"><i class="fas fa-image"></i></div><?php endif; ?>
                </td>
                <td class="fw-semibold"><?= e($row['title']) ?></td>
                <td><?= format_currency((float)$row['goal_amount']) ?></td>
                <td class="text-success"><?= format_currency((float)$row['raised_amount']) ?></td>
                <td style="min-width:120px">
                    <div class="progress progress-campaign"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
                    <small class="text-muted"><?= $pct ?>%</small>
                </td>
                <td><?= format_date($row['start_date']) ?></td>
                <td><?= format_date($row['end_date']) ?></td>
                <td><?= status_badge($row['status'] === 'Active' ? 'Active' : ($row['status'] === 'Upcoming' ? 'Upcoming' : ($row['status'] === 'Paused' ? 'Pending' : 'Past'))) ?></td>
                <td>
                    <a href="<?= base_url('campaigns/view.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>
                    <a href="<?= base_url('campaigns/create.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('campaigns/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
