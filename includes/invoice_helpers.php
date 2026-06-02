<?php

function generate_invoice_number(PDO $pdo): string
{
    $year = date('Y');
    $prefix = INVOICE_PREFIX . '-' . $year . '-';
    $stmt = $pdo->prepare("SELECT invoice_number FROM donations WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $seq = 1;
    if ($last && preg_match('/-(\d+)$/', $last, $m)) {
        $seq = (int) $m[1] + 1;
    }
    return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
}

function assign_donation_invoice(PDO $pdo, int $donationId): string
{
    $check = $pdo->prepare('SELECT invoice_number FROM donations WHERE id = ?');
    $check->execute([$donationId]);
    $existing = $check->fetchColumn();
    if ($existing) {
        return $existing;
    }
    $invoice = generate_invoice_number($pdo);
    $pdo->prepare('UPDATE donations SET invoice_number = ? WHERE id = ?')->execute([$invoice, $donationId]);
    return $invoice;
}

function fetch_donation_for_invoice(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT d.*, dn.name AS donor_name, dn.email AS donor_email, dn.phone AS donor_phone,
               dn.address AS donor_address, dn.donor_type,
               c.title AS campaign_title
        FROM donations d
        JOIN donors dn ON dn.id = d.donor_id
        LEFT JOIN campaigns c ON c.id = d.campaign_id
        WHERE d.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    if (empty($row['invoice_number'])) {
        $row['invoice_number'] = assign_donation_invoice($pdo, $id);
    }
    return $row;
}
