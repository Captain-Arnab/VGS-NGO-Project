<?php
$isDashboard = basename(current_page()) === 'index.php'
    && !preg_match('#/(donors|donations|volunteers|campaigns|events|beneficiaries|reports|documents|blogs|case_studies|media|banners|contact_queries)/#', current_page());
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-wrap">
            <img src="<?= e(org_logo_url()) ?>" alt="<?= e(get_setting('org_name')) ?>" class="brand-logo-img">
        </div>
        <div class="brand-text">
            <span class="brand-name"><?= e(get_setting('org_short_name', 'Bharati Admin')) ?></span>
            <span class="brand-tagline"><?= e(get_setting('org_tagline', ORG_TAGLINE)) ?></span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link<?= $isDashboard ? ' active' : '' ?>" href="<?= base_url('index.php') ?>" data-title="Dashboard">
                    <i class="fas fa-chart-pie"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-section">People</li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('donors') ?>" href="<?= base_url('donors/index.php') ?>" data-title="Donors">
                    <i class="fas fa-hand-holding-heart"></i><span>Donors</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('donations') ?>" href="<?= base_url('donations/index.php') ?>" data-title="Donations">
                    <i class="fas fa-indian-rupee-sign"></i><span>Donations</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('volunteers') ?>" href="<?= base_url('volunteers/index.php') ?>" data-title="Volunteers">
                    <i class="fas fa-people-group"></i><span>Volunteers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('contact_queries') ?>" href="<?= base_url('contact_queries/index.php') ?>" data-title="Contact Queries">
                    <i class="fas fa-envelope-open-text"></i><span>Contact Queries</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('beneficiaries') ?>" href="<?= base_url('beneficiaries/index.php') ?>" data-title="Beneficiaries">
                    <i class="fas fa-user-injured"></i><span>Beneficiaries</span>
                </a>
            </li>
            <li class="nav-section">Programs</li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('campaigns') ?>" href="<?= base_url('campaigns/index.php') ?>" data-title="Campaigns">
                    <i class="fas fa-bullhorn"></i><span>Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('events') ?>" href="<?= base_url('events/index.php') ?>" data-title="Events">
                    <i class="fas fa-calendar-days"></i><span>Events</span>
                </a>
            </li>
            <li class="nav-section">Impact</li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('case_studies') ?>" href="<?= base_url('case_studies/index.php') ?>" data-title="Case Studies">
                    <i class="fas fa-book-open"></i><span>Case Studies</span>
                </a>
            </li>
            <li class="nav-section">Content</li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('banners') ?>" href="<?= base_url('banners/index.php') ?>" data-title="Homepage Banners">
                    <i class="fas fa-panorama"></i><span>Homepage Banners</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('media') ?>" href="<?= base_url('media/index.php') ?>" data-title="Media Gallery">
                    <i class="fas fa-images"></i><span>Media Gallery</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('blogs') ?>" href="<?= base_url('blogs/index.php') ?>" data-title="Blog">
                    <i class="fas fa-blog"></i><span>Blog</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('documents') ?>" href="<?= base_url('documents/index.php') ?>" data-title="Documents">
                    <i class="fas fa-folder-open"></i><span>Documents</span>
                </a>
            </li>
            <li class="nav-section">Insights</li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('reports') ?>" href="<?= base_url('reports/index.php') ?>" data-title="Reports">
                    <i class="fas fa-chart-line"></i><span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= basename(current_page()) === 'settings.php' ? ' active' : '' ?>" href="<?= base_url('settings.php') ?>" data-title="Settings">
                    <i class="fas fa-gear"></i><span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <small>© <?= date('Y') ?> <?= e(get_setting('org_name', ORG_NAME)) ?></small>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
