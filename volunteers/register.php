<?php
$pageTitle = 'Register Volunteer';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$volunteer = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM volunteers WHERE id = ?');
    $stmt->execute([$id]);
    $volunteer = $stmt->fetch();
    if (!$volunteer) {
        redirect('volunteers/index.php', null, 'Volunteer not found.');
    }
    $pageTitle = 'Edit Volunteer';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post_string('name', 150);
    $email = post_string('email', 150);
    $phone = post_string('phone', 20);
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?: null;
    $gender = $_POST['gender'] ?? null;
    $skills = trim($_POST['skills'] ?? '');
    $availability = $_POST['availability'] ?? 'Part-time';
    $status = $_POST['status'] ?? 'Pending';
    $joined_date = $_POST['joined_date'] ?: null;

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    $photoPath = $volunteer['profile_photo'] ?? null;
    try {
        if (!empty($_FILES['profile_photo']['name'])) {
            if ($photoPath) {
                delete_upload($photoPath);
            }
            $photoPath = upload_file($_FILES['profile_photo'], 'volunteers', ['jpg', 'jpeg', 'png', 'webp']);
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare('UPDATE volunteers SET name=?, email=?, phone=?, address=?, date_of_birth=?, gender=?, skills=?, availability=?, status=?, joined_date=?, profile_photo=? WHERE id=?')
                ->execute([$name, $email ?: null, $phone ?: null, $address ?: null, $date_of_birth, $gender, $skills ?: null, $availability, $status, $joined_date, $photoPath, $id]);
            redirect('volunteers/index.php', 'Volunteer updated.');
        } else {
            $pdo->prepare('INSERT INTO volunteers (name, email, phone, address, date_of_birth, gender, skills, availability, status, joined_date, profile_photo) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$name, $email ?: null, $phone ?: null, $address ?: null, $date_of_birth, $gender, $skills ?: null, $availability, $status, $joined_date, $photoPath]);
            redirect('volunteers/index.php', 'Volunteer registered.');
        }
    }
    stash_form_errors($errors);
    $volunteer = array_merge($volunteer ?? [], $_POST);
}

$v = $volunteer ?? ['status' => 'Pending', 'availability' => 'Part-time', 'joined_date' => date('Y-m-d')];
?>

<div class="page-header-row">
    <a href="<?= base_url('volunteers/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card p-4">
    <form method="post" enctype="multipart/form-data" class="js-prevent-double">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required value="<?= e($v['name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Profile Photo</label>
                <input type="file" name="profile_photo" class="form-control file-upload-preview" accept="image/*">
                <div class="file-preview">
                    <?php if (!empty($v['profile_photo'])): ?>
                    <img src="<?= base_url('uploads/' . e($v['profile_photo'])) ?>" class="thumb-sm" alt="">
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($v['email'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($v['phone'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="text" name="date_of_birth" class="form-control flatpickr" value="<?= e($v['date_of_birth'] ?? '') ?>"></div>
            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">—</option>
                    <?php foreach (['Male','Female','Other'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($v['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Joined Date</label><input type="text" name="joined_date" class="form-control flatpickr" value="<?= e($v['joined_date'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Address / City</label><textarea name="address" class="form-control" rows="2"><?= e($v['address'] ?? $v['city'] ?? '') ?></textarea></div>
            <?php
            $volCols = array_column($pdo->query('SHOW COLUMNS FROM volunteers')->fetchAll(), 'Field');
            if (in_array('area_of_interest', $volCols, true) && !empty($v['area_of_interest'])): ?>
            <div class="col-12">
                <div class="alert alert-light border small mb-0">
                    <strong>Website registration</strong>
                    <?php if (!empty($v['registration_source'])): ?> · Source: <?= e($v['registration_source']) ?><?php endif; ?>
                    <ul class="mb-0 mt-2">
                        <?php if (!empty($v['age'])): ?><li>Age: <?= e((string) $v['age']) ?></li><?php endif; ?>
                        <?php if (!empty($v['area_of_interest'])): ?><li>Interest: <?= e($v['area_of_interest']) ?></li><?php endif; ?>
                        <?php if (!empty($v['preferred_days'])): ?><li>Preferred days: <?= e($v['preferred_days']) ?></li><?php endif; ?>
                        <?php if (!empty($v['preferred_time'])): ?><li>Preferred time: <?= e($v['preferred_time']) ?></li><?php endif; ?>
                        <?php if (!empty($v['prior_experience'])): ?><li>Prior experience: <?= e($v['prior_experience']) ?></li><?php endif; ?>
                        <?php if (!empty($v['motivation'])): ?><li>Why volunteer: <?= e($v['motivation']) ?></li><?php endif; ?>
                        <?php if (!empty($v['additional_notes'])): ?><li>Notes: <?= e($v['additional_notes']) ?></li><?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-12"><label class="form-label">Skills</label><textarea name="skills" class="form-control" rows="2"><?= e($v['skills'] ?? '') ?></textarea></div>
            <div class="col-md-6">
                <label class="form-label">Availability</label>
                <select name="availability" class="form-select">
                    <?php foreach (['Full-time','Part-time','Weekends Only'] as $a): ?>
                    <option value="<?= $a ?>" <?= ($v['availability'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['Active','Inactive','Pending'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($v['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text"><?= $id ? 'Update' : 'Register' ?></span></button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
