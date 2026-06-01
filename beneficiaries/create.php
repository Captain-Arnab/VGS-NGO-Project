<?php
$pageTitle = 'Add Beneficiary';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$beneficiary = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM beneficiaries WHERE id = ?');
    $stmt->execute([$id]);
    $beneficiary = $stmt->fetch();
    if (!$beneficiary) {
        redirect('beneficiaries/index.php', null, 'Not found.');
    }
    $pageTitle = 'Edit Beneficiary';
}

$campaigns = $pdo->query('SELECT id, title FROM campaigns ORDER BY title')->fetchAll();
$events = $pdo->query('SELECT id, name FROM events ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post_string('name', 150);
    $age = !empty($_POST['age']) ? (int) $_POST['age'] : null;
    $gender = $_POST['gender'] ?? null;
    $phone = post_string('phone', 20);
    $address = trim($_POST['address'] ?? '');
    $family_size = !empty($_POST['family_size']) ? (int) $_POST['family_size'] : null;
    $aid_category = $_POST['aid_category'] ?? 'Other';
    $campaign_id = !empty($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;
    $event_id = !empty($_POST['event_id']) ? (int) $_POST['event_id'] : null;
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    $docPath = $beneficiary['document_path'] ?? null;
    try {
        if (!empty($_FILES['document']['name'])) {
            if ($docPath) {
                delete_upload($docPath);
            }
            $docPath = upload_file($_FILES['document'], 'beneficiaries', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare('UPDATE beneficiaries SET name=?, age=?, gender=?, phone=?, address=?, family_size=?, aid_category=?, campaign_id=?, event_id=?, document_path=?, notes=? WHERE id=?')
                ->execute([$name, $age, $gender, $phone ?: null, $address ?: null, $family_size, $aid_category, $campaign_id, $event_id, $docPath, $notes ?: null, $id]);
            redirect('beneficiaries/index.php', 'Updated.');
        } else {
            $pdo->prepare('INSERT INTO beneficiaries (name, age, gender, phone, address, family_size, aid_category, campaign_id, event_id, document_path, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$name, $age, $gender, $phone ?: null, $address ?: null, $family_size, $aid_category, $campaign_id, $event_id, $docPath, $notes ?: null]);
            redirect('beneficiaries/index.php', 'Beneficiary added.');
        }
    }
    stash_form_errors($errors);
    $beneficiary = array_merge($beneficiary ?? [], $_POST);
}

$b = $beneficiary ?? ['aid_category' => 'Other'];
?>

<div class="page-header-row">
    <a href="<?= base_url('beneficiaries/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card p-4">
    <form method="post" enctype="multipart/form-data" class="js-prevent-double">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?= e($b['name'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Age</label><input type="number" name="age" class="form-control" value="<?= e($b['age'] ?? '') ?>"></div>
            <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">—</option>
                    <?php foreach (['Male','Female','Other'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($b['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($b['phone'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Family Size</label><input type="number" name="family_size" class="form-control" value="<?= e($b['family_size'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= e($b['address'] ?? '') ?></textarea></div>
            <div class="col-md-4">
                <label class="form-label">Aid Category</label>
                <select name="aid_category" class="form-select">
                    <?php foreach (['Food','Education','Medical','Shelter','Employment','Other'] as $a): ?>
                    <option value="<?= $a ?>" <?= ($b['aid_category'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Campaign</label>
                <select name="campaign_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)($b['campaign_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($events as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= (int)($b['event_id'] ?? 0) === (int)$ev['id'] ? 'selected' : '' ?>><?= e($ev['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Document</label>
                <input type="file" name="document" class="form-control file-upload-preview" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </div>
            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"><?= e($b['notes'] ?? '') ?></textarea></div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text">Save</span></button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
