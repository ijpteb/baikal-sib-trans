<?php
/**
 * Общий макет страниц сайта: <head>, шапка, подвал, окна заявки.
 *
 * @var string $content
 * @var array  $meta  title, description, canonical, robots, page, jsonld, og_title
 */

$siteName = (string) setting('site.name', 'Байкал Сиб-Транс');
$title = trim((string) ($meta['title'] ?? ''));
if ($title === '') {
    $title = $siteName;
} elseif (!str_contains($title, $siteName)) {
    $title .= ' — ' . $siteName;
}
$description = trim((string) ($meta['description'] ?? ''));
$canonicalPath = array_key_exists('canonical', $meta) ? $meta['canonical'] : request_path();
$canonical = $canonicalPath === null ? null : abs_url((string) $canonicalPath);
$robots = (string) ($meta['robots'] ?? 'index, follow');
$page = (string) ($meta['page'] ?? '');
$og = site_og_image();
$jsonLd = array_values(array_filter((array) ($meta['jsonld'] ?? [])));
$verification = preg_replace('/[^A-Za-z0-9_-]/', '', (string) setting('seo.yandex_verification', '')) ?? '';
$customFavicon = site_has_custom_favicon();
$iconVersion = favicon_version();
$metrika = metrika_id();
// Иконки всплывающих уведомлений: их рисует скрипт, поэтому добавляем в набор заранее.
foreach (['check-circle', 'alert-circle', 'info'] as $toastIcon) {
    Icons::markUsed($toastIcon);
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<?php if ($description !== ''): ?>
<meta name="description" content="<?= e($description) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<?php if ($canonical !== null): ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<meta name="theme-color" content="#0B4F8A">
<meta name="format-detection" content="telephone=no">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($meta['og_title'] ?? $title) ?>">
<?php if ($description !== ''): ?>
<meta property="og:description" content="<?= e($description) ?>">
<?php endif; ?>
<?php if ($canonical !== null): ?>
<meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
<?php if ($og !== null): ?>
<meta property="og:image" content="<?= e($og['url']) ?>">
<?php if ($og['width'] > 0 && $og['height'] > 0): ?>
<meta property="og:image:width" content="<?= (int) $og['width'] ?>">
<meta property="og:image:height" content="<?= (int) $og['height'] ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<?php if ($verification !== ''): ?>
<meta name="yandex-verification" content="<?= e($verification) ?>">
<?php endif; ?>
<link rel="icon" href="/favicon.ico?v=<?= e($iconVersion) ?>" sizes="32x32">
<?php if (!$customFavicon): ?>
<link rel="icon" href="/favicon.svg?v=<?= e($iconVersion) ?>" type="image/svg+xml">
<?php endif; ?>
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=<?= e($iconVersion) ?>">
<link rel="manifest" href="/site.webmanifest?v=<?= e($iconVersion) ?>">
<link rel="preload" href="/assets/fonts/manrope-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/manrope-latin.woff2" as="font" type="font/woff2" crossorigin>
<style><?= inline_css('site.css') ?></style>
<script nonce="<?= e(csp_nonce()) ?>">document.documentElement.classList.add('js')</script>
<script type="module" src="<?= e(asset('js/site.js')) ?>"></script>
<?php foreach ($jsonLd as $block): ?>
<script type="application/ld+json"><?= json_for_script($block) ?></script>
<?php endforeach; ?>
</head>
<body<?= attrs(['class' => $page !== '' ? 'page-' . $page : null]) ?>>
<a class="skip-link" href="#main">Перейти к содержанию</a>
<!--icon-sprite-->
<?= view('partials/header', ['page' => $page]) ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?= view('partials/footer', ['page' => $page]) ?>
<?= view('partials/action-bar', ['page' => $page]) ?>
<?= view('partials/request-dialog', ['page' => $page]) ?>
<?= view('partials/send-dialog') ?>
<div class="toasts" id="toasts" role="status" aria-live="polite"></div>
<?php if ($metrika !== ''): ?>
<?= view('partials/metrika', ['id' => $metrika]) ?>
<?php endif; ?>
<script type="application/json" id="site-config"><?= json_for_script(site_config_for_js($page)) ?></script>
</body>
</html>
