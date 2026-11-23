<?php
/**
 * Смена логина и пароля.
 *
 * @var array $values
 * @var array $errors
 */
?>
<?= admin_head('Логин и пароль', 'Чтобы что-то изменить, введите текущий пароль. Новый пароль можно не указывать, если меняете только логин.') ?>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>

<form class="card card--pad form-narrow" method="post" action="/admin/password" novalidate>
  <?= csrf_field() ?>
  <div class="form-stack">
    <?= af_input('current_password', 'Текущий пароль', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'error' => $errors['current_password'] ?? '', 'attrs' => ['data-password' => 'true']]) ?>
    <?= af_input('username', 'Логин', $values['username'], ['required' => true, 'max' => 40, 'autocomplete' => 'username', 'spellcheck' => 'false', 'error' => $errors['username'] ?? '', 'hint' => 'Латинские буквы и цифры.', 'attrs' => ['autocapitalize' => 'none']]) ?>
    <?= af_input('new_password', 'Новый пароль', '', ['type' => 'password', 'autocomplete' => 'new-password', 'optional' => true, 'error' => $errors['new_password'] ?? '', 'hint' => 'Не меньше 10 символов. Удобно взять 3–4 случайных слова.', 'attrs' => ['data-password' => 'true']]) ?>
    <?= af_input('new_password2', 'Новый пароль ещё раз', '', ['type' => 'password', 'autocomplete' => 'new-password', 'optional' => true, 'error' => $errors['new_password2'] ?? '', 'attrs' => ['data-password' => 'true']]) ?>
  </div>
  <?= admin_savebar('Сохранить') ?>
</form>
