<?php

declare(strict_types=1);

/*
 * Цены машин и настройки калькулятора на главной странице.
 */

$settings = Settings::group('calculator');
$vehicles = Content::vehicles(false);
$errors = [];
$posted = [];

if (is_post()) {
    [$settings, $posted, $errors] = admin_calculator_from_post($_POST);
    if (!$errors) {
        Settings::save('calculator', $settings);
        Db::transaction(static function () use ($posted, $vehicles): void {
            $known = array_column($vehicles, 'id');
            foreach ($posted as $id => $prices) {
                if (in_array($id, $known, true)) {
                    Db::update('vehicles', $id, $prices + ['updated_at' => time()]);
                }
            }
        });
        Content::forget();
        flash('success', Content::calculatorVisible()
            ? 'Цены сохранены. Калькулятор показывается на сайте.'
            : 'Цены сохранены. Калькулятор пока скрыт: ' . ($settings['enabled'] ? 'ни у одной показанной машины нет цены.' : 'он выключен.'));
        redirect('/admin/calculator');
    }
    // Показываем введённые значения поверх сохранённых
    foreach ($vehicles as &$vehicle) {
        if (isset($posted[$vehicle['id']])) {
            $vehicle = array_merge($vehicle, $posted[$vehicle['id']]);
        }
    }
    unset($vehicle);
}

admin_page('calculator', [
    'settings' => $settings,
    'vehicles' => $vehicles,
    'errors' => $errors,
], ['title' => 'Цены и калькулятор', 'section' => 'calculator'], $errors ? 422 : 200);
