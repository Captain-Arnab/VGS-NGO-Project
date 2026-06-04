<?php
$pageTitle = 'Homepage Banners';
require_once dirname(__DIR__) . '/includes/header.php';

if (isset($_GET['delete'])) {
    $bid = (int) $_GET['delete'];
    $img = $pdo->prepare('SELECT background_image FROM homepage_banners WHERE id = ?');
    $img->execute([$bid]);
    delete_upload($img->fetchColumn());
    $pdo->prepare('DELETE FROM homepage_banners WHERE id = ?')->execute([$bid]);
    redirect('banners/index.php', 'Banner deleted.');
}

if (isset($_GET['toggle'])) {
    $bid = (int) $_GET['toggle'];
    $pdo->prepare('UPDATE homepage_banners SET is_active = IF(is_active=1,0,1) WHERE id = ?')->execute([$bid]);
    redirect('banners/index.php', 'Banner status updated.');
}

$rows = $pdo->query('SELECT * FROM homepage_banners ORDER BY sort_order ASC, id ASC')->fetchAll();
?>

<div class="page-header-row d-flex flex-wrap justify-content-between align-items-center gap-2">
    <p class="text-muted mb-0">Manage hero slider banners shown on the public website homepage.</p>
    <a href="<?= base_url('banners/edit.php') ?>" class="btn btn-accent"><i class="fas fa-plus me-1"></i> Add Banner</a>
</div>

<div class="card card-shadow table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Preview</th>
                    <th>Title</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No banners yet. Add your first homepage banner.</td></tr>
            <?php else:
                $i = 1;
                foreach ($rows as $row):
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <?php if ($row['background_image']): ?>
                            <img src="<?= base_url('uploads/' . e($row['background_image'])) ?>" class="thumb-sm" alt="">
                        <?php else: ?>
                            <div class="thumb-sm d-flex align-items-center justify-content-center" style="background:<?= e($row['bg_gradient'] ?: '#880e4f') ?>;color:#fff;font-size:10px;">Gradient</div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold">
                        <?= e($row['title']) ?>
                        <?php if ($row['subtitle']): ?><br><small class="text-muted"><?= e($row['subtitle']) ?></small><?php endif; ?>
                    </td>
                    <td><?= (int) $row['sort_order'] ?></td>
                    <td><?= status_badge($row['is_active'] ? 'Active' : 'Inactive') ?></td>
                    <td>
                        <a href="<?= base_url('banners/edit.php?id=' . $row['id']) ?>" class="btn btn-sm btn-light" title="Edit"><i class="fas fa-pen"></i></a>
                        <a href="<?= base_url('banners/index.php?toggle=' . $row['id']) ?>" class="btn btn-sm btn-light" title="Toggle"><i class="fas fa-toggle-<?= $row['is_active'] ? 'on' : 'off' ?>"></i></a>
                        <a href="<?= base_url('banners/index.php?delete=' . $row['id']) ?>" class="btn btn-sm btn-light text-danger" title="Delete" onclick="return confirm('Delete this banner?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
