<?php
require_once dirname(__DIR__) . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('events/index.php');
}

$targetType = $_POST['target_type'] ?? 'participant';
$targetId = (int) ($_POST['target_id'] ?? 0);
$eventId = (int) ($_POST['event_id'] ?? 0);

if ($targetId <= 0 || $eventId <= 0 || !in_array($targetType, ['participant', 'organiser'], true)) {
    redirect('events/index.php', null, 'Invalid certificate target.');
}

$table = $targetType === 'organiser' ? 'event_organisers' : 'event_participants';
$check = $pdo->prepare("SELECT id, certificate_path FROM {$table} WHERE id = ? AND event_id = ?");
$check->execute([$targetId, $eventId]);
$row = $check->fetch();
if (!$row) {
    redirect('events/index.php', null, ucfirst($targetType) . ' not found.');
}

try {
    $path = upload_file($_FILES['certificate'], 'certificates', ['pdf', 'jpg', 'jpeg', 'png']);
    if (!$path) {
        redirect('events/view.php?id=' . $eventId, null, 'Please choose a certificate file.');
    }
    if (!empty($row['certificate_path'])) {
        delete_upload($row['certificate_path']);
    }
    $pdo->prepare("UPDATE {$table} SET certificate_path = ? WHERE id = ?")->execute([$path, $targetId]);
    try {
        $pdo->prepare("UPDATE {$table} SET certificate_source = 'upload' WHERE id = ?")->execute([$targetId]);
    } catch (PDOException $e) {
    }
    redirect('events/view.php?id=' . $eventId, ucfirst($targetType) . ' certificate uploaded.');
} catch (RuntimeException $ex) {
    redirect('events/view.php?id=' . $eventId, null, $ex->getMessage());
}
