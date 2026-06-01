<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

logout_admin();
session_start();
$_SESSION['flash_success'] = 'You have been logged out successfully.';
header('Location: ' . base_url('login.php'));
exit;
