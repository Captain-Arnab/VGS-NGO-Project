<?php
$pageTitle = 'Events';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $eid = (int) $_GET['delete'];
    $img = $pdo->prepare('SELECT banner_image FROM events WHERE id = ?');
    $img->execute([$eid]);
    delete_upload($img->fetchColumn());
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$eid]);
    redirect('events/index.php', 'Event deleted.');
}

$where = ['1=1'];
$params = [];
if (!empty($_GET['search'])) {
    $where[] = 'e.name LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['event_type'])) {
    $where[] = 'e.event_type = ?';
    $params[] = $_GET['event_type'];
}
if (!empty($_GET['status'])) {
    $where[] = 'e.status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'e.event_date >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'e.event_date <= ?';
    $params[] = $_GET['date_to'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM events e WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$sql = "SELECT e.*,
        (SELECT COUNT(*) FROM event_organisers o WHERE o.event_id = e.id) AS organisers_count,
        (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants_count
        FROM events e WHERE $whereSql ORDER BY e.event_date DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filterAction = base_url('events/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Event name', 'col' => 3],
    ['name' => 'event_type', 'label' => 'Type', 'type' => 'select', 'options' => ['Workshop'=>'Workshop','Fundraiser'=>'Fundraiser','Awareness Drive'=>'Awareness Drive','Cultural'=>'Cultural','Medical Camp'=>'Medical Camp','Other'=>'Other'], 'col' => 2],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Upcoming'=>'Upcoming','Live'=>'Live','Past'=>'Past','Cancelled'=>'Cancelled'], 'col' => 2],
    ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
    ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Plan and track NGO events.</p>
    <a href="<?= base_url('events/create.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Add New Event</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>#</th><th>Banner</th><th>Name</th><th>Type</th><th>Location</th><th>Date & Time</th><th>Status</th><th>Org.</th><th>Part.</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="text-center text-muted py-5">No events.</td></tr>
            <?php else:
                $i = $p['offset'] + 1;
                foreach ($rows as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?php if ($row['banner_image']): ?><img src="<?= base_url('uploads/' . e($row['banner_image'])) ?>" class="thumb-sm" alt=""><?php else: ?><div class="thumb-placeholder"><i class="fas fa-calendar"></i></div><?php endif; ?></td>
                <td class="fw-semibold"><?= e($row['name']) ?></td>
                <td><?= e($row['event_type']) ?></td>
                <td><?= e($row['location'] ?? '—') ?></td>
                <td><?= format_date($row['event_date']) ?><br><small class="text-muted"><?= $row['event_time'] ? date('h:i A', strtotime($row['event_time'])) : '' ?></small></td>
                <td><?= status_badge($row['status']) ?></td>
                <td><?= (int)$row['organisers_count'] ?></td>
                <td><?= (int)$row['participants_count'] ?></td>
                <td>
                    <a href="<?= base_url('events/view.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>
                    <a href="<?= base_url('events/create.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('events/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
