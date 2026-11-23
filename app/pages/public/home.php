<?php

declare(strict_types=1);

/*
 * Главная страница: все разделы на одной странице.
 */

$faq = Content::list('faq');

$html = render_public_page('public/home', [
    'services' => Content::list('services'),
    'vehicles' => Content::vehicles(),
    'advantages' => Content::list('advantages'),
    'stats' => Content::list('stats'),
    'steps' => Content::list('steps'),
    'partners' => Content::list('partners'),
    'reviews' => Content::list('reviews'),
    'faq' => $faq,
    'calcVehicles' => Content::calculatorVisible() ? Content::calculatorVehicles() : [],
], [
    'title' => (string) setting('seo.home_title'),
    'description' => (string) setting('seo.home_description'),
    'canonical' => '/',
    'page' => 'home',
    'jsonld' => [local_business_jsonld(), faq_jsonld($faq)],
]);

send_html($html);
