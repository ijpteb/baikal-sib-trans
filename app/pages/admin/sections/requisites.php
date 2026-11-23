<?php

declare(strict_types=1);

/*
 * Реквизиты компании (показываются внизу сайта и в политике конфиденциальности).
 */

$values = Settings::group('requisites');
$errors = [];

if (is_post()) {
    [$values, $errors] = admin_requisites_from_post($_POST);
    if (!$errors) {
        Settings::save('requisites', $values);
        flash('success', 'Реквизиты сохранены.');
        redirect('/admin/requisites');
    }
}

admin_page('requisites', [
    'values' => $values,
    'errors' => $errors,
], ['title' => 'Реквизиты', 'section' => 'requisites'], $errors ? 422 : 200);
