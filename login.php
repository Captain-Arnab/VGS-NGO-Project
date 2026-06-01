<?php
define('SKIP_AUTH', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, password FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            login_admin((int) $admin['id']);
            $redirect = $_SESSION['login_redirect'] ?? '';
            unset($_SESSION['login_redirect']);
            if ($redirect === '' || strpos($redirect, 'login.php') !== false) {
                $redirect = base_url('index.php');
            }
            $_SESSION['flash_success'] = 'Welcome back, ' . $admin['name'] . '!';
            header('Location: ' . $redirect);
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NGO Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-card card-shadow animate-fade-in">
        <div class="login-brand text-center mb-4">
            <div class="brand-icon mx-auto mb-3"><i class="fas fa-hands-holding-heart"></i></div>
            <h1 class="login-title">NGO Admin</h1>
            <p class="text-muted mb-0">Sign in to manage your impact dashboard</p>
        </div>
        <form method="post" class="js-prevent-double" id="loginForm">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control" required autofocus
                           value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@ngo.local">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="loginPassword" class="form-control" required placeholder="••••••••">
                    <button type="button" class="btn btn-light border" id="togglePassword" tabindex="-1"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-accent w-100 py-2"><span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Sign In</span></button>
        </form>
        <p class="login-hint text-center text-muted small mt-4 mb-0">
            Default: <code>admin@ngo.local</code> / <code>admin123</code>
        </p>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php swal_flash_script(); ?>
<script src="<?= base_url('assets/js/main.js') ?>"></script>
<?php if ($error): ?>
<script>
Swal.fire({ icon: 'error', title: 'Login failed', text: <?= json_encode($error) ?>, confirmButtonColor: '#2FA58A' });
</script>
<?php endif; ?>
<script>
document.getElementById('togglePassword')?.addEventListener('click', function () {
  const inp = document.getElementById('loginPassword');
  const icon = this.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
  else { inp.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
});
</script>
</body>
</html>
