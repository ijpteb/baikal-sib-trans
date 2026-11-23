<?php

declare(strict_types=1);

/*
 * Значки сайта: /favicon.ico, /favicon.svg, /apple-touch-icon.png, /icon-192.png, /icon-512.png.
 * Если в админке загружен свой значок — отдаём его, иначе стандартный.
 */

$name = (string) ($params['icon'] ?? 'favicon.ico');
$defaultSvg = PUBLIC_DIR . '/assets/img/favicon.svg';

if ($name === 'favicon.svg') {
    send_static_file($defaultSvg, 'image/svg+xml', 86400);
    return;
}

$sizes = ['favicon.ico' => 32, 'apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512];
$size = $sizes[$name] ?? 32;

$png = null;
$custom = setting('site.favicon', []);
if (is_array($custom) && !empty($custom['file']) && preg_match('/^[a-z0-9-]+$/', (string) $custom['file'])) {
    $candidate = UPLOADS_DIR . '/' . $custom['file'] . '-' . $size . '.png';
    if (is_file($candidate)) {
        $png = $candidate;
    }
}
if ($png === null) {
    $candidate = PUBLIC_DIR . '/assets/img/icons/icon-' . $size . '.png';
    if (is_file($candidate)) {
        $png = $candidate;
    }
}

if ($png === null) {
    // Готовых PNG нет — отдаём векторный значок (его понимают все современные браузеры).
    send_static_file($defaultSvg, 'image/svg+xml', 3600);
    return;
}

if ($name === 'favicon.ico') {
    send_static_file($png, 'image/x-icon', 86400, static fn (string $data): string => png_to_ico($data, $size));
    return;
}

send_static_file($png, 'image/png', 86400);
