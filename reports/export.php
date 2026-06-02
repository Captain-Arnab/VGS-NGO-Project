<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/report_exporter.php';

require_login();

$req = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$tab = $req['tab'] ?? 'donations';
$format = strtolower($req['format'] ?? 'pdf');
$dateFrom = $req['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $req['date_to'] ?? date('Y-m-d');
$chartImages = [];
if (!empty($req['chart_images']) && is_string($req['chart_images'])) {
    $decoded = json_decode($req['chart_images'], true);
    if (is_array($decoded)) {
        $chartImages = $decoded;
    }
}

$allowedTabs = ['donations', 'volunteers', 'campaigns', 'events', 'beneficiaries'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'donations';
}
if (!in_array($format, ['pdf', 'docx', 'doc'], true)) {
    $format = 'pdf';
}

try {
    $data = report_export_load_data($pdo, $tab, $dateFrom, $dateTo);
    $forWord = $format === 'docx' || $format === 'doc';
    $html = report_export_build_html($tab, $dateFrom, $dateTo, $data, $forWord, $chartImages);
    $filename = report_export_filename($tab, $forWord ? 'doc' : 'pdf');

    if ($forWord) {
        report_export_send_docx($html, $filename);
    }
    report_export_send_pdf($html, $filename);
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Could not export report. Ensure database migration is applied.';
    header('Location: ' . base_url('reports/index.php?tab=' . urlencode($tab)));
    exit;
}
