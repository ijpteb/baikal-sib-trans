<?php

declare(strict_types=1);

/*
 * site.webmanifest — название и значки сайта для телефонов («добавить на экран „Домой“»).
 */

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

// Короткое название под значком на телефоне: целыми словами, а не «Байкал Сиб-Т».
$name = trim((string) setting('site.name'));
$shortName = $name;
if (mb_strlen($name) > 18) {
    $shortName = '';
    foreach (preg_split('/\s+/u', $name) ?: [] as $word) {
        $next = trim($shortName . ' ' . $word);
        if (mb_strlen($next) > 18) {
            break;
        }
        $shortName = $next;
    }
    $shortName = $shortName !== '' ? $shortName : mb_substr($name, 0, 18);
}

$icons = [
    ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
    ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
];
if (!site_has_custom_favicon()) {
    // Стандартный значок залит цветом во всю площадь, знак — в середине:
    // Android может обрезать его под свою форму (круг, капля), и значок не окажется в белом кружке.
    $icons[] = ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'];
}

echo json_encode([
    'name' => $name,
    'short_name' => $shortName,
    'lang' => 'ru',
    'start_url' => '/',
    'display' => 'browser',
    'background_color' => '#FFFFFF',
    'theme_color' => '#0B4F8A',
    'icons' => $icons,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
