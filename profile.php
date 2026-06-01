<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';

$admin = current_admin();
if (!$admin) {
    redirect('login.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post_string('name', 150);
    $email = post_string('email', 150);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    $dup = $pdo->prepare('SELECT id FROM admins WHERE email = ? AND id != ?');
    $dup->execute([$email, $admin['id']]);
    if ($dup->fetch()) {
        $errors[] = 'Email is already used by another account.';
    }

    $avatar = $admin['avatar'] ?? null;
    try {
        if (!empty($_FILES['avatar']['name'])) {
            if ($avatar) {
                delete_upload($avatar);
            }
            $avatar = upload_file($_FILES['avatar'], 'admins', ['jpg', 'jpeg', 'png', 'webp']);
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    $passwordHash = null;
    if ($new_password !== '' || $confirm_password !== '' || $current_password !== '') {
        $row = $pdo->prepare('SELECT password FROM admins WHERE id = ?');
        $row->execute([$admin['id']]);
        $hash = $row->fetchColumn();
        if (!password_verify($current_password, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } else {
            $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }

    if (empty($errors)) {
        if ($passwordHash) {
            $pdo->prepare('UPDATE admins SET name=?, email=?, avatar=?, password=? WHERE id=?')
                ->execute([$name, $email, $avatar, $passwordHash, $admin['id']]);
        } else {
            $pdo->prepare('UPDATE admins SET name=?, email=?, avatar=? WHERE id=?')
                ->execute([$name, $email, $avatar, $admin['id']]);
        }
        redirect('profile.php', 'Profile updated successfully.');
    }

    stash_form_errors($errors);
    $admin = array_merge($admin, ['name' => $name, 'email' => $email, 'avatar' => $avatar]);
}

$a = $admin;
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Update your account details and password.</p>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-shadow profile-card">
            <?php if (!empty($a['avatar'])): ?>
            <img src="<?= base_url('uploads/' . e($a['avatar'])) ?>" class="profile-avatar" alt="">
            <?php else: ?>
            <div class="profile-avatar mx-auto d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success fs-2 fw-bold">
                <?= e(admin_initials($a['name'])) ?>
            </div>
            <?php endif; ?>
            <h4 class="mt-3"><?= e($a['name']) ?></h4>
            <p class="text-muted"><?= e($a['email']) ?></p>
            <p class="small text-muted mb-0">Member since <?= format_date($a['created_at'] ?? date('Y-m-d')) ?></p>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-shadow form-card p-4">
            <form method="post" enctype="multipart/form-data" class="js-prevent-double">
                <h5 class="mb-3">Account details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= e($a['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="<?= e($a['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profile photo</label>
                        <input type="file" name="avatar" class="form-control file-upload-preview" accept="image/*">
                        <div class="file-preview"></div>
                    </div>
                </div>
                <hr class="my-4">
                <h5 class="mb-3">Change password</h5>
                <p class="text-muted small">Leave blank to keep your current password.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Current password</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">New password</label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirm new password</label>
                        <input type="password" name="confirm_password" class="form-control" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-accent mt-4"><span class="btn-text"><i class="fas fa-save me-1"></i> Save changes</span></button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
