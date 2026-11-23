<?php
/**
 * Первый экран: заголовок, преимущества одной строкой и быстрая форма заявки.
 */

$photo = setting('hero.photo', []);
$hasPhoto = is_array($photo) && !empty($photo['file']);
$points = hero_points();
$phone = primary_phone();
$hours = dispatcher_hours();
$today = date('Y-m-d');
?>
<section class="<?= e(classes('hero', $hasPhoto ? 'hero--photo' : null)) ?>" id="top" aria-labelledby="hero-title">
  <div class="hero__bg">
<?php if ($hasPhoto): ?>
    <?= image_tag($photo, '100vw', [
        'class' => 'hero__photo',
        'alt' => (string) setting('hero.photo_alt', ''),
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'decoding' => null,
        'src' => image_url($photo, 1600),
    ]) ?>
<?php else: ?>
    <?= inline_svg_file(APP_DIR . '/views/partials/hero-art.svg') ?>
<?php endif; ?>
  </div>

  <div class="container hero__inner">
    <div class="hero__content">
      <h1 class="hero__title" id="hero-title"><?= e((string) setting('hero.title')) ?></h1>
<?php if (trim((string) setting('hero.subtitle', '')) !== ''): ?>
      <p class="hero__subtitle"><?= e((string) setting('hero.subtitle')) ?></p>
<?php endif; ?>
<?php if ($points): ?>
      <ul class="hero__points">
<?php foreach ($points as $point): ?>
        <li><?= icon('check') ?><?= e($point) ?></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php if ($phone !== null): ?>
      <div class="hero__call">
        <a class="btn btn--glass btn--lg" href="<?= e($phone['href']) ?>"><?= icon('phone') ?><?= e($phone['number']) ?></a>
<?php if ($hours !== ''): ?>
        <span class="hero__phone-hint"><?= e($hours === 'Круглосуточно' ? 'Диспетчер на связи круглосуточно' : 'Диспетчер: ' . $hours) ?></span>
<?php endif; ?>
      </div>
<?php endif; ?>
    </div>

    <form class="quick-form" id="quick-form" action="#contacts" novalidate aria-labelledby="quick-form-title">
      <h2 class="quick-form__title" id="quick-form-title">Подберём транспорт под вашу поездку</h2>
      <p class="quick-form__lead">Четыре поля — и заявка почти готова.</p>
      <div class="quick-form__grid">
        <div class="field">
          <label class="field__label" for="qf-date">Дата</label>
          <input class="input" type="date" id="qf-date" name="date" min="<?= e($today) ?>">
        </div>
        <div class="field">
          <label class="field__label" for="qf-passengers">Пассажиров</label>
          <div class="stepper" data-stepper>
            <button class="stepper__btn" type="button" data-step="-1" aria-label="Меньше пассажиров"><?= icon('minus') ?></button>
            <input class="stepper__input" type="number" id="qf-passengers" name="passengers" inputmode="numeric" min="1" max="999" step="1" placeholder="—">
            <button class="stepper__btn" type="button" data-step="1" aria-label="Больше пассажиров"><?= icon('plus') ?></button>
          </div>
        </div>
        <div class="field field--wide">
          <label class="field__label" for="qf-from">Откуда</label>
          <input class="input" type="text" id="qf-from" name="from" maxlength="200" placeholder="Например: аэропорт Иркутска" autocomplete="off">
        </div>
        <div class="field field--wide">
          <label class="field__label" for="qf-to">Куда</label>
          <input class="input" type="text" id="qf-to" name="to" maxlength="200" placeholder="Например: Листвянка" autocomplete="off">
        </div>
      </div>
      <button class="btn btn--primary btn--lg btn--block quick-form__submit" type="submit">Подобрать транспорт<?= icon('arrow-right') ?></button>
      <p class="quick-form__note"><?= icon('lock') ?>Данные не сохраняются на сайте</p>
    </form>
  </div>
</section>
