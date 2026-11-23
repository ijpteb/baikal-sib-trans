<?php
/**
 * Вход в админку.
 *
 * @var array  $errors
 * @var string $username
 * @var int    $wait
 * @var string $notice
 */
?>
<div class="card card--pad auth__card">
  <h1 class="auth__title">Вход в админку</h1>
<?php if ($notice !== '' && !$errors): ?>
  <?= admin_alert('info', e($notice)) ?>
<?php endif; ?>
<?php if (!empty($errors['form'])): ?>
  <?= admin_alert('error', e($errors['form'])) ?>
<?php endif; ?>
  <form class="form-stack" method="post" action="/admin/login" novalidate>
    <?= csrf_field() ?>
    <?= af_input('username', 'Логин', $username, ['required' => true, 'autocomplete' => 'username', 'spellcheck' => 'false', 'attrs' => ['autocapitalize' => 'none', 'autofocus' => $username === '']]) ?>
    <?= af_input('password', 'Пароль', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'input_class' => 'input--password', 'attrs' => ['autofocus' => $username !== '', 'data-password' => 'true']]) ?>
    <button class="btn btn--secondary btn--lg btn--block" type="submit"<?= $wait > 0 ? ' disabled' : '' ?>><?= icon('lock') ?>Войти</button>
  </form>
  <details class="auth__help">
    <summary>Забыли пароль?</summary>
    <ol>
      <li>Откройте файловый менеджер в панели хостинга.</li>
      <li>В папке <b>data</b> создайте пустой файл с именем <b>RESET_PASSWORD</b> (без расширения).</li>
      <li>Откройте страницу <a href="/admin/setup">/admin/setup</a> — сайт предложит задать новый пароль.</li>
      <li>Код для подтверждения будет в файле <b>SETUP_CODE.txt</b> в той же папке data.</li>
    </ol>
  </details>
</div>
<p class="auth__back"><a href="/"><?= icon('arrow-left') ?>На сайт</a></p>
