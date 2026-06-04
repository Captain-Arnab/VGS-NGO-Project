<?php

/** In-memory cache of site_settings rows */
$GLOBALS['_site_settings_cache'] = null;

function site_settings_table_exists(PDO $pdo): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        $pdo->query('SELECT 1 FROM site_settings LIMIT 1');
        $exists = true;
    } catch (PDOException $e) {
        $exists = false;
    }
    return $exists;
}

function site_settings_load(PDO $pdo): array
{
    if ($GLOBALS['_site_settings_cache'] !== null) {
        return $GLOBALS['_site_settings_cache'];
    }
    $defaults = site_settings_defaults();
    if (!site_settings_table_exists($pdo)) {
        $GLOBALS['_site_settings_cache'] = $defaults;
        return $defaults;
    }
    $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    $GLOBALS['_site_settings_cache'] = array_merge($defaults, $rows);
    return $GLOBALS['_site_settings_cache'];
}

function site_settings_defaults(): array
{
    return [
        'org_name' => defined('ORG_NAME') ? ORG_NAME : 'Bharati Foundation',
        'org_tagline' => defined('ORG_TAGLINE') ? ORG_TAGLINE : 'Empowering Lives. Enriching Futures.',
        'org_short_name' => 'Bharati Admin',
        'org_logo' => 'assets/logo/logo.jpeg',
        'cert_title' => 'CERTIFICATE OF PARTICIPATION',
        'cert_intro' => 'This is to certify that',
        'cert_body' => 'has successfully participated in {event_name} held on {event_date} at {event_location}, organized by {org_name}.',
        'cert_footer' => 'We appreciate your valuable participation and contribution towards building a better Bharat.',
        'cert_signatory' => 'Authorized Signatory',
        'cert_signatory_role' => 'Bharati Foundation',
        'donation_bank_name' => '',
        'donation_account_number' => '',
        'donation_ifsc' => '',
        'donation_branch' => '',
    ];
}

function get_setting(string $key, ?string $default = null): string
{
    global $pdo;
    $all = site_settings_load($pdo);
    return (string) ($all[$key] ?? $default ?? '');
}

function set_setting(PDO $pdo, string $key, ?string $value): void
{
    if (!site_settings_table_exists($pdo)) {
        return;
    }
    $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
        ->execute([$key, $value]);
    $GLOBALS['_site_settings_cache'] = null;
}

function org_logo_path(): string
{
    $path = get_setting('org_logo', 'assets/logo/logo.jpeg');
    if ($path === '') {
        return 'assets/logo/logo.jpeg';
    }
    return $path;
}

function org_logo_url(): string
{
    $path = org_logo_path();
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $isAssetPath = str_starts_with($path, 'assets/');
    $full = $isAssetPath
        ? BASE_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/')
        : UPLOAD_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');
    if (!is_readable($full)) {
        $websiteLogo = dirname(BASE_PATH) . '/website/assets/img/logo.jpeg';
        if (is_readable($websiteLogo)) {
            return '../website/assets/img/logo.jpeg';
        }
        return base_url('assets/logo/logo.jpeg');
    }
    return $isAssetPath ? base_url($path) : base_url('uploads/' . $path);
}

function org_logo_file_uri(): ?string
{
    $path = org_logo_path();
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    $isAssetPath = str_starts_with($path, 'assets/');
    $full = $isAssetPath
        ? BASE_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/')
        : UPLOAD_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');
    if (!is_readable($full)) {
        $fallback = BASE_PATH . '/assets/logo/logo.jpeg';
        if (!is_readable($fallback)) {
            return null;
        }
        $full = $fallback;
    }
    $mime = mime_content_type($full) ?: 'image/jpeg';
    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($full));
}

function certificate_apply_placeholders(string $text, array $vars): string
{
    foreach ($vars as $key => $value) {
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    }
    return $text;
}
