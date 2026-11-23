<?php

declare(strict_types=1);

/*
 * Страница станции техосмотра.
 */

$crumbs = [
    ['label' => 'Главная', 'href' => '/'],
    ['label' => 'Техосмотр', 'href' => '/tehosmotr'],
];

$html = render_public_page('public/tehosmotr', [
    'categories' => Content::list('inspection_categories'),
    'crumbs' => $crumbs,
], [
    'title' => (string) setting('seo.tehosmotr_title'),
    'description' => (string) setting('seo.tehosmotr_description'),
    'canonical' => '/tehosmotr',
    'page' => 'tehosmotr',
    'jsonld' => [inspection_jsonld(), breadcrumbs_jsonld($crumbs)],
]);

send_html($html);
