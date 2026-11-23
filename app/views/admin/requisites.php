<?php
/**
 * Реквизиты компании.
 *
 * @var array $values
 * @var array $errors
 */

$field = static function (string $key, string $label, array $o = []) use ($values, $errors): string {
    return af_input($key, $label, $values[$key] ?? '', $o + ['error' => $errors[$key] ?? '', 'optional' => true]);
};
?>
<?= admin_head('Реквизиты', 'Заполненные реквизиты показываются внизу сайта и в политике конфиденциальности. Пустые поля на сайте не выводятся.') ?>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>

<form class="card card--pad" method="post" action="/admin/requisites" novalidate data-dirty-form>
  <?= csrf_field() ?>
  <div class="form-grid">
    <?= $field('full_name', 'Полное наименование', ['max' => 200, 'wide' => true, 'placeholder' => 'Общество с ограниченной ответственностью «Байкал Сиб-Транс»']) ?>
    <?= $field('inn', 'ИНН', ['inputmode' => 'numeric', 'max' => 12]) ?>
    <?= $field('kpp', 'КПП', ['inputmode' => 'numeric', 'max' => 9]) ?>
    <?= $field('ogrn', 'ОГРН', ['inputmode' => 'numeric', 'max' => 15]) ?>
    <?= $field('bik', 'БИК банка', ['inputmode' => 'numeric', 'max' => 9]) ?>
    <?= $field('legal_address', 'Юридический адрес', ['max' => 250, 'wide' => true]) ?>
    <?= $field('bank', 'Банк', ['max' => 200, 'wide' => true]) ?>
    <?= $field('account', 'Расчётный счёт', ['inputmode' => 'numeric', 'max' => 24]) ?>
    <?= $field('corr_account', 'Корреспондентский счёт', ['inputmode' => 'numeric', 'max' => 24]) ?>
  </div>
  <?= admin_savebar('Сохранить реквизиты') ?>
</form>
