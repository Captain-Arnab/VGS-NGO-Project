<?php
$pageTitle = 'Add Event';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$event = null;
$organisers = [];
$participants = [];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    if (!$event) {
        redirect('events/index.php', null, 'Event not found.');
    }
    $pageTitle = 'Edit Event';
    $oStmt = $pdo->prepare('SELECT * FROM event_organisers WHERE event_id = ?');
    $oStmt->execute([$id]);
    $organisers = $oStmt->fetchAll();
    $pStmt = $pdo->prepare('SELECT * FROM event_participants WHERE event_id = ?');
    $pStmt->execute([$id]);
    $participants = $pStmt->fetchAll();
}

$campaigns = $pdo->query('SELECT id, title FROM campaigns ORDER BY title')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post_string('name', 255);
    $description = trim($_POST['description'] ?? '');
    $location = post_string('location', 255);
    $maps_link = post_string('maps_link', 500);
    $event_date = $_POST['event_date'] ?: null;
    $event_time = $_POST['event_time'] ?: null;
    $event_type = $_POST['event_type'] ?? 'Other';
    $status = $_POST['status'] ?? 'Upcoming';
    $campaign_id = !empty($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;

    if ($name === '') {
        $errors[] = 'Event name is required.';
    }

    $banner = $event['banner_image'] ?? null;
    try {
        if (!empty($_FILES['banner_image']['name'])) {
            if ($banner) {
                delete_upload($banner);
            }
            $banner = upload_file($_FILES['banner_image'], 'events', ['jpg', 'jpeg', 'png', 'webp']);
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare('UPDATE events SET name=?, description=?, location=?, maps_link=?, event_date=?, event_time=?, event_type=?, status=?, banner_image=?, campaign_id=? WHERE id=?')
                ->execute([$name, $description ?: null, $location ?: null, $maps_link ?: null, $event_date, $event_time, $event_type, $status, $banner, $campaign_id, $id]);
            $eventId = $id;
            $pdo->prepare('DELETE FROM event_organisers WHERE event_id = ?')->execute([$eventId]);
            $pdo->prepare('DELETE FROM event_participants WHERE event_id = ?')->execute([$eventId]);
        } else {
            $pdo->prepare('INSERT INTO events (name, description, location, maps_link, event_date, event_time, event_type, status, banner_image, campaign_id) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$name, $description ?: null, $location ?: null, $maps_link ?: null, $event_date, $event_time, $event_type, $status, $banner, $campaign_id]);
            $eventId = (int) $pdo->lastInsertId();
        }

        $orgNames = $_POST['org_name'] ?? [];
        $orgPhones = $_POST['org_phone'] ?? [];
        $orgEmails = $_POST['org_email'] ?? [];
        $orgRoles = $_POST['org_role'] ?? [];
        $insOrg = $pdo->prepare('INSERT INTO event_organisers (event_id, name, phone, email, role) VALUES (?,?,?,?,?)');
        foreach ($orgNames as $idx => $on) {
            $on = trim($on);
            if ($on === '') {
                continue;
            }
            $insOrg->execute([$eventId, $on, $orgPhones[$idx] ?? null, $orgEmails[$idx] ?? null, $orgRoles[$idx] ?? null]);
        }

        $partNames = $_POST['part_name'] ?? [];
        $partEmails = $_POST['part_email'] ?? [];
        $partPhones = $_POST['part_phone'] ?? [];
        $partAddresses = $_POST['part_address'] ?? [];
        $existingDocs = $_POST['part_existing_doc'] ?? [];
        $insPart = $pdo->prepare('INSERT INTO event_participants (event_id, name, email, phone, address, document_path) VALUES (?,?,?,?,?,?)');

        foreach ($partNames as $idx => $pn) {
            $pn = trim($pn);
            if ($pn === '') {
                continue;
            }
            $docPath = $existingDocs[$idx] ?? null;
            if (!empty($_FILES['part_document']['name'][$idx])) {
                $file = [
                    'name' => $_FILES['part_document']['name'][$idx],
                    'type' => $_FILES['part_document']['type'][$idx],
                    'tmp_name' => $_FILES['part_document']['tmp_name'][$idx],
                    'error' => $_FILES['part_document']['error'][$idx],
                    'size' => $_FILES['part_document']['size'][$idx],
                ];
                try {
                    if ($docPath) {
                        delete_upload($docPath);
                    }
                    $docPath = upload_file($file, 'events/participants', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
                } catch (RuntimeException $ex) {
                    $errors[] = 'Participant document: ' . $ex->getMessage();
                }
            }
            $insPart->execute([$eventId, $pn, $partEmails[$idx] ?? null, $partPhones[$idx] ?? null, $partAddresses[$idx] ?? null, $docPath]);
        }

        if (empty($errors)) {
            redirect('events/index.php', $id ? 'Event updated.' : 'Event created.');
        }
    }
    stash_form_errors($errors);
    $event = array_merge($event ?? [], $_POST);
}

$e = $event ?? ['status' => 'Upcoming', 'event_type' => 'Other'];
if (empty($organisers)) {
    $organisers = [['name' => '', 'phone' => '', 'email' => '', 'role' => '']];
}
if (empty($participants)) {
    $participants = [['name' => '', 'email' => '', 'phone' => '', 'address' => '', 'document_path' => '']];
}
?>

<div class="page-header-row">
    <a href="<?= base_url('events/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card">
    <form method="post" enctype="multipart/form-data" class="js-prevent-double p-4">
        <ul class="nav nav-tabs nav-tabs-custom mb-4" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-info">Event Info</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-organisers">Organisers</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-participants">Participants</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-info">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Event Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= e($e['name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="event_type" class="form-select">
                            <?php foreach (['Workshop','Fundraiser','Awareness Drive','Cultural','Medical Camp','Other'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($e['event_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['Upcoming','Live','Past','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($e['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="text" name="event_date" class="form-control flatpickr" value="<?= e($e['event_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Time</label>
                        <input type="text" name="event_time" class="form-control flatpickr-time-only" value="<?= e($e['event_time'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= e($e['location'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Maps Link</label>
                        <input type="url" name="maps_link" class="form-control" value="<?= e($e['maps_link'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Link to Campaign</label>
                        <select name="campaign_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($campaigns as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)($e['campaign_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Banner Image</label>
                        <input type="file" name="banner_image" class="form-control file-upload-preview" accept="image/*">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= e($e['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-organisers">
                <div id="organisers-container">
                    <?php foreach ($organisers as $idx => $org): ?>
                    <div class="dynamic-row">
                        <div class="row g-2">
                            <div class="col-md-3"><input type="text" name="org_name[]" class="form-control" placeholder="Name" value="<?= e($org['name'] ?? '') ?>"></div>
                            <div class="col-md-2"><input type="text" name="org_phone[]" class="form-control" placeholder="Phone" value="<?= e($org['phone'] ?? '') ?>"></div>
                            <div class="col-md-3"><input type="email" name="org_email[]" class="form-control" placeholder="Email" value="<?= e($org['email'] ?? '') ?>"></div>
                            <div class="col-md-3"><input type="text" name="org_role[]" class="form-control" placeholder="Role" value="<?= e($org['role'] ?? '') ?>"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-light btn-sm remove-dynamic-row"><i class="fas fa-times"></i></button></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-light btn-sm add-dynamic-row" data-target="#organisers-container" data-template="#org-row-template"><i class="fas fa-plus me-1"></i> Add Organiser</button>
            </div>
            <div class="tab-pane fade" id="tab-participants">
                <div id="participants-container">
                    <?php foreach ($participants as $idx => $part): ?>
                    <div class="dynamic-row">
                        <div class="row g-2">
                            <div class="col-md-3"><input type="text" name="part_name[]" class="form-control" placeholder="Name" value="<?= e($part['name'] ?? '') ?>"></div>
                            <div class="col-md-2"><input type="email" name="part_email[]" class="form-control" placeholder="Email" value="<?= e($part['email'] ?? '') ?>"></div>
                            <div class="col-md-2"><input type="text" name="part_phone[]" class="form-control" placeholder="Phone" value="<?= e($part['phone'] ?? '') ?>"></div>
                            <div class="col-md-3"><input type="text" name="part_address[]" class="form-control" placeholder="Address" value="<?= e($part['address'] ?? '') ?>"></div>
                            <div class="col-md-1">
                                <input type="file" name="part_document[]" class="form-control form-control-sm">
                                <input type="hidden" name="part_existing_doc[]" value="<?= e($part['document_path'] ?? '') ?>">
                            </div>
                            <div class="col-md-1"><button type="button" class="btn btn-light btn-sm remove-dynamic-row"><i class="fas fa-times"></i></button></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-light btn-sm add-dynamic-row" data-target="#participants-container" data-template="#part-row-template"><i class="fas fa-plus me-1"></i> Add Participant</button>
            </div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text"><?= $id ? 'Update' : 'Save' ?> Event</span></button>
    </form>
</div>

<script type="text/template" id="org-row-template">
<div class="dynamic-row">
    <div class="row g-2">
        <div class="col-md-3"><input type="text" name="org_name[]" class="form-control" placeholder="Name"></div>
        <div class="col-md-2"><input type="text" name="org_phone[]" class="form-control" placeholder="Phone"></div>
        <div class="col-md-3"><input type="email" name="org_email[]" class="form-control" placeholder="Email"></div>
        <div class="col-md-3"><input type="text" name="org_role[]" class="form-control" placeholder="Role"></div>
        <div class="col-md-1"><button type="button" class="btn btn-light btn-sm remove-dynamic-row"><i class="fas fa-times"></i></button></div>
    </div>
</div>
</script>
<script type="text/template" id="part-row-template">
<div class="dynamic-row">
    <div class="row g-2">
        <div class="col-md-3"><input type="text" name="part_name[]" class="form-control" placeholder="Name"></div>
        <div class="col-md-2"><input type="email" name="part_email[]" class="form-control" placeholder="Email"></div>
        <div class="col-md-2"><input type="text" name="part_phone[]" class="form-control" placeholder="Phone"></div>
        <div class="col-md-3"><input type="text" name="part_address[]" class="form-control" placeholder="Address"></div>
        <div class="col-md-1"><input type="file" name="part_document[]" class="form-control form-control-sm"><input type="hidden" name="part_existing_doc[]" value=""></div>
        <div class="col-md-1"><button type="button" class="btn btn-light btn-sm remove-dynamic-row"><i class="fas fa-times"></i></button></div>
    </div>
</div>
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
