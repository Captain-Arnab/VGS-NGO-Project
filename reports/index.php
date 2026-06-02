<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/report_exporter.php';

require_login();

$tab = $_GET['tab'] ?? 'donations';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    report_export_send_csv($pdo, $tab, $dateFrom, $dateTo);
}

$pageTitle = 'Reports';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/report_ui.php';

// Report data (SQL written for strict MySQL / shared hosting ONLY_FULL_GROUP_BY)
$reportLoadError = null;
$donationTotal = 0.0;
$donationCount = 0;
$donationAvg = 0.0;
$donByType = [];
$donByCampaign = [];
$donByPayment = [];
$donMonthly = [];
$volTotal = $volActive = $volInactive = $volPending = 0;
$volByAvail = [];
$volByGender = [];
$campaignRows = [];
$campTotalGoal = $campTotalRaised = 0.0;
$campActive = 0;
$eventList = [];
$eventTotal = 0;
$eventParticipants = 0;
$eventByType = [];
$benTotal = 0;
$benByAid = [];
$benByGender = [];
$benByCampaign = [];

try {
    $donTotal = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM donations WHERE donation_date BETWEEN ? AND ?');
    $donTotal->execute([$dateFrom, $dateTo]);
    $donationTotal = (float) $donTotal->fetchColumn();

    $donCountStmt = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE donation_date BETWEEN ? AND ?');
    $donCountStmt->execute([$dateFrom, $dateTo]);
    $donationCount = (int) $donCountStmt->fetchColumn();
    $donationAvg = $donationCount > 0 ? $donationTotal / $donationCount : 0;

    $byType = $pdo->prepare("
        SELECT dn.donor_type, SUM(d.amount) AS total
        FROM donations d JOIN donors dn ON dn.id = d.donor_id
        WHERE d.donation_date BETWEEN ? AND ?
        GROUP BY dn.donor_type
    ");
    $byType->execute([$dateFrom, $dateTo]);
    $donByType = $byType->fetchAll();

    $byCampaign = $pdo->prepare("
        SELECT IFNULL(MAX(c.title), 'Unlinked') AS title, SUM(d.amount) AS total
        FROM donations d
        LEFT JOIN campaigns c ON c.id = d.campaign_id
        WHERE d.donation_date BETWEEN ? AND ?
        GROUP BY IFNULL(d.campaign_id, 0)
    ");
    $byCampaign->execute([$dateFrom, $dateTo]);
    $donByCampaign = $byCampaign->fetchAll();

    $byPayment = $pdo->prepare("
        SELECT payment_mode, SUM(amount) AS total FROM donations
        WHERE donation_date BETWEEN ? AND ? GROUP BY payment_mode
    ");
    $byPayment->execute([$dateFrom, $dateTo]);
    $donByPayment = $byPayment->fetchAll();

    $monthlyDon = $pdo->prepare("
        SELECT DATE_FORMAT(MIN(donation_date), '%b %y') AS label, SUM(amount) AS total
        FROM donations
        WHERE donation_date BETWEEN ? AND ?
        GROUP BY YEAR(donation_date), MONTH(donation_date)
        ORDER BY MIN(donation_date) ASC
    ");
    $monthlyDon->execute([$dateFrom, $dateTo]);
    $donMonthly = $monthlyDon->fetchAll();

    $volTotal = (int) $pdo->query('SELECT COUNT(*) FROM volunteers')->fetchColumn();
    $volActive = (int) $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status='Active'")->fetchColumn();
    $volInactive = (int) $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status='Inactive'")->fetchColumn();
    $volPending = (int) $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status='Pending'")->fetchColumn();
    $volByAvail = $pdo->query('SELECT availability, COUNT(*) AS cnt FROM volunteers GROUP BY availability')->fetchAll();
    $volByGender = $pdo->query("SELECT gender, COUNT(*) AS cnt FROM volunteers WHERE gender IS NOT NULL AND gender != '' GROUP BY gender")->fetchAll();

    $campaignRows = $pdo->query("
        SELECT c.*,
        (SELECT COUNT(DISTINCT d.donor_id) FROM donations d WHERE d.campaign_id = c.id) AS donor_count
        FROM campaigns c ORDER BY c.title
    ")->fetchAll();
    $campTotalGoal = array_sum(array_column($campaignRows, 'goal_amount'));
    $campTotalRaised = array_sum(array_column($campaignRows, 'raised_amount'));
    $campActive = count(array_filter($campaignRows, static fn($c) => $c['status'] === 'Active'));

    $eventList = $pdo->query("
        SELECT e.name, e.event_type, e.status, e.event_date,
        (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants
        FROM events e ORDER BY e.event_date DESC LIMIT 50
    ")->fetchAll();
    $eventTotal = count($eventList);
    $eventParticipants = (int) array_sum(array_column($eventList, 'participants'));
    $eventByType = $pdo->query('SELECT event_type, COUNT(*) AS cnt FROM events GROUP BY event_type')->fetchAll();

    $benTotal = (int) $pdo->query('SELECT COUNT(*) FROM beneficiaries')->fetchColumn();
    $benByAid = $pdo->query('SELECT aid_category, COUNT(*) AS cnt FROM beneficiaries GROUP BY aid_category')->fetchAll();
    $benByGender = $pdo->query('SELECT gender, COUNT(*) AS cnt FROM beneficiaries WHERE gender IS NOT NULL AND gender != \'\' GROUP BY gender')->fetchAll();
    $benByCampaign = $pdo->query("
        SELECT IFNULL(MAX(c.title), 'Unlinked') AS title, COUNT(*) AS cnt
        FROM beneficiaries b
        LEFT JOIN campaigns c ON c.id = b.campaign_id
        GROUP BY IFNULL(b.campaign_id, 0)
    ")->fetchAll();
} catch (PDOException $e) {
    $reportLoadError = 'Could not load report data. Import the latest database/ngo_admin.sql on the server and ensure all tables exist.';
    error_log('Reports page DB error: ' . $e->getMessage());
}

$exportQs = http_build_query(['tab' => $tab, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'export' => 'csv']);
$exportBaseQs = http_build_query(['tab' => $tab, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
$dateRangeLabel = format_date($dateFrom) . ' – ' . format_date($dateTo);

$reportsChartConfigs = [];
$chartExportData = [];
?>

<div class="reports-page animate-fade-in">
    <?php if ($reportLoadError): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
        <i class="fas fa-circle-exclamation mt-1"></i>
        <div>
            <strong>Reports could not be loaded</strong>
            <p class="mb-0 small"><?= e($reportLoadError) ?></p>
        </div>
    </div>
    <?php endif; ?>
    <div class="page-header-row">
        <div>
            <p class="text-muted mb-0">Analytics and exportable reports.</p>
            <small class="text-muted"><i class="far fa-calendar-alt me-1"></i><?= e($dateRangeLabel) ?></small>
        </div>
        <div class="btn-group">
            <a href="?<?= e($exportQs) ?>" class="btn btn-light btn-export"><i class="fas fa-file-csv me-1"></i> CSV</a>
            <a href="<?= base_url('reports/export.php?' . $exportBaseQs . '&format=pdf') ?>" class="btn btn-light btn-export js-report-export" data-format="pdf"><i class="fas fa-file-pdf me-1"></i> PDF</a>
            <a href="<?= base_url('reports/export.php?' . $exportBaseQs . '&format=docx') ?>" class="btn btn-light btn-export js-report-export" data-format="docx"><i class="fas fa-file-word me-1"></i> DOCX</a>
        </div>
    </div>

    <div class="card card-shadow report-filter-card mb-4">
        <div class="card-body py-3">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <div class="col-md-3 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="text" name="date_from" class="form-control flatpickr" value="<?= e($dateFrom) ?>">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="text" name="date_to" class="form-control flatpickr" value="<?= e($dateTo) ?>">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-accent"><i class="fas fa-filter me-1"></i> Apply Range</button>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs nav-tabs-report mb-4">
        <?php
        $tabs = [
            'donations' => ['label' => 'Donations', 'icon' => 'fa-indian-rupee-sign'],
            'volunteers' => ['label' => 'Volunteers', 'icon' => 'fa-people-group'],
            'campaigns' => ['label' => 'Campaigns', 'icon' => 'fa-bullhorn'],
            'events' => ['label' => 'Events', 'icon' => 'fa-calendar-days'],
            'beneficiaries' => ['label' => 'Beneficiaries', 'icon' => 'fa-user-injured'],
        ];
        foreach ($tabs as $k => $t): ?>
        <li class="nav-item">
            <a class="nav-link<?= $tab === $k ? ' active' : '' ?>" href="?tab=<?= $k ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>">
                <i class="fas <?= e($t['icon']) ?> me-1"></i><?= e($t['label']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($tab === 'donations'): ?>
    <div class="row g-3 g-lg-4 mb-4" id="donation-kpis">
        <div class="col-lg-4 d-flex">
            <a href="#donation-breakdowns" class="report-hero-total report-card-clickable report-hero-link w-100">
                <span class="report-hero-label">Total raised (period)</span>
                <span class="report-hero-amount"><?= format_currency($donationTotal) ?></span>
                <span class="report-hero-meta"><?= $donationCount ?> donation<?= $donationCount !== 1 ? 's' : '' ?> · Avg <?= format_currency($donationAvg) ?></span>
                <i class="fas fa-hand-holding-heart report-hero-icon"></i>
                <span class="report-card-hint"><i class="fas fa-arrow-down"></i></span>
            </a>
        </div>
        <div class="col-lg-4">
            <?php report_kpi('Transactions', (string) $donationCount, 'fa-receipt', 'blue', '#donation-breakdowns'); ?>
        </div>
        <div class="col-lg-4">
            <?php report_kpi('Average gift', format_currency($donationAvg), 'fa-chart-line', 'purple', '#donation-breakdowns'); ?>
        </div>
    </div>

    <div class="row g-3 g-lg-4 mb-4" id="donation-charts">
        <div class="col-lg-7">
            <a href="#donation-breakdowns" class="report-chart-panel report-card-clickable">
                <?php report_panel_title('Donation trend', 'By month in selected range', 'fa-chart-area'); ?>
                <div class="report-chart-wrap">
                    <?php if (empty($donMonthly)): ?>
                    <?php report_chart_empty('No donations in this date range'); ?>
                    <?php else: ?>
                    <canvas id="donLineChart"></canvas>
                    <?php
                    $reportsChartConfigs[] = [
                        'id' => 'donLineChart',
                        'type' => 'line',
                        'labels' => array_column($donMonthly, 'label'),
                        'data' => array_map('floatval', array_column($donMonthly, 'total')),
                    ];
                    $chartExportData[] = [
                        'id' => 'donLineChart',
                        'title' => 'Donation trend',
                        'type' => 'line',
                        'labels' => array_column($donMonthly, 'label'),
                        'data' => array_map('floatval', array_column($donMonthly, 'total')),
                    ];
                    ?>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <div class="col-lg-5">
            <a href="#donation-breakdowns" class="report-chart-panel report-card-clickable">
                <?php report_panel_title('Payment methods', 'Share of total amount', 'fa-wallet'); ?>
                <div class="report-chart-wrap">
                    <?php if (empty($donByPayment)): ?>
                    <?php report_chart_empty(); ?>
                    <?php else: ?>
                    <canvas id="donPieChart"></canvas>
                    <?php
                    $reportsChartConfigs[] = [
                        'id' => 'donPieChart',
                        'type' => 'doughnut',
                        'labels' => array_column($donByPayment, 'payment_mode'),
                        'data' => array_map('floatval', array_column($donByPayment, 'total')),
                        'cutout' => '62%',
                    ];
                    $chartExportData[] = [
                        'id' => 'donPieChart',
                        'title' => 'Payment methods',
                        'type' => 'doughnut',
                        'labels' => array_column($donByPayment, 'payment_mode'),
                        'data' => array_map('floatval', array_column($donByPayment, 'total')),
                    ];
                    ?>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 g-lg-4" id="donation-breakdowns">
        <div class="col-md-4"><?php report_breakdown_list('By donor type', $donByType, 'donor_type', 'total', 'currency', 'fa-user-tag'); ?></div>
        <div class="col-md-4"><?php report_breakdown_list('By campaign', $donByCampaign, 'title', 'total', 'currency', 'fa-bullhorn'); ?></div>
        <div class="col-md-4"><?php report_breakdown_list('By payment mode', $donByPayment, 'payment_mode', 'total', 'currency', 'fa-credit-card'); ?></div>
    </div>

    <?php elseif ($tab === 'volunteers'): ?>
    <div class="row g-3 g-lg-4 mb-4" id="volunteer-kpis">
        <div class="col-6 col-lg-3"><?php report_kpi('Total volunteers', (string) $volTotal, 'fa-people-group', 'blue', '#volunteer-breakdowns'); ?></div>
        <div class="col-6 col-lg-3"><?php report_kpi('Active', (string) $volActive, 'fa-circle-check', 'green', '#volunteer-breakdowns'); ?></div>
        <div class="col-6 col-lg-3"><?php report_kpi('Inactive', (string) $volInactive, 'fa-user-slash', 'slate', '#volunteer-breakdowns'); ?></div>
        <div class="col-6 col-lg-3"><?php report_kpi('Pending', (string) $volPending, 'fa-clock', 'amber', '#volunteer-breakdowns'); ?></div>
    </div>

    <div class="row g-3 g-lg-4 mb-4" id="volunteer-charts">
        <div class="col-lg-6">
            <a href="#volunteer-breakdowns" class="report-chart-panel report-card-clickable">
                <?php report_panel_title('By gender', 'Registered volunteers', 'fa-venus-mars'); ?>
                <div class="report-chart-wrap">
                    <?php if (empty($volByGender)): ?>
                    <?php report_chart_empty('Gender not recorded for volunteers yet'); ?>
                    <?php else: ?>
                    <canvas id="volGenderChart"></canvas>
                    <?php
                    $reportsChartConfigs[] = [
                        'id' => 'volGenderChart',
                        'type' => 'bar',
                        'labels' => array_column($volByGender, 'gender'),
                        'data' => array_map('intval', array_column($volByGender, 'cnt')),
                    ];
                    $chartExportData[] = [
                        'id' => 'volGenderChart',
                        'title' => 'Volunteers by gender',
                        'type' => 'bar',
                        'labels' => array_column($volByGender, 'gender'),
                        'data' => array_map('intval', array_column($volByGender, 'cnt')),
                    ];
                    ?>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <div class="col-lg-6">
            <a href="#volunteer-breakdowns" class="report-chart-panel report-card-clickable">
                <?php report_panel_title('By availability', 'How volunteers can help', 'fa-calendar-check'); ?>
                <div class="report-chart-wrap">
                    <?php if (empty($volByAvail)): ?>
                    <?php report_chart_empty(); ?>
                    <?php else: ?>
                    <canvas id="volAvailChart"></canvas>
                    <?php
                    $reportsChartConfigs[] = [
                        'id' => 'volAvailChart',
                        'type' => 'doughnut',
                        'labels' => array_column($volByAvail, 'availability'),
                        'data' => array_map('intval', array_column($volByAvail, 'cnt')),
                        'cutout' => '58%',
                    ];
                    $chartExportData[] = [
                        'id' => 'volAvailChart',
                        'title' => 'Volunteers by availability',
                        'type' => 'doughnut',
                        'labels' => array_column($volByAvail, 'availability'),
                        'data' => array_map('intval', array_column($volByAvail, 'cnt')),
                    ];
                    ?>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 g-lg-4" id="volunteer-breakdowns">
        <div class="col-md-6">
            <?php
            $availRows = array_map(fn($r) => ['availability' => $r['availability'], 'total' => $r['cnt']], $volByAvail);
            report_breakdown_list('Availability breakdown', $availRows, 'availability', 'total', 'number', 'fa-clock');
            ?>
        </div>
        <div class="col-md-6">
            <?php
            $genderRows = array_map(fn($r) => ['gender' => $r['gender'], 'total' => $r['cnt']], $volByGender);
            report_breakdown_list('Gender breakdown', $genderRows, 'gender', 'total', 'number', 'fa-venus-mars');
            ?>
        </div>
    </div>

    <?php elseif ($tab === 'campaigns'): ?>
    <div class="row g-3 g-lg-4 mb-4" id="campaign-kpis">
        <div class="col-6 col-lg-3"><?php report_kpi('Campaigns', (string) count($campaignRows), 'fa-bullhorn', 'green', '#campaign-table'); ?></div>
        <div class="col-6 col-lg-3"><?php report_kpi('Active', (string) $campActive, 'fa-play', 'blue', '#campaign-table'); ?></div>
        <div class="col-6 col-lg-3"><?php report_kpi('Total raised', format_currency((float) $campTotalRaised), 'fa-indian-rupee-sign', 'purple', '#campaign-table'); ?></div>
        <div class="col-6 col-lg-3"><?php report_kpi('Combined goal', format_currency((float) $campTotalGoal), 'fa-flag', 'amber', '#campaign-table'); ?></div>
    </div>

    <div class="card card-shadow table-card" id="campaign-table">
        <div class="report-table-head">
            <h5><i class="fas fa-chart-simple me-2 text-success"></i>Campaign performance</h5>
            <p class="text-muted small mb-0">Goal vs raised across all campaigns</p>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Campaign</th><th>Goal</th><th>Raised</th><th>Donors</th><th>Progress</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($campaignRows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No campaigns yet.</td></tr>
                <?php else: foreach ($campaignRows as $c):
                    $pct = $c['goal_amount'] > 0 ? min(100, round(($c['raised_amount'] / $c['goal_amount']) * 100)) : 0;
                ?>
                <tr>
                    <td class="fw-semibold"><?= e($c['title']) ?></td>
                    <td><?= format_currency((float) $c['goal_amount']) ?></td>
                    <td class="text-success fw-semibold"><?= format_currency((float) $c['raised_amount']) ?></td>
                    <td><span class="badge bg-light text-dark"><?= (int) $c['donor_count'] ?></span></td>
                    <td style="min-width:160px">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress progress-campaign flex-grow-1"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
                            <small class="fw-semibold text-muted"><?= $pct ?>%</small>
                        </div>
                    </td>
                    <td><?= status_badge($c['status'] === 'Active' ? 'Active' : ($c['status'] === 'Upcoming' ? 'Upcoming' : 'Past')) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($tab === 'events'): ?>
    <div class="row g-3 g-lg-4 mb-4" id="event-kpis">
        <div class="col-6 col-lg-4"><?php report_kpi('Total events', (string) $eventTotal, 'fa-calendar-days', 'pink', '#event-table'); ?></div>
        <div class="col-6 col-lg-4"><?php report_kpi('Participants', (string) $eventParticipants, 'fa-users', 'blue', '#event-table'); ?></div>
        <div class="col-lg-4">
            <div class="report-breakdown card-shadow h-100">
                <?php report_panel_title('By event type', null, 'fa-tags'); ?>
                <div class="report-pill-grid px-0">
                    <?php if (empty($eventByType)): ?>
                    <p class="report-empty-inline text-muted mb-0">No events recorded.</p>
                    <?php else: foreach ($eventByType as $et): ?>
                    <span class="report-pill"><?= e($et['event_type']) ?><strong><?= (int) $et['cnt'] ?></strong></span>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-shadow table-card" id="event-table">
        <div class="report-table-head">
            <h5><i class="fas fa-list me-2 text-success"></i>Event listing</h5>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Event</th><th>Type</th><th>Status</th><th>Date</th><th>Participants</th></tr></thead>
                <tbody>
                <?php if (empty($eventList)): ?>
                <tr><td colspan="5" class="text-center text-muted py-5">No events found.</td></tr>
                <?php else: foreach ($eventList as $ev): ?>
                <tr>
                    <td class="fw-semibold"><?= e($ev['name']) ?></td>
                    <td><?= e($ev['event_type']) ?></td>
                    <td><?= status_badge($ev['status']) ?></td>
                    <td><?= format_date($ev['event_date']) ?></td>
                    <td><span class="badge bg-light text-dark"><?= (int) $ev['participants'] ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: /* beneficiaries */ ?>
    <div class="row g-3 g-lg-4 mb-4" id="beneficiary-kpis">
        <div class="col-lg-4 d-flex">
            <a href="#beneficiary-breakdowns" class="report-hero-total report-card-clickable report-hero-link w-100">
                <span class="report-hero-label">People aided</span>
                <span class="report-hero-amount"><?= $benTotal ?></span>
                <span class="report-hero-meta">Across all aid programs</span>
                <i class="fas fa-user-injured report-hero-icon"></i>
                <span class="report-card-hint"><i class="fas fa-arrow-down"></i></span>
            </a>
        </div>
        <div class="col-lg-4">
            <?php report_kpi('Aid categories', (string) count($benByAid), 'fa-layer-group', 'blue', '#beneficiary-breakdowns'); ?>
        </div>
        <div class="col-lg-4">
            <?php report_kpi('Linked campaigns', (string) count(array_filter($benByCampaign, fn($r) => $r['title'] !== 'Unlinked')), 'fa-link', 'purple', '#beneficiary-breakdowns'); ?>
        </div>
    </div>

    <div class="row g-3 g-lg-4 mb-4" id="beneficiary-charts">
        <div class="col-lg-6">
            <a href="#beneficiary-breakdowns" class="report-chart-panel report-card-clickable">
                <?php report_panel_title('Aid category', 'Distribution of support types', 'fa-hand-holding-heart'); ?>
                <div class="report-chart-wrap">
                    <?php if (empty($benByAid)): ?>
                    <?php report_chart_empty(); ?>
                    <?php else: ?>
                    <canvas id="benAidChart"></canvas>
                    <?php
                    $reportsChartConfigs[] = [
                        'id' => 'benAidChart',
                        'type' => 'doughnut',
                        'labels' => array_column($benByAid, 'aid_category'),
                        'data' => array_map('intval', array_column($benByAid, 'cnt')),
                        'cutout' => '55%',
                    ];
                    $chartExportData[] = [
                        'id' => 'benAidChart',
                        'title' => 'Aid category distribution',
                        'type' => 'doughnut',
                        'labels' => array_column($benByAid, 'aid_category'),
                        'data' => array_map('intval', array_column($benByAid, 'cnt')),
                    ];
                    ?>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <div class="col-lg-6">
            <a href="#beneficiary-breakdowns" class="report-chart-panel report-card-clickable">
                <?php report_panel_title('By gender', 'Beneficiary demographics', 'fa-venus-mars'); ?>
                <div class="report-chart-wrap">
                    <?php if (empty($benByGender)): ?>
                    <?php report_chart_empty('Gender data not recorded'); ?>
                    <?php else: ?>
                    <canvas id="benGenderChart"></canvas>
                    <?php
                    $reportsChartConfigs[] = [
                        'id' => 'benGenderChart',
                        'type' => 'bar-horizontal',
                        'labels' => array_column($benByGender, 'gender'),
                        'data' => array_map('intval', array_column($benByGender, 'cnt')),
                    ];
                    $chartExportData[] = [
                        'id' => 'benGenderChart',
                        'title' => 'Beneficiaries by gender',
                        'type' => 'bar-horizontal',
                        'labels' => array_column($benByGender, 'gender'),
                        'data' => array_map('intval', array_column($benByGender, 'cnt')),
                    ];
                    ?>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 g-lg-4" id="beneficiary-breakdowns">
        <div class="col-md-4">
            <?php
            $aidRows = array_map(fn($r) => ['aid_category' => $r['aid_category'], 'total' => $r['cnt']], $benByAid);
            report_breakdown_list('Aid categories', $aidRows, 'aid_category', 'total', 'number', 'fa-heart');
            ?>
        </div>
        <div class="col-md-4">
            <?php
            $genRows = array_map(fn($r) => ['gender' => $r['gender'], 'total' => $r['cnt']], $benByGender);
            report_breakdown_list('Gender', $genRows, 'gender', 'total', 'number', 'fa-venus-mars');
            ?>
        </div>
        <div class="col-md-4">
            <?php
            $campRows = array_map(fn($r) => ['title' => $r['title'], 'total' => $r['cnt']], $benByCampaign);
            report_breakdown_list('By campaign', $campRows, 'title', 'total', 'number', 'fa-bullhorn');
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
if (!empty($reportsChartConfigs)) {
    $inlineJs = 'window.__reportsChartConfigs=' . json_for_script($reportsChartConfigs) . ';'
        . 'window.__reportChartExportData=' . json_for_script($chartExportData) . ';'
        . 'window.__pageCharts=window.__pageCharts||[];'
        . 'window.__pageCharts.push(function(){'
        . 'if(typeof initReportsCharts==="function")initReportsCharts();'
        . 'if(typeof initReportExports==="function")initReportExports();'
        . '});';
} else {
    $inlineJs = 'window.__reportChartExportData=' . json_for_script($chartExportData) . ';'
        . 'window.__pageCharts=window.__pageCharts||[];'
        . 'window.__pageCharts.push(function(){if(typeof initReportExports==="function")initReportExports();});';
}
require_once dirname(__DIR__) . '/includes/footer.php';
