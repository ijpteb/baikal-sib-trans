<?php

declare(strict_types=1);

/*
 * Новый порядок записей. Скрипт присылает весь список (ids[]),
 * а кнопки без скрипта — одно перемещение (move = «id:up» или «id:down»).
 */

$section = (string) ($params['section'] ?? '');
$config = admin_table($section);
if ($config === null) {
    not_found();
}
$table = $config['table'];
$current = array_map('intval', array_column(Db::all('SELECT id FROM ' . $table . ' ORDER BY sort, id'), 'id'));
$anchor = '';

$move = (string) ($_POST['move'] ?? '');
if (preg_match('/^(\d+):(up|down)$/', $move, $m)) {
    $pos = array_search((int) $m[1], $current, true);
    if ($pos !== false) {
        $swap = $m[2] === 'up' ? $pos - 1 : $pos + 1;
        if (isset($current[$swap])) {
            [$current[$pos], $current[$swap]] = [$current[$swap], $current[$pos]];
        }
    }
    $order = $current;
    $anchor = '#row-' . (int) $m[1];
} else {
    $given = array_values(array_unique(array_map('intval', (array) ($_POST['ids'] ?? []))));
    $order = array_values(array_filter($given, static fn (int $id): bool => in_array($id, $current, true)));
    foreach ($current as $id) {
        if (!in_array($id, $order, true)) {
            $order[] = $id;
        }
    }
}

Db::reorder($table, $order);

if (admin_wants_json()) {
    send_json(['ok' => true]);
}
redirect('/admin/' . $section . $anchor);
