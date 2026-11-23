<?php
/**
 * Список записей раздела: порядок (кнопки ↑ ↓), показ на сайте, изменить, удалить.
 *
 * @var string $section
 * @var array  $config
 * @var array  $rows
 */

$count = count($rows);
$actions = '<a class="btn btn--secondary btn--sm" href="/admin/' . e($section) . '/new">' . icon('plus') . e($config['add']) . '</a>';
?>
<?= admin_head($config['title'], e($config['lead']), $actions) ?>

<?php if (!$rows): ?>
<div class="card card--pad empty-state">
  <?= icon('list') ?>
  <p><?= e($config['empty']) ?></p>
  <a class="btn btn--secondary" href="/admin/<?= e($section) ?>/new"><?= icon('plus') ?><?= e($config['add']) ?></a>
</div>
<?php else: ?>
<p class="list-hint"><?= icon('info') ?><span>Порядок меняется стрелками <span class="list-hint__keys" aria-hidden="true">↑ ↓</span> — так же записи стоят на сайте. Переключатель «На сайте» прячет запись, не удаляя её.</span></p>
<ol class="rows card" data-rows data-reorder-url="/admin/<?= e($section) ?>/reorder">
<?php foreach ($rows as $i => $row): ?>
<?php
    $id = (int) $row['id'];
    if ($section === 'vehicles') {
        $title = (string) $row['name'];
        $metaParts = array_filter([
            $row['type_label'],
            $row['seats'] > 0 ? $row['seats'] . ' ' . plural_ru($row['seats'], 'место', 'места', 'мест') : '',
            $row['price_label'] ?? 'цена не указана',
            count($row['photos']) . ' фото',
        ]);
        $meta = implode(' · ', $metaParts);
        $badges = [];
        if ($row['is_demo']) {
            $badges[] = ['label' => 'Пример — замените', 'type' => 'warning'];
        }
        if (!$row['photos']) {
            $badges[] = ['label' => 'Без фото', 'type' => 'muted'];
        }
    } else {
        $summary = admin_row_summary($section, $row);
        $title = $summary['title'] !== '' ? $summary['title'] : 'Без названия';
        $meta = $summary['meta'];
        $badges = $summary['badges'];
    }
    $visible = (bool) $row['is_visible'];
    $editUrl = '/admin/' . $section . '/' . $id;
?>
  <li class="row<?= $visible ? '' : ' row--hidden' ?>" id="row-<?= $id ?>" data-row data-id="<?= $id ?>">
    <form class="row__order" method="post" action="/admin/<?= e($section) ?>/reorder" data-move-form>
      <?= csrf_field() ?>
      <button class="row__move" type="submit" name="move" value="<?= $id ?>:up" data-move="up"<?= $i === 0 ? ' disabled' : '' ?>><?= icon('chevron-up') ?><span class="visually-hidden">Выше: <?= e($title) ?></span></button>
      <button class="row__move" type="submit" name="move" value="<?= $id ?>:down" data-move="down"<?= $i === $count - 1 ? ' disabled' : '' ?>><?= icon('chevron-down') ?><span class="visually-hidden">Ниже: <?= e($title) ?></span></button>
    </form>
    <div class="row__thumb" aria-hidden="true">
<?php if ($section === 'vehicles'): ?>
<?php if ($row['photos']): ?>
      <img src="<?= e(image_url($row['photos'][0], 480)) ?>" alt="" width="96" height="72" loading="lazy">
<?php else: ?>
      <img src="<?= e(asset('img/vehicles/' . (in_array($row['type'], ['car', 'minibus', 'bus'], true) ? $row['type'] : 'bus') . '.svg')) ?>" alt="" width="96" height="72" loading="lazy">
<?php endif; ?>
<?php elseif ($section === 'partners'): ?>
<?php $logo = json_decode_array($row['logo']); ?>
<?php if (!empty($logo['file'])): ?>
      <img class="row__logo" src="<?= e(image_url($logo, 240)) ?>" alt="" loading="lazy">
<?php else: ?>
      <span class="row__icon"><?= icon('handshake') ?></span>
<?php endif; ?>
<?php elseif (in_array($section, ['services', 'advantages'], true)): ?>
      <span class="row__icon"><?= icon((string) $row['icon']) ?: icon('check-circle') ?></span>
<?php elseif ($section === 'steps'): ?>
      <span class="row__icon row__icon--num"><?= $i + 1 ?></span>
<?php else: ?>
      <span class="row__icon"><?= icon(['stats' => 'sparkles', 'reviews' => 'quote', 'faq' => 'help-circle'][$section] ?? 'list') ?></span>
<?php endif; ?>
    </div>
    <div class="row__main">
      <a class="row__title" href="<?= e($editUrl) ?>"><?= e($title) ?></a>
<?php if ($meta !== ''): ?>
      <p class="row__meta"><?= e($meta) ?></p>
<?php endif; ?>
<?php if ($badges): ?>
      <p class="row__badges">
<?php foreach ($badges as $badge): ?>
        <span class="tag tag--<?= e($badge['type']) ?>"><?= e($badge['label']) ?></span>
<?php endforeach; ?>
      </p>
<?php endif; ?>
    </div>
    <form class="row__visible" method="post" action="/admin/<?= e($section) ?>/<?= $id ?>/toggle" data-toggle-form>
      <?= csrf_field() ?>
      <input type="hidden" name="visible" value="<?= $visible ? '0' : '1' ?>">
      <button class="switch" type="submit" role="switch" aria-checked="<?= $visible ? 'true' : 'false' ?>">
        <span class="switch__track" aria-hidden="true"><span class="switch__thumb"></span></span>
        <span class="switch__label" data-on="На сайте" data-off="Скрыто"><?= $visible ? 'На сайте' : 'Скрыто' ?></span>
        <span class="visually-hidden">: <?= e($title) ?></span>
      </button>
    </form>
    <div class="row__actions">
      <a class="btn btn--ghost btn--sm btn--icon" href="<?= e($editUrl) ?>" title="Изменить"><?= icon('edit', '', 'Изменить: ' . $title) ?></a>
      <form method="post" action="<?= e($editUrl) ?>" data-confirm="Удалить «<?= e(str_excerpt($title, 60)) ?>»? Это нельзя отменить. Если нужно только спрятать запись — выключите «На сайте».">
        <?= csrf_field() ?>
        <button class="btn btn--ghost btn--sm btn--icon btn--danger" type="submit" name="action" value="delete" title="Удалить"><?= icon('trash', '', 'Удалить: ' . $title) ?></button>
      </form>
    </div>
  </li>
<?php endforeach; ?>
</ol>
<?php endif; ?>
