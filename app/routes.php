<?php

declare(strict_types=1);

/*
 * Адреса страниц сайта.
 * Формат: [методы, адрес (регулярное выражение), обработчик из app/pages, параметры].
 *   area — 'public' (сайт) или 'admin' (админка);
 *   auth — нужен вход в админку;
 *   json — ответ в формате JSON (для кнопок, которые работают без перезагрузки страницы).
 */

$adminSections = 'vehicles|services|advantages|steps|stats|reviews|partners|faq|inspection|calculator|contacts|requisites|texts|backup|password';

return [
    // Публичная часть
    [['GET'], '/', 'public/home'],
    [['GET'], '/tehosmotr', 'public/tehosmotr'],
    [['GET'], '/privacy', 'public/privacy'],
    [['GET'], '/robots\.txt', 'public/robots'],
    [['GET'], '/sitemap\.xml', 'public/sitemap'],
    [['GET'], '/(?<icon>favicon\.ico|favicon\.svg|apple-touch-icon\.png|icon-192\.png|icon-512\.png)', 'public/favicon'],
    [['GET'], '/site\.webmanifest', 'public/manifest'],
    [['GET'], '/styleguide', 'public/styleguide'],

    // Админка
    [['GET', 'POST'], '/admin/setup', 'admin/setup', ['area' => 'admin']],
    [['GET', 'POST'], '/admin/login', 'admin/login', ['area' => 'admin']],
    [['POST'], '/admin/logout', 'admin/logout', ['area' => 'admin', 'auth' => true]],
    [['GET', 'POST'], '/admin', 'admin/dashboard', ['area' => 'admin', 'auth' => true]],
    [['POST'], '/admin/upload', 'admin/upload', ['area' => 'admin', 'auth' => true, 'json' => true]],
    [['POST'], '/admin/(?<section>vehicles|services|advantages|steps|stats|reviews|partners|faq)/reorder', 'admin/reorder', ['area' => 'admin', 'auth' => true, 'json' => true]],
    [['POST'], '/admin/(?<section>vehicles|services|advantages|steps|stats|reviews|partners|faq)/(?<id>\d+)/toggle', 'admin/toggle', ['area' => 'admin', 'auth' => true, 'json' => true]],
    [['GET', 'POST'], '/admin/backup/download', 'admin/backup', ['area' => 'admin', 'auth' => true]],
    [['GET', 'POST'], '/admin/(?<section>' . $adminSections . ')', 'admin/section', ['area' => 'admin', 'auth' => true]],
    [['GET', 'POST'], '/admin/(?<section>vehicles|services|advantages|steps|stats|reviews|partners|faq)/(?<id>new|\d+)', 'admin/item', ['area' => 'admin', 'auth' => true]],
];
