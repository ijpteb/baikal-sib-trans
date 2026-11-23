<?php

declare(strict_types=1);

/*
 * Главная страница админки: что осталось заполнить перед запуском и состояние сервера.
 */

if (is_post()) {
    if (($_POST['action'] ?? '') === 'probe') {
        $result = uploads_php_probe();
        admin_system_save(['uploads_probe' => ['at' => time(), 'result' => $result]]);
        match ($result) {
            'safe' => flash('success', 'Проверено: папка с картинками защищена.'),
            'unsafe' => flash('error', 'Внимание: сервер запускает PHP-файлы из папки uploads. Напишите в поддержку хостинга: «Запретите выполнение PHP в папке uploads».'),
            default => flash('error', 'Проверить не удалось: сайт не смог обратиться сам к себе. Если сайт загружен по инструкции, это не страшно.'),
        };
    }
    redirect('/admin');
}

uploads_gc_daily();

admin_page('dashboard', [
    'checklist' => admin_checklist(),
    'checks' => admin_system_checks(),
], ['title' => 'Главная', 'section' => 'dashboard']);
