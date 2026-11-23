<?php

declare(strict_types=1);

/*
 * site.webmanifest — название и значки сайта для телефонов («добавить на экран „Домой“»).
 */

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

echo json_encode([
    'name' => (string) setting('site.name'),
    'short_name' => mb_substr((string) setting('site.name'), 0, 12),
    'lang' => 'ru',
    'start_url' => '/',
    'display' => 'browser',
    'background_color' => '#FFFFFF',
    'theme_color' => '#0B4F8A',
    'icons' => [
        ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
