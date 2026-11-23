<?php
/**
 * Карточка машины: основное, фото, удобства, цены.
 *
 * @var array|null $vehicle
 * @var array      $values
 * @var array      $errors
 * @var string     $title
 */

$purpose = image_purposes()['vehicle'];
$photoItem = static function (?array $photo): string {
    $token = $photo !== null ? image_token($photo) : '';
    $preview = $photo !== null ? image_url($photo, 480) : '';
    $alt = (string) ($photo['alt'] ?? '');
    return '<li class="gallery__item" data-gallery-item>'
        . '<div class="gallery__media"><img src="' . e($preview) . '" alt="" loading="lazy" data-gallery-img>'
        . '<span class="gallery__cover">' . icon('star') . 'Главное фото</span></div>'
        . '<input type="hidden" name="photo_token[]" value="' . e($token) . '" data-gallery-token>'
        . '<label class="gallery__alt"><span class="visually-hidden">Что на фото (необязательно)</span>'
        . '<input class="input input--sm" type="text" name="photo_alt[]" value="' . e($alt) . '" maxlength="150" placeholder="Что на фото?"></label>'
        . '<div class="gallery__tools">'
        . '<button class="btn btn--ghost btn--sm btn--icon" type="button" data-gallery-move="-1" title="Левее">' . icon('arrow-left', '', 'Переместить левее') . '</button>'
        . '<button class="btn btn--ghost btn--sm btn--icon" type="button" data-gallery-move="1" title="Правее">' . icon('arrow-right', '', 'Переместить правее') . '</button>'
        . '<button class="btn btn--ghost btn--sm btn--icon" type="button" data-gallery-cover title="Сделать главным">' . icon('star', '', 'Сделать главным фото') . '</button>'
        . '<button class="btn btn--ghost btn--sm btn--icon btn--danger" type="button" data-gallery-remove title="Убрать">' . icon('trash', '', 'Убрать фото') . '</button>'
        . '</div></li>';
};

$features = vehicle_features();
$deleteButton = $vehicle !== null
    ? '<button class="btn btn--ghost btn--danger savebar__delete" type="submit" name="action" value="delete" formnovalidate'
        . ' data-confirm="Удалить машину «' . e($vehicle['name']) . '» вместе с фото? Это нельзя отменить. Чтобы только спрятать её, снимите галочку «Показывать на сайте».">'
        . icon('trash') . '<span class="savebar__text">Удалить машину</span></button>'
    : '';
$priceField = static function (string $key, string $label, string $hint) use ($values, $errors): string {
    return af_input($key, $label, admin_price_value($values[$key] ?? null), [
        'inputmode' => 'numeric',
        'max' => 12,
        'placeholder' => '—',
        'hint' => $hint,
        'error' => $errors[$key] ?? '',
        'input_class' => 'input--money',
    ]);
};
?>
<?= admin_head($title, '', $vehicle !== null ? '<a class="btn btn--outline btn--sm" href="/#fleet" target="_blank" rel="noopener">' . icon('external-link') . 'Автопарк на сайте</a>' : '', ['href' => '/admin/vehicles', 'label' => 'Автопарк']) ?>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>
<?php if (!empty($values['is_demo'])): ?>
<?= admin_alert('warning', '<b>Это пример из стартового наполнения.</b> Впишите настоящую машину: модель, число мест, удобства — и добавьте фото. После сохранения пометка «пример» исчезнет.') ?>
<?php endif; ?>

<form method="post" action="/admin/vehicles/<?= $vehicle !== null ? (int) $vehicle['id'] : 'new' ?>" novalidate data-dirty-form>
  <?= csrf_field() ?>

  <section class="card card--pad form-section" aria-labelledby="v-main">
    <h2 class="form-section__title" id="v-main">Основное</h2>
    <div class="form-grid">
      <?= af_input('name', 'Название', $values['name'], [
          'required' => true, 'max' => 120, 'wide' => true,
          'placeholder' => 'Например: Mercedes-Benz Sprinter',
          'hint' => 'Марка и модель или понятное описание. Так машина называется на сайте.',
          'error' => $errors['name'] ?? '',
      ]) ?>
      <?= af_select('type', 'Тип транспорта', $values['type'], vehicle_types()) ?>
      <?= af_input('seats', 'Пассажирских мест', $values['seats'], [
          'required' => true, 'inputmode' => 'numeric', 'max' => 3,
          'hint' => 'Без места водителя. По этому числу работает фильтр на сайте.',
          'error' => $errors['seats'] ?? '',
      ]) ?>
      <?= af_textarea('description', 'Описание', $values['description'], [
          'max' => 600, 'rows' => 3, 'counter' => true, 'wide' => true, 'optional' => true,
          'hint' => 'Коротко: для каких поездок подходит, чем удобна.',
      ]) ?>
    </div>
  </section>

  <section class="card card--pad form-section" aria-labelledby="v-photos">
    <h2 class="form-section__title" id="v-photos">Фотографии</h2>
    <p class="form-section__lead">Первое фото — главное: его видно в карточке. Порядок меняется стрелками. До 12 фото.</p>
    <div class="gallery" data-gallery data-purpose="vehicle" data-max="12">
      <ul class="gallery__list" data-gallery-list>
<?php foreach ($values['photos'] as $photo): ?>
        <?= $photoItem($photo) ?>
<?php endforeach; ?>
      </ul>
      <div class="gallery__add">
        <label class="btn btn--outline gallery__pick"><?= icon('upload') ?><span>Добавить фото</span><input class="visually-hidden" type="file" multiple accept="<?= e(image_accept('vehicle')) ?>" aria-describedby="gallery-hint" data-gallery-input></label>
        <p class="field__hint" id="gallery-hint"><?= e($purpose['hint']) ?> Можно выбрать несколько сразу.</p>
        <p class="field__error" data-gallery-error<?= empty($errors['photos']) ? ' hidden' : '' ?>><?= icon('alert-circle') ?><span><?= e($errors['photos'] ?? '') ?></span></p>
        <p class="gallery__progress" data-gallery-progress hidden><span class="spinner" aria-hidden="true"></span><span data-gallery-progress-text>Загружаем…</span></p>
        <noscript><p class="field__hint">Чтобы загружать фото, включите JavaScript в браузере.</p></noscript>
      </div>
      <template data-gallery-template><?= $photoItem(null) ?></template>
    </div>
  </section>

  <section class="card card--pad form-section" aria-labelledby="v-features">
    <h2 class="form-section__title" id="v-features">Удобства в салоне</h2>
    <div class="feature-grid" role="group" aria-labelledby="v-features">
<?php foreach ($features as $key => $feature): ?>
      <label class="feature-option">
        <input type="checkbox" name="features[]" value="<?= e($key) ?>"<?= in_array($key, $values['features'], true) ? ' checked' : '' ?>>
        <span class="feature-option__box"><?= icon($feature['icon']) ?><?= e($feature['label']) ?></span>
      </label>
<?php endforeach; ?>
    </div>
    <div class="form-grid form-grid--gap-top">
      <?= af_textarea('custom_features', 'Другие удобства', implode("\n", $values['custom_features']), [
          'rows' => 3, 'max' => 800, 'wide' => true, 'optional' => true,
          'hint' => 'По одному на строку, например: «Холодильник» или «Тонированные стёкла». До 8 строк.',
      ]) ?>
    </div>
  </section>

  <section class="card card--pad form-section" id="prices" aria-labelledby="v-prices">
    <h2 class="form-section__title" id="v-prices">Цены</h2>
    <p class="form-section__lead">Можно не заполнять. Если указать хотя бы одну цену, она появится в карточке, а на сайте — калькулятор стоимости. Суммы — в рублях, только цифры.</p>
    <div class="form-grid form-grid--3">
      <?= $priceField('price_hour', 'За час, ₽', 'Почасовая аренда.') ?>
      <?= af_input('min_hours', 'Минимум часов', admin_price_value($values['min_hours'] ?? null), [
          'inputmode' => 'numeric', 'max' => 2, 'placeholder' => '—',
          'hint' => 'Минимальный заказ при почасовой оплате.',
          'error' => $errors['min_hours'] ?? '',
      ]) ?>
      <?= $priceField('price_km', 'За километр, ₽', 'Для поездок по области.') ?>
      <?= $priceField('price_transfer', 'Трансфер, ₽', 'Фиксированная цена, например в аэропорт.') ?>
      <?= $priceField('delivery_fee', 'Подача, ₽', 'Добавляется к любой поездке.') ?>
    </div>
    <?= af_check('price_on_request', 'Не показывать цены — писать «Цена по запросу»', (bool) $values['price_on_request'], ['hint' => 'Цены останутся сохранёнными, но не появятся ни в карточке, ни в калькуляторе.']) ?>
  </section>

  <section class="card card--pad form-section">
    <?= af_check('is_visible', 'Показывать на сайте', (bool) $values['is_visible'], ['hint' => 'Снимите галочку, чтобы временно спрятать машину (например, на ремонте).']) ?>
  </section>

  <?= admin_savebar('Сохранить машину', $deleteButton) ?>
</form>
