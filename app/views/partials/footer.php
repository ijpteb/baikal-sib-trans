<?php
/**
 * Подвал: логотип, описание, разделы, контакты, реквизиты.
 *
 * @var string $page
 */

$nav = site_nav($page);
$phones = company_phones();
$email = trim((string) setting('contacts.email', ''));
$office = trim((string) setting('contacts.office_address', ''));
$hours = dispatcher_hours();
$messengers = messengers_enabled();
$requisites = company_requisites();
$company = trim((string) setting('requisites.full_name', '')) ?: (string) setting('site.company');
?>
<footer class="site-footer">
  <div class="container">
    <div class="<?= e(classes('footer__grid', $requisites ? 'footer__grid--4' : 'footer__grid--3')) ?>">
      <div class="footer__brand">
        <?= view('partials/logo', ['class' => 'logo--light']) ?>
        <p class="footer__text"><?= e((string) setting('site.footer_text', '')) ?></p>
      </div>

      <nav aria-label="Разделы сайта">
        <h2 class="footer__title">Разделы</h2>
        <ul class="footer__list">
<?php foreach ($nav as $item): ?>
          <li><a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a></li>
<?php endforeach; ?>
          <li><a href="/tehosmotr">Техосмотр</a></li>
        </ul>
      </nav>

      <div>
        <h2 class="footer__title">Контакты</h2>
        <ul class="footer__list">
<?php foreach ($phones as $p): ?>
          <li><a href="<?= e($p['href']) ?>"><?= e($p['number']) ?></a><?= $p['label'] !== '' ? '&nbsp;— ' . e($p['label']) : '' ?></li>
<?php endforeach; ?>
<?php if ($email !== ''): ?>
          <li><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
<?php endif; ?>
<?php if ($office !== ''): ?>
          <li class="footer__address"><?= e($office) ?></li>
<?php endif; ?>
<?php if ($hours !== ''): ?>
          <li class="footer__address"><?= e($hours === 'Круглосуточно' ? 'Диспетчер — круглосуточно' : $hours) ?></li>
<?php endif; ?>
<?php foreach ($messengers as $m): ?>
          <li><a href="<?= e($m['url']) ?>" target="_blank" rel="noopener"><?= e($m['label']) ?></a></li>
<?php endforeach; ?>
        </ul>
      </div>

<?php if ($requisites): ?>
      <div>
        <h2 class="footer__title">Реквизиты</h2>
        <dl class="footer__requisites">
<?php foreach ($requisites as $r): ?>
          <div><dt><?= e($r['label']) ?>:</dt> <dd><?= e($r['value']) ?></dd></div>
<?php endforeach; ?>
        </dl>
      </div>
<?php endif; ?>
    </div>

    <div class="footer__bottom">
      <span>© <?= e(current_year()) ?> <?= e($company) ?></span>
      <a href="/privacy">Политика конфиденциальности</a>
    </div>
  </div>
</footer>
