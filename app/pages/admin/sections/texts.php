<?php

declare(strict_types=1);

/*
 * Тексты сайта, логотип, значок, SEO и политика конфиденциальности — по вкладкам.
 */

$tabs = admin_text_tabs();
$tab = (string) ($_GET['tab'] ?? 'site');
if (!isset($tabs[$tab])) {
    $tab = 'site';
}

$values = [
    'site' => Settings::group('site'),
    'hero' => Settings::group('hero'),
    'about' => Settings::group('about'),
    'seo' => Settings::group('seo'),
    'privacy' => Settings::group('privacy'),
];
$errors = [];

if (is_post()) {
    if (($_POST['action'] ?? '') === 'privacy_default') {
        Settings::save('privacy', ['text' => privacy_default_text(), 'reviewed' => false]);
        flash('success', 'Возвращён исходный текст политики. Проверьте его и отметьте галочкой.');
        redirect('/admin/texts?tab=privacy');
    }
    [$groups, $errors] = admin_texts_from_post($tab, $_POST);
    if (!$errors) {
        foreach ($groups as $group => $groupValues) {
            Settings::save($group, $groupValues);
        }
        flash('success', 'Сохранено.');
        redirect('/admin/texts?tab=' . $tab);
    }
    foreach ($groups as $group => $groupValues) {
        $values[$group] = array_merge($values[$group], $groupValues);
    }
}

admin_page('texts', [
    'tabs' => $tabs,
    'tab' => $tab,
    'values' => $values,
    'errors' => $errors,
], ['title' => $tabs[$tab] . ' — тексты', 'section' => 'texts'], $errors ? 422 : 200);
