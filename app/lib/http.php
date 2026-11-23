<?php

declare(strict_types=1);

/*
 * Маршрутизация, заголовки безопасности, ответы сервера и страницы ошибок.
 */

/** Одноразовый код (nonce) для разрешённых встроенных скриптов на странице. */
function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

/** Номер счётчика Яндекс Метрики (только цифры) или пустая строка. */
function metrika_id(): string
{
    $id = preg_replace('/\D+/', '', (string) setting('seo.metrika_id', '')) ?? '';
    return strlen($id) >= 5 && strlen($id) <= 12 ? $id : '';
}

function build_csp(string $area): string
{
    $nonce = csp_nonce();
    $script = ["'self'", "'nonce-{$nonce}'"];
    $img = ["'self'", 'data:'];
    $connect = ["'self'"];
    $frame = ['https://yandex.ru', 'https://*.yandex.ru'];

    if ($area === 'admin') {
        $img[] = 'blob:';
    }

    $ancestors = $area === 'admin' ? ["'none'"] : ["'self'"];
    if ($area === 'public' && metrika_id() !== '') {
        // Домены Яндекс Метрики (по официальной инструкции Яндекса для CSP)
        $script = array_merge($script, ['https://mc.yandex.ru', 'https://yastatic.net']);
        $img = array_merge($img, ['https://mc.yandex.ru', 'https://mc.yandex.com']);
        $connect = array_merge($connect, [
            'https://mc.yandex.ru', 'https://mc.yandex.com', 'https://mc.webvisor.org', 'https://mc.webvisor.com', 'wss://mc.yandex.ru',
        ]);
        $frame = array_merge($frame, ['blob:', 'https://mc.yandex.ru', 'https://mc.yandex.com']);
        // Чтобы в Метрике открывались карты кликов и Вебвизор
        $ancestors = array_merge($ancestors, [
            'https://metrika.yandex.ru', 'https://metrika.yandex.by', 'https://metrika.yandex.kz', 'https://metrika.yandex.com',
            'https://metrica.yandex.com', 'https://metrika.ya.ru', 'https://analytics.yandex.ru', 'https://metr.yandex.ru',
        ]);
    }

    $directives = [
        "default-src 'self'",
        'script-src ' . implode(' ', $script),
        "style-src 'self' 'unsafe-inline'",
        'img-src ' . implode(' ', array_unique($img)),
        "font-src 'self'",
        'connect-src ' . implode(' ', array_unique($connect)),
        'frame-src ' . implode(' ', array_unique($frame)),
        'child-src ' . implode(' ', array_unique($frame)),
        "worker-src 'self' blob:",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        'frame-ancestors ' . implode(' ', $ancestors),
    ];
    if (is_https()) {
        $directives[] = 'upgrade-insecure-requests';
    }
    return implode('; ', $directives);
}

function send_security_headers(string $area = 'public'): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    // Со включённой Метрикой страницы должны открываться внутри её отчётов — тогда работает только frame-ancestors из CSP.
    if ($area === 'admin' || metrika_id() === '') {
        header('X-Frame-Options: ' . ($area === 'admin' ? 'DENY' : 'SAMEORIGIN'));
    }
    if (is_https()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
    header('Content-Security-Policy: ' . build_csp($area));
}

function send_html(string $html, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    echo $html;
}

function send_json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Отдаёт файл с диска с кэшированием в браузере (ответ 304, если файл не менялся).
 * $transform — необязательная обработка содержимого (например, PNG → ICO).
 */
function send_static_file(string $path, string $type, int $maxAge = 86400, ?callable $transform = null): void
{
    if (!is_file($path) || !is_readable($path)) {
        render_error_page(404);
        return;
    }
    $mtime = (int) filemtime($path);
    $etag = '"' . substr(sha1($path . '|' . $mtime . '|' . filesize($path)), 0, 20) . '"';
    header('Content-Type: ' . $type);
    header('Cache-Control: public, max-age=' . $maxAge);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('ETag: ' . $etag);
    if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
        http_response_code(304);
        return;
    }
    $data = (string) file_get_contents($path);
    if ($transform !== null) {
        $data = $transform($data);
    }
    header('Content-Length: ' . strlen($data));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        echo $data;
    }
}

/** Упаковывает PNG в формат ICO (для /favicon.ico). */
function png_to_ico(string $png, int $size): string
{
    $dim = $size >= 256 ? 0 : $size;
    return pack('vvv', 0, 1, 1)
        . pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, strlen($png), 22)
        . $png;
}

/** Главная функция: определяет, какую страницу показать. */
function app_run(): void
{
    $path = request_path();
    $method = request_method();

    if ($path === '/index.php') {
        redirect('/' . query_string_suffix(), 301);
    }
    // «/tehosmotr/» → «/tehosmotr»
    if ($path !== '/' && str_ends_with($path, '/') && $method === 'GET') {
        redirect(rtrim($path, '/') . query_string_suffix(), 301);
    }

    /** @var list<array{0: list<string>, 1: string, 2: string, 3?: array<string, mixed>}> $routes */
    $routes = require APP_DIR . '/routes.php';
    $allowedMethods = [];

    foreach ($routes as $route) {
        [$methods, $pattern, $handler] = $route;
        $options = $route[3] ?? [];
        if (!preg_match('#^' . $pattern . '$#u', $path, $matches)) {
            continue;
        }
        if (!in_array($method, $methods, true)) {
            $allowedMethods = array_merge($allowedMethods, $methods);
            continue;
        }
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        $area = (string) ($options['area'] ?? 'public');
        send_security_headers($area);
        if ($area === 'admin') {
            admin_boot(!empty($options['auth']), !empty($options['json']));
        }
        run_page($handler, $params);
        return;
    }

    if ($allowedMethods) {
        send_security_headers();
        header('Allow: ' . implode(', ', array_unique($allowedMethods)));
        render_error_page(405);
        return;
    }

    send_security_headers();
    render_error_page(404);
}

/** Подключает обработчик страницы из app/pages. */
function run_page(string $handler, array $params = []): void
{
    $file = APP_DIR . '/pages/' . $handler . '.php';
    if (!is_file($file)) {
        render_error_page(404);
        return;
    }
    (static function (string $__file, array $params): void {
        require $__file;
    })($file, $params);
}

/** Страница ошибки в стиле сайта (404, 405, 500…). */
function render_error_page(int $status): void
{
    try {
        $html = render_public_page('public/error', [
            'status' => $status,
        ], [
            'title' => $status === 404 ? 'Страница не найдена' : 'Ошибка',
            'robots' => 'noindex',
            'canonical' => null,
        ]);
        send_html($html, $status);
    } catch (Throwable $e) {
        error_log((string) $e);
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $status === 404 ? 'Страница не найдена' : 'Ошибка';
    }
}

function not_found(): never
{
    render_error_page(404);
    exit;
}

/** Последний рубеж: непойманная ошибка → понятная страница вместо белого экрана. */
function handle_uncaught_exception(Throwable $e): void
{
    error_log('[' . date('c') . '] ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $details = is_debug()
        ? '<pre style="white-space:pre-wrap;font-size:13px;background:#f4f9fd;padding:12px;border-radius:8px;overflow:auto">'
            . e(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString())
            . '</pre>'
        : '';
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Временная ошибка</title></head>'
        . '<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;max-width:720px;margin:10vh auto;padding:0 16px;color:#0A1F33;line-height:1.6">'
        . '<h1 style="font-size:28px">Сайт временно не открывается</h1>'
        . '<p>Мы уже знаем о проблеме. Чтобы заказать транспорт, позвоните нам: '
        . '<a href="tel:+79526221144" style="color:#0B4F8A;font-weight:700">+7 952 622-11-44</a>.</p>'
        . $details
        . '</body></html>';
}

/** Простая страница-сообщение до того, как загрузились шаблоны (например, нет прав на папку data). */
function fatal_page(string $message): never
{
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Нужна настройка сайта</title></head>'
        . '<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;max-width:720px;margin:10vh auto;padding:0 16px;color:#0A1F33;line-height:1.6">'
        . '<h1 style="font-size:26px">Нужна настройка сайта</h1><p>' . e($message) . '</p></body></html>';
    exit;
}
