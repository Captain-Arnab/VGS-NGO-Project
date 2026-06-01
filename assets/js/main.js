(function ($) {
  'use strict';

  const SWAL_DEFAULTS = {
    confirmButtonColor: '#2FA58A',
    cancelButtonColor: '#6B7280',
    customClass: {
      confirmButton: 'btn btn-accent px-4',
      cancelButton: 'btn btn-light px-4'
    },
    buttonsStyling: false
  };

  function runWhenReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  // SweetAlert flash messages from PHP
  runWhenReady(function () {
    if (typeof Swal === 'undefined' || !window.__swalFlash) {
      return;
    }
    const items = window.__swalFlash;
    delete window.__swalFlash;
    items.forEach(function (item, index) {
      setTimeout(function () {
        Swal.fire(Object.assign({}, SWAL_DEFAULTS, {
          icon: item.icon,
          title: item.title,
          text: item.text,
          timer: item.icon === 'success' ? 3200 : undefined,
          showConfirmButton: item.icon !== 'success'
        }));
      }, index * 120);
    });
  });

  // Sidebar mobile + tablet collapse
  $('#sidebarToggle').on('click', function () {
    $('#sidebar').toggleClass('open');
    $('#sidebarOverlay').toggleClass('show');
  });

  $('#sidebarOverlay').on('click', function () {
    $('#sidebar').removeClass('open');
    $(this).removeClass('show');
  });

  $('#sidebarCollapse').on('click', function () {
    $('body').toggleClass('sidebar-collapsed');
  });

  // Logout confirmation
  $(document).on('click', '#logoutLink', function (e) {
    e.preventDefault();
    if (typeof Swal === 'undefined') {
      window.location.href = (window.BASE_URL || '') + '/logout.php';
      return;
    }
    Swal.fire(Object.assign({}, SWAL_DEFAULTS, {
      icon: 'question',
      title: 'Log out?',
      text: 'You will need to sign in again to access the admin panel.',
      showCancelButton: true,
      confirmButtonText: 'Yes, log out',
      cancelButtonText: 'Cancel'
    })).then(function (result) {
      if (result.isConfirmed) {
        window.location.href = (window.BASE_URL || '') + '/logout.php';
      }
    });
  });

  // Delete confirmation with SweetAlert
  $(document).on('click', '[data-delete-url]', function (e) {
    e.preventDefault();
    const url = $(this).data('delete-url');
    if (typeof Swal === 'undefined') {
      if (confirm('Delete this record?')) {
        window.location.href = url;
      }
      return;
    }
    Swal.fire(Object.assign({}, SWAL_DEFAULTS, {
      icon: 'warning',
      title: 'Delete record?',
      text: 'This action cannot be undone.',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#D45656',
      customClass: {
        confirmButton: 'btn btn-danger px-4',
        cancelButton: 'btn btn-light px-4'
      }
    })).then(function (result) {
      if (result.isConfirmed) {
        window.location.href = url;
      }
    });
  });

  // Flatpickr
  if (typeof flatpickr !== 'undefined') {
    flatpickr('.flatpickr', { dateFormat: 'Y-m-d', allowInput: true });
    flatpickr('.flatpickr-time', { enableTime: true, noCalendar: false, dateFormat: 'Y-m-d H:i', allowInput: true });
    document.querySelectorAll('.flatpickr-time-only').forEach(function (el) {
      flatpickr(el, { enableTime: true, noCalendar: true, dateFormat: 'H:i', allowInput: true });
    });
  }

  // Select2
  if ($.fn.select2) {
    $('.select2').select2({ theme: 'default', width: '100%' });
  }

  // DataTables optional
  if ($.fn.DataTable && $('.datatable').length) {
    $('.datatable').DataTable({
      pageLength: 15,
      order: [],
      language: { search: '', searchPlaceholder: 'Search table...' }
    });
  }

  // Animated counters (run once per element)
  function animateCounter($el) {
    if ($el.data('animated')) {
      return;
    }
    $el.data('animated', true);
    const target = parseFloat($el.data('target')) || 0;
    const prefix = $el.data('prefix') || '';
    const suffix = $el.data('suffix') || '';
    const isCurrency = String($el.data('currency')) === 'true';
    const duration = 1200;
    const start = performance.now();

    function tick(now) {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const val = target * eased;
      if (isCurrency) {
        $el.text(prefix + val.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + suffix);
      } else {
        $el.text(prefix + Math.floor(val).toLocaleString('en-IN') + suffix);
      }
      if (progress < 1) {
        requestAnimationFrame(tick);
      } else if (isCurrency) {
        $el.text(prefix + target.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + suffix);
      } else {
        $el.text(prefix + Math.floor(target).toLocaleString('en-IN') + suffix);
      }
    }
    requestAnimationFrame(tick);
  }

  $('.counter').each(function () {
    animateCounter($(this));
  });

  // Form submit loading
  $('form.js-prevent-double').on('submit', function () {
    const $btn = $(this).find('[type="submit"]');
    $btn.addClass('btn-loading').prop('disabled', true);
    if (!$btn.find('.btn-spinner').length) {
      $btn.append('<span class="btn-spinner spinner-border spinner-border-sm ms-1 d-none" role="status"></span>');
    }
    $btn.find('.btn-spinner').removeClass('d-none');
  });

  // Donor type toggle company name
  function toggleCompanyField() {
    const type = $('input[name="donor_type"]:checked').val();
    $('.company-field-wrap').toggle(type === 'Company');
  }
  $('input[name="donor_type"]').on('change', toggleCompanyField);
  toggleCompanyField();

  // Blog slug auto-generate
  $('#blog_title').on('input', function () {
    const $slug = $('#blog_slug');
    if ($slug.length && !$slug.data('manual')) {
      $slug.val(
        $(this).val().toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
      );
    }
  });
  $('#blog_slug').on('input', function () {
    $(this).data('manual', true);
  });

  // File upload preview
  $(document).on('change', '.file-upload-preview', function () {
    const input = this;
    const $preview = $(input).closest('.mb-3, .col-md-6, .col-12').find('.file-preview');
    if (!$preview.length) {
      return;
    }
    $preview.empty();
    if (!input.files || !input.files[0]) {
      return;
    }
    const file = input.files[0];
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function (ev) {
        $preview.html('<img src="' + ev.target.result + '" class="thumb-sm mt-2" alt="Preview">');
      };
      reader.readAsDataURL(file);
    } else {
      $preview.html('<i class="fas fa-file me-1"></i> ' + file.name);
    }
  });

  // Dynamic rows
  $(document).on('click', '.add-dynamic-row', function () {
    const template = $(this).data('template');
    const $container = $($(this).data('target'));
    const $tpl = $(template);
    const html = $tpl.length ? ($tpl[0].content ? $tpl[0].innerHTML : $tpl.html()) : '';
    $container.append(html);
    $container.find('.flatpickr').each(function () {
      if (!this._flatpickr && typeof flatpickr !== 'undefined') {
        flatpickr(this, { dateFormat: 'Y-m-d', allowInput: true });
      }
    });
  });

  $(document).on('click', '.remove-dynamic-row', function () {
    $(this).closest('.dynamic-row').remove();
  });

  const hash = window.location.hash;
  if (hash && $(hash).length) {
    const tabEl = document.querySelector('[href="' + hash + '"]');
    if (tabEl) {
      new bootstrap.Tab(tabEl).show();
    }
  }

  function destroyChart(canvas) {
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }
    const existing = Chart.getChart(canvas);
    if (existing) {
      existing.destroy();
    }
  }

  const REPORT_CHART_PALETTE = ['#2FA58A', '#4A8FD4', '#E09A3E', '#7B6BC8', '#D4689A', '#3BAFA8', '#5E7185'];
  const REPORT_BAR_COLORS = ['#2FA58A', '#4A8FD4', '#7B6BC8'];

  window.initReportsCharts = function () {
    const configs = window.__reportsChartConfigs;
    if (!configs || !configs.length || typeof Chart === 'undefined') {
      return;
    }

    const baseOpts = {
      responsive: true,
      maintainAspectRatio: false
    };

    configs.forEach(function (cfg) {
      const canvas = document.getElementById(cfg.id);
      if (!canvas) {
        return;
      }
      destroyChart(canvas);

      const labels = cfg.labels || [];
      const data = cfg.data || [];

      if (cfg.type === 'line') {
        new Chart(canvas, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Amount',
              data: data,
              borderColor: '#2FA58A',
              backgroundColor: 'rgba(47,165,138,0.15)',
              fill: true,
              tension: 0.35,
              borderWidth: 2,
              pointRadius: 4,
              pointBackgroundColor: '#2FA58A'
            }]
          },
          options: Object.assign({}, baseOpts, {
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
              x: { grid: { display: false } }
            }
          })
        });
        return;
      }

      if (cfg.type === 'doughnut') {
        new Chart(canvas, {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              data: data,
              backgroundColor: REPORT_CHART_PALETTE,
              borderWidth: 0,
              hoverOffset: 8
            }]
          },
          options: Object.assign({}, baseOpts, {
            cutout: cfg.cutout || '60%',
            plugins: {
              legend: {
                position: 'bottom',
                labels: { padding: 12, usePointStyle: true, font: { family: 'DM Sans', size: 12 } }
              }
            }
          })
        });
        return;
      }

      if (cfg.type === 'bar' || cfg.type === 'bar-horizontal') {
        const horizontal = cfg.type === 'bar-horizontal';
        new Chart(canvas, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Count',
              data: data,
              backgroundColor: REPORT_BAR_COLORS,
              borderRadius: 8,
              borderSkipped: false
            }]
          },
          options: Object.assign({}, baseOpts, {
            indexAxis: horizontal ? 'y' : 'x',
            plugins: { legend: { display: false } },
            scales: horizontal
              ? { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
              : {
                  y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                  x: { grid: { display: false } }
                }
          })
        });
      }
    });
  };

  window.initDashboardCharts = function () {
    const d = window.__dashboardChartData;
    if (!d || typeof Chart === 'undefined') {
      return;
    }
    const baseOpts = {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 750 },
      resizeDelay: 200
    };

    const lineCanvas = document.getElementById('donationLineChart');
    if (lineCanvas && d.line) {
      destroyChart(lineCanvas);
      new Chart(lineCanvas, {
        type: 'line',
        data: {
          labels: d.line.labels || [],
          datasets: [{
            label: 'Donations',
            data: d.line.values || [],
            borderColor: '#2FA58A',
            backgroundColor: 'rgba(47,165,138,0.15)',
            fill: true,
            tension: 0.35,
            pointRadius: 4
          }]
        },
        options: Object.assign({}, baseOpts, {
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
          }
        })
      });
    }

    const barCanvas = document.getElementById('campaignBarChart');
    if (barCanvas && d.bar) {
      destroyChart(barCanvas);
      new Chart(barCanvas, {
        type: 'bar',
        data: {
          labels: d.bar.labels || [],
          datasets: [
            {
              label: 'Raised',
              data: d.bar.raised || [],
              backgroundColor: '#2FA58A',
              borderRadius: 6,
              maxBarThickness: 48
            },
            {
              label: 'Goal',
              data: d.bar.goal || [],
              backgroundColor: '#B8C5D0',
              borderRadius: 6,
              maxBarThickness: 48
            }
          ]
        },
        options: Object.assign({}, baseOpts, {
          plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } } },
          scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
          }
        })
      });
    }
  };

  // Page charts (dashboard, reports) — run once
  runWhenReady(function () {
    if (typeof Chart === 'undefined' || !window.__pageCharts || !window.__pageCharts.length) {
      return;
    }
    if (window.__pageChartsRan) {
      return;
    }
    window.__pageChartsRan = true;
    window.__pageCharts.forEach(function (initFn) {
      try {
        initFn();
      } catch (err) {
        console.error('Chart init failed', err);
      }
    });
  });

})(jQuery);
