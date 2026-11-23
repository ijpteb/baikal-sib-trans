<?php

declare(strict_types=1);

/*
 * Общие данные для шаблонов публичной части: меню, логотип, данные для скриптов, разметка для поисковиков.
 */

/**
 * Пункты меню. На главной — переходы к разделам, на других страницах — ссылки на разделы главной.
 * desktop = false — пункт показывается только в мобильном меню (в шапке компьютера мало места).
 *
 * @return list<array{href: string, label: string, section: string, desktop: bool}>
 */
function site_nav(string $page): array
{
    $prefix = $page === 'home' ? '' : '/';
    $items = [
        ['href' => $prefix . '#services', 'label' => 'Поездки', 'section' => 'services', 'desktop' => true],
        ['href' => $prefix . '#fleet', 'label' => 'Автопарк', 'section' => 'fleet', 'desktop' => true],
    ];
    if (Content::calculatorVisible()) {
        $items[] = ['href' => $prefix . '#calculator', 'label' => 'Цены', 'section' => 'calculator', 'desktop' => true];
    }
    $items[] = ['href' => $prefix . '#about', 'label' => 'О компании', 'section' => 'about', 'desktop' => false];
    if (Content::list('reviews')) {
        $items[] = ['href' => $prefix . '#reviews', 'label' => 'Отзывы', 'section' => 'reviews', 'desktop' => false];
    }
    if (Content::list('faq')) {
        $items[] = ['href' => $prefix . '#faq', 'label' => 'Вопросы', 'section' => 'faq', 'desktop' => true];
    }
    $items[] = ['href' => $prefix . '#contacts', 'label' => 'Контакты', 'section' => 'contacts', 'desktop' => true];
    return $items;
}

/** Загруженный в админке логотип (или null — тогда рисуется стандартный знак с названием). */
function site_logo(): ?array
{
    $logo = setting('site.logo', []);
    return is_array($logo) && !empty($logo['file']) ? $logo : null;
}

/** Картинка для соцсетей и мессенджеров (когда ссылку на сайт отправляют в чат). */
function site_og_image(): ?array
{
    $custom = setting('site.og_image', []);
    if (is_array($custom) && !empty($custom['file'])) {
        return [
            'url' => abs_url(image_url($custom, 1600)),
            'width' => (int) ($custom['w'] ?? 0),
            'height' => (int) ($custom['h'] ?? 0),
        ];
    }
    $default = PUBLIC_DIR . '/assets/img/og-image.jpg';
    if (is_file($default)) {
        return ['url' => abs_url(asset('img/og-image.jpg')), 'width' => 1200, 'height' => 630];
    }
    return null;
}

function site_has_custom_favicon(): bool
{
    $icon = setting('site.favicon', []);
    return is_array($icon) && !empty($icon['file']);
}

/** Типы поездок для формы заявки — из раздела «Для каких поездок». */
function trip_types(): array
{
    $types = [];
    foreach (Content::list('services') as $service) {
        $title = trim((string) $service['title']);
        if ($title !== '' && !in_array($title, $types, true)) {
            $types[] = $title;
        }
    }
    return $types;
}

/** Пункты под заголовком первого экрана (по одному на строку в админке). */
function hero_points(): array
{
    return array_slice(lines_list((string) setting('hero.points', '')), 0, 4);
}

/** Данные для скриптов страницы (заявка, калькулятор, мессенджеры). */
function site_config_for_js(string $page): array
{
    $phone = primary_phone();
    $vehicles = [];
    foreach (Content::vehicles() as $v) {
        $vehicles[] = ['id' => $v['id'], 'name' => $v['name'], 'seats' => $v['seats']];
    }
    $email = trim((string) setting('contacts.email', ''));
    return [
        'page' => $page,
        'phone' => $phone ? ['number' => $phone['number'], 'href' => $phone['href']] : null,
        'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
        'messengers' => array_map(
            static fn (array $m): array => [
                'key' => $m['key'],
                'label' => $m['label'],
                'url' => $m['url'],
                'prefill' => $m['prefill'],
                'param' => $m['param'],
            ],
            messengers_enabled()
        ),
        'vehicles' => $vehicles,
        'calculator' => Content::calculatorVisible() ? Content::calculatorVehicles() : [],
        'today' => date('Y-m-d'),
        'metrika' => metrika_id() !== '' ? metrika_id() : null,
        'cookieNotice' => (bool) setting('seo.cookie_notice', true),
    ];
}

/* ---------- Калькулятор (та же формула работает в скрипте страницы) ---------- */

/** Способы расчёта, доступные для машины: почасово, по километрам, трансфер. */
function calc_modes(array $v): array
{
    $modes = [];
    if ((int) ($v['hour'] ?? 0) > 0) {
        $modes[] = 'hour';
    }
    if ((int) ($v['km'] ?? 0) > 0) {
        $modes[] = 'km';
    }
    if ((int) ($v['transfer'] ?? 0) > 0) {
        $modes[] = 'transfer';
    }
    return $modes;
}

/**
 * Ориентировочная стоимость.
 *
 * @return array{total: int, lines: list<array{0: string, 1: string}>}
 */
function calc_estimate(array $v, string $mode, int $amount): array
{
    $lines = [];
    if ($mode === 'hour' && (int) ($v['hour'] ?? 0) > 0) {
        $hours = max(max(1, (int) ($v['minHours'] ?? 1)), $amount);
        $total = $hours * (int) $v['hour'];
        $lines[] = [$hours . ' ' . plural_ru($hours, 'час', 'часа', 'часов') . ' × ' . money((int) $v['hour']), money($total)];
    } elseif ($mode === 'km' && (int) ($v['km'] ?? 0) > 0) {
        $km = max(1, $amount);
        $total = $km * (int) $v['km'];
        $lines[] = [$km . ' км × ' . money((int) $v['km']), money($total)];
    } else {
        $total = (int) ($v['transfer'] ?? 0);
        $lines[] = ['Трансфер', money($total)];
    }
    $delivery = (int) ($v['delivery'] ?? 0);
    if ($delivery > 0) {
        $total += $delivery;
        $lines[] = ['Подача транспорта', money($delivery)];
    }
    return ['total' => $total, 'lines' => $lines];
}

/* ---------- Адрес и карта ---------- */

/**
 * Разбирает адрес вида «664009, г. Иркутск, ул. Ширямова, д. 40, офис 306».
 *
 * @return array{postal: string, street: string}
 */
function address_parts(string $address): array
{
    $postal = '';
    if (preg_match('/\b(\d{6})\b/u', $address, $m)) {
        $postal = $m[1];
    }
    $parts = array_map('trim', explode(',', $address));
    $street = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === $postal || preg_match('/^(г\.|город)\s*/ui', $part) || preg_match('/область$/ui', $part)) {
            continue;
        }
        $street[] = $part;
    }
    return ['postal' => $postal, 'street' => implode(', ', $street)];
}

/** Адрес карты Яндекса для встраивания (метка по координатам или поиск по адресу). */
function map_embed_url(string $address): string
{
    $lat = str_replace(',', '.', trim((string) setting('contacts.map_lat', '')));
    $lng = str_replace(',', '.', trim((string) setting('contacts.map_lng', '')));
    $zoom = max(10, min(19, (int) setting('contacts.map_zoom', 16)));
    if (is_numeric($lat) && is_numeric($lng) && abs((float) $lat) <= 90 && abs((float) $lng) <= 180) {
        $point = $lng . ',' . $lat;
        return 'https://yandex.ru/map-widget/v1/?' . http_build_query([
            'll' => $point,
            'z' => $zoom,
            'pt' => $point . ',pm2rdm',
        ], '', '&', PHP_QUERY_RFC3986);
    }
    return 'https://yandex.ru/map-widget/v1/?' . http_build_query([
        'text' => $address,
        'z' => $zoom,
    ], '', '&', PHP_QUERY_RFC3986);
}

/** Ссылка «Открыть в Яндекс Картах» (полная версия карты, маршрут). */
function map_link_url(string $address): string
{
    $lat = str_replace(',', '.', trim((string) setting('contacts.map_lat', '')));
    $lng = str_replace(',', '.', trim((string) setting('contacts.map_lng', '')));
    if (is_numeric($lat) && is_numeric($lng)) {
        return 'https://yandex.ru/maps/?' . http_build_query([
            'pt' => $lng . ',' . $lat,
            'z' => 17,
            'l' => 'map',
        ], '', '&', PHP_QUERY_RFC3986);
    }
    return 'https://yandex.ru/maps/?' . http_build_query(['text' => $address], '', '&', PHP_QUERY_RFC3986);
}

/* ---------- Разметка для поисковиков (JSON-LD) ---------- */

function local_business_jsonld(): array
{
    $base = base_url();
    $phones = company_phones();
    $address = (string) setting('contacts.office_address', '');
    $parts = address_parts($address);
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        '@id' => $base . '/#organization',
        'name' => (string) setting('site.name'),
        'legalName' => trim((string) setting('requisites.full_name', '')) ?: (string) setting('site.company'),
        'description' => (string) setting('seo.home_description'),
        'url' => $base . '/',
        'areaServed' => [
            ['@type' => 'City', 'name' => (string) setting('contacts.city', 'Иркутск')],
            ['@type' => 'State', 'name' => (string) setting('contacts.region', 'Иркутская область')],
        ],
    ];
    if ($phones) {
        $data['telephone'] = '+' . phone_digits($phones[0]['number']);
    }
    $email = trim((string) setting('contacts.email', ''));
    if ($email !== '') {
        $data['email'] = $email;
    }
    if ($address !== '') {
        $data['address'] = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $parts['street'],
            'addressLocality' => (string) setting('contacts.city', 'Иркутск'),
            'addressRegion' => (string) setting('contacts.region', 'Иркутская область'),
            'postalCode' => $parts['postal'],
            'addressCountry' => 'RU',
        ]);
    }
    $lat = str_replace(',', '.', trim((string) setting('contacts.map_lat', '')));
    $lng = str_replace(',', '.', trim((string) setting('contacts.map_lng', '')));
    if (is_numeric($lat) && is_numeric($lng)) {
        $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng];
    }
    if (setting('contacts.dispatcher_247')) {
        $data['openingHoursSpecification'] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens' => '00:00',
            'closes' => '23:59',
        ];
    }
    $logo = site_logo();
    if ($logo) {
        $data['logo'] = abs_url(image_url($logo, 480));
    } else {
        $data['logo'] = abs_url('/assets/img/logo-mark.svg');
    }
    $og = site_og_image();
    if ($og) {
        $data['image'] = $og['url'];
    }
    $inn = trim((string) setting('requisites.inn', ''));
    if ($inn !== '') {
        $data['taxID'] = $inn;
    }
    $sameAs = array_values(array_filter(array_map(
        static fn (array $m): string => str_starts_with($m['url'], 'https://') ? $m['url'] : '',
        messengers_enabled()
    )));
    if ($sameAs) {
        $data['sameAs'] = $sameAs;
    }
    return $data;
}

/** Станция техосмотра для поисковиков: адрес, телефон и цены по категориям. */
function inspection_jsonld(): array
{
    $address = trim((string) setting('contacts.inspection_address', '')) ?: (string) setting('contacts.office_address', '');
    $parts = address_parts($address);
    $phone = primary_phone();
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'AutomotiveBusiness',
        '@id' => base_url() . '/tehosmotr#station',
        'name' => 'Станция техосмотра «' . (string) setting('site.name') . '»',
        'description' => (string) setting('seo.tehosmotr_description'),
        'url' => abs_url('/tehosmotr'),
        'parentOrganization' => ['@id' => base_url() . '/#organization'],
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $parts['street'],
            'addressLocality' => (string) setting('contacts.city', 'Иркутск'),
            'addressRegion' => (string) setting('contacts.region', 'Иркутская область'),
            'postalCode' => $parts['postal'],
            'addressCountry' => 'RU',
        ]),
    ];
    if ($phone !== null) {
        $data['telephone'] = '+' . phone_digits($phone['number']);
    }
    $offers = [];
    foreach (Content::list('inspection_categories') as $cat) {
        $price = (int) ($cat['price'] ?? 0);
        if ($price <= 0) {
            continue;
        }
        $offers[] = [
            '@type' => 'Offer',
            'name' => 'Технический осмотр, категория ' . $cat['code'] . ' (' . $cat['title'] . ')',
            'price' => $price,
            'priceCurrency' => 'RUB',
        ];
    }
    if ($offers) {
        $data['makesOffer'] = $offers;
    }
    return $data;
}

function faq_jsonld(array $faq): ?array
{
    if (!$faq) {
        return null;
    }
    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static fn (array $item): array => [
            '@type' => 'Question',
            'name' => (string) $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(preg_replace('/\s+/u', ' ', str_replace('**', '', (string) $item['answer'])) ?? '')],
        ], $faq),
    ];
}

function breadcrumbs_jsonld(array $crumbs): array
{
    $list = [];
    foreach (array_values($crumbs) as $i => $crumb) {
        $list[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $crumb['label'],
            'item' => abs_url($crumb['href']),
        ];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

/** Подставляет реальные контакты в текст политики конфиденциальности. */
function privacy_html(): string
{
    $email = trim((string) setting('contacts.email', ''));
    $phone = primary_phone();
    $vars = [
        '{компания}' => e(trim((string) setting('requisites.full_name', '')) ?: (string) setting('site.company')),
        '{сайт}' => e(preg_replace('#^https?://#', '', base_url()) ?? ''),
        '{адрес}' => e((string) setting('contacts.office_address', '')),
        '{email}' => $email !== '' ? '<a href="mailto:' . e($email) . '">' . e($email) . '</a>' : 'по телефону',
        '{телефон}' => $phone ? '<a href="' . e($phone['href']) . '">' . e($phone['number']) . '</a>' : '',
    ];
    return rich_text_to_html((string) setting('privacy.text', ''), $vars);
}
