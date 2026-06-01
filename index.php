<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$stats = [
    'donors' => (int) $pdo->query('SELECT COUNT(*) FROM donors')->fetchColumn(),
    'donations' => (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM donations')->fetchColumn(),
    'volunteers' => (int) $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status='Active'")->fetchColumn(),
    'campaigns' => (int) $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status='Active'")->fetchColumn(),
    'events' => (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status='Upcoming' AND event_date >= CURDATE()")->fetchColumn(),
    'beneficiaries' => (int) $pdo->query('SELECT COUNT(*) FROM beneficiaries')->fetchColumn(),
];

$monthlyStmt = $pdo->query("
    SELECT DATE_FORMAT(MIN(donation_date), '%Y-%m') AS ym,
           DATE_FORMAT(MIN(donation_date), '%b %Y') AS label,
           SUM(amount) AS total
    FROM donations
    WHERE donation_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(donation_date), MONTH(donation_date)
    ORDER BY ym ASC
");
$monthlyData = $monthlyStmt->fetchAll();

$campaignStmt = $pdo->query("
    SELECT title, goal_amount, raised_amount
    FROM campaigns
    WHERE status IN ('Active','Completed')
    ORDER BY raised_amount DESC
    LIMIT 8
");
$campaignChart = $campaignStmt->fetchAll();

$recentDonations = $pdo->query("
    SELECT d.id, d.amount, d.donation_date, d.payment_mode, dn.name AS donor_name, c.title AS campaign_title
    FROM donations d
    JOIN donors dn ON dn.id = d.donor_id
    LEFT JOIN campaigns c ON c.id = d.campaign_id
    ORDER BY d.created_at DESC LIMIT 5
")->fetchAll();

$upcomingEvents = $pdo->query("
    SELECT id, name, event_date, event_time, location, status
    FROM events
    WHERE status IN ('Upcoming','Live') AND event_date >= CURDATE()
    ORDER BY event_date ASC, event_time ASC LIMIT 3
")->fetchAll();

$chartLabels = array_column($monthlyData, 'label');
$chartValues = array_map('floatval', array_column($monthlyData, 'total'));
if (empty($chartLabels)) {
    for ($i = 5; $i >= 0; $i--) {
        $chartLabels[] = date('M Y', strtotime("-$i months"));
        $chartValues[] = 0;
    }
}
?>

<div class="page-header-row">
    <p class="text-muted mb-0">Welcome back, here's your impact at a glance.</p>
</div>

<div class="row g-3 g-xl-4 mb-4 dashboard-kpi-row">
    <?php
    $cards = [
        ['icon' => 'fa-hand-holding-heart', 'color' => '#4A8FD4', 'label' => 'Total Donors', 'value' => $stats['donors'], 'prefix' => '', 'currency' => false, 'href' => 'donors/index.php'],
        ['icon' => 'fa-indian-rupee-sign', 'color' => '#2FA58A', 'label' => 'Total Donations', 'value' => $stats['donations'], 'prefix' => '₹', 'currency' => true, 'href' => 'donations/index.php'],
        ['icon' => 'fa-people-group', 'color' => '#7B6BC8', 'label' => 'Active Volunteers', 'value' => $stats['volunteers'], 'prefix' => '', 'currency' => false, 'href' => 'volunteers/index.php?status=Active'],
        ['icon' => 'fa-bullhorn', 'color' => '#E09A3E', 'label' => 'Active Campaigns', 'value' => $stats['campaigns'], 'prefix' => '', 'currency' => false, 'href' => 'campaigns/index.php?status=Active'],
        ['icon' => 'fa-calendar-days', 'color' => '#D4689A', 'label' => 'Upcoming Events', 'value' => $stats['events'], 'prefix' => '', 'currency' => false, 'href' => 'events/index.php?status=Upcoming'],
        ['icon' => 'fa-user-injured', 'color' => '#3BAFA8', 'label' => 'Beneficiaries', 'value' => $stats['beneficiaries'], 'prefix' => '', 'currency' => false, 'href' => 'beneficiaries/index.php'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <a href="<?= base_url($c['href']) ?>"
           class="stat-card-link"
           style="--kpi-accent: <?= e($c['color']) ?>"
           title="Open <?= e($c['label']) ?>">
            <article class="stat-card stat-card--kpi">
                <span class="stat-card-glow" aria-hidden="true"></span>
                <div class="stat-card-top">
                    <div class="stat-icon stat-icon--kpi">
                        <i class="fas <?= e($c['icon']) ?>"></i>
                    </div>
                    <span class="stat-card-go" aria-hidden="true"><i class="fas fa-arrow-up-right"></i></span>
                </div>
                <div class="stat-value counter" data-target="<?= e((string)$c['value']) ?>" data-prefix="<?= e($c['prefix']) ?>" data-currency="<?= $c['currency'] ? 'true' : 'false' ?>">0</div>
                <div class="stat-label"><?= e($c['label']) ?></div>
            </article>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-shadow p-4 h-100">
            <h5 class="mb-3">Monthly Donations (6 months)</h5>
            <div class="dashboard-chart-wrap">
                <canvas id="donationLineChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-shadow p-4 h-100">
            <h5 class="mb-3">Campaign Funds vs Goal</h5>
            <div class="dashboard-chart-wrap">
                <canvas id="campaignBarChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-shadow table-card">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Donations</h5>
                <a href="<?= base_url('donations/index.php') ?>" class="btn btn-sm btn-light">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Donor</th><th>Campaign</th><th>Amount</th><th>Mode</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentDonations)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No donations yet.</td></tr>
                    <?php else: foreach ($recentDonations as $r): ?>
                    <tr>
                        <td><?= e($r['donor_name']) ?></td>
                        <td><?= e($r['campaign_title'] ?? '—') ?></td>
                        <td class="fw-semibold text-success"><?= format_currency((float)$r['amount']) ?></td>
                        <td><?= e($r['payment_mode']) ?></td>
                        <td><?= format_date($r['donation_date']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow p-4 h-100">
            <h5 class="mb-3">Upcoming Events</h5>
            <?php if (empty($upcomingEvents)): ?>
            <p class="text-muted">No upcoming events.</p>
            <?php else: foreach ($upcomingEvents as $ev): ?>
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <div class="stat-icon flex-shrink-0" style="background:rgba(212,104,154,0.15);color:#D4689A;width:40px;height:40px;font-size:0.9rem">
                    <i class="fas fa-calendar"></i>
                </div>
                <div>
                    <a href="<?= base_url('events/view.php?id=' . $ev['id']) ?>" class="fw-semibold text-decoration-none"><?= e($ev['name']) ?></a>
                    <div class="small text-muted"><?= format_date($ev['event_date']) ?> · <?= e($ev['location'] ?? 'TBA') ?></div>
                    <?= status_badge($ev['status']) ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12"><h5 class="mb-2">Quick Actions</h5></div>
    <div class="col-md-6 col-lg-3"><a href="<?= base_url('donors/create.php') ?>" class="quick-action-btn"><i class="fas fa-plus-circle text-success"></i> Add Donor</a></div>
    <div class="col-md-6 col-lg-3"><a href="<?= base_url('donations/create.php') ?>" class="quick-action-btn"><i class="fas fa-plus-circle text-success"></i> Add Donation</a></div>
    <div class="col-md-6 col-lg-3"><a href="<?= base_url('volunteers/register.php') ?>" class="quick-action-btn"><i class="fas fa-plus-circle text-success"></i> Register Volunteer</a></div>
    <div class="col-md-6 col-lg-3"><a href="<?= base_url('campaigns/create.php') ?>" class="quick-action-btn"><i class="fas fa-plus-circle text-success"></i> Create Campaign</a></div>
</div>

<?php
$dashboardChartData = [
    'line' => [
        'labels' => $chartLabels,
        'values' => $chartValues,
    ],
    'bar' => [
        'labels' => array_column($campaignChart, 'title') ?: ['No campaigns'],
        'raised' => array_map('floatval', array_column($campaignChart, 'raised_amount') ?: [0]),
        'goal' => array_map('floatval', array_column($campaignChart, 'goal_amount') ?: [0]),
    ],
];
$inlineJs = 'window.__dashboardChartData=' . json_for_script($dashboardChartData) . ';'
    . 'window.__pageCharts=window.__pageCharts||[];'
    . 'window.__pageCharts.push(function(){if(typeof initDashboardCharts==="function")initDashboardCharts();});';
require_once __DIR__ . '/includes/footer.php';
