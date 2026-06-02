<?php
/** Report UI partials */

function report_kpi(string $label, string $value, string $icon, string $variant = 'green'): void
{
    $variants = [
        'green' => ['bg' => 'rgba(19,136,8,0.14)', 'color' => '#138808'],
        'blue' => ['bg' => 'rgba(0,33,71,0.10)', 'color' => '#002147'],
        'purple' => ['bg' => 'rgba(245,130,32,0.14)', 'color' => '#F58220'],
        'amber' => ['bg' => 'rgba(245,130,32,0.16)', 'color' => '#F58220'],
        'slate' => ['bg' => 'rgba(94,113,133,0.14)', 'color' => '#5E7185'],
        'pink' => ['bg' => 'rgba(0,33,71,0.12)', 'color' => '#002147'],
    ];
    $v = $variants[$variant] ?? $variants['green'];
    ?>
    <div class="report-kpi card-shadow">
        <div class="report-kpi-icon" style="background:<?= $v['bg'] ?>;color:<?= $v['color'] ?>">
            <i class="fas <?= e($icon) ?>"></i>
        </div>
        <div class="report-kpi-body">
            <span class="report-kpi-value"><?= $value ?></span>
            <span class="report-kpi-label"><?= e($label) ?></span>
        </div>
    </div>
    <?php
}

function report_panel_title(string $title, ?string $subtitle = null, ?string $icon = null): void
{
    ?>
    <div class="report-panel-head">
        <?php if ($icon): ?><span class="report-panel-icon"><i class="fas <?= e($icon) ?>"></i></span><?php endif; ?>
        <div>
            <h5 class="report-panel-title mb-0"><?= e($title) ?></h5>
            <?php if ($subtitle): ?><p class="report-panel-sub mb-0"><?= e($subtitle) ?></p><?php endif; ?>
        </div>
    </div>
    <?php
}

function report_breakdown_list(string $title, array $rows, string $labelKey, string $valueKey, string $format = 'currency', ?string $icon = null): void
{
    $max = 0;
    foreach ($rows as $r) {
        $max = max($max, (float) ($r[$valueKey] ?? 0));
    }
    if ($max <= 0) {
        $max = 1;
    }
    ?>
    <div class="report-breakdown card-shadow">
        <?php report_panel_title($title, null, $icon); ?>
        <div class="report-breakdown-body">
            <?php if (empty($rows)): ?>
            <p class="report-empty-inline text-muted mb-0"><i class="far fa-chart-bar me-1"></i> No data for this period.</p>
            <?php else: foreach ($rows as $r):
                $val = (float) ($r[$valueKey] ?? 0);
                $pct = min(100, round(($val / $max) * 100));
                $display = $format === 'currency' ? format_currency($val) : (string) (int) $val;
            ?>
            <div class="report-bar-row">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="report-bar-label"><?= e($r[$labelKey]) ?></span>
                    <span class="report-bar-value"><?= $display ?></span>
                </div>
                <div class="report-bar-track"><div class="report-bar-fill" style="width:<?= $pct ?>%"></div></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <?php
}

function report_chart_empty(string $message = 'Not enough data to display chart'): void
{
    echo '<div class="report-chart-empty"><i class="fas fa-chart-pie"></i><p>' . e($message) . '</p></div>';
}
