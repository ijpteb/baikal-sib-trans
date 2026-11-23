<?php
/**
 * Общий макет админки: меню слева (на телефоне — по кнопке «Меню»), сообщения, содержимое.
 *
 * @var string $content
 * @var array  $meta  title, section, bare
 */

$siteName = (string) setting('site.name', 'Байкал Сиб-Транс');
$title = (string) ($meta['title'] ?? 'Админка');
$section = (string) ($meta['section'] ?? '');
$bare = !empty($meta['bare']);
$user = $bare ? null : admin_user();
$flashes = flash_take();
$config = [
    'csrf' => csrf_token(),
    'maxUpload' => min(IMAGE_MAX_BYTES, upload_limit_bytes() ?: IMAGE_MAX_BYTES),
];
// Иконки уведомлений рисует скрипт — добавляем их в набор заранее.
foreach (['check-circle', 'alert-circle', 'info', 'trash', 'arrow-left', 'arrow-right', 'grip', 'star', 'eye', 'eye-off'] as $scriptIcon) {
    Icons::markUsed($scriptIcon);
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?> — админка «<?= e($siteName) ?>»</title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0B4F8A">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="preload" href="/assets/fonts/manrope-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script type="module" src="<?= e(asset('js/admin.js')) ?>"></script>
</head>
<body class="<?= e(classes('admin', $bare ? 'admin--bare' : null)) ?>">
<a class="skip-link" href="#main">Перейти к содержанию</a>
<!--icon-sprite-->
<?php if ($bare): ?>
<main class="auth" id="main" tabindex="-1">
  <div class="auth__inner">
    <a class="auth__logo" href="/">
      <img src="<?= e(asset('img/logo-mark.svg')) ?>" width="44" height="44" alt="">
      <span><?= e($siteName) ?><small>Админка сайта</small></span>
    </a>
<?php foreach ($flashes as $flash): ?>
    <?= admin_alert($flash['type'], e($flash['message'])) ?>
<?php endforeach; ?>
<?= $content ?>
  </div>
</main>
<?php else: ?>
<header class="admin-top">
  <button class="btn btn--ghost btn--sm admin-top__menu" type="button" aria-expanded="false" aria-controls="admin-nav" data-nav-toggle><?= icon('menu') ?>Меню</button>
  <a class="admin-top__title" href="/admin"><?= e($siteName) ?></a>
  <a class="btn btn--ghost btn--sm btn--icon" href="/" target="_blank" rel="noopener" title="Открыть сайт"><?= icon('external-link', '', 'Открыть сайт в новой вкладке') ?></a>
</header>
<div class="admin-backdrop" data-nav-close hidden></div>
<div class="admin-shell">
  <aside class="admin-side" id="admin-nav" aria-label="Разделы админки">
    <div class="admin-side__head">
      <a class="admin-brand" href="/admin">
        <img src="<?= e(asset('img/logo-mark.svg')) ?>" width="36" height="36" alt="">
        <span><?= e($siteName) ?><small>Админка</small></span>
      </a>
      <button class="btn btn--ghost btn--sm btn--icon admin-side__close" type="button" data-nav-close aria-label="Закрыть меню"><?= icon('close') ?></button>
    </div>
    <nav class="admin-nav">
<?php foreach (admin_nav() as $group => $items): ?>
<?php if ($group !== ''): ?>
      <p class="admin-nav__group"><?= e($group) ?></p>
<?php endif; ?>
      <ul class="admin-nav__list">
<?php foreach ($items as $key => $item): ?>
        <li><a class="admin-nav__link" href="<?= e($item['href']) ?>"<?= $key === $section ? ' aria-current="page"' : '' ?>><?= icon($item['icon']) ?><span><?= e($item['label']) ?></span></a></li>
<?php endforeach; ?>
      </ul>
<?php endforeach; ?>
    </nav>
    <div class="admin-side__foot">
      <a class="admin-nav__link" href="/" target="_blank" rel="noopener"><?= icon('external-link') ?><span>Открыть сайт</span></a>
      <form method="post" action="/admin/logout">
        <?= csrf_field() ?>
        <button class="admin-nav__link admin-nav__button" type="submit"><?= icon('logout') ?><span>Выйти<?= $user !== null ? ' <small>(' . e($user['username']) . ')</small>' : '' ?></span></button>
      </form>
    </div>
  </aside>
  <main class="admin-main" id="main" tabindex="-1">
    <div class="admin-container">
<?php foreach ($flashes as $flash): ?>
      <?= admin_alert($flash['type'], e($flash['message']), 'alert--flash') ?>
<?php endforeach; ?>
<?= $content ?>
    </div>
  </main>
</div>
<?php endif; ?>
<div class="toasts" id="toasts" role="status" aria-live="polite"></div>
<script type="application/json" id="admin-config"><?= json_for_script($config) ?></script>
</body>
</html>
