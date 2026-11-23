<?php

declare(strict_types=1);

/*
 * Байкал Сиб-Транс — единая точка входа сайта.
 * Все адреса (/, /tehosmotr, /admin …) обрабатываются здесь.
 */

define('PUBLIC_DIR', __DIR__);

// Локальный сервер PHP (php -S): существующие файлы (картинки, стили, шрифты) отдаёт сам сервер.
if (PHP_SAPI === 'cli-server') {
    $uriPath = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    $file = realpath(__DIR__ . $uriPath);
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $isHidden = str_starts_with(basename($file), '.');
        if ($file !== __FILE__ && ($isHidden || in_array($ext, ['php', 'phtml', 'phar', 'php8', 'php7'], true))) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Доступ запрещён';
            return true;
        }
        if ($file !== __FILE__) {
            return false;
        }
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';
