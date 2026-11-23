<?php

declare(strict_types=1);

/*
 * Страница «Резервная копия»: что в неё входит, кнопка скачивания, как восстановить.
 */

admin_page('backup', [
    'dbSize' => is_file(Db::file()) ? (int) filesize(Db::file()) : 0,
    'uploads' => uploads_usage(),
    'lastBackup' => (int) admin_system('last_backup', 0),
], ['title' => 'Резервная копия', 'section' => 'backup']);
