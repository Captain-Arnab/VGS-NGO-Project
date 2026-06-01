        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>window.BASE_URL = <?= json_for_script(base_url()) ?>;</script>
<?php if (!empty($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>
<?php swal_flash_script(); ?>
<script src="<?= base_url('assets/js/main.js') ?>"></script>
<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= e($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
