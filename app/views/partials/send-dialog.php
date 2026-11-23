<?php
/**
 * Окно «Как отправить заявку?»: мессенджеры, звонок, почта, копирование текста.
 */

$messengers = messengers_enabled();
$phones = company_phones();
$email = trim((string) setting('contacts.email', ''));
$emailValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
?>
<dialog class="sheet" id="send-dialog" aria-labelledby="send-title" aria-describedby="send-subtitle">
  <div class="sheet__panel">
    <div class="sheet__grabber" aria-hidden="true"></div>
    <div class="sheet__header">
      <div>
        <h2 class="sheet__title" id="send-title">Как отправить заявку?</h2>
        <p class="sheet__subtitle" id="send-subtitle">Текст заявки готов. Выберите удобный способ связи.</p>
      </div>
      <button class="sheet__close" type="button" aria-label="Закрыть" data-dialog-close><?= icon('close') ?></button>
    </div>

    <div class="sheet__body">
      <div class="send-options">
<?php foreach ($messengers as $m): ?>
        <a class="send-option" href="<?= e($m['url']) ?>" target="_blank" rel="noopener" data-send="<?= e($m['key']) ?>">
          <span class="send-option__icon"><?= icon($m['icon']) ?></span>
          <span class="send-option__text">
            <span>Написать в <?= e($m['label']) ?></span>
            <span class="send-option__hint"><?= $m['prefill'] ? 'Текст заявки подставится в сообщение' : 'Текст скопируется — вставьте его в чат' ?></span>
          </span>
          <?= icon('external-link') ?>
        </a>
<?php endforeach; ?>
<?php foreach ($phones as $i => $p): ?>
        <a class="<?= e(classes('send-option', $i === 0 ? 'send-option--call' : null)) ?>" href="<?= e($p['href']) ?>" data-send="call">
          <span class="send-option__icon"><?= icon($i === 0 ? 'phone-call' : 'phone') ?></span>
          <span class="send-option__text">
            <span><?= $i === 0 ? 'Позвонить диспетчеру' : 'Позвонить в офис' ?></span>
            <span class="send-option__hint"><?= e($p['number']) ?><?= $p['label'] !== '' ? ' — ' . e($p['label']) : '' ?></span>
          </span>
        </a>
<?php endforeach; ?>
<?php if ($emailValid): ?>
        <a class="send-option" href="mailto:<?= e($email) ?>" data-send="email">
          <span class="send-option__icon"><?= icon('mail') ?></span>
          <span class="send-option__text">
            <span>Отправить на почту</span>
            <span class="send-option__hint"><?= e($email) ?></span>
          </span>
        </a>
<?php endif; ?>
      </div>

      <details class="message-details">
        <summary>Показать текст заявки</summary>
        <pre class="message-preview" id="send-preview"></pre>
      </details>
    </div>

    <div class="sheet__footer sheet__footer--split">
      <button class="btn btn--ghost" type="button" data-send-back><?= icon('arrow-left') ?>Изменить</button>
      <button class="btn btn--outline" type="button" data-copy-message><?= icon('copy') ?>Скопировать текст</button>
    </div>
  </div>
</dialog>
