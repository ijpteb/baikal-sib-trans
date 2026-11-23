<?php
/**
 * Страница ошибки (404 и другие) в стиле сайта.
 *
 * @var int $status
 */

$is404 = $status === 404;
$phone = primary_phone();
?>
<section class="section error-page">
  <div class="container">
    <span class="error-page__code" aria-hidden="true"><?= (int) $status ?></span>
    <h1 class="error-page__title"><?= $is404 ? 'Такой страницы нет' : 'Что-то пошло не так' ?></h1>
    <p class="error-page__text">
<?php if ($is404): ?>
      Возможно, ссылка устарела или в адресе опечатка. Начните с главной страницы — там всё о заказе транспорта.
<?php else: ?>
      Попробуйте обновить страницу через минуту. Если нужно срочно заказать транспорт — позвоните нам.
<?php endif; ?>
    </p>
    <div class="error-page__actions">
      <a class="btn btn--primary btn--lg" href="/"><?= icon('home') ?>На главную</a>
<?php if ($phone !== null): ?>
      <a class="btn btn--outline btn--lg" href="<?= e($phone['href']) ?>"><?= icon('phone') ?><?= e($phone['number']) ?></a>
<?php endif; ?>
    </div>
  </div>
</section>
