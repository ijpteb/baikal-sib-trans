<?php
/**
 * Первая настройка (создание администратора) и сброс пароля.
 *
 * @var bool  $fatal
 * @var bool  $reset
 * @var array $errors
 * @var array $values
 * @var int   $wait
 */
?>
<div class="card card--pad auth__card auth__card--wide">
<?php if ($fatal): ?>
  <h1 class="auth__title">Нужна настройка хостинга</h1>
  <?= admin_alert('error', 'Сайт не может записывать файлы в папку <b>data</b>. В файловом менеджере хостинга поставьте для папки data права <b>755</b> (или 775) и обновите эту страницу.') ?>
<?php else: ?>
  <h1 class="auth__title"><?= $reset ? 'Новый пароль' : 'Добро пожаловать!' ?></h1>
  <p class="auth__lead"><?= $reset
      ? 'Задайте новый логин и пароль для входа в админку.'
      : 'Это первый запуск. Придумайте логин и пароль — с ними вы будете входить в админку и менять содержимое сайта.' ?></p>
<?php if (!empty($errors['form'])): ?>
  <?= admin_alert('error', e($errors['form'])) ?>
<?php endif; ?>
  <form class="form-stack" method="post" action="/admin/setup" novalidate>
    <?= csrf_field() ?>
    <div class="setup-code">
      <p class="setup-code__title"><?= icon('shield-check') ?>Сначала — защитный код</p>
      <p>Код лежит в файле <b>SETUP_CODE.txt</b> в папке <b>data</b> сайта. Откройте его:</p>
      <ul>
        <li>на хостинге — в файловом менеджере панели управления;</li>
        <li>на своём компьютере — в папке проекта → <b>data</b>.</li>
      </ul>
      <p class="setup-code__why">Так никто посторонний не сможет занять админку раньше вас.</p>
    </div>
    <?= af_input('code', 'Код из файла SETUP_CODE.txt', '', [
        'required' => true,
        'inputmode' => 'numeric',
        'autocomplete' => 'one-time-code',
        'placeholder' => '1234-5678',
        'max' => 12,
        'error' => $errors['code'] ?? '',
        'input_class' => 'input--code',
    ]) ?>
    <?= af_input('username', 'Логин', $values['username'] ?? '', [
        'required' => true,
        'max' => 40,
        'autocomplete' => 'username',
        'spellcheck' => 'false',
        'hint' => 'Латинские буквы и цифры, например: admin или dispatcher.',
        'error' => $errors['username'] ?? '',
        'attrs' => ['autocapitalize' => 'none'],
    ]) ?>
    <?= af_input('password', 'Пароль', '', [
        'type' => 'password',
        'required' => true,
        'autocomplete' => 'new-password',
        'hint' => 'Не меньше 10 символов. Удобно взять 3–4 случайных слова: «зелёный трамвай байкал 7».',
        'error' => $errors['password'] ?? '',
        'attrs' => ['data-password' => 'true', 'minlength' => '10'],
    ]) ?>
    <?= af_input('password2', 'Пароль ещё раз', '', [
        'type' => 'password',
        'required' => true,
        'autocomplete' => 'new-password',
        'error' => $errors['password2'] ?? '',
        'attrs' => ['data-password' => 'true'],
    ]) ?>
    <button class="btn btn--secondary btn--lg btn--block" type="submit"<?= $wait > 0 ? ' disabled' : '' ?>><?= icon('check') ?><?= $reset ? 'Сохранить пароль' : 'Создать и войти' ?></button>
    <p class="auth__note"><?= icon('info') ?>Запишите логин и пароль в надёжном месте. Пароль хранится в зашифрованном виде — восстановить его нельзя, только задать новый.</p>
  </form>
<?php endif; ?>
</div>
