<?php

declare(strict_types=1);

/*
 * Загрузка картинки (вызывается скриптом админки). Отвечает в формате JSON.
 */

$purpose = (string) ($_POST['purpose'] ?? '');
if (!isset(image_purposes()[$purpose])) {
    send_json(['ok' => false, 'error' => 'Неизвестный тип картинки.'], 422);
}

try {
    $img = image_process_upload($_FILES['file'] ?? null, $purpose);
} catch (UserFacingError $e) {
    send_json(['ok' => false, 'error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('[upload] ' . $e);
    send_json(['ok' => false, 'error' => 'Не удалось обработать картинку. Попробуйте другой файл.'], 500);
}

send_json([
    'ok' => true,
    'token' => image_token($img),
    'preview' => image_url($img, 480),
    'width' => $img['w'],
    'height' => $img['h'],
]);
