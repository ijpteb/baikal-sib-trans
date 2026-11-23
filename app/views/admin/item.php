<?php
/**
 * Форма одной записи (тип поездки, преимущество, шаг, цифра, отзыв, компания, вопрос).
 *
 * @var string     $section
 * @var array      $config
 * @var array|null $row
 * @var array      $values
 * @var array      $errors
 * @var string     $title
 */

$renderField = static function (string $key, array $f) use ($values, $errors): string {
    $o = [
        'error' => $errors[$key] ?? '',
        'required' => !empty($f['required']),
        'optional' => !empty($f['optional']),
        'hint' => $f['hint'] ?? '',
        'counter' => !empty($f['counter']),
        'max' => $f['type'] === 'int' ? 9 : ($f['max'] ?? null),
    ];
    $value = $values[$key] ?? '';
    return match ($f['type']) {
        'textarea' => af_textarea($key, $f['label'], $value, $o + ['rows' => $f['rows'] ?? 4, 'wide' => true]),
        'int' => af_input($key, $f['label'], $value, $o + ['inputmode' => 'numeric']),
        'date' => af_date($key, $f['label'], $value, $o + ['picker_max' => !empty($f['past']) ? date('Y-m-d') : null]),
        'url' => af_input($key, $f['label'], $value, ['type' => 'url', 'inputmode' => 'url', 'placeholder' => 'https://', 'wide' => true] + $o),
        'icon' => af_icons($key, $f['label'], (string) $value),
        'image' => af_image($key, $f['label'], $value, (string) $f['purpose'], $o),
        default => af_input($key, $f['label'], $value, $o + ['wide' => !in_array($key, ['suffix'], true)]),
    };
};

$deleteButton = $row !== null
    ? '<button class="btn btn--ghost btn--danger savebar__delete" type="submit" name="action" value="delete" formnovalidate'
        . ' data-confirm="Удалить эту запись? Это нельзя отменить. Чтобы только спрятать её, снимите галочку «Показывать на сайте».">'
        . icon('trash') . '<span class="savebar__text">Удалить</span></button>'
    : '';
?>
<?= admin_head($title, '', '', ['href' => '/admin/' . $section, 'label' => $config['title']]) ?>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>
<?php if ($section === 'faq' && $row !== null && !empty($row['needs_review'])): ?>
<?= admin_alert('warning', 'Этот ответ составлен заранее. Прочитайте его, поправьте при необходимости и нажмите «Сохранить» — пометка «Не проверено» исчезнет.') ?>
<?php endif; ?>
<?php if ($section === 'reviews'): ?>
<?= admin_alert('info', 'Публикуйте только настоящие отзывы и только с согласия автора. Придуманные отзывы вводят клиентов в заблуждение и нарушают закон о рекламе.') ?>
<?php endif; ?>

<form class="card card--pad" method="post" action="/admin/<?= e($section) ?>/<?= $row !== null ? (int) $row['id'] : 'new' ?>" novalidate data-dirty-form>
  <?= csrf_field() ?>
  <div class="form-grid">
<?php foreach ($config['fields'] as $key => $field): ?>
    <?= $renderField($key, $field) ?>
<?php endforeach; ?>
    <?= af_check('is_visible', 'Показывать на сайте', (bool) $values['is_visible'], ['wide' => true, 'hint' => 'Снимите галочку, чтобы спрятать запись, не удаляя её.']) ?>
  </div>
  <?= admin_savebar('Сохранить', $deleteButton) ?>
</form>
