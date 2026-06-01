<?php
$pageTitle = 'Add Donor';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$donor = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM donors WHERE id = ?');
    $stmt->execute([$id]);
    $donor = $stmt->fetch();
    if (!$donor) {
        redirect('donors/index.php', null, 'Donor not found.');
    }
    $pageTitle = 'Edit Donor';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post_string('name', 150);
    $donor_type = $_POST['donor_type'] ?? 'Individual';
    $company_name = post_string('company_name', 150);
    $email = post_string('email', 150);
    $phone = post_string('phone', 20);
    $address = trim($_POST['address'] ?? '');
    $pan_number = post_string('pan_number', 50);
    $category = $_POST['category'] ?? 'Regular';
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    $allowedTypes = ['Individual', 'Company'];
    $allowedCat = ['Regular', 'One-time', 'Corporate', 'Anonymous'];
    if (!in_array($donor_type, $allowedTypes, true)) {
        $donor_type = 'Individual';
    }
    if (!in_array($category, $allowedCat, true)) {
        $category = 'Regular';
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare('UPDATE donors SET name=?, donor_type=?, company_name=?, email=?, phone=?, address=?, pan_number=?, category=?, notes=? WHERE id=?')
                ->execute([$name, $donor_type, $company_name ?: null, $email ?: null, $phone ?: null, $address ?: null, $pan_number ?: null, $category, $notes ?: null, $id]);
            redirect('donors/index.php', 'Donor updated successfully.');
        } else {
            $pdo->prepare('INSERT INTO donors (name, donor_type, company_name, email, phone, address, pan_number, category, notes) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$name, $donor_type, $company_name ?: null, $email ?: null, $phone ?: null, $address ?: null, $pan_number ?: null, $category, $notes ?: null]);
            redirect('donors/index.php', 'Donor added successfully.');
        }
    }
    stash_form_errors($errors);
    $donor = compact('name', 'donor_type', 'company_name', 'email', 'phone', 'address', 'pan_number', 'category', 'notes');
}

$d = $donor ?? [];
?>

<div class="page-header-row">
    <a href="<?= base_url('donors/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="card card-shadow form-card">
    <div class="card-body p-4">
        <form method="post" class="js-prevent-double">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= e($d['name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Donor Type</label>
                    <?php foreach (['Individual', 'Company'] as $t): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="donor_type" id="type_<?= $t ?>" value="<?= $t ?>" <?= ($d['donor_type'] ?? 'Individual') === $t ? 'checked' : '' ?>>
                        <label class="form-check-label" for="type_<?= $t ?>"><?= $t ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-6 company-field-wrap">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="<?= e($d['company_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($d['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($d['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">PAN / Tax ID</label>
                    <input type="text" name="pan_number" class="form-control" value="<?= e($d['pan_number'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= e($d['address'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach (['Regular','One-time','Corporate','Anonymous'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($d['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= e($d['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-accent"><span class="btn-text"><?= $id ? 'Update' : 'Save' ?> Donor</span></button>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
