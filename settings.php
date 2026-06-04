<?php
$pageTitle = 'Organization Settings';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$tab = $_GET['tab'] ?? 'branding';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? 'branding';

    if ($section === 'branding') {
        set_setting($pdo, 'org_name', post_string('org_name', 255));
        set_setting($pdo, 'org_tagline', post_string('org_tagline', 500));
        set_setting($pdo, 'org_short_name', post_string('org_short_name', 100));
        try {
            if (!empty($_FILES['org_logo']['name'])) {
                $path = upload_file($_FILES['org_logo'], 'settings', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                if ($path) {
                    $old = get_setting('org_logo');
                    if ($old && str_starts_with($old, 'uploads/')) {
                        delete_upload($old);
                    }
                    set_setting($pdo, 'org_logo', $path);
                }
            }
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
        if (empty($errors)) {
            redirect('settings.php?tab=branding', 'Organization branding updated.');
        }
    }

    if ($section === 'certificate') {
        set_setting($pdo, 'cert_title', post_string('cert_title', 255));
        set_setting($pdo, 'cert_intro', post_string('cert_intro', 500));
        set_setting($pdo, 'cert_body', trim($_POST['cert_body'] ?? ''));
        set_setting($pdo, 'cert_footer', trim($_POST['cert_footer'] ?? ''));
        set_setting($pdo, 'cert_signatory', post_string('cert_signatory', 150));
        set_setting($pdo, 'cert_signatory_role', post_string('cert_signatory_role', 150));
        redirect('settings.php?tab=certificate', 'Certificate template saved.');
    }

    if ($section === 'donation') {
        set_setting($pdo, 'donation_bank_name', post_string('donation_bank_name', 150));
        set_setting($pdo, 'donation_account_number', post_string('donation_account_number', 50));
        set_setting($pdo, 'donation_ifsc', post_string('donation_ifsc', 20));
        set_setting($pdo, 'donation_branch', post_string('donation_branch', 150));
        redirect('settings.php?tab=donation', 'Donation bank details saved.');
    }
}

if (!empty($errors)) {
    stash_form_errors($errors);
}

$placeholdersHelp = '{recipient_name}, {recipient_role}, {participant_name}, {event_name}, {event_date}, {event_location}, {org_name}';
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Logo, organization name, and certificate template used across invoices, reports, and events.</p>
</div>

<ul class="nav nav-tabs nav-tabs-report mb-4">
    <li class="nav-item"><a class="nav-link<?= $tab === 'branding' ? ' active' : '' ?>" href="?tab=branding">Branding & logo</a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'donation' ? ' active' : '' ?>" href="?tab=donation">Donation / bank</a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'certificate' ? ' active' : '' ?>" href="?tab=certificate">Certificate template</a></li>
</ul>

<?php if ($tab === 'branding'): ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-shadow p-4 text-center">
            <img src="<?= e(org_logo_url()) ?>" alt="Logo" class="settings-logo-preview mb-3" id="logoPreview">
            <p class="small text-muted mb-0">Current logo — admin sidebar, login, invoices, certificates, and <strong>public website</strong> header/footer.</p>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-shadow p-4">
            <form method="post" enctype="multipart/form-data" class="js-prevent-double">
                <input type="hidden" name="section" value="branding">
                <div class="mb-3">
                    <label class="form-label">Organization name</label>
                    <input type="text" name="org_name" class="form-control" value="<?= e(get_setting('org_name')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="org_tagline" class="form-control" value="<?= e(get_setting('org_tagline')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin panel short name</label>
                    <input type="text" name="org_short_name" class="form-control" value="<?= e(get_setting('org_short_name')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload new logo</label>
                    <input type="file" name="org_logo" class="form-control" accept="image/*" id="logoUpload">
                    <small class="text-muted">JPG, PNG, or WebP. Recommended max height <strong>80–100 px</strong> for the website header (width auto). Default theme logo: <code>website/assets/img/logo.jpeg</code>.</small>
                </div>
                <button type="submit" class="btn btn-accent"><span class="btn-text">Save branding</span></button>
            </form>
        </div>
    </div>
</div>
<?php elseif ($tab === 'donation'): ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-shadow p-4">
            <p class="text-muted">These details appear on the public <strong>Donate</strong> page. Leave blank to show placeholders until you add real bank information.</p>
            <form method="post" class="js-prevent-double">
                <input type="hidden" name="section" value="donation">
                <div class="mb-3">
                    <label class="form-label">Organization name (display)</label>
                    <input type="text" class="form-control" value="<?= e(get_setting('org_name')) ?>" disabled>
                    <small class="text-muted">Edit under Branding & logo.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bank name</label>
                    <input type="text" name="donation_bank_name" class="form-control" value="<?= e(get_setting('donation_bank_name')) ?>" placeholder="e.g. State Bank of India">
                </div>
                <div class="mb-3">
                    <label class="form-label">Account number</label>
                    <input type="text" name="donation_account_number" class="form-control" value="<?= e(get_setting('donation_account_number')) ?>">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">IFSC code</label>
                        <input type="text" name="donation_ifsc" class="form-control" value="<?= e(get_setting('donation_ifsc')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" name="donation_branch" class="form-control" value="<?= e(get_setting('donation_branch')) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-accent mt-4"><span class="btn-text">Save bank details</span></button>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-shadow p-4">
            <form method="post" class="js-prevent-double">
                <input type="hidden" name="section" value="certificate">
                <p class="small text-muted mb-3">Placeholders: <code><?= e($placeholdersHelp) ?></code></p>
                <div class="mb-3">
                    <label class="form-label">Certificate title</label>
                    <input type="text" name="cert_title" class="form-control" value="<?= e(get_setting('cert_title')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Opening line</label>
                    <input type="text" name="cert_intro" class="form-control" value="<?= e(get_setting('cert_intro')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Main body</label>
                    <textarea name="cert_body" class="form-control" rows="4"><?= e(get_setting('cert_body')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Footer note</label>
                    <textarea name="cert_footer" class="form-control" rows="2"><?= e(get_setting('cert_footer')) ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Signatory name</label>
                        <input type="text" name="cert_signatory" class="form-control" value="<?= e(get_setting('cert_signatory')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Signatory title</label>
                        <input type="text" name="cert_signatory_role" class="form-control" value="<?= e(get_setting('cert_signatory_role')) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-accent mt-4"><span class="btn-text">Save template</span></button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-shadow p-4">
            <h6 class="mb-3">Preview</h6>
            <div class="cert-preview-mini border rounded p-3 bg-white text-center small">
                <img src="<?= e(org_logo_url()) ?>" alt="" style="max-height:48px" class="mb-2">
                <div class="fw-bold text-uppercase" style="color:#002147;font-size:11px"><?= e(get_setting('cert_title')) ?></div>
                <p class="mb-1 mt-2"><?= e(get_setting('cert_intro')) ?></p>
                <p class="mb-1"><strong>Sample Participant</strong></p>
                <p class="text-muted mb-0" style="font-size:10px"><?= e(get_setting('cert_footer')) ?></p>
            </div>
            <a href="<?= base_url('events/certificate.php?preview=1') ?>" class="btn btn-light btn-sm w-100 mt-3" target="_blank">Full preview</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$inlineJs = "document.getElementById('logoUpload')&&document.getElementById('logoUpload').addEventListener('change',function(e){var f=e.target.files[0];if(!f)return;var r=new FileReader();r.onload=function(){var img=document.getElementById('logoPreview');if(img)img.src=r.result;};r.readAsDataURL(f);});";
require_once __DIR__ . '/includes/footer.php';
