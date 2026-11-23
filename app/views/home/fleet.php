<?php
/**
 * Автопарк: фильтр по количеству мест и карточки машин.
 *
 * @var array $vehicles
 */

$counts = array_fill_keys(array_keys(seat_ranges()), 0);
foreach ($vehicles as $v) {
    $counts[$v['range']] = ($counts[$v['range']] ?? 0) + 1;
}
// Кнопки только для групп, где есть машины; сам фильтр — если групп хотя бы две.
$ranges = array_filter(seat_ranges(), static fn (string $key): bool => $counts[$key] > 0, ARRAY_FILTER_USE_KEY);
$showFilter = count($ranges) > 1;
?>
<section class="section section--soft" id="fleet" aria-labelledby="fleet-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Автопарк</p>
      <h2 class="section-title" id="fleet-title">Транспорт на любую группу</h2>
      <p class="section-lead">Легковые автомобили, микроавтобусы и автобусы — всегда с водителем. Нажмите «Выбрать», и машина подставится в заявку.</p>
    </div>

<?php if (!$vehicles): ?>
    <p class="fleet-empty card card--pad">Скоро здесь появятся фотографии и описание автопарка. А пока <a href="#contacts" data-open-request>оставьте заявку</a> — диспетчер подберёт транспорт под вашу поездку.</p>
<?php else: ?>
<?php if ($showFilter): ?>
    <div class="fleet-filter" role="group" aria-label="Фильтр по количеству мест" data-fleet-filter>
      <button class="chip" type="button" aria-pressed="true" data-filter="all">Все<span class="chip__count"><?= count($vehicles) ?></span></button>
<?php foreach ($ranges as $key => $range): ?>
      <button class="chip" type="button" aria-pressed="false" data-filter="<?= e($key) ?>"><?= e($range['label']) ?> мест<span class="chip__count"><?= (int) $counts[$key] ?></span></button>
<?php endforeach; ?>
    </div>
<?php endif; ?>
    <ul class="fleet-grid" data-fleet-list data-stagger>
<?php foreach ($vehicles as $i => $vehicle): ?>
      <li data-reveal data-range="<?= e($vehicle['range']) ?>">
        <?= view('home/vehicle-card', ['v' => $vehicle, 'index' => $i]) ?>
      </li>
<?php endforeach; ?>
    </ul>
    <p class="fleet-empty card card--pad" data-fleet-empty hidden>В этой группе сейчас нет машин. <a href="#contacts" data-open-request>Оставьте заявку</a> — диспетчер подберёт подходящий транспорт.</p>
<?php endif; ?>
  </div>
</section>
