<?php

function report_export_filename(string $tab, string $ext): string
{
    return 'ngo-report-' . $tab . '-' . date('Y-m-d') . '.' . $ext;
}

function report_export_styles(bool $forWord = false): string
{
    if ($forWord) {
        return '
        @page Section1 { size: 595.45pt 841.7pt; margin: 36pt 36pt 36pt 36pt; }
        body { font-family: Calibri, Arial, sans-serif; color: #002147; font-size: 11pt; margin: 0; }
        table { border-collapse: collapse; }
        .report-header { width: 100%; border-bottom: 3pt solid #F58220; margin-bottom: 14pt; }
        .report-header td { vertical-align: middle; padding: 0 0 10pt 0; }
        .report-header img { max-height: 52pt; max-width: 140pt; }
        .report-header .org-name { font-size: 16pt; font-weight: bold; color: #002147; margin: 0; }
        .report-header .org-tag { font-size: 9pt; color: #5e7185; margin: 4pt 0 0 0; }
        h1.report-title { font-size: 14pt; color: #002147; margin: 0 0 6pt 0; }
        .meta { color: #5e7185; margin-bottom: 14pt; font-size: 9pt; }
        .data-table { width: 100%; border: 1pt solid #d5dee6; margin: 10pt 0 16pt 0; }
        .data-table th, .data-table td { border: 1pt solid #d5dee6; padding: 6pt 8pt; text-align: left; vertical-align: top; }
        .data-table th { background: #eef2f6; color: #002147; font-weight: bold; }
        .kpi-table { width: 100%; margin: 0 0 14pt 0; }
        .kpi-cell { background: #f8f9fc; border: 1pt solid #e8eef2; padding: 10pt 12pt; width: 25%; }
        .kpi-cell span { font-size: 8pt; color: #5e7185; text-transform: uppercase; display: block; }
        .kpi-cell strong { font-size: 13pt; color: #138808; display: block; margin-top: 4pt; }
        h2 { font-size: 12pt; margin: 16pt 0 8pt 0; color: #002147; border-left: 4pt solid #F58220; padding-left: 8pt; }
        ';
    }

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

function report_export_header_html(bool $forWord = false): string
{
    $logo = org_logo_file_uri();
    $org = htmlspecialchars(get_setting('org_name', ORG_NAME));
    $tag = htmlspecialchars(get_setting('org_tagline', ORG_TAGLINE));

    if ($forWord) {
        $img = $logo ? '<img src="' . $logo . '" alt="" width="120" style="width:120pt;max-width:120pt;height:auto;display:block;">' : '';
        return '<table class="report-header" cellpadding="0" cellspacing="0"><tr>'
            . '<td width="130">' . $img . '</td>'
            . '<td><p class="org-name">' . $org . '</p><p class="org-tag">' . $tag . '</p></td>'
            . '</tr></table>';
    }

    $img = $logo ? '<img src="' . $logo . '" alt="">' : '';
    return '<div class="report-header">' . $img . '<div><h1>' . $org . '</h1><p class="org">' . $tag . '</p></div></div>';
}

function report_export_kpis_html(array $items, bool $forWord = false): string
{
    if ($forWord) {
        $html = '<table class="kpi-table" cellpadding="0" cellspacing="6"><tr>';
        foreach ($items as $item) {
            $html .= '<td class="kpi-cell"><span>' . htmlspecialchars($item['label']) . '</span>'
                . '<strong>' . htmlspecialchars($item['value']) . '</strong></td>';
        }
        return $html . '</tr></table>';
    }

    $html = '';
    foreach ($items as $item) {
        $html .= '<div class="kpi"><span>' . htmlspecialchars($item['label']) . '</span>'
            . '<strong>' . htmlspecialchars($item['value']) . '</strong></div>';
    }
    return $html;
}

function report_export_table_open(bool $forWord = false): string
{
    return $forWord ? '<table class="data-table">' : '<table>';
}

function report_export_chart_sections(array $charts, bool $forWord = false): string
{
    if (empty($charts)) {
        return '';
    }
    $html = '<h2>Visual charts</h2>';
    foreach ($charts as $chart) {
        $title = trim((string) ($chart['title'] ?? 'Chart'));
        $img = (string) ($chart['image'] ?? '');
        if (!preg_match('#^data:image/(png|jpe?g|webp);base64,#i', $img)) {
            continue;
        }
        $titleHtml = '<p style="margin:8pt 0 6pt 0;font-weight:700;color:#002147;">' . htmlspecialchars($title) . '</p>';
        if ($forWord) {
            $html .= $titleHtml
                . '<table class="data-table" style="margin-top:0"><tr><td style="text-align:center">'
                . '<img src="' . htmlspecialchars($img) . '" alt="" style="max-width:520pt;width:100%;height:auto;">'
                . '</td></tr></table>';
        } else {
            $html .= $titleHtml
                . '<table style="width:100%;margin-top:0"><tr><td style="text-align:center">'
                . '<img src="' . htmlspecialchars($img) . '" alt="" style="max-width:100%;height:auto;">'
                . '</td></tr></table>';
        }
    }
    return $html;
}

function report_export_build_html(string $tab, string $dateFrom, string $dateTo, array $data, bool $forWord = false, array $chartImages = []): string
{
    $title = ucfirst($tab) . ' Report';
    $range = format_date($dateFrom) . ' – ' . format_date($dateTo);
    $html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'
        . '<title>' . htmlspecialchars($title) . '</title><style>' . report_export_styles($forWord) . '</style></head><body>';
    $html .= report_export_header_html($forWord);
    $html .= '<h1 class="report-title">' . htmlspecialchars($title) . '</h1>';
    $html .= '<p class="meta">Period: ' . htmlspecialchars($range) . ' · Generated: ' . date('d M Y H:i') . '</p>';

    switch ($tab) {
        case 'donations':
            $html .= report_export_kpis_html([
                ['label' => 'Total raised', 'value' => format_currency_pdf((float) ($data['total'] ?? 0))],
                ['label' => 'Transactions', 'value' => (string) (int) ($data['count'] ?? 0)],
                ['label' => 'Average gift', 'value' => format_currency_pdf((float) ($data['avg'] ?? 0))],
            ], $forWord);
            $html .= '<h2>Monthly trend</h2>' . report_export_table_open($forWord) . '<tr><th>Month</th><th>Amount (INR)</th></tr>';
            foreach ($data['monthly'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['label']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table><h2>By payment mode</h2>' . report_export_table_open($forWord) . '<tr><th>Mode</th><th>Amount (INR)</th></tr>';
            foreach ($data['by_payment'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['payment_mode']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table><h2>By donor type</h2>' . report_export_table_open($forWord) . '<tr><th>Type</th><th>Amount (INR)</th></tr>';
            foreach ($data['by_type'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['donor_type']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table><h2>By campaign</h2>' . report_export_table_open($forWord) . '<tr><th>Campaign</th><th>Amount (INR)</th></tr>';
            foreach ($data['by_campaign'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['title']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['total'])) . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'volunteers':
            $html .= report_export_kpis_html([
                ['label' => 'Total', 'value' => (string) (int) ($data['total'] ?? 0)],
                ['label' => 'Active', 'value' => (string) (int) ($data['active'] ?? 0)],
                ['label' => 'Inactive', 'value' => (string) (int) ($data['inactive'] ?? 0)],
                ['label' => 'Pending', 'value' => (string) (int) ($data['pending'] ?? 0)],
            ], $forWord);
            $html .= '<h2>By availability</h2>' . report_export_table_open($forWord) . '<tr><th>Availability</th><th>Count</th></tr>';
            foreach ($data['by_avail'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['availability']) . '</td><td>' . (int) $r['cnt'] . '</td></tr>';
            }
            $html .= '</table><h2>By gender</h2>' . report_export_table_open($forWord) . '<tr><th>Gender</th><th>Count</th></tr>';
            foreach ($data['by_gender'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['gender']) . '</td><td>' . (int) $r['cnt'] . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'campaigns':
            $html .= report_export_table_open($forWord) . '<tr><th>Campaign</th><th>Goal (INR)</th><th>Raised (INR)</th><th>Status</th></tr>';
            foreach ($data['rows'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['title']) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['goal_amount'])) . '</td><td>' . htmlspecialchars(format_currency_pdf((float) $r['raised_amount'])) . '</td><td>' . htmlspecialchars($r['status']) . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'events':
            $html .= report_export_kpis_html([
                ['label' => 'Events', 'value' => (string) (int) ($data['total'] ?? 0)],
                ['label' => 'Participants', 'value' => (string) (int) ($data['participants'] ?? 0)],
            ], $forWord);
            $html .= report_export_table_open($forWord) . '<tr><th>Event</th><th>Type</th><th>Status</th><th>Date</th><th>Participants</th></tr>';
            foreach ($data['rows'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['name']) . '</td><td>' . htmlspecialchars($r['event_type']) . '</td><td>' . htmlspecialchars($r['status']) . '</td><td>' . htmlspecialchars(format_date($r['event_date'])) . '</td><td>' . (int) $r['participants'] . '</td></tr>';
            }
            $html .= '</table>';
            break;

        case 'beneficiaries':
            $html .= report_export_kpis_html([
                ['label' => 'Total aided', 'value' => (string) (int) ($data['total'] ?? 0)],
            ], $forWord);
            $html .= '<h2>By aid category</h2>' . report_export_table_open($forWord) . '<tr><th>Category</th><th>Count</th></tr>';
            foreach ($data['by_aid'] ?? [] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['aid_category']) . '</td><td>' . (int) $r['cnt'] . '</td></tr>';
            }
            $html .= '</table>';
            break;
    }

    $html .= report_export_chart_sections($chartImages, $forWord);
    $html .= '</body></html>';
    return $html;
}

function report_export_send_docx(string $html, string $filename): void
{
    if (ob_get_level()) {
        ob_end_clean();
    }

    $body = $html;
    $styles = report_export_styles(true);
    if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $match)) {
        $body = $match[1];
    }
    if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $html, $styleMatch)) {
        $styles = $styleMatch[1];
    }

    $document = '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
        . 'xmlns:w="urn:schemas-microsoft-com:office:word" '
        . 'xmlns="http://www.w3.org/TR/REC-html40">'
        . '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'
        . '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->'
        . '<style>' . $styles . '</style></head><body>' . $body . '</body></html>';

    header('Content-Type: application/vnd.ms-word; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate');
    header('Pragma: public');
    echo $document;
    exit;
}

function report_export_send_pdf(string $html, string $filename): void
{
    if (ob_get_level()) {
        ob_end_clean();
    }

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

function report_export_send_csv(PDO $pdo, string $tab, string $dateFrom, string $dateTo): void
{
    $allowedTabs = ['donations', 'volunteers', 'campaigns', 'events', 'beneficiaries'];
    if (!in_array($tab, $allowedTabs, true)) {
        redirect('reports/index.php', null, 'Invalid report type for CSV export.');
    }

    switch ($tab) {
        case 'donations':
            $rows = $pdo->prepare("
                SELECT d.donation_date, dn.name, dn.donor_type, c.title, d.amount, d.payment_mode
                FROM donations d
                JOIN donors dn ON dn.id = d.donor_id
                LEFT JOIN campaigns c ON c.id = d.campaign_id
                WHERE d.donation_date BETWEEN ? AND ?
                ORDER BY d.donation_date DESC
            ");
            $rows->execute([$dateFrom, $dateTo]);
            $data = [];
            foreach ($rows->fetchAll() as $r) {
                $data[] = [$r['donation_date'], $r['name'], $r['donor_type'], $r['title'] ?? '', $r['amount'], $r['payment_mode']];
            }
            export_csv('donation-report.csv', ['Date', 'Donor', 'Type', 'Campaign', 'Amount', 'Payment Mode'], $data);
            break;

        case 'volunteers':
            $rows = $pdo->query('SELECT name, email, status, availability, gender, joined_date FROM volunteers ORDER BY name')->fetchAll();
            $data = array_map(fn ($r) => [$r['name'], $r['email'], $r['status'], $r['availability'], $r['gender'], $r['joined_date']], $rows);
            export_csv('volunteer-report.csv', ['Name', 'Email', 'Status', 'Availability', 'Gender', 'Joined'], $data);
            break;

        case 'campaigns':
            $rows = $pdo->query('SELECT title, goal_amount, raised_amount, status, start_date, end_date FROM campaigns')->fetchAll();
            $data = array_map(fn ($r) => [$r['title'], $r['goal_amount'], $r['raised_amount'], $r['status'], $r['start_date'], $r['end_date']], $rows);
            export_csv('campaign-report.csv', ['Title', 'Goal', 'Raised', 'Status', 'Start', 'End'], $data);
            break;

        case 'events':
            $rows = $pdo->query("
                SELECT e.name, e.event_type, e.status, e.event_date,
                (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants
                FROM events e ORDER BY e.event_date DESC
            ")->fetchAll();
            $data = array_map(fn ($r) => [$r['name'], $r['event_type'], $r['status'], $r['event_date'], $r['participants']], $rows);
            export_csv('event-report.csv', ['Name', 'Type', 'Status', 'Date', 'Participants'], $data);
            break;

        case 'beneficiaries':
            $rows = $pdo->query('SELECT name, aid_category, gender, created_at FROM beneficiaries')->fetchAll();
            $data = array_map(fn ($r) => [$r['name'], $r['aid_category'], $r['gender'], $r['created_at']], $rows);
            export_csv('beneficiary-report.csv', ['Name', 'Aid Category', 'Gender', 'Added'], $data);
            break;
    }
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
