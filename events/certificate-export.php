<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/site_settings.php';
require_once dirname(__DIR__) . '/includes/certificate_helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid export request.');
}

$targetType = $_POST['target_type'] ?? 'participant';
$targetId = (int) ($_POST['target_id'] ?? 0);
$eventId = (int) ($_POST['event_id'] ?? 0);

if ($targetId <= 0 || $eventId <= 0 || !in_array($targetType, ['participant', 'organiser'], true)) {
    die('Invalid export request.');
}

$table = certificate_table_for($targetType);
$check = $pdo->prepare("SELECT name FROM {$table} WHERE id = ? AND event_id = ?");
$check->execute([$targetId, $eventId]);
$person = $check->fetch();
if (!$person) {
    die('Recipient not found.');
}

$saved = certificate_load_saved_data($pdo, $targetType, $targetId);
$defaults = [
    'recipient_name' => $person['name'],
    'recipient_role' => $targetType === 'organiser' ? 'Organiser' : 'Participant',
];
$ev = $pdo->prepare('SELECT name, event_date, location FROM events WHERE id = ?');
$ev->execute([$eventId]);
if ($row = $ev->fetch()) {
    $defaults['event_name'] = $row['name'];
    $defaults['event_date'] = format_date($row['event_date']);
    $defaults['event_location'] = $row['location'] ?? 'India';
}
if ($saved) {
    $defaults = array_merge($defaults, $saved);
}

$data = certificate_build_data_from_request($_POST, $defaults);
certificate_prepare_signature($data);
$rendered = certificate_apply_vars($data);
$html = certificate_render_html($rendered);

$safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $data['recipient_name']);
$downloadName = 'certificate-' . ($safeName ?: 'export') . '.pdf';

try {
    $pdf = certificate_render_pdf($html);
} catch (RuntimeException $e) {
    die('PDF export failed: ' . htmlspecialchars($e->getMessage()));
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
