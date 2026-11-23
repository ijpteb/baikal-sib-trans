<?php

declare(strict_types=1);

/*
 * Раздел админки: список записей (автопарк, отзывы…) или страница настроек (контакты, тексты…).
 */

$section = (string) ($params['section'] ?? '');

if (admin_table($section) !== null) {
    require __DIR__ . '/sections/list.php';
    return;
}

$file = __DIR__ . '/sections/' . $section . '.php';
if (!preg_match('/^[a-z]+$/', $section) || !is_file($file)) {
    not_found();
}
require $file;
