<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/site_settings.php';
require_once dirname(__DIR__) . '/includes/certificate_helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('events/index.php');
}

$targetType = $_POST['target_type'] ?? 'participant';
$targetId = (int) ($_POST['target_id'] ?? 0);
$eventId = (int) ($_POST['event_id'] ?? 0);

if ($targetId <= 0 || $eventId <= 0 || !in_array($targetType, ['participant', 'organiser'], true)) {
    redirect('events/index.php', null, 'Invalid certificate request.');
}

$table = certificate_table_for($targetType);
$check = $pdo->prepare("SELECT id, name FROM {$table} WHERE id = ? AND event_id = ?");
$check->execute([$targetId, $eventId]);
$person = $check->fetch();
if (!$person) {
    redirect('events/index.php', null, ucfirst($targetType) . ' not found.');
}

$defaults = [
    'recipient_name' => $person['name'],
    'recipient_role' => $targetType === 'organiser' ? 'Organiser' : 'Participant',
    'event_name' => '',
    'event_date' => '',
    'event_location' => '',
];
$ev = $pdo->prepare('SELECT name, event_date, location FROM events WHERE id = ?');
$ev->execute([$eventId]);
if ($row = $ev->fetch()) {
    $defaults['event_name'] = $row['name'];
    $defaults['event_date'] = format_date($row['event_date']);
    $defaults['event_location'] = $row['location'] ?? 'India';
}

$saved = certificate_load_saved_data($pdo, $targetType, $targetId);
if ($saved) {
    $defaults = array_merge($defaults, $saved);
}

$data = certificate_build_data_from_request($_POST, $defaults);
certificate_prepare_signature($data);
$rendered = certificate_apply_vars($data);

try {
    $html = certificate_render_html($rendered);
    $pdfBinary = certificate_render_pdf($html);
} catch (RuntimeException $e) {
    redirect(
        'events/certificate.php?target_type=' . urlencode($targetType) . '&target_id=' . $targetId . '&event_id=' . $eventId,
        null,
        'Could not create PDF: ' . $e->getMessage()
    );
}

$dir = UPLOAD_PATH . '/certificates';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$filename = 'cert-' . $eventId . '-' . $targetType . '-' . $targetId . '-' . date('YmdHis') . '.pdf';
$relativePath = 'certificates/' . $filename;
$fullPath = $dir . '/' . $filename;
file_put_contents($fullPath, $pdfBinary);

$old = $pdo->prepare("SELECT certificate_path FROM {$table} WHERE id = ?");
$old->execute([$targetId]);
$oldPath = $old->fetchColumn();
if ($oldPath) {
    delete_upload($oldPath);
}

try {
    $pdo->prepare("UPDATE {$table} SET certificate_path = ?, certificate_source = 'generated' WHERE id = ?")
        ->execute([$relativePath, $targetId]);
} catch (PDOException $e) {
    $pdo->prepare("UPDATE {$table} SET certificate_path = ? WHERE id = ?")
        ->execute([$relativePath, $targetId]);
}

certificate_save_data($pdo, $targetType, $targetId, $data);

$label = ucfirst($targetType);
redirect(
    'events/view.php?id=' . $eventId,
    'Certificate assigned to ' . $person['name'] . ' and saved as PDF.'
);
