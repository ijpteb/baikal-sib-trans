<?php

declare(strict_types=1);

/*
 * Карточка машины: название, места, удобства, фото, цены.
 * Подключается из item.php для раздела «Автопарк».
 *
 * @var array $params
 */

$isNew = ($params['id'] ?? '') === 'new';
$vehicle = $isNew ? null : Content::vehicle((int) $params['id']);
if (!$isNew && $vehicle === null) {
    flash('error', 'Машина не найдена — возможно, её уже удалили.');
    redirect('/admin/vehicles');
}

$values = $vehicle ?? admin_vehicle_defaults();
$errors = [];

if (is_post()) {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        if ($vehicle !== null) {
            Db::delete('vehicles', $vehicle['id']);
            flash('success', 'Машина удалена: «' . $vehicle['name'] . '».');
        }
        redirect('/admin/vehicles');
    }

    [$data, $photos, $errors] = admin_vehicle_from_post($_POST);
    if (!$errors) {
        admin_vehicle_save($vehicle['id'] ?? null, $data, $photos);
        flash('success', 'Сохранено: «' . $data['name'] . '».');
        redirect('/admin/vehicles');
    }
    $values = array_merge($values, $data, [
        'photos' => $photos,
        'seats' => $data['seats'] > 0 ? $data['seats'] : (string) ($_POST['seats'] ?? ''),
    ]);
}

$title = $isNew ? 'Новая машина' : $vehicle['name'];
admin_page('vehicle', [
    'vehicle' => $vehicle,
    'values' => $values,
    'errors' => $errors,
    'title' => $title,
], ['title' => $title, 'section' => 'vehicles'], $errors ? 422 : 200);
