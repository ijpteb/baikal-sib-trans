<?php
/**
 * Шапка сайта и мобильное меню.
 *
 * @var string $page
 */

$nav = site_nav($page);
$phones = company_phones();
$phone = $phones[0] ?? null;
$hours = dispatcher_hours();
$messengers = messengers_enabled();
$requestHref = $page === 'home' ? '#contacts' : '/#contacts';
?>
<header class="site-header" id="site-header">
  <div class="site-header__bar" aria-hidden="true"></div>
  <div class="container site-header__inner">
    <?= view('partials/logo', ['current' => $page === 'home']) ?>

    <nav class="nav" aria-label="Основное меню">
      <ul class="nav__list">
<?php foreach ($nav as $item): ?>
<?php if ($item['desktop']): ?>
        <li><a class="nav__link" href="<?= e($item['href']) ?>" data-nav="<?= e($item['section']) ?>"><?= e($item['label']) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
        <li class="nav__sep" aria-hidden="true"></li>
        <li><a class="nav__link nav__link--secondary" href="/tehosmotr"<?= $page === 'tehosmotr' ? ' aria-current="page"' : '' ?>><?= icon('clipboard-check') ?>Техосмотр</a></li>
      </ul>
    </nav>

    <div class="header-actions">
<?php if ($phone !== null): ?>
      <a class="header-phone" href="<?= e($phone['href']) ?>">
        <span class="header-phone__number"><?= icon('phone') ?><?= e($phone['number']) ?></span>
<?php if ($hours !== ''): ?>
        <span class="header-phone__hint"><?= e($hours === 'Круглосуточно' ? 'Диспетчер круглосуточно' : $hours) ?></span>
<?php endif; ?>
      </a>
<?php endif; ?>
      <a class="btn btn--primary btn--sm header-cta" href="<?= e($requestHref) ?>" data-open-request>Заказать транспорт</a>
      <button class="burger" type="button" aria-label="Открыть меню" aria-haspopup="dialog" aria-controls="menu" aria-expanded="false" data-menu-open>
        <span class="burger__lines" aria-hidden="true"></span>
      </button>
    </div>
  </div>
</header>

<dialog class="menu" id="menu" aria-label="Меню сайта">
  <div class="menu__inner">
    <div class="container menu__top">
      <?= view('partials/logo') ?>
      <button class="menu__close" type="button" aria-label="Закрыть меню" data-menu-close><?= icon('close') ?></button>
    </div>
    <nav class="container menu__nav" aria-label="Меню">
      <ul class="menu__list">
<?php foreach ($nav as $item): ?>
        <li><a class="menu__link" href="<?= e($item['href']) ?>" data-menu-link><?= e($item['label']) ?><?= icon('arrow-right') ?></a></li>
<?php endforeach; ?>
        <li><a class="menu__link" href="/tehosmotr" data-menu-link<?= $page === 'tehosmotr' ? ' aria-current="page"' : '' ?>>Техосмотр<?= icon('clipboard-check') ?></a></li>
      </ul>
    </nav>
    <div class="container menu__footer">
<?php if ($phone !== null): ?>
      <a class="menu__phone" href="<?= e($phone['href']) ?>"><?= e($phone['number']) ?></a>
<?php if ($hours !== ''): ?>
      <p class="menu__hours"><?= e($hours === 'Круглосуточно' ? 'Диспетчер на связи круглосуточно' : $hours) ?></p>
<?php endif; ?>
<?php endif; ?>
      <a class="btn btn--primary btn--lg btn--block" href="<?= e($requestHref) ?>" data-open-request data-menu-link>Заказать транспорт</a>
<?php if ($messengers): ?>
      <div class="menu__messengers">
<?php foreach ($messengers as $m): ?>
        <a class="btn btn--outline btn--sm" href="<?= e($m['url']) ?>" target="_blank" rel="noopener"><?= icon($m['icon']) ?><?= e($m['label']) ?></a>
<?php endforeach; ?>
      </div>
<?php endif; ?>
    </div>
  </div>
</dialog>
