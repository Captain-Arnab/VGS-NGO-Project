<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

if (!defined('SKIP_AUTH')) {
    require_login();
}

$currentAdmin = current_admin();
$pageTitle = $pageTitle ?? 'Dashboard';
$extraCss = $extraCss ?? [];
$extraHead = $extraHead ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= page_title($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <?php foreach ($extraCss as $css): ?>
    <link href="<?= e($css) ?>" rel="stylesheet">
    <?php endforeach; ?>
    <?= $extraHead ?>
</head>
<body class="admin-body">
<div class="app-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <button type="button" class="btn btn-icon sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <button type="button" class="btn btn-icon sidebar-collapse-btn d-none d-lg-inline-flex d-xl-none" id="sidebarCollapse" aria-label="Collapse sidebar">
                <i class="fas fa-angles-left"></i>
            </button>
            <div class="topbar-title">
                <h1 class="page-heading"><?= e($pageTitle) ?></h1>
            </div>
            <div class="topbar-actions ms-auto d-flex align-items-center gap-3">
                <span class="topbar-date text-muted d-none d-md-inline"><i class="far fa-calendar-alt me-1"></i><?= date('l, d M Y') ?></span>
                <?php if ($currentAdmin): ?>
                <div class="dropdown admin-profile-dropdown">
                    <button class="btn admin-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($currentAdmin['avatar'])): ?>
                        <img src="<?= base_url('uploads/' . e($currentAdmin['avatar'])) ?>" alt="" class="admin-avatar">
                        <?php else: ?>
                        <span class="admin-avatar admin-avatar-initials"><?= e(admin_initials($currentAdmin['name'])) ?></span>
                        <?php endif; ?>
                        <span class="admin-profile-name d-none d-sm-inline"><?= e($currentAdmin['name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-dropdown-menu shadow border-0">
                        <li class="dropdown-header px-3 py-2">
                            <strong><?= e($currentAdmin['name']) ?></strong>
                            <div class="small text-muted"><?= e($currentAdmin['email']) ?></div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= base_url('profile.php') ?>"><i class="fas fa-user-pen me-2 text-success"></i> My Profile</a></li>
                        <li><a class="dropdown-item text-danger" href="#" id="logoutLink"><i class="fas fa-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </header>
        <main class="content-area animate-fade-in">
