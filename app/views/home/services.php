<?php
/**
 * «Для каких поездок»: карточки открывают заявку с выбранным типом поездки.
 *
 * @var array $services
 */
?>
<section class="section" id="services" aria-labelledby="services-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Услуги</p>
      <h2 class="section-title" id="services-title">Для каких поездок</h2>
      <p class="section-lead">Выберите тип поездки — заявка откроется уже с нужными настройками.</p>
    </div>
    <ul class="services-grid" data-stagger>
<?php foreach ($services as $service): ?>
      <li data-reveal>
        <article class="card card--hover service-card">
          <span class="icon-tile"><?= icon((string) $service['icon']) ?: icon('bus') ?></span>
          <h3 class="service-card__title"><?= e($service['title']) ?></h3>
<?php if (trim((string) $service['text']) !== ''): ?>
          <p class="service-card__text"><?= e($service['text']) ?></p>
<?php endif; ?>
          <a class="service-card__action stretched" href="#contacts" data-open-request data-trip="<?= e($service['title']) ?>">Заказать<span class="visually-hidden">: <?= e($service['title']) ?></span><?= icon('arrow-right') ?></a>
        </article>
      </li>
<?php endforeach; ?>
    </ul>
  </div>
</section>
