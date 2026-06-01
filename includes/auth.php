<?php
/**
 * Admin authentication helpers.
 */

function require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        $loginUrl = base_url('login.php');
        if ($next && strpos($next, 'login.php') === false) {
            $_SESSION['login_redirect'] = $next;
        }
        header('Location: ' . $loginUrl);
        exit;
    }
}

function current_admin(): ?array
{
    global $pdo;
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $cached = null;
    if ($cached !== null) {
        return $cached ?: null;
    }
    $stmt = $pdo->prepare('SELECT id, name, email, avatar, created_at FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $cached = $stmt->fetch() ?: false;
    return $cached ?: null;
}

function login_admin(int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function admin_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= strtoupper(substr($p, 0, 1));
    }
    return $initials ?: 'A';
}
