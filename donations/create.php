<?php
$pageTitle = 'Add Donation';
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$donation = null;
$errors = [];
$preDonor = get_int('donor_id');

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM donations WHERE id = ?');
    $stmt->execute([$id]);
    $donation = $stmt->fetch();
    if (!$donation) {
        redirect('donations/index.php', null, 'Donation not found.');
    }
    $pageTitle = 'Edit Donation';
    $preDonor = (int) $donation['donor_id'];
}

$donors = $pdo->query('SELECT id, name, email FROM donors ORDER BY name')->fetchAll();
$campaigns = $pdo->query('SELECT id, title FROM campaigns ORDER BY title')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donor_id = (int) ($_POST['donor_id'] ?? 0);
    $campaign_id = !empty($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;
    $amount = (float) ($_POST['amount'] ?? 0);
    $donation_date = $_POST['donation_date'] ?? '';
    $payment_mode = $_POST['payment_mode'] ?? 'Cash';
    $reference_number = post_string('reference_number', 100);
    $purpose = post_string('purpose', 255);
    $notes = trim($_POST['notes'] ?? '');

    if ($donor_id <= 0) {
        $errors[] = 'Please select a donor.';
    }
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero.';
    }
    if (!$donation_date) {
        $errors[] = 'Donation date is required.';
    }

    if (empty($errors)) {
        $oldCampaign = null;
        $oldAmount = 0;
        if ($id) {
            $old = $pdo->prepare('SELECT campaign_id, amount FROM donations WHERE id = ?');
            $old->execute([$id]);
            $oldRow = $old->fetch();
            $oldCampaign = $oldRow['campaign_id'];
            $oldAmount = (float) $oldRow['amount'];
        }

        if ($id) {
            $pdo->prepare('UPDATE donations SET donor_id=?, campaign_id=?, amount=?, donation_date=?, payment_mode=?, reference_number=?, purpose=?, notes=? WHERE id=?')
                ->execute([$donor_id, $campaign_id, $amount, $donation_date, $payment_mode, $reference_number ?: null, $purpose ?: null, $notes ?: null, $id]);
        } else {
            $pdo->prepare('INSERT INTO donations (donor_id, campaign_id, amount, donation_date, payment_mode, reference_number, purpose, notes) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$donor_id, $campaign_id, $amount, $donation_date, $payment_mode, $reference_number ?: null, $purpose ?: null, $notes ?: null]);
        }

        if ($oldCampaign) {
            $pdo->prepare('UPDATE campaigns SET raised_amount = GREATEST(0, raised_amount - ?) WHERE id = ?')->execute([$oldAmount, $oldCampaign]);
        }
        if ($campaign_id) {
            $diff = $id ? ($amount - $oldAmount) : $amount;
            if ($id && $oldCampaign == $campaign_id) {
                $pdo->prepare('UPDATE campaigns SET raised_amount = raised_amount + ? WHERE id = ?')->execute([$diff, $campaign_id]);
            } elseif (!$id || $oldCampaign != $campaign_id) {
                if ($id && $oldCampaign != $campaign_id) {
                    $pdo->prepare('UPDATE campaigns SET raised_amount = raised_amount + ? WHERE id = ?')->execute([$amount, $campaign_id]);
                } else {
                    $pdo->prepare('UPDATE campaigns SET raised_amount = raised_amount + ? WHERE id = ?')->execute([$amount, $campaign_id]);
                }
            }
        }

        redirect('donations/index.php', $id ? 'Donation updated.' : 'Donation recorded.');
    }
    stash_form_errors($errors);
    $donation = $_POST;
}

$d = $donation ?? ['donor_id' => $preDonor, 'donation_date' => date('Y-m-d'), 'payment_mode' => 'Cash'];
?>

<div class="page-header-row">
    <a href="<?= base_url('donations/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card p-4">
    <form method="post" class="js-prevent-double">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Donor <span class="text-danger">*</span></label>
                <select name="donor_id" class="form-select select2" required>
                    <option value="">Select donor</option>
                    <?php foreach ($donors as $dn): ?>
                    <option value="<?= $dn['id'] ?>" <?= (int)($d['donor_id'] ?? 0) === (int)$dn['id'] ? 'selected' : '' ?>><?= e($dn['name']) ?> (<?= e($dn['email'] ?? 'no email') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Campaign (optional)</label>
                <select name="campaign_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)($d['campaign_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="amount" class="form-control" required value="<?= e($d['amount'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="text" name="donation_date" class="form-control flatpickr" required value="<?= e($d['donation_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Mode</label>
                <select name="payment_mode" class="form-select">
                    <?php foreach (['Cash','Cheque','Bank Transfer','UPI','Online'] as $pm): ?>
                    <option value="<?= $pm ?>" <?= ($d['payment_mode'] ?? '') === $pm ? 'selected' : '' ?>><?= $pm ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Reference Number</label>
                <input type="text" name="reference_number" class="form-control" value="<?= e($d['reference_number'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Purpose</label>
                <input type="text" name="purpose" class="form-control" value="<?= e($d['purpose'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($d['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text"><?= $id ? 'Update' : 'Save' ?> Donation</span></button>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
