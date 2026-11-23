<?php

declare(strict_types=1);

/*
 * Список записей раздела: порядок, показ на сайте, ссылки на редактирование.
 *
 * @var string $section
 */

$config = admin_table($section);
$rows = $section === 'vehicles'
    ? Content::vehicles(false)
    : Db::all('SELECT * FROM ' . $config['table'] . ' ORDER BY sort, id');

admin_page('list', [
    'section' => $section,
    'config' => $config,
    'rows' => $rows,
], ['title' => $config['title'], 'section' => $section]);
