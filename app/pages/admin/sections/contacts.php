<?php

declare(strict_types=1);

/*
 * Контакты: телефоны, почта, адреса, часы работы, карта, мессенджеры.
 */

$contacts = Settings::group('contacts');
$messengers = Settings::group('messengers');
$errors = [];
$warnings = [];

if (is_post()) {
    [$contacts, $messengers, $errors, $warnings] = admin_contacts_from_post($_POST);
    if (!$errors) {
        Settings::save('contacts', $contacts);
        Settings::save('messengers', $messengers);
        flash('success', 'Контакты сохранены.');
        foreach ($warnings as $warning) {
            flash('warning', $warning);
        }
        redirect('/admin/contacts');
    }
} else {
    foreach (messenger_list() as $key => $meta) {
        $row = (array) ($messengers[$key] ?? []);
        if (!empty($row['enabled']) && trim((string) ($row['value'] ?? '')) === '') {
            $warnings['messengers.' . $key] = 'Пока ссылки нет, ' . $meta['label'] . ' не показывается на сайте.';
        }
    }
}

admin_page('contacts', [
    'contacts' => $contacts,
    'messengers' => $messengers,
    'errors' => $errors,
    'warnings' => $warnings,
], ['title' => 'Контакты и мессенджеры', 'section' => 'contacts'], $errors ? 422 : 200);
