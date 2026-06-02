<?php

function report_export_filename(string $tab, string $ext): string
{
    return 'ngo-report-' . $tab . '-' . date('Y-m-d') . '.' . $ext;
}

function report_export_styles(): string
{
    return '
    body{font-family:"DejaVu Sans",sans-serif;color:#002147;margin:24px;font-size:12px;}
    .report-header{display:flex;align-items:center;gap:16px;border-bottom:3px solid #F58220;padding-bottom:16px;margin-bottom:20px;}
    .report-header img{max-height:56px;max-width:160px;}
    .report-header h1{font-size:20px;color:#002147;margin:0;}
    .report-header .org{font-size:11px;color:#5e7185;margin:4px 0 0;}
    h1.report-title{color:#002147;font-size:18px;margin:0 0 4px;}
    .meta{color:#5e7185;margin-bottom:20px;font-size:11px;}
    table{width:100%;border-collapse:collapse;margin:16px 0;}
    th,td{border:1px solid #d5dee6;padding:8px 10px;text-align:left;}
    th{background:#eef2f6;color:#002147;}
    .kpi{display:inline-block;margin:0 12px 12px 0;padding:12px 14px;background:#f8f9fc;border:1px solid #e8eef2;border-radius:8px;min-width:130px;}
    .kpi span{font-size:10px;color:#5e7185;text-transform:uppercase;}
    .kpi strong{display:block;font-size:16px;color:#138808;margin-top:4px;}
    h2{font-size:14px;margin-top:22px;color:#002147;border-left:4px solid #F58220;padding-left:8px;}
    ';
}

function report_export_header_html(): string
{
    $logo = org_logo_file_uri();
    $org = htmlspecialchars(get_setting('org_name', ORG_NAME));
    $tag = htmlspecialchars(get_setting('org_tagline', ORG_TAGLINE));
    $img = $logo ? '<img src="' . $logo . '" alt="">' : '';
    return '<div class="report-header">' . $img . '<div><h1>' . $org . '</h1><p class="org">' . $tag . '</p></div></div>';
}

function report_export_build_html(string $tab, string $dateFrom, string $dateTo, array $data): string
{
    $title = ucfirst($tab) . ' Report';
    $range = format_date($dateFrom) . ' – ' . format_date($dateTo);
    $html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>' . htmlspecialchars($title) . '</title><style>' . report_export_styles() . '</style></head><body>';
    $html .= report_export_header_html();
    $html .= '<h1 class="report-title">' . htmlspecialchars($title) . '</h1>';
    $html .= '<p class="meta">Period: ' . htmlspecialchars($range) . ' · Generated: ' . date('d M Y H:i') . '</p>';

    switch ($tab) {
        case 'donations':
            $html .= '<div class="kpi"><span>Total raised</span><strong>' . htmlspecialchars(format_currency_pdf((float) ($data['total'] ?? 0))) . '</strong></div>';
            $html .= '<div class="kpi"><span>Transactions</span><strong>' . (int) ($data['count'] ?? 0) . '</strong></div>';
            $html .= '<div class="kpi"><span>Average gift</span><strong>' . htmlspecialchars(format_currency_pdf((float) ($data['avg'] ?? 0))) . '</strong></div>';
            $html .= '<h2>Monthly trend</h2><table><tr><th>Month</th><th>Amount (INR)</th></tr>';
            foreach ($data['monthly'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['label']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table><h2>By payment mode</h2><table><tr><th>Mode</th><th>Amount (INR)</th></tr>';
            foreach ($data['by_payment'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['payment_mode']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table><h2>By donor type</h2><table><tr><th>Type</th><th>Amount (INR)</th></tr>';
            foreach ($data['by_type'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['donor_type']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table><h2>By campaign</h2><table><tr><th>Campaign</th><th>Amount (INR)</th></tr>';
            foreach ($data['by_campaign'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['title']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'volunteers':
            $html .= '<div class="kpi"><span>Total</span><strong>' . (int) ($data['total'] ?? 0) . '</strong></div>';
            $html .= '<div class="kpi"><span>Active</span><strong>' . (int) ($data['active'] ?? 0) . '</strong></div>';
            $html .= '<div class="kpi"><span>Inactive</span><strong>' . (int) ($data['inactive'] ?? 0) . '</strong></div>';
            $html .= '<div class="kpi"><span>Pending</span><strong>' . (int) ($data['pending'] ?? 0) . '</strong></div>';
            $html .= '<h2>By availability</h2><table><tr><th>Availability</th><th>Count</th></tr>';
            foreach ($data['by_avail'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['availability']) . '</td><td>' . (int) $r['cnt'] . '</td></tr>';
            }
            $html .= '</table><h2>By gender</h2><table><tr><th>Gender</th><th>Count</th></tr>';
            foreach ($data['by_gender'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['gender']) . '</td><td>' . (int) $r['cnt'] . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'campaigns':
            $html .= '<table><tr><th>Campaign</th><th>Goal (INR)</th><th>Raised (INR)</th><th>Status</th></tr>';
            foreach ($data['rows'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['title']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['goal_amount'])) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['raised_amount'])) . '</td><td>' . htmlspecialchars($r['status']) . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'events':
            $html .= '<div class="kpi"><span>Events</span><strong>' . (int) ($data['total'] ?? 0) . '</strong></div>';
            $html .= '<div class="kpi"><span>Participants</span><strong>' . (int) ($data['participants'] ?? 0) . '</strong></div>';
            $html .= '<table><tr><th>Event</th><th>Type</th><th>Status</th><th>Date</th><th>Participants</th></tr>';
            foreach ($data['rows'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['name']) . '</td><td>' . htmlspecialchars($r['event_type']) . '</td><td>' . htmlspecialchars($r['status']) . '</td><td>' . htmlspecialchars(format_date($r['event_date'])) . '</td><td>' . (int) $r['participants'] . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'beneficiaries':
            $html .= '<div class="kpi"><span>Total aided</span><strong>' . (int) ($data['total'] ?? 0) . '</strong></div>';
            $html .= '<h2>By aid category</h2><table><tr><th>Category</th><th>Count</th></tr>';
            foreach ($data['by_aid'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['aid_category']) . '</td><td>' . (int) $r['cnt'] . '</td></tr>';
            }
            $html .= '</table>';
            break;
    }

    $html .= '</body></html>';
    return $html;
}

function report_export_send_docx(string $html, string $filename): void
{
    header('Content-Type: application/vnd.ms-word; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="UTF-8"></head><body>';
    echo $html;
    echo '</body></html>';
    exit;
}

function report_export_send_pdf(string $html, string $filename): void
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_readable($autoload)) {
        require_once $autoload;
        if (class_exists(\Dompdf\Dompdf::class)) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $dompdf->output();
            exit;
        }
    }
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . preg_replace('/\.pdf$/', '.html', $filename) . '"');
    echo $html;
    echo '<script>window.onload=function(){window.print();};</script>';
    exit;
}

function report_export_load_data(PDO $pdo, string $tab, string $dateFrom, string $dateTo): array
{
    $data = [];
    switch ($tab) {
        case 'donations':
            $t = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM donations WHERE donation_date BETWEEN ? AND ?');
            $t->execute([$dateFrom, $dateTo]);
            $data['total'] = (float) $t->fetchColumn();
            $c = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE donation_date BETWEEN ? AND ?');
            $c->execute([$dateFrom, $dateTo]);
            $data['count'] = (int) $c->fetchColumn();
            $data['avg'] = $data['count'] > 0 ? $data['total'] / $data['count'] : 0;
            $m = $pdo->prepare("SELECT DATE_FORMAT(MIN(donation_date), '%b %y') AS label, SUM(amount) AS total FROM donations WHERE donation_date BETWEEN ? AND ? GROUP BY YEAR(donation_date), MONTH(donation_date) ORDER BY MIN(donation_date)");
            $m->execute([$dateFrom, $dateTo]);
            $data['monthly'] = $m->fetchAll();
            $p = $pdo->prepare('SELECT payment_mode, SUM(amount) AS total FROM donations WHERE donation_date BETWEEN ? AND ? GROUP BY payment_mode');
            $p->execute([$dateFrom, $dateTo]);
            $data['by_payment'] = $p->fetchAll();
            $ty = $pdo->prepare("SELECT dn.donor_type, SUM(d.amount) AS total FROM donations d JOIN donors dn ON dn.id = d.donor_id WHERE d.donation_date BETWEEN ? AND ? GROUP BY dn.donor_type");
            $ty->execute([$dateFrom, $dateTo]);
            $data['by_type'] = $ty->fetchAll();
            $ca = $pdo->prepare("SELECT IFNULL(MAX(c.title), 'Unlinked') AS title, SUM(d.amount) AS total FROM donations d LEFT JOIN campaigns c ON c.id = d.campaign_id WHERE d.donation_date BETWEEN ? AND ? GROUP BY IFNULL(d.campaign_id, 0)");
            $ca->execute([$dateFrom, $dateTo]);
            $data['by_campaign'] = $ca->fetchAll();
            break;
        case 'volunteers':
            $data['total'] = (int) $pdo->query('SELECT COUNT(*) FROM volunteers')->fetchColumn();
            $data['active'] = (int) $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status='Active'")->fetchColumn();
            $data['inactive'] = (int) $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status='Inactive'")->fetchColumn();
            $data['pending'] = (int) $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status='Pending'")->fetchColumn();
            $data['by_avail'] = $pdo->query('SELECT availability, COUNT(*) AS cnt FROM volunteers GROUP BY availability')->fetchAll();
            $data['by_gender'] = $pdo->query("SELECT gender, COUNT(*) AS cnt FROM volunteers WHERE gender IS NOT NULL AND gender != '' GROUP BY gender")->fetchAll();
            break;
        case 'campaigns':
            $data['rows'] = $pdo->query('SELECT title, goal_amount, raised_amount, status FROM campaigns ORDER BY title')->fetchAll();
            break;
        case 'events':
            $data['rows'] = $pdo->query("SELECT e.name, e.event_type, e.status, e.event_date, (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants FROM events e ORDER BY e.event_date DESC")->fetchAll();
            $data['total'] = count($data['rows']);
            $data['participants'] = array_sum(array_column($data['rows'], 'participants'));
            break;
        case 'beneficiaries':
            $data['total'] = (int) $pdo->query('SELECT COUNT(*) FROM beneficiaries')->fetchColumn();
            $data['by_aid'] = $pdo->query('SELECT aid_category, COUNT(*) AS cnt FROM beneficiaries GROUP BY aid_category')->fetchAll();
            break;
    }
    return $data;
}
