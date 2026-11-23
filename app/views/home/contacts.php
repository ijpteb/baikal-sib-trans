<?php
/**
 * Контакты: телефоны, мессенджеры, почта, адреса, часы работы и карта.
 */

$phones = company_phones();
$messengers = messengers_enabled();
$email = trim((string) setting('contacts.email', ''));
$office = trim((string) setting('contacts.office_address', ''));
$inspection = trim((string) setting('contacts.inspection_address', ''));
$hours = dispatcher_hours();
$officeHours = trim((string) setting('contacts.office_hours', ''));
$mapAddress = $office !== '' ? $office : $inspection;
?>
<section class="section" id="contacts" aria-labelledby="contacts-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Контакты</p>
      <h2 class="section-title" id="contacts-title">Закажите транспорт</h2>
      <p class="section-lead">Позвоните или напишите — диспетчер подберёт машину и назовёт стоимость.</p>
    </div>

    <div class="contacts">
      <div class="contacts__info">
        <div class="card contact-card" data-reveal>
<?php foreach ($phones as $i => $p): ?>
          <div class="contact-line">
            <span class="contact-line__icon"><?= icon($i === 0 ? 'phone-call' : 'phone') ?></span>
            <span class="contact-line__body">
              <span class="contact-line__label"><?= e($p['label'] !== '' ? $p['label'] : ($i === 0 ? 'Диспетчер' : 'Офис')) ?></span>
              <a class="<?= e(classes('contact-line__value', $i === 0 ? 'contact-line__value--big' : null)) ?>" href="<?= e($p['href']) ?>"><?= e($p['number']) ?></a>
            </span>
          </div>
<?php endforeach; ?>
<?php if ($hours !== '' || $officeHours !== ''): ?>
          <div class="contact-line">
            <span class="contact-line__icon"><?= icon('clock') ?></span>
            <span class="contact-line__body">
              <span class="contact-line__label">Часы работы</span>
<?php if ($hours !== ''): ?>
              <span class="contact-line__value"><?= e($hours === 'Круглосуточно' ? 'Диспетчер — круглосуточно' : 'Диспетчер: ' . $hours) ?></span>
<?php endif; ?>
<?php if ($officeHours !== ''): ?>
              <span class="contact-line__value contact-line__value--normal">Офис: <?= e($officeHours) ?></span>
<?php endif; ?>
            </span>
          </div>
<?php endif; ?>
          <div class="contact-card__actions">
            <a class="btn btn--primary btn--lg btn--block" href="#contacts" data-open-request>Оставить заявку</a>
<?php if ($messengers): ?>
            <div class="messenger-btns">
<?php foreach ($messengers as $m): ?>
              <a class="btn btn--outline" href="<?= e($m['url']) ?>" target="_blank" rel="noopener"><?= icon($m['icon']) ?><?= e($m['label']) ?></a>
<?php endforeach; ?>
            </div>
<?php endif; ?>
          </div>
        </div>

        <div class="card contact-card" data-reveal>
<?php if ($email !== ''): ?>
          <div class="contact-line">
            <span class="contact-line__icon"><?= icon('mail') ?></span>
            <span class="contact-line__body">
              <span class="contact-line__label">Электронная почта</span>
              <a class="contact-line__value" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            </span>
          </div>
<?php endif; ?>
<?php if ($office !== ''): ?>
          <div class="contact-line">
            <span class="contact-line__icon"><?= icon('building') ?></span>
            <span class="contact-line__body">
              <span class="contact-line__label">Офис</span>
              <span class="contact-line__value"><?= e($office) ?></span>
            </span>
          </div>
<?php endif; ?>
<?php if ($inspection !== ''): ?>
          <div class="contact-line">
            <span class="contact-line__icon"><?= icon('clipboard-check') ?></span>
            <span class="contact-line__body">
              <span class="contact-line__label">Станция техосмотра</span>
              <span class="contact-line__value"><?= e($inspection) ?></span>
              <a class="link-arrow contact-line__link" href="/tehosmotr">Цены и запись<?= icon('arrow-right') ?></a>
            </span>
          </div>
<?php endif; ?>
        </div>
      </div>

<?php if ($mapAddress !== ''): ?>
      <?= view('partials/map', ['address' => $mapAddress, 'title' => 'Карта: ' . $mapAddress]) ?>
<?php endif; ?>
    </div>
  </div>
</section>
