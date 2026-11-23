<?php
/**
 * Калькулятор стоимости. Показывается, только если хотя бы у одной машины указаны цены.
 *
 * @var array $calcVehicles
 */

$first = $calcVehicles[0];
$modes = calc_modes($first);
$mode = $modes[0] ?? 'hour';
$hours = max(4, (int) ($first['minHours'] ?? 1));
$km = 50;
$estimate = calc_estimate($first, $mode, $mode === 'km' ? $km : $hours);
$disclaimer = trim((string) setting('calculator.disclaimer', ''));
$modeLabels = ['hour' => 'Почасово', 'km' => 'По километрам', 'transfer' => 'Трансфер'];
?>
<section class="section" id="calculator" aria-labelledby="calc-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Цены</p>
      <h2 class="section-title" id="calc-title">Сколько стоит поездка</h2>
      <p class="section-lead">Ориентировочная стоимость за пару секунд. Точную цену назовёт диспетчер.</p>
    </div>

    <form class="calc" id="calc" novalidate data-reveal aria-label="Калькулятор стоимости">
      <div class="calc__form">
        <div class="field">
          <label class="field__label" for="calc-vehicle">Транспорт</label>
          <select class="select" id="calc-vehicle" name="vehicle">
<?php foreach ($calcVehicles as $v): ?>
            <option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option>
<?php endforeach; ?>
          </select>
        </div>

        <fieldset class="field calc__modes">
          <legend class="field__label">Как считать</legend>
          <div class="segmented">
<?php foreach ($modeLabels as $value => $label): ?>
            <label class="segmented__option">
              <input type="radio" name="mode" value="<?= e($value) ?>"<?= $value === $mode ? ' checked' : '' ?><?= in_array($value, $modes, true) ? '' : ' disabled' ?>>
              <span><?= e($label) ?></span>
            </label>
<?php endforeach; ?>
          </div>
        </fieldset>

        <div class="field" data-calc-show="hour"<?= $mode === 'hour' ? '' : ' hidden' ?>>
          <label class="field__label" for="calc-hours">Сколько часов</label>
          <div class="stepper" data-stepper>
            <button class="stepper__btn" type="button" data-step="-1" aria-label="Меньше часов"><?= icon('minus') ?></button>
            <input class="stepper__input" type="number" id="calc-hours" name="hours" inputmode="numeric" min="1" max="72" step="1" value="<?= $hours ?>">
            <button class="stepper__btn" type="button" data-step="1" aria-label="Больше часов"><?= icon('plus') ?></button>
          </div>
          <p class="field__hint" data-calc-min-hours><?= (int) ($first['minHours'] ?? 1) > 1 ? 'Минимальный заказ — ' . (int) $first['minHours'] . ' ' . plural_ru((int) $first['minHours'], 'час', 'часа', 'часов') . '.' : '' ?></p>
        </div>

        <div class="field" data-calc-show="km"<?= $mode === 'km' ? '' : ' hidden' ?>>
          <label class="field__label" for="calc-km">Расстояние, км</label>
          <input class="input" type="number" id="calc-km" name="km" inputmode="numeric" min="1" max="5000" step="1" value="<?= $km ?>">
          <p class="field__hint">Учитывайте дорогу туда и обратно, если машина нужна в обе стороны.</p>
        </div>

        <p class="field__hint calc__transfer-hint" data-calc-show="transfer"<?= $mode === 'transfer' ? '' : ' hidden' ?>>Фиксированная цена поездки по городу или в аэропорт, на вокзал.</p>
      </div>

      <div class="calc__result">
        <p class="calc__label">Ориентировочно</p>
        <p class="calc__total" aria-live="polite" data-calc-total>от <?= e(money($estimate['total'])) ?></p>
        <ul class="calc__breakdown" data-calc-breakdown>
<?php foreach ($estimate['lines'] as $line): ?>
          <li><span><?= e($line[0]) ?></span><span><?= e($line[1]) ?></span></li>
<?php endforeach; ?>
        </ul>
        <a class="btn btn--primary btn--lg btn--block" href="#contacts" data-open-request data-calc-order>Заказать по этому расчёту</a>
<?php if ($disclaimer !== ''): ?>
        <p class="calc__note"><?= icon('info') ?><span><?= e($disclaimer) ?></span></p>
<?php endif; ?>
      </div>
    </form>
  </div>
</section>
