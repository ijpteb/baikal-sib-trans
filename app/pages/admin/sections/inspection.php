<?php

declare(strict_types=1);

/*
 * Страница техосмотра: тексты, часы работы, документы, цены по категориям.
 */

$page = Settings::group('inspection');
$categories = Db::all('SELECT * FROM inspection_categories ORDER BY sort, id');
$errors = [];

if (is_post()) {
    [$page, $posted, $errors] = admin_inspection_from_post($_POST);
    if (!$errors) {
        Settings::save('inspection', $page);
        Db::transaction(static function () use ($posted, $categories): void {
            $known = array_map('intval', array_column($categories, 'id'));
            foreach ($posted as $id => $data) {
                if (in_array($id, $known, true)) {
                    Db::update('inspection_categories', $id, $data + ['updated_at' => time()]);
                }
            }
        });
        flash('success', 'Страница техосмотра сохранена.');
        redirect('/admin/inspection');
    }
    foreach ($categories as &$category) {
        if (isset($posted[(int) $category['id']])) {
            $category = array_merge($category, $posted[(int) $category['id']]);
        }
    }
    unset($category);
}

admin_page('inspection', [
    'page' => $page,
    'categories' => $categories,
    'errors' => $errors,
], ['title' => 'Техосмотр', 'section' => 'inspection'], $errors ? 422 : 200);
