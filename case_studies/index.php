<?php
$pageTitle = 'Case Studies';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $img = $pdo->prepare('SELECT featured_image FROM case_studies WHERE id = ?');
    $img->execute([$id]);
    delete_upload($img->fetchColumn());
    $pdo->prepare('DELETE FROM case_studies WHERE id = ?')->execute([$id]);
    redirect('case_studies/index.php', 'Case study deleted.');
}

$statusFilter = $_GET['status'] ?? '';
$where = ['1=1'];
$params = [];
if ($statusFilter === 'Active' || $statusFilter === 'Inactive') {
    $where[] = 'cs.status = ?';
    $params[] = $statusFilter;
}
if (!empty($_GET['search'])) {
    $where[] = '(cs.title LIKE ? OR cs.subject_name LIKE ? OR cs.summary LIKE ?)';
    $q = '%' . $_GET['search'] . '%';
    $params[] = $q;
    $params[] = $q;
    $params[] = $q;
}
if (!empty($_GET['category'])) {
    $where[] = 'cs.category = ?';
    $params[] = $_GET['category'];
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM case_studies cs WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = max(1, get_int('page', 1));
$p = pagination($total, $page);

$sql = "SELECT cs.*, b.name AS beneficiary_name, c.title AS campaign_title,
        (SELECT COUNT(*) FROM case_study_milestones m WHERE m.case_study_id = cs.id) AS milestone_count
        FROM case_studies cs
        LEFT JOIN beneficiaries b ON b.id = cs.beneficiary_id
        LEFT JOIN campaigns c ON c.id = cs.campaign_id
        WHERE $whereSql
        ORDER BY cs.status ASC, cs.updated_at DESC
        LIMIT {$p['perPage']} OFFSET {$p['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filterAction = base_url('case_studies/index.php');
$fields = [
    ['name' => 'search', 'label' => 'Search', 'placeholder' => 'Title, subject, summary', 'col' => 3],
    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => ['Education'=>'Education','Medical'=>'Medical','Food'=>'Food','Shelter'=>'Shelter','Employment'=>'Employment','Other'=>'Other'], 'col' => 2],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [''=>'All','Active'=>'Active','Inactive'=>'Inactive (past)'], 'col' => 2],
];
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Impact stories with timeline — active cases and archived past studies.</p>
    <a href="<?= base_url('case_studies/create.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Add Case Study</a>
</div>

<?php include dirname(__DIR__) . '/includes/filter_bar.php'; ?>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Started</th>
                    <th>Milestones</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted py-5">No case studies found.</td></tr>
            <?php else: foreach ($rows as $row): ?>
            <tr>
                <td class="fw-semibold">
                    <a href="<?= base_url('case_studies/view.php?id=' . $row['id']) ?>" class="text-decoration-none"><?= e($row['title']) ?></a>
                </td>
                <td><?= e($row['subject_name'] ?? '—') ?></td>
                <td><?= e($row['category']) ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td><?= format_date($row['started_at']) ?></td>
                <td><span class="badge bg-light text-dark"><?= (int) $row['milestone_count'] ?></span></td>
                <td>
                    <a href="<?= base_url('case_studies/view.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light" title="View roadmap"><i class="fas fa-route"></i></a>
                    <a href="<?= base_url('case_studies/create.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a>
                    <a href="#" class="btn btn-sm btn-light text-danger" data-delete-url="<?= base_url('case_studies/index.php?delete=' . $row['id']) ?>"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0"><?php render_pagination($total, $page, $_GET); ?></div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
