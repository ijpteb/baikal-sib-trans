<?php

declare(strict_types=1);

/*
 * Показать или скрыть запись на сайте.
 */

$section = (string) ($params['section'] ?? '');
$config = admin_table($section);
if ($config === null) {
    not_found();
}
$table = $config['table'];
$id = (int) ($params['id'] ?? 0);
$row = Db::one('SELECT id, is_visible FROM ' . $table . ' WHERE id = ?', [$id]);
if ($row === null) {
    if (admin_wants_json()) {
        send_json(['ok' => false, 'error' => 'Запись не найдена — обновите страницу.'], 404);
    }
    redirect('/admin/' . $section);
}

$visible = isset($_POST['visible']) ? bool_from($_POST['visible']) : !$row['is_visible'];
Db::run('UPDATE ' . $table . ' SET is_visible = ?, updated_at = ? WHERE id = ?', [$visible ? 1 : 0, time(), $id]);

if (admin_wants_json()) {
    send_json(['ok' => true, 'visible' => $visible]);
}
flash('success', $visible ? 'Запись снова показывается на сайте.' : 'Запись скрыта с сайта.');
redirect('/admin/' . $section . '#row-' . $id);
