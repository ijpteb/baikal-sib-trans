<?php

declare(strict_types=1);

/*
 * Политика конфиденциальности (текст редактируется в админке).
 */

$crumbs = [
    ['label' => 'Главная', 'href' => '/'],
    ['label' => 'Политика конфиденциальности', 'href' => '/privacy'],
];

$html = render_public_page('public/privacy', [
    'crumbs' => $crumbs,
    'body' => privacy_html(),
    'updatedAt' => Settings::updatedAt('privacy'),
], [
    'title' => (string) setting('seo.privacy_title'),
    'description' => (string) setting('seo.privacy_description'),
    'canonical' => '/privacy',
    'page' => 'privacy',
    'jsonld' => [breadcrumbs_jsonld($crumbs)],
]);

send_html($html);
