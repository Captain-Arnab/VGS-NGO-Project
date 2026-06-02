<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ngo_admin');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('PER_PAGE', 15);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);
define('MEDIA_MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

define('INVOICE_PREFIX', 'INV');
define('ORG_NAME', 'Bharati Foundation');
define('ORG_TAGLINE', 'Empowering Lives. Enriching Futures.');
define('ORG_EMAIL', 'contact@bharatifoundation.org');
define('ORG_PHONE', '+91 98765 43210');
define('ORG_ADDRESS', 'Mumbai, Maharashtra, India');

/**
 * Web path to the admin app root (no trailing slash).
 * Auto-detected from DOCUMENT_ROOT — works on localhost and live hosting.
 * Optional override: copy config.local.php.example → config.local.php
 */
if (is_readable(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

if (!function_exists('detect_base_url')) {
    function detect_base_url(): string
    {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $appRoot = realpath(BASE_PATH);

        if ($docRoot && $appRoot) {
            $docRoot = str_replace('\\', '/', $docRoot);
            $appRoot = str_replace('\\', '/', $appRoot);
            if (str_starts_with($appRoot, $docRoot)) {
                $path = substr($appRoot, strlen($docRoot));
                return $path === '' || $path === '/' ? '' : rtrim($path, '/');
            }
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(dirname($script), '/');
        $strip = [
            '/database',
            '/includes',
            '/donors',
            '/donations',
            '/volunteers',
            '/campaigns',
            '/events',
            '/beneficiaries',
            '/reports',
            '/documents',
            '/blogs',
            '/case_studies',
            '/media',
        ];
        foreach ($strip as $suffix) {
            if (str_ends_with($dir, $suffix)) {
                $dir = substr($dir, 0, -strlen($suffix));
            }
        }

        return $dir === '/' ? '' : $dir;
    }
}

define('BASE_URL', defined('BASE_URL_OVERRIDE') ? rtrim(BASE_URL_OVERRIDE, '/') : detect_base_url());
