<?php

function certificate_build_data_from_request(array $post, array $defaults = []): array
{
    return [
        'recipient_name' => trim($post['recipient_name'] ?? $defaults['recipient_name'] ?? ''),
        'recipient_role' => trim($post['recipient_role'] ?? $defaults['recipient_role'] ?? ''),
        'event_name' => trim($post['event_name'] ?? $defaults['event_name'] ?? ''),
        'event_date' => trim($post['event_date'] ?? $defaults['event_date'] ?? ''),
        'event_location' => trim($post['event_location'] ?? $defaults['event_location'] ?? ''),
        'cert_title' => trim($post['cert_title'] ?? $defaults['cert_title'] ?? get_setting('cert_title')),
        'cert_intro' => trim($post['cert_intro'] ?? $defaults['cert_intro'] ?? get_setting('cert_intro')),
        'cert_body' => trim($post['cert_body'] ?? $defaults['cert_body'] ?? get_setting('cert_body')),
        'cert_footer' => trim($post['cert_footer'] ?? $defaults['cert_footer'] ?? get_setting('cert_footer')),
        'cert_signatory' => trim($post['cert_signatory'] ?? $defaults['cert_signatory'] ?? get_setting('cert_signatory')),
        'cert_signatory_role' => trim($post['cert_signatory_role'] ?? $defaults['cert_signatory_role'] ?? get_setting('cert_signatory_role')),
        'signature_type' => certificate_normalize_signature_type($post['signature_type'] ?? $defaults['signature_type'] ?? 'type'),
        'signature_text' => trim($post['signature_text'] ?? $defaults['signature_text'] ?? ''),
        'signature_image' => trim($post['signature_image'] ?? $defaults['signature_image'] ?? ''),
        'signature_data' => trim($post['signature_data'] ?? ''),
        'org_name' => get_setting('org_name', ORG_NAME),
    ];
}

function certificate_normalize_signature_type(string $type): string
{
    if ($type === 'none') {
        $type = 'type';
    }
    return in_array($type, ['type', 'image', 'draw'], true) ? $type : 'type';
}

function certificate_signature_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    return base_url('uploads/' . ltrim(str_replace('\\', '/', $path), '/'));
}

function certificate_signature_file_uri(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    $full = UPLOAD_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');
    if (!is_readable($full)) {
        return null;
    }
    $mime = mime_content_type($full) ?: 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($full));
}

function certificate_save_data_url_image(string $dataUrl, string $module): ?string
{
    if (!preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $dataUrl, $m)) {
        return null;
    }
    $ext = strtolower($m[1]);
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }
    $bin = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
    if ($bin === false || strlen($bin) > 2 * 1024 * 1024) {
        return null;
    }
    $dir = UPLOAD_PATH . '/' . $module;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = uniqid('sig_', true) . '.' . $ext;
    if (file_put_contents($dir . '/' . $filename, $bin) === false) {
        return null;
    }
    return $module . '/' . $filename;
}

function certificate_prepare_signature(array &$data): void
{
    $type = $data['signature_type'] ?? 'none';
    $oldPath = $data['signature_image'] ?? '';
    $dataUrl = trim($data['signature_data'] ?? '');

    if ($type === 'type') {
        if ($oldPath !== '') {
            delete_upload($oldPath);
        }
        $data['signature_image'] = '';
        unset($data['signature_data']);
        return;
    }

    $newPath = null;
    if ($dataUrl !== '' && str_starts_with($dataUrl, 'data:image')) {
        $newPath = certificate_save_data_url_image($dataUrl, 'certificates/signatures');
    }

    if ($newPath !== null) {
        if ($oldPath !== '' && $oldPath !== $newPath) {
            delete_upload($oldPath);
        }
        $data['signature_image'] = $newPath;
    }

    unset($data['signature_data']);
}

function certificate_apply_vars(array $data): array
{
    $vars = [
        'recipient_name' => $data['recipient_name'],
        'participant_name' => $data['recipient_name'],
        'recipient_role' => $data['recipient_role'],
        'event_name' => $data['event_name'],
        'event_date' => $data['event_date'],
        'event_location' => $data['event_location'],
        'org_name' => $data['org_name'],
    ];
    return [
        'title' => certificate_apply_placeholders($data['cert_title'], $vars),
        'intro' => certificate_apply_placeholders($data['cert_intro'], $vars),
        'body' => certificate_apply_placeholders($data['cert_body'], $vars),
        'footer' => certificate_apply_placeholders($data['cert_footer'], $vars),
        'recipient_name' => $vars['recipient_name'],
        'recipient_role' => $vars['recipient_role'],
        'signatory' => $data['cert_signatory'],
        'signatory_role' => $data['cert_signatory_role'],
        'signature_type' => $data['signature_type'] ?? 'type',
        'signature_text' => $data['signature_text'] ?? '',
        'signature_image' => $data['signature_image'] ?? '',
        'org_name' => $data['org_name'],
    ];
}

function certificate_render_signature_block(array $rendered, bool $forPdf = false): string
{
    $type = $rendered['signature_type'] ?? 'type';
    $markup = '';

    if ($type === 'type') {
        $text = trim($rendered['signature_text'] ?? '');
        if ($text !== '') {
            $markup = '<p class="cert-signature-typed">' . htmlspecialchars($text) . '</p>';
        }
    } elseif (in_array($type, ['image', 'draw'], true)) {
        $path = $rendered['signature_image'] ?? '';
        if ($path !== '') {
            $src = $forPdf ? certificate_signature_file_uri($path) : certificate_signature_url($path);
            if ($src) {
                $markup = '<img src="' . htmlspecialchars($src) . '" alt="" class="cert-signature-img">';
            }
        }
    }

    $lineOnly = $markup === '' ? ' cert-sign-visual--line-only' : '';
    return '<div class="cert-sign-visual' . $lineOnly . '">' . $markup . '<div class="cert-sign-divider line"></div></div>';
}

function certificate_render_body(array $rendered, bool $forPdf = false): string
{
    $logo = $forPdf ? org_logo_file_uri() : org_logo_url();
    $org = htmlspecialchars($rendered['org_name'] ?? get_setting('org_name', ORG_NAME));
    $logoHtml = $logo ? '<img src="' . htmlspecialchars($logo) . '" alt="" class="cert-logo">' : '';

    return '<div class="certificate" id="certificate-preview">
        <span class="cert-corner tl"></span><span class="cert-corner br"></span>
        <div class="cert-border-inner">
            <div>
                ' . $logoHtml . '
                <p class="cert-org">' . $org . '</p>
                <h1 class="cert-title">' . htmlspecialchars($rendered['title']) . '</h1>
                <div class="cert-saffron-line"></div>
                <p class="cert-intro">' . htmlspecialchars($rendered['intro']) . '</p>
                <p class="cert-name">' . htmlspecialchars($rendered['recipient_name']) . '</p>
                <p class="cert-body">' . nl2br(htmlspecialchars($rendered['body'])) . '</p>
                <p class="cert-footer">' . htmlspecialchars($rendered['footer']) . '</p>
            </div>
            <div class="cert-sign">' . certificate_render_signature_block($rendered, $forPdf) . '
                <strong class="cert-signatory">' . htmlspecialchars($rendered['signatory']) . '</strong>
                <span class="cert-signatory-role">' . htmlspecialchars($rendered['signatory_role']) . '</span>
            </div>
        </div>
    </div>';
}

function certificate_render_html(array $rendered): string
{
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . certificate_inline_styles() . '</style></head><body>'
        . certificate_render_body($rendered, true)
        . '</body></html>';
}

function certificate_inline_styles(): string
{
    return '
    @page { size: A4 landscape; margin: 0; }
    body { margin: 0; font-family: Georgia, "Times New Roman", serif; color: #002147; background: #fff; }
    .certificate {
        width: 297mm; min-height: 210mm; margin: 0 auto; box-sizing: border-box;
        background: #fff; padding: 18mm 22mm; position: relative; text-align: center;
        border: 8px double #002147; outline: 2px solid #F58220; outline-offset: -14px;
    }
    .cert-border-inner {
        border: 1px solid #138808; padding: 12mm 10mm; min-height: calc(210mm - 36mm - 24px);
        box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;
    }
    .cert-logo { max-height: 72px; max-width: 200px; margin-bottom: 8px; }
    .cert-org { font-size: 14px; letter-spacing: 0.15em; color: #5A6B7D; margin: 0; text-transform: uppercase; }
    .cert-title { font-size: 28px; font-weight: 700; color: #002147; margin: 16px 0 8px; letter-spacing: 0.08em; }
    .cert-saffron-line { width: 120px; height: 3px; background: linear-gradient(90deg, #F58220, #138808); margin: 0 auto 20px; }
    .cert-intro { font-size: 16px; margin-bottom: 8px; }
    .cert-name { font-size: 32px; font-weight: 700; color: #F58220; margin: 12px 0 20px; font-style: italic; }
    .cert-body { font-size: 15px; line-height: 1.7; max-width: 85%; margin: 0 auto 24px; color: #1a3a5c; }
    .cert-footer { font-size: 13px; color: #5A6B7D; font-style: italic; margin-top: 16px; }
    .cert-sign { margin-top: 28px; }
    .cert-sign-visual { margin: 0 auto 8px; text-align: center; }
    .cert-sign-visual--line-only .cert-sign-divider { margin-top: 40px; }
    .cert-sign-divider.line { width: 200px; border-top: 2px solid #002147; margin: 6px auto 10px; }
    .cert-signature-img { max-height: 64px; max-width: 220px; display: block; margin: 0 auto 4px; object-fit: contain; }
    .cert-signature-typed { font-family: "Brush Script MT", "Segoe Script", "Lucida Handwriting", cursive; font-size: 28px; color: #002147; margin: 0 0 4px; line-height: 1.2; }
    .cert-sign strong { display: block; font-size: 14px; }
    .cert-sign span { font-size: 12px; color: #5A6B7D; }
    .cert-corner { position: absolute; width: 40px; height: 40px; border: 3px solid #138808; }
    .cert-corner.tl { top: 22px; left: 22px; border-right: none; border-bottom: none; }
    .cert-corner.br { bottom: 22px; right: 22px; border-left: none; border-top: none; }
    ';
}

function certificate_render_pdf(string $html): string
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_readable($autoload)) {
        throw new RuntimeException('PDF library not installed. Run composer install in the admin folder.');
    }
    require_once $autoload;
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper([0, 0, 842, 595]);
    $dompdf->render();
    return $dompdf->output();
}

function certificate_table_for(string $targetType): string
{
    return $targetType === 'organiser' ? 'event_organisers' : 'event_participants';
}

function certificate_load_saved_data(PDO $pdo, string $targetType, int $targetId): ?array
{
    try {
        $table = certificate_table_for($targetType);
        $stmt = $pdo->prepare("SELECT certificate_data FROM {$table} WHERE id = ?");
        $stmt->execute([$targetId]);
        $json = $stmt->fetchColumn();
        if (!$json) {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    } catch (PDOException $e) {
        return null;
    }
}

function certificate_save_data(PDO $pdo, string $targetType, int $targetId, array $data): void
{
    $table = certificate_table_for($targetType);
    try {
        $pdo->prepare("UPDATE {$table} SET certificate_data = ? WHERE id = ?")
            ->execute([json_encode($data, JSON_UNESCAPED_UNICODE), $targetId]);
    } catch (PDOException $e) {
        // column may not exist
    }
}
