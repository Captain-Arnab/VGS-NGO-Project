<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/report_exporter.php';

require_login();

$tab = $_GET['tab'] ?? 'donations';
$format = strtolower($_GET['format'] ?? 'pdf');
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$allowedTabs = ['donations', 'volunteers', 'campaigns', 'events', 'beneficiaries'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'donations';
}
if (!in_array($format, ['pdf', 'docx', 'doc'], true)) {
    $format = 'pdf';
}

try {
    $data = report_export_load_data($pdo, $tab, $dateFrom, $dateTo);
    $html = report_export_build_html($tab, $dateFrom, $dateTo, $data);
    $filename = report_export_filename($tab, $format === 'docx' || $format === 'doc' ? 'doc' : 'pdf');

    if ($format === 'docx' || $format === 'doc') {
        report_export_send_docx($html, $filename);
    }
    report_export_send_pdf($html, $filename);
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Could not export report. Ensure database migration is applied.';
    header('Location: ' . base_url('reports/index.php?tab=' . urlencode($tab)));
    exit;
}
