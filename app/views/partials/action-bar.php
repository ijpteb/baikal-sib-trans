<?php
/**
 * Нижняя панель на телефоне: «Позвонить» и «Написать».
 * На странице техосмотра вторая кнопка открывает запись на техосмотр.
 *
 * @var string $page
 */

$phone = primary_phone();
$single = $phone === null ? ' action-bar__single' : '';
?>
<div class="action-bar" id="action-bar">
<?php if ($phone !== null): ?>
  <a class="btn btn--secondary" href="<?= e($phone['href']) ?>"><?= icon('phone') ?>Позвонить</a>
<?php endif; ?>
<?php if ($page === 'tehosmotr'): ?>
  <a class="btn btn--primary<?= $single ?>" href="#booking" data-open-inspection><?= icon('calendar') ?>Записаться</a>
<?php else: ?>
  <a class="btn btn--primary<?= $single ?>" href="<?= e($page === 'home' ? '#contacts' : '/#contacts') ?>" data-open-request><?= icon('chat') ?>Написать</a>
<?php endif; ?>
</div>
