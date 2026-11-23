<?php
/**
 * Цены всех машин на одной странице и настройки калькулятора.
 *
 * @var array $settings
 * @var array $vehicles
 * @var array $errors
 */

$visibleNow = Content::calculatorVisible();
?>
<?= admin_head('Цены и калькулятор', 'Калькулятор на главной считает ориентировочную стоимость по этим ценам. Он появляется, когда хотя бы у одной показанной машины указана цена.') ?>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>
<?= admin_alert($visibleNow ? 'success' : 'info', $visibleNow
    ? 'Сейчас калькулятор показывается на сайте.'
    : 'Сейчас калькулятор скрыт' . (empty($settings['enabled']) ? ' (выключен ниже).' : ': ни у одной показанной машины нет цены.')) ?>

<form method="post" action="/admin/calculator" novalidate data-dirty-form>
  <?= csrf_field() ?>

  <section class="card card--pad form-section" aria-labelledby="calc-settings">
    <h2 class="form-section__title" id="calc-settings">Калькулятор</h2>
    <div class="form-grid">
      <?= af_check('enabled', 'Показывать калькулятор на сайте', !empty($settings['enabled']), ['wide' => true]) ?>
      <?= af_textarea('disclaimer', 'Пояснение под расчётом', $settings['disclaimer'] ?? '', ['max' => 300, 'rows' => 2, 'wide' => true, 'counter' => true]) ?>
    </div>
  </section>

<?php if (!$vehicles): ?>
  <div class="card card--pad empty-state">
    <?= icon('bus') ?>
    <p>Сначала добавьте машины в разделе «Автопарк».</p>
    <a class="btn btn--secondary" href="/admin/vehicles/new"><?= icon('plus') ?>Добавить машину</a>
  </div>
<?php else: ?>
  <h2 class="admin-section__title">Цены машин, ₽</h2>
  <p class="list-hint"><?= icon('info') ?>Только цифры. Пустое поле — цены нет. Эти же цены можно менять в карточке каждой машины.</p>
<?php foreach ($vehicles as $v): ?>
<?php $id = (int) $v['id']; $err = $errors['prices.' . $id] ?? ''; ?>
  <section class="card card--pad form-section price-card<?= $v['is_visible'] ? '' : ' price-card--hidden' ?>" aria-labelledby="price-<?= $id ?>">
    <div class="price-card__head">
      <h3 class="price-card__title" id="price-<?= $id ?>"><?= e($v['name']) ?></h3>
      <p class="price-card__meta"><?= e($v['type_label']) ?><?= $v['seats'] > 0 ? ' · ' . (int) $v['seats'] . ' ' . plural_ru($v['seats'], 'место', 'места', 'мест') : '' ?><?= $v['is_visible'] ? '' : ' · скрыта с сайта' ?></p>
    </div>
<?php
    $money = static fn (string $field, string $label): string => af_input('prices[' . $id . '][' . $field . ']', $label, admin_price_value($v[$field]), [
        'inputmode' => 'numeric', 'max' => 12, 'placeholder' => '—', 'input_class' => 'input--money',
        'error' => $errors['prices.' . $id . '.' . $field] ?? '',
    ]);
?>
    <div class="form-grid form-grid--5">
      <?= $money('price_hour', 'За час') ?>
      <?= af_input('prices[' . $id . '][min_hours]', 'Мин. часов', admin_price_value($v['min_hours']), ['inputmode' => 'numeric', 'max' => 2, 'placeholder' => '—', 'error' => $err]) ?>
      <?= $money('price_km', 'За км') ?>
      <?= $money('price_transfer', 'Трансфер') ?>
      <?= $money('delivery_fee', 'Подача') ?>
    </div>
    <?= af_check('prices[' . $id . '][price_on_request]', 'Писать «Цена по запросу» вместо цен', (bool) $v['price_on_request']) ?>
  </section>
<?php endforeach; ?>
<?php endif; ?>

  <?= admin_savebar('Сохранить цены') ?>
</form>
