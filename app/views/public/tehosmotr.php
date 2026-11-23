<?php
/**
 * Станция техосмотра: категории и цены, часы работы, документы, запись, адрес и карта.
 *
 * @var array $categories
 * @var array $crumbs
 */

$inspection = Settings::group('inspection');
$phone = primary_phone();
$messengers = messengers_enabled();
$address = trim((string) setting('contacts.inspection_address', '')) ?: trim((string) setting('contacts.office_address', ''));
$hours = trim((string) $inspection['hours']);
$documents = lines_list((string) $inspection['documents']);
$bookingText = trim((string) $inspection['booking_text']);
$intro = trim((string) $inspection['intro']);
?>
<section class="page-hero" aria-labelledby="page-title">
  <div class="hero__bg"><?= inline_svg_file(APP_DIR . '/views/partials/hero-art.svg') ?></div>
  <div class="container page-hero__inner">
    <?= view('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <h1 class="page-hero__title" id="page-title"><?= e((string) $inspection['title']) ?></h1>
<?php if ($intro !== ''): ?>
    <p class="page-hero__lead"><?= e($intro) ?></p>
<?php endif; ?>
    <div class="page-hero__actions">
      <a class="btn btn--primary btn--lg" href="#booking" data-open-inspection><?= icon('calendar') ?>Записаться на техосмотр</a>
<?php if ($phone !== null): ?>
      <a class="btn btn--glass btn--lg" href="<?= e($phone['href']) ?>"><?= icon('phone') ?><?= e($phone['number']) ?></a>
<?php endif; ?>
    </div>
  </div>
</section>

<section class="section" id="prices" aria-labelledby="prices-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Категории</p>
      <h2 class="section-title" id="prices-title">Категории транспорта и цены</h2>
      <p class="section-lead">Цена указана за один технический осмотр. Если цены нет — уточните её по телефону.</p>
    </div>
<?php if ($categories): ?>
    <ul class="categories" data-stagger>
<?php foreach ($categories as $cat): ?>
<?php $price = $cat['price'] !== null && $cat['price'] !== '' && (int) $cat['price'] > 0 ? (int) $cat['price'] : null; ?>
      <li data-reveal>
        <article class="card category">
          <span class="category__code" aria-hidden="true"><?= e($cat['code']) ?></span>
          <h3 class="category__title"><span class="visually-hidden">Категория <?= e($cat['code']) ?>: </span><?= e($cat['title']) ?></h3>
<?php if (trim((string) $cat['description']) !== ''): ?>
          <p class="category__desc"><?= e($cat['description']) ?></p>
<?php endif; ?>
<?php if ($price !== null): ?>
          <p class="category__price"><?= !empty($cat['price_from']) ? 'от ' : '' ?><?= e(money($price)) ?></p>
<?php else: ?>
          <p class="category__price category__price--ask">Цену уточняйте</p>
<?php endif; ?>
        </article>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </div>
</section>

<section class="section section--soft" id="booking" aria-labelledby="booking-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Запись</p>
      <h2 class="section-title" id="booking-title">Как пройти техосмотр</h2>
    </div>
    <div class="info-grid" data-stagger>
      <article class="card info-card" data-reveal>
        <h3 class="info-card__title"><span class="icon-tile"><?= icon('clock') ?></span>Часы работы</h3>
        <?= $hours !== '' ? text_to_html($hours) : '<p>Уточняйте по телефону.</p>' ?>
      </article>
<?php if ($documents): ?>
      <article class="card info-card" data-reveal>
        <h3 class="info-card__title"><span class="icon-tile"><?= icon('file-text') ?></span>Что взять с собой</h3>
        <ul class="doc-list">
<?php foreach ($documents as $doc): ?>
          <li><?= icon('check') ?><span><?= e($doc) ?></span></li>
<?php endforeach; ?>
        </ul>
      </article>
<?php endif; ?>
      <article class="card info-card" data-reveal>
        <h3 class="info-card__title"><span class="icon-tile"><?= icon('calendar') ?></span>Запись</h3>
<?php if ($bookingText !== ''): ?>
        <?= text_to_html($bookingText) ?>
<?php endif; ?>
        <div class="info-card__actions">
          <a class="btn btn--primary btn--block" href="#booking" data-open-inspection>Записаться онлайн</a>
<?php if ($phone !== null): ?>
          <a class="btn btn--outline btn--block" href="<?= e($phone['href']) ?>"><?= icon('phone') ?><?= e($phone['number']) ?></a>
<?php endif; ?>
        </div>
      </article>
    </div>
  </div>
</section>

<?php if ($address !== ''): ?>
<section class="section" id="address" aria-labelledby="address-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Адрес</p>
      <h2 class="section-title" id="address-title">Как нас найти</h2>
    </div>
    <div class="contacts">
      <div class="contacts__info">
        <div class="card contact-card" data-reveal>
          <div class="contact-line">
            <span class="contact-line__icon"><?= icon('map-pin') ?></span>
            <span class="contact-line__body">
              <span class="contact-line__label">Станция техосмотра</span>
              <span class="contact-line__value"><?= e($address) ?></span>
              <a class="link-arrow contact-line__link" href="<?= e(map_link_url($address)) ?>" target="_blank" rel="noopener">Построить маршрут<?= icon('external-link') ?></a>
            </span>
          </div>
<?php if ($phone !== null): ?>
          <div class="contact-line">
            <span class="contact-line__icon"><?= icon('phone') ?></span>
            <span class="contact-line__body">
              <span class="contact-line__label">Телефон</span>
              <a class="contact-line__value contact-line__value--big" href="<?= e($phone['href']) ?>"><?= e($phone['number']) ?></a>
            </span>
          </div>
<?php endif; ?>
<?php if ($messengers): ?>
          <div class="messenger-btns">
<?php foreach ($messengers as $m): ?>
            <a class="btn btn--outline" href="<?= e($m['url']) ?>" target="_blank" rel="noopener"><?= icon($m['icon']) ?><?= e($m['label']) ?></a>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
      </div>
      <?= view('partials/map', ['address' => $address, 'title' => 'Карта: станция техосмотра, ' . $address]) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--tight">
  <div class="container">
    <div class="promo" data-reveal>
      <div>
        <h2>Нужен автобус или микроавтобус с водителем?</h2>
        <p>Наше основное направление — пассажирские перевозки по Иркутску и области: мероприятия, экскурсии, трансферы, вахта.</p>
      </div>
      <a class="btn btn--primary btn--lg" href="/#fleet">Посмотреть транспорт<?= icon('arrow-right') ?></a>
    </div>
  </div>
</section>

<?= view('partials/inspection-dialog', ['categories' => $categories]) ?>
