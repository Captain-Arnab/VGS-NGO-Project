<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function base_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path, ?string $success = null, ?string $error = null): void
{
    if ($success) {
        $_SESSION['flash_success'] = $success;
    }
    if ($error) {
        $_SESSION['flash_error'] = $error;
    }
    header('Location: ' . base_url($path));
    exit;
}

/** @deprecated Use swal_flash_script() in footer — kept empty for backward compatibility */
function flash_messages(): void
{
}

function stash_form_errors(array $errors): void
{
    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode("\n", $errors);
    }
}

function swal_flash_script(): void
{
    $items = [];
    if (!empty($_SESSION['flash_success'])) {
        $items[] = ['icon' => 'success', 'title' => 'Success', 'text' => $_SESSION['flash_success']];
        unset($_SESSION['flash_success']);
    }
    if (!empty($_SESSION['flash_error'])) {
        $items[] = ['icon' => 'error', 'title' => 'Error', 'text' => $_SESSION['flash_error']];
        unset($_SESSION['flash_error']);
    }
    if (!empty($_SESSION['flash_warning'])) {
        $items[] = ['icon' => 'warning', 'title' => 'Notice', 'text' => $_SESSION['flash_warning']];
        unset($_SESSION['flash_warning']);
    }
    if (empty($items)) {
        return;
    }
    $json = json_encode($items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    echo '<script>window.__swalFlash=' . $json . ';</script>';
}

function run_when_ready_js(string $body): string
{
    return "(function(){function run(){" . $body . "}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}})();";
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Safe JSON for embedding inside HTML <script> tags */
function json_for_script($data): string
{
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
    $json = json_encode($data, $flags);

    return $json !== false ? $json : 'null';
}

function format_currency(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

/** PDF-safe currency (DejaVu Sans supports U+20B9 when UTF-8) */
function format_currency_pdf(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '—';
    }
    return date($format, strtotime($date));
}

function status_badge(string $status): string
{
    $map = [
        'Upcoming' => 'badge-upcoming',
        'Live' => 'badge-live',
        'Past' => 'badge-past',
        'Cancelled' => 'badge-cancelled',
        'Active' => 'badge-active',
        'Inactive' => 'badge-inactive',
        'Pending' => 'badge-pending',
        'Completed' => 'badge-past',
        'Paused' => 'badge-pending',
        'Draft' => 'badge-pending',
        'Published' => 'badge-active',
        'Archived' => 'badge-past',
    ];
    $class = $map[$status] ?? 'badge-past';
    $pulse = $status === 'Live' ? ' badge-pulse' : '';
    return '<span class="badge-status ' . $class . $pulse . '">' . e($status) . '</span>';
}

function pagination(int $total, int $page, string $baseQuery = ''): array
{
    $perPage = PER_PAGE;
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return compact('perPage', 'totalPages', 'page', 'offset');
}

function render_pagination(int $total, int $page, array $queryParams = []): void
{
    $p = pagination($total, $page);
    if ($p['totalPages'] <= 1) {
        return;
    }
    unset($queryParams['page']);
    $qs = http_build_query($queryParams);
    $prefix = $qs ? '?' . $qs . '&' : '?';
    echo '<nav class="mt-3"><ul class="pagination justify-content-end mb-0">';
    $prev = max(1, $p['page'] - 1);
    $next = min($p['totalPages'], $p['page'] + 1);
    echo '<li class="page-item' . ($p['page'] <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . $prefix . 'page=' . $prev . '">Prev</a></li>';
    for ($i = 1; $i <= $p['totalPages']; $i++) {
        if ($i === 1 || $i === $p['totalPages'] || abs($i - $p['page']) <= 2) {
            $active = $i === $p['page'] ? ' active' : '';
            echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $prefix . 'page=' . $i . '">' . $i . '</a></li>';
        } elseif (abs($i - $p['page']) === 3) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }
    echo '<li class="page-item' . ($p['page'] >= $p['totalPages'] ? ' disabled' : '') . '"><a class="page-link" href="' . $prefix . 'page=' . $next . '">Next</a></li>';
    echo '</ul></nav>';
}

function upload_file(array $file, string $module, array $allowedExtensions, ?int $maxSize = null): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }
    $limit = $maxSize ?? MAX_UPLOAD_SIZE;
    $limitMb = round($limit / 1048576, 1);
    if ($file['size'] > $limit) {
        throw new RuntimeException('File exceeds ' . $limitMb . 'MB limit.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        throw new RuntimeException('Invalid file type.');
    }
    $dir = UPLOAD_PATH . '/' . $module;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Could not save file.');
    }
    return $module . '/' . $filename;
}

function delete_upload(?string $relativePath): void
{
    if ($relativePath && file_exists(UPLOAD_PATH . '/' . $relativePath)) {
        unlink(UPLOAD_PATH . '/' . $relativePath);
    }
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function get_int(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
}

function post_string(string $key, int $maxLen = 255): string
{
    return trim(substr($_POST[$key] ?? '', 0, $maxLen));
}

function export_csv(string $filename, array $headers, array $rows): void
{
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate');
    header('Pragma: public');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function page_title(string $title): string
{
    return e($title) . ' | NGO Admin';
}

function current_page(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_replace('\\', '/', $script);
}

function nav_active(string $segment): string
{
    return strpos(current_page(), '/' . $segment . '/') !== false || basename(current_page()) === $segment . '.php' ? ' active' : '';
}

function dashboard_active(): string
{
    $page = basename(current_page());
    return ($page === 'index.php' && strpos(current_page(), '/admin/index.php') !== false) ? ' active' : '';
}
