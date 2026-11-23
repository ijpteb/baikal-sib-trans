<?php

declare(strict_types=1);

/*
 * Добавление и редактирование одной записи (тип поездки, отзыв, вопрос…). Автопарк — в vehicle.php.
 */

$section = (string) ($params['section'] ?? '');
$config = admin_table($section);
if ($config === null) {
    not_found();
}
if ($section === 'vehicles') {
    require __DIR__ . '/vehicle.php';
    return;
}

$table = $config['table'];
$fields = $config['fields'];
$isNew = ($params['id'] ?? '') === 'new';
$row = $isNew ? null : Db::one('SELECT * FROM ' . $table . ' WHERE id = ?', [(int) $params['id']]);
if (!$isNew && $row === null) {
    flash('error', 'Запись не найдена — возможно, её уже удалили.');
    redirect('/admin/' . $section);
}

$values = [];
foreach ($fields as $key => $field) {
    $value = $row[$key] ?? ($field['type'] === 'icon' ? 'bus' : '');
    $values[$key] = $field['type'] === 'image' ? json_decode_array($value) : $value;
}
$values['is_visible'] = $row === null || (bool) $row['is_visible'];
$errors = [];

if (is_post()) {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        if ($row !== null) {
            Db::delete($table, (int) $row['id']);
            $title = admin_row_summary($section, $row)['title'];
            flash('success', 'Удалено' . ($title !== '' ? ': «' . str_excerpt($title, 60) . '»' : '') . '.');
        }
        redirect('/admin/' . $section);
    }

    [$clean, $errors] = admin_validate_fields($fields, $_POST);
    $visible = bool_from($_POST['is_visible'] ?? '0');

    if (!$errors) {
        $data = [];
        foreach ($fields as $key => $field) {
            $data[$key] = $field['type'] === 'image' ? ($clean[$key] ? json_store($clean[$key]) : '') : $clean[$key];
        }
        $data['is_visible'] = $visible ? 1 : 0;
        $data['updated_at'] = time();
        if ($section === 'faq') {
            $data['needs_review'] = 0; // открыли, проверили, сохранили
        }
        if ($row === null) {
            $data['sort'] = Db::nextSort($table);
            Db::insert($table, $data);
        } else {
            Db::update($table, (int) $row['id'], $data);
        }
        $title = admin_row_summary($section, array_merge($row ?? [], $data, ['logo' => $data['logo'] ?? '']))['title'];
        flash('success', 'Сохранено' . ($title !== '' ? ': «' . str_excerpt($title, 60) . '»' : '') . '.');
        redirect('/admin/' . $section);
    }

    $values = array_merge($values, $clean, ['is_visible' => $visible]);
}

$title = $isNew ? $config['new_title'] : str_excerpt(admin_row_summary($section, $row)['title'], 70);
admin_page('item', [
    'section' => $section,
    'config' => $config,
    'row' => $row,
    'values' => $values,
    'errors' => $errors,
    'title' => $title,
], ['title' => $title, 'section' => $section], $errors ? 422 : 200);
