<?php

declare(strict_types=1);

/*
 * Запуск сайта: проверка окружения, подключение модулей, обработка ошибок, маршрутизация.
 * Подключается из public/index.php.
 */

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Сайту нужен PHP 8.1 или новее (сейчас ' . PHP_VERSION . '). '
        . 'Выберите PHP 8.1 или выше в панели управления хостингом.';
    exit;
}

define('APP_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));
define('DATA_DIR', ROOT_DIR . '/data');
if (!defined('PUBLIC_DIR')) {
    define('PUBLIC_DIR', ROOT_DIR . '/public');
}
define('UPLOADS_DIR', PUBLIC_DIR . '/uploads');
define('APP_VERSION', '1.0.0');

date_default_timezone_set('Asia/Irkutsk');

error_reporting(E_ALL);
ini_set('display_errors', PHP_SAPI === 'cli-server' ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(DATA_DIR) && is_writable(DATA_DIR)) {
    if (!is_dir(DATA_DIR . '/logs')) {
        @mkdir(DATA_DIR . '/logs', 0775, true);
    }
    ini_set('error_log', DATA_DIR . '/logs/php-errors.log');
}

// Без этих расширений сайт работать не может.
$missing = array_values(array_filter(
    ['pdo_sqlite', 'mbstring', 'json'],
    static fn (string $ext): bool => !extension_loaded($ext)
));
if ($missing) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'На хостинге не включены расширения PHP: ' . implode(', ', $missing)
        . '. Включите их в панели управления хостингом (раздел настроек PHP) или напишите в поддержку хостинга.';
    exit;
}
mb_internal_encoding('UTF-8');

$libFiles = glob(APP_DIR . '/lib/*.php') ?: [];
sort($libFiles);
foreach ($libFiles as $libFile) {
    require_once $libFile;
}
unset($libFiles, $libFile, $missing);

set_exception_handler('handle_uncaught_exception');

app_run();
