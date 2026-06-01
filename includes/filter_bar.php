<?php
/** @var string $filterAction */
/** @var array $fields - each: type, name, label, options?, placeholder?, col? */
?>
<div class="card card-shadow filter-card mb-4">
    <div class="card-body">
        <form method="get" action="<?= e($filterAction) ?>" class="filter-form">
            <div class="row g-3 align-items-end">
                <?php foreach ($fields as $field): ?>
                <div class="col-md-<?= (int)($field['col'] ?? 3) ?>">
                    <label class="form-label small text-muted mb-1"><?= e($field['label']) ?></label>
                    <?php if (($field['type'] ?? '') === 'select'): ?>
                    <select name="<?= e($field['name']) ?>" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($field['options'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= (($_GET[$field['name']] ?? '') === (string)$val) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php elseif (($field['type'] ?? '') === 'date'): ?>
                    <input type="text" name="<?= e($field['name']) ?>" class="form-control form-control-sm flatpickr" placeholder="<?= e($field['placeholder'] ?? 'Select date') ?>" value="<?= e($_GET[$field['name']] ?? '') ?>">
                    <?php else: ?>
                    <input type="<?= e($field['type'] ?? 'text') ?>" name="<?= e($field['name']) ?>" class="form-control form-control-sm" placeholder="<?= e($field['placeholder'] ?? '') ?>" value="<?= e($_GET[$field['name']] ?? '') ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-accent btn-sm"><i class="fas fa-filter me-1"></i>Apply</button>
                    <a href="<?= e($filterAction) ?>" class="btn btn-light btn-sm">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>
