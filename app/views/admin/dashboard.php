<?php
/**
 * Главная админки: готовность сайта к запуску и состояние сервера.
 *
 * @var array $checklist
 * @var array $checks
 */

$required = array_values(array_filter($checklist, static fn (array $i): bool => $i['required']));
$optional = array_values(array_filter($checklist, static fn (array $i): bool => !$i['required']));
$done = count(array_filter($required, static fn (array $i): bool => $i['done']));
$total = count($required);
$percent = $total > 0 ? (int) round($done / $total * 100) : 100;
// Сначала то, что ещё не сделано
$sortTodo = static fn (array $a, array $b): int => (int) $a['done'] <=> (int) $b['done'];
usort($required, $sortTodo);
usort($optional, $sortTodo);

$renderItem = static function (array $item): string {
    $status = $item['done'] ? 'done' : ($item['required'] ? 'todo' : 'optional');
    $iconName = $item['done'] ? 'check-circle' : ($item['required'] ? 'alert-circle' : 'info');
    $label = $item['done'] ? 'Сделано' : ($item['required'] ? 'Нужно сделать' : 'Желательно');
    $html = '<li class="todo todo--' . $status . '">';
    $html .= '<span class="todo__icon">' . icon($iconName, '', $label) . '</span>';
    $html .= '<div class="todo__body"><p class="todo__title">' . e($item['title']) . '</p>';
    $html .= '<p class="todo__text">' . e($item['text']) . '</p></div>';
    if (!$item['done']) {
        $html .= '<a class="btn btn--outline btn--sm todo__action" href="' . e($item['href']) . '">' . e($item['action']) . icon('arrow-right') . '</a>';
    }
    return $html . '</li>';
};

$actions = '<a class="btn btn--outline btn--sm" href="/" target="_blank" rel="noopener">' . icon('external-link') . 'Открыть сайт</a>'
    . '<a class="btn btn--secondary btn--sm" href="/admin/vehicles/new">' . icon('plus') . 'Добавить машину</a>';
?>
<?= admin_head('Главная', 'Здесь видно, что осталось заполнить перед запуском сайта. Пункты исчезают из списка «нужно», когда вы их выполните.', $actions) ?>

<section class="card card--pad progress-card" aria-labelledby="ready-title">
  <div class="progress-card__head">
    <h2 class="progress-card__title" id="ready-title">Готовность к запуску</h2>
    <p class="progress-card__count"><b><?= $done ?></b> из <?= $total ?></p>
  </div>
  <div class="progress" role="progressbar" aria-labelledby="ready-title" aria-valuemin="0" aria-valuemax="<?= $total ?>" aria-valuenow="<?= $done ?>">
    <span class="progress__bar" style="width: <?= $percent ?>%"></span>
  </div>
<?php if ($done === $total): ?>
  <p class="progress-card__note"><?= icon('check-circle') ?>Всё обязательное готово — сайт можно показывать клиентам.</p>
<?php endif; ?>
</section>

<section class="admin-section" aria-labelledby="todo-title">
  <h2 class="admin-section__title" id="todo-title">Перед запуском</h2>
  <ul class="todos card">
<?php foreach ($required as $item): ?>
    <?= $renderItem($item) ?>
<?php endforeach; ?>
  </ul>
</section>

<section class="admin-section" aria-labelledby="optional-title">
  <h2 class="admin-section__title" id="optional-title">Желательно</h2>
  <ul class="todos card">
<?php foreach ($optional as $item): ?>
    <?= $renderItem($item) ?>
<?php endforeach; ?>
  </ul>
</section>

<section class="admin-section" aria-labelledby="system-title">
  <h2 class="admin-section__title" id="system-title">Сервер и безопасность</h2>
  <ul class="checks card">
<?php foreach ($checks as $check): ?>
<?php $iconName = ['ok' => 'check-circle', 'warn' => 'alert-circle', 'bad' => 'alert-circle'][$check['status']] ?? 'info'; ?>
    <li class="check-row check-row--<?= e($check['status']) ?>">
      <span class="check-row__icon"><?= icon($iconName, '', ['ok' => 'В порядке', 'warn' => 'Внимание', 'bad' => 'Проблема'][$check['status']] ?? '') ?></span>
      <div class="check-row__body">
        <p class="check-row__title"><?= e($check['title']) ?></p>
        <p class="check-row__text"><?= e($check['text']) ?></p>
      </div>
<?php if (!empty($check['probe'])): ?>
      <form method="post" action="/admin" class="check-row__action">
        <?= csrf_field() ?>
        <button class="btn btn--outline btn--sm" type="submit" name="action" value="probe" data-busy-text="Проверяем…"><?= icon('refresh') ?>Проверить</button>
      </form>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ul>
</section>
