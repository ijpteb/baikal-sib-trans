<?php

declare(strict_types=1);

/*
 * Скачивание резервной копии (только по кнопке — запрос с защитным кодом формы).
 */

if (!is_post()) {
    redirect('/admin/backup');
}

try {
    backup_send();
} catch (Throwable $e) {
    error_log('[backup] ' . $e);
    if (!headers_sent()) {
        flash('error', 'Не удалось собрать резервную копию: ' . $e->getMessage());
        redirect('/admin/backup');
    }
    throw $e;
}
