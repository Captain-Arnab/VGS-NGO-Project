<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/site_settings.php';
require_once dirname(__DIR__) . '/includes/certificate_helpers.php';

require_login();

$preview = isset($_GET['preview']);
$targetType = $_REQUEST['target_type'] ?? 'participant';
$targetType = in_array($targetType, ['participant', 'organiser'], true) ? $targetType : 'participant';
$targetId = (int) ($_REQUEST['target_id'] ?? 0);
$eventId = (int) ($_REQUEST['event_id'] ?? 0);

$recipient = ['name' => 'Sample Recipient', 'role' => $targetType === 'organiser' ? 'Organiser' : 'Participant'];
$event = [
    'name' => 'Annual Fundraiser Gala',
    'event_date' => date('Y-m-d'),
    'location' => 'Mumbai',
];
$hasCert = false;

if (!$preview) {
    if ($targetId <= 0 || $eventId <= 0) {
        redirect('events/index.php', null, 'Invalid certificate request.');
    }

    if ($targetType === 'organiser') {
        $stmt = $pdo->prepare('SELECT o.*, e.name AS event_name, e.event_date, e.location FROM event_organisers o JOIN events e ON e.id = o.event_id WHERE o.id = ? AND o.event_id = ?');
    } else {
        $stmt = $pdo->prepare('SELECT p.*, e.name AS event_name, e.event_date, e.location FROM event_participants p JOIN events e ON e.id = p.event_id WHERE p.id = ? AND p.event_id = ?');
    }
    $stmt->execute([$targetId, $eventId]);
    $row = $stmt->fetch();
    if (!$row) {
        redirect('events/view.php?id=' . $eventId, null, ucfirst($targetType) . ' not found.');
    }
    $recipient = [
        'name' => $row['name'],
        'role' => $targetType === 'organiser' ? ($row['role'] ?: 'Organiser') : 'Participant',
    ];
    $event = $row;
    $hasCert = !empty($row['certificate_path']);
}

$defaults = [
    'recipient_name' => $recipient['name'],
    'recipient_role' => $recipient['role'],
    'event_name' => $event['event_name'] ?? $event['name'],
    'event_date' => format_date($event['event_date']),
    'event_location' => $event['location'] ?? 'India',
];

if (!$preview && $targetId > 0) {
    $saved = certificate_load_saved_data($pdo, $targetType, $targetId);
    if ($saved) {
        $defaults = array_merge($defaults, $saved);
    }
}

$data = certificate_build_data_from_request($_POST ?: $_GET, $defaults);
if (($data['signature_type'] ?? '') === 'none') {
    $data['signature_type'] = 'type';
}
if (!empty($data['signature_image']) && ($data['signature_type'] ?? '') === 'type' && ($data['signature_text'] ?? '') === '') {
    $data['signature_type'] = 'image';
}
if (($data['signature_text'] ?? '') === '' && ($data['signature_type'] ?? '') === 'type') {
    $data['signature_text'] = $data['cert_signatory'] ?? '';
}
$rendered = certificate_apply_vars($data);
$signaturePreviewUrl = certificate_signature_url($data['signature_image'] ?? '') ?? '';

$pageTitle = $preview ? 'Certificate preview' : 'Certificate — ' . $data['recipient_name'];
$extraJs = ['https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js'];

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="cert-page-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <a href="<?= $preview ? base_url('settings.php?tab=certificate') : base_url('events/view.php?id=' . $eventId) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i><?= $preview ? 'Certificate template' : 'Back to event' ?>
        </a>
        <h1 class="page-heading h4 mb-0 mt-2"><?= e($pageTitle) ?></h1>
        <?php if (!$preview): ?>
        <p class="text-muted small mb-0">Customize the certificate, then assign it to <?= e($data['recipient_name']) ?>.</p>
        <?php endif; ?>
    </div>
    <?php if (!$preview && $hasCert): ?>
    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check-circle me-1"></i>Certificate already assigned</span>
    <?php endif; ?>
</div>

<?php if ($preview): ?>
<div class="cert-preview-wrap mb-3">
    <?= certificate_render_body($rendered, false) ?>
</div>
<div class="d-flex gap-2">
    <button type="button" class="btn btn-accent" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
</div>
<?php else: ?>

<div class="cert-editor-layout">
    <div class="card card-shadow-sm cert-editor-panel">
        <div class="card-body">
            <h2 class="h6 mb-1">Edit certificate</h2>
            <p class="small text-muted mb-3">Live preview updates as you type. Placeholders: <code>{recipient_name}</code>, <code>{event_name}</code>, <code>{event_date}</code>, <code>{event_location}</code>, <code>{org_name}</code></p>

            <form id="cert-editor-form" class="cert-editor-form">
                <input type="hidden" name="target_type" value="<?= e($targetType) ?>">
                <input type="hidden" name="target_id" value="<?= (int) $targetId ?>">
                <input type="hidden" name="event_id" value="<?= (int) $eventId ?>">

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Recipient name</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="recipient_name" data-preview="name" value="<?= e($data['recipient_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Role</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="recipient_role" value="<?= e($data['recipient_role']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Event name</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="event_name" data-var="event_name" value="<?= e($data['event_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Event date</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="event_date" data-var="event_date" value="<?= e($data['event_date']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Location</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="event_location" data-var="event_location" value="<?= e($data['event_location']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Certificate title</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="cert_title" data-tpl="title" value="<?= e($data['cert_title']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Intro line</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="cert_intro" data-tpl="intro" value="<?= e($data['cert_intro']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Body text</label>
                        <textarea class="form-control form-control-sm cert-field" name="cert_body" rows="3" data-tpl="body"><?= e($data['cert_body']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Closing line</label>
                        <textarea class="form-control form-control-sm cert-field" name="cert_footer" rows="2" data-tpl="footer"><?= e($data['cert_footer']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Signatory name</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="cert_signatory" data-preview="signatory" value="<?= e($data['cert_signatory']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Signatory role</label>
                        <input type="text" class="form-control form-control-sm cert-field" name="cert_signatory_role" data-preview="signatory_role" value="<?= e($data['cert_signatory_role']) ?>">
                    </div>

                    <div class="col-12 cert-signature-section">
                        <label class="form-label small d-block mb-2">Signature</label>
                        <div class="btn-group btn-group-sm w-100 cert-sig-type-toggle" role="group">
                            <input type="radio" class="btn-check cert-field" name="signature_type" id="sig-type-type" value="type" <?= ($data['signature_type'] ?? 'type') === 'type' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="sig-type-type"><i class="fas fa-keyboard me-1"></i>Type signature</label>
                            <input type="radio" class="btn-check cert-field" name="signature_type" id="sig-type-image" value="image" <?= ($data['signature_type'] ?? '') === 'image' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="sig-type-image"><i class="fas fa-image me-1"></i>Upload image</label>
                            <input type="radio" class="btn-check cert-field" name="signature_type" id="sig-type-draw" value="draw" <?= ($data['signature_type'] ?? '') === 'draw' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="sig-type-draw"><i class="fas fa-pen-nib me-1"></i>Sign digitally</label>
                        </div>
                        <input type="hidden" name="signature_image" id="signature-image-path" value="<?= e($data['signature_image'] ?? '') ?>">
                        <input type="hidden" name="signature_data" id="signature-data-field" value="">

                        <div class="cert-sig-panel cert-sig-panel-type mt-2" id="sig-panel-type">
                            <label class="form-label small mb-1" for="signature-text-input">Typed signature</label>
                            <input type="text" class="form-control form-control-sm cert-field" name="signature_text" id="signature-text-input" value="<?= e($data['signature_text'] ?? '') ?>" placeholder="e.g. John Smith" maxlength="80" autocomplete="off">
                            <p class="small text-muted mb-0 mt-1">Shown in script style above the line on the certificate.</p>
                        </div>

                        <div class="cert-sig-panel cert-sig-panel-image mt-2" id="sig-panel-image" hidden>
                            <input type="file" class="form-control form-control-sm" id="signature-file-input" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif">
                            <p class="small text-muted mb-1 mt-1">PNG or JPG, max 2 MB. Transparent background works best.</p>
                            <div class="cert-sig-upload-preview" id="sig-upload-preview" hidden>
                                <img src="" alt="Signature preview" id="sig-upload-preview-img">
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" id="sig-upload-clear">Remove</button>
                            </div>
                        </div>

                        <div class="cert-sig-panel cert-sig-panel-draw mt-2" id="sig-panel-draw" hidden>
                            <p class="small text-muted mb-1">Draw your signature with mouse or finger.</p>
                            <div class="cert-signature-pad-wrap">
                                <canvas id="signature-canvas" width="400" height="120" aria-label="Draw signature"></canvas>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="sig-draw-clear">Clear</button>
                                <button type="button" class="btn btn-sm btn-accent" id="sig-draw-apply">Apply to preview</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="cert-actions mt-4 pt-3 border-top">
                <p class="small fw-semibold mb-2">Actions</p>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" action="<?= base_url('events/certificate-save.php') ?>" id="cert-use-form" class="d-inline">
                        <?php foreach ($data as $key => $val): if (in_array($key, ['org_name', 'signature_data'], true)) continue; ?>
                        <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $val) ?>" class="cert-sync-hidden" data-name="<?= e($key) ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="signature_data" value="" class="cert-sync-hidden" data-name="signature_data" id="sync-signature-data">
                        <input type="hidden" name="target_type" value="<?= e($targetType) ?>">
                        <input type="hidden" name="target_id" value="<?= (int) $targetId ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $eventId ?>">
                        <button type="submit" class="btn btn-accent" id="btn-use-cert">
                            <i class="fas fa-check-circle me-1"></i>Use this certificate
                        </button>
                    </form>
                    <form method="post" action="<?= base_url('events/certificate-export.php') ?>" id="cert-pdf-form" class="d-inline" target="_blank">
                        <?php foreach ($data as $key => $val): if (in_array($key, ['org_name', 'signature_data'], true)) continue; ?>
                        <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $val) ?>" class="cert-sync-hidden" data-name="<?= e($key) ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="signature_data" value="" class="cert-sync-hidden" data-name="signature_data" id="sync-signature-data-pdf">
                        <input type="hidden" name="target_type" value="<?= e($targetType) ?>">
                        <input type="hidden" name="target_id" value="<?= (int) $targetId ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $eventId ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-file-pdf me-1"></i>Download PDF
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary" id="btn-download-png">
                        <i class="fas fa-image me-1"></i>Download as image
                    </button>
                </div>
                <p class="small text-muted mt-2 mb-0"><strong>Use this certificate</strong> saves a PDF and links it to this <?= e($targetType) ?> on the event page.</p>
            </div>
        </div>
    </div>

    <div class="cert-preview-column">
        <div class="card card-shadow-sm">
            <div class="card-header bg-transparent py-2">
                <span class="small fw-semibold text-muted"><i class="fas fa-eye me-1"></i>Live preview</span>
            </div>
            <div class="card-body cert-preview-scroll p-2 p-md-3">
                <div class="cert-preview-scale">
                    <?= certificate_render_body($rendered, false) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('cert-editor-form');
    if (!form) return;

    var orgName = <?= json_encode(get_setting('org_name', ORG_NAME), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var initialSignatureUrl = <?= json_encode($signaturePreviewUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var signaturePreviewSrc = initialSignatureUrl || '';

    var sigPanelImage = document.getElementById('sig-panel-image');
    var sigPanelDraw = document.getElementById('sig-panel-draw');
    var sigFileInput = document.getElementById('signature-file-input');
    var sigDataField = document.getElementById('signature-data-field');
    var sigImagePath = document.getElementById('signature-image-path');
    var sigCanvas = document.getElementById('signature-canvas');
    var sigUploadPreview = document.getElementById('sig-upload-preview');
    var sigUploadPreviewImg = document.getElementById('sig-upload-preview-img');
    var drawCtx = sigCanvas ? sigCanvas.getContext('2d') : null;
    var drawing = false;
    var lastX = 0;
    var lastY = 0;

    function getSignatureType() {
        var checked = form.querySelector('input[name="signature_type"]:checked');
        return checked ? checked.value : 'none';
    }

    function fieldValues() {
        var fd = new FormData(form);
        var o = {};
        fd.forEach(function (v, k) { o[k] = v; });
        o.org_name = orgName;
        o.signature_type = getSignatureType();
        o.signature_data = sigDataField ? sigDataField.value : '';
        o.signature_image = sigImagePath ? sigImagePath.value : '';
        return o;
    }

    function applyPlaceholders(tpl, vars) {
        if (!tpl) return '';
        return tpl.replace(/\{(\w+)\}/g, function (_, key) {
            return vars[key] !== undefined ? vars[key] : '';
        });
    }

    function syncHiddenForms() {
        var vals = fieldValues();
        document.querySelectorAll('.cert-sync-hidden').forEach(function (inp) {
            var n = inp.getAttribute('data-name') || inp.name;
            if (vals[n] !== undefined) inp.value = vals[n];
        });
        document.querySelectorAll('#cert-use-form input[name], #cert-pdf-form input[name]').forEach(function (inp) {
            if (vals[inp.name] !== undefined) inp.value = vals[inp.name];
        });
    }

    function clearSignatureVisual(visual) {
        var divider = visual.querySelector('.cert-sign-divider');
        visual.querySelectorAll('.cert-signature-img, .cert-signature-typed').forEach(function (el) { el.remove(); });
        if (!divider) {
            var line = document.createElement('div');
            line.className = 'cert-sign-divider line';
            visual.appendChild(line);
        }
    }

    function updateSignaturePreview() {
        var root = document.getElementById('certificate-preview');
        if (!root) return;
        var signBlock = root.querySelector('.cert-sign');
        if (!signBlock) return;
        var visual = signBlock.querySelector('.cert-sign-visual');
        if (!visual) return;
        var divider = visual.querySelector('.cert-sign-divider');
        var type = getSignatureType();
        clearSignatureVisual(visual);

        if (type === 'type') {
            var text = (form.querySelector('[name="signature_text"]')?.value || '').trim();
            visual.classList.toggle('cert-sign-visual--line-only', !text);
            if (text) {
                var p = document.createElement('p');
                p.className = 'cert-signature-typed';
                p.textContent = text;
                visual.insertBefore(p, divider);
            }
        } else if ((type === 'image' || type === 'draw') && signaturePreviewSrc) {
            visual.classList.remove('cert-sign-visual--line-only');
            var img = document.createElement('img');
            img.className = 'cert-signature-img';
            img.alt = '';
            img.src = signaturePreviewSrc;
            visual.insertBefore(img, divider);
        } else {
            visual.classList.add('cert-sign-visual--line-only');
        }
    }

    function toggleSignaturePanels() {
        var type = getSignatureType();
        var sigPanelType = document.getElementById('sig-panel-type');
        if (sigPanelType) sigPanelType.hidden = type !== 'type';
        if (sigPanelImage) sigPanelImage.hidden = type !== 'image';
        if (sigPanelDraw) sigPanelDraw.hidden = type !== 'draw';
        if (type === 'type') {
            signaturePreviewSrc = '';
            if (sigDataField) sigDataField.value = '';
        } else if (type === 'image' && initialSignatureUrl && !signaturePreviewSrc) {
            signaturePreviewSrc = initialSignatureUrl;
        }
        updateSignaturePreview();
    }

    function initSignatureCanvas() {
        if (!drawCtx || !sigCanvas) return;
        drawCtx.strokeStyle = '#002147';
        drawCtx.lineWidth = 2.2;
        drawCtx.lineCap = 'round';
        drawCtx.lineJoin = 'round';
    }

    function canvasPos(e) {
        var rect = sigCanvas.getBoundingClientRect();
        var scaleX = sigCanvas.width / rect.width;
        var scaleY = sigCanvas.height / rect.height;
        var clientX = e.touches ? e.touches[0].clientX : e.clientX;
        var clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function startDraw(e) {
        e.preventDefault();
        drawing = true;
        var p = canvasPos(e);
        lastX = p.x;
        lastY = p.y;
    }

    function moveDraw(e) {
        if (!drawing) return;
        e.preventDefault();
        var p = canvasPos(e);
        drawCtx.beginPath();
        drawCtx.moveTo(lastX, lastY);
        drawCtx.lineTo(p.x, p.y);
        drawCtx.stroke();
        lastX = p.x;
        lastY = p.y;
    }

    function endDraw() {
        drawing = false;
    }

    function clearCanvas() {
        if (!drawCtx || !sigCanvas) return;
        drawCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        if (sigDataField) sigDataField.value = '';
    }

    function applyDrawnSignature() {
        if (!sigCanvas) return;
        var dataUrl = sigCanvas.toDataURL('image/png');
        if (sigDataField) sigDataField.value = dataUrl;
        if (sigImagePath) sigImagePath.value = '';
        signaturePreviewSrc = dataUrl;
        updateSignaturePreview();
        syncHiddenForms();
    }

    function setSignatureFromFile(file) {
        if (!file || !file.type.match(/^image\//)) {
            alert('Please choose an image file.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Image must be 2 MB or smaller.');
            return;
        }
        var reader = new FileReader();
        reader.onload = function () {
            var dataUrl = reader.result;
            if (sigDataField) sigDataField.value = dataUrl;
            if (sigImagePath) sigImagePath.value = '';
            signaturePreviewSrc = dataUrl;
            if (sigUploadPreviewImg) sigUploadPreviewImg.src = dataUrl;
            if (sigUploadPreview) sigUploadPreview.hidden = false;
            updateSignaturePreview();
            syncHiddenForms();
        };
        reader.readAsDataURL(file);
    }

    function updatePreview() {
        var v = fieldValues();
        var vars = {
            recipient_name: v.recipient_name,
            participant_name: v.recipient_name,
            recipient_role: v.recipient_role,
            event_name: v.event_name,
            event_date: v.event_date,
            event_location: v.event_location,
            org_name: orgName
        };
        var root = document.getElementById('certificate-preview');
        if (!root) return;
        var title = applyPlaceholders(v.cert_title, vars);
        var intro = applyPlaceholders(v.cert_intro, vars);
        var body = applyPlaceholders(v.cert_body, vars);
        var footer = applyPlaceholders(v.cert_footer, vars);
        var elTitle = root.querySelector('.cert-title');
        var elIntro = root.querySelector('.cert-intro');
        var elName = root.querySelector('.cert-name');
        var elBody = root.querySelector('.cert-body');
        var elFooter = root.querySelector('.cert-footer');
        var elSign = root.querySelector('.cert-signatory');
        var elSignRole = root.querySelector('.cert-signatory-role');
        if (elTitle) elTitle.textContent = title;
        if (elIntro) elIntro.textContent = intro;
        if (elName) elName.textContent = v.recipient_name;
        if (elBody) elBody.innerHTML = body.replace(/\n/g, '<br>');
        if (elFooter) elFooter.textContent = footer;
        if (elSign) elSign.textContent = v.cert_signatory;
        if (elSignRole) elSignRole.textContent = v.cert_signatory_role;
        updateSignaturePreview();
        syncHiddenForms();
    }

    form.querySelectorAll('.cert-field').forEach(function (el) {
        el.addEventListener('input', updatePreview);
        el.addEventListener('change', function () {
            if (el.name === 'signature_type') {
                toggleSignaturePanels();
            }
            updatePreview();
        });
    });

    if (sigFileInput) {
        sigFileInput.addEventListener('change', function () {
            if (sigFileInput.files && sigFileInput.files[0]) {
                setSignatureFromFile(sigFileInput.files[0]);
            }
        });
    }
    document.getElementById('sig-upload-clear')?.addEventListener('click', function () {
        if (sigFileInput) sigFileInput.value = '';
        if (sigUploadPreview) sigUploadPreview.hidden = true;
        if (sigDataField) sigDataField.value = '';
        if (sigImagePath) sigImagePath.value = '';
        signaturePreviewSrc = '';
        updateSignaturePreview();
        syncHiddenForms();
    });

    if (sigCanvas) {
        initSignatureCanvas();
        sigCanvas.addEventListener('mousedown', startDraw);
        sigCanvas.addEventListener('mousemove', moveDraw);
        sigCanvas.addEventListener('mouseup', endDraw);
        sigCanvas.addEventListener('mouseleave', endDraw);
        sigCanvas.addEventListener('touchstart', startDraw, { passive: false });
        sigCanvas.addEventListener('touchmove', moveDraw, { passive: false });
        sigCanvas.addEventListener('touchend', endDraw);
    }
    document.getElementById('sig-draw-clear')?.addEventListener('click', clearCanvas);
    document.getElementById('sig-draw-apply')?.addEventListener('click', applyDrawnSignature);

    if (initialSignatureUrl && getSignatureType() === 'image') {
        if (sigUploadPreviewImg) sigUploadPreviewImg.src = initialSignatureUrl;
        if (sigUploadPreview) sigUploadPreview.hidden = false;
        signaturePreviewSrc = initialSignatureUrl;
    }

    toggleSignaturePanels();
    updatePreview();

    document.getElementById('btn-download-png')?.addEventListener('click', function () {
        var node = document.getElementById('certificate-preview');
        if (!node || typeof html2canvas === 'undefined') {
            alert('Image export is loading. Please try again.');
            return;
        }
        var btn = this;
        btn.disabled = true;
        var scaleWrap = node.closest('.cert-preview-scale');
        var prevTransform = scaleWrap ? scaleWrap.style.transform : '';
        if (scaleWrap) scaleWrap.style.transform = 'none';
        html2canvas(node, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            width: node.offsetWidth,
            height: node.offsetHeight
        }).then(function (canvas) {
            var name = (fieldValues().recipient_name || 'certificate').replace(/[^\w\-]+/g, '_');
            var link = document.createElement('a');
            link.download = 'certificate-' + name + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }).catch(function () {
            alert('Could not create image. Ensure the logo loads from this site.');
        }).finally(function () {
            if (scaleWrap) scaleWrap.style.transform = prevTransform;
            btn.disabled = false;
        });
    });

    function beforeSubmit() {
        if (getSignatureType() === 'draw' && sigCanvas && drawCtx) {
            var blank = document.createElement('canvas');
            blank.width = sigCanvas.width;
            blank.height = sigCanvas.height;
            if (sigCanvas.toDataURL() !== blank.toDataURL()) {
                applyDrawnSignature();
            }
        }
        syncHiddenForms();
    }

    document.getElementById('cert-use-form')?.addEventListener('submit', beforeSubmit);
    document.getElementById('cert-pdf-form')?.addEventListener('submit', beforeSubmit);
})();
</script>
<?php endif; ?>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
