<?php

declare(strict_types=1);

/*
 * Небольшие вспомогательные функции, которые используются по всему сайту.
 */

/** Экранирование текста для вывода в HTML (и в тексте, и в атрибутах). */
function e(mixed $value): string
{
    if ($value === null || is_array($value)) {
        return '';
    }
    if (is_bool($value)) {
        $value = $value ? '1' : '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Сайт запущен на компьютере разработчика (php -S)? */
function is_debug(): bool
{
    return PHP_SAPI === 'cli-server';
}

function request_method(): string
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    return $method === 'HEAD' ? 'GET' : $method;
}

function is_post(): bool
{
    return request_method() === 'POST';
}

/** Путь запроса без параметров: «/tehosmotr». */
function request_path(): string
{
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '/';
    }
    $path = rawurldecode($path);
    $path = preg_replace('#/{2,}#', '/', $path) ?? '/';
    return $path === '' ? '/' : $path;
}

function query_string_suffix(): string
{
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return $qs === '' ? '' : '?' . $qs;
}

function is_https(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    // Timeweb сообщает о защищённом соединении заголовком X-HTTPS: 1
    if ((string) ($_SERVER['HTTP_X_HTTPS'] ?? '') === '1') {
        return true;
    }
    return strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https';
}

/** Имя хоста из запроса, только безопасные символы. */
function request_host(): string
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost')));
    if (preg_match('/^[a-z0-9.-]{1,253}(:\d{1,5})?$/', $host) || preg_match('/^\[[0-9a-f:.]+\](:\d{1,5})?$/', $host)) {
        return $host;
    }
    return 'localhost';
}

/** Адрес сайта без слэша на конце: «https://baikalst.ru». */
function base_url(): string
{
    $configured = rtrim(trim((string) setting('site.url', '')), '/');
    if ($configured !== '' && preg_match('#^https?://[a-z0-9.-]+(:\d{1,5})?$#i', $configured)) {
        return strtolower($configured);
    }
    return (is_https() ? 'https' : 'http') . '://' . request_host();
}

function abs_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return base_url() . '/' . ltrim($path, '/');
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/** Адрес файла из public/assets с меткой версии (чтобы браузер не брал старую копию из кэша). */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = PUBLIC_DIR . '/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : APP_VERSION;
    return '/assets/' . $path . '?v=' . $version;
}

function upload_url(string $fileName): string
{
    return '/uploads/' . rawurlencode($fileName);
}

/** Строка из формы: обрезаем пробелы, убираем управляющие символы, ограничиваем длину. */
function str_clean(mixed $value, int $maxLength = 2000, bool $multiline = true): string
{
    if (!is_scalar($value)) {
        return '';
    }
    $s = (string) $value;
    if (!mb_check_encoding($s, 'UTF-8')) {
        $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    }
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
    if (!$multiline) {
        $s = preg_replace('/\s+/u', ' ', $s) ?? '';
    } else {
        $s = preg_replace("/\n{3,}/", "\n\n", $s) ?? '';
    }
    $s = trim($s);
    if (mb_strlen($s) > $maxLength) {
        $s = rtrim(mb_substr($s, 0, $maxLength));
    }
    return $s;
}

function str_line(mixed $value, int $maxLength = 255): string
{
    return str_clean($value, $maxLength, false);
}

/** Целое число из формы («2 500» → 2500). Пустое значение → null. */
function int_or_null(mixed $value, int $min = 0, int $max = 100000000): ?int
{
    if (is_int($value)) {
        $n = $value;
    } else {
        $digits = preg_replace('/[^\d-]/', '', (string) (is_scalar($value) ? $value : ''));
        if ($digits === null || $digits === '' || $digits === '-') {
            return null;
        }
        $n = (int) $digits;
    }
    return max($min, min($max, $n));
}

function bool_from(mixed $value): bool
{
    return in_array($value, [true, 1, '1', 'on', 'yes', 'true'], true);
}

/** Склонение: plural_ru(5, 'место', 'места', 'мест') → «мест». */
function plural_ru(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 === 1) {
        return $one;
    }
    return $many;
}

/** 2500 → «2 500 ₽» (с неразрывными пробелами). */
function money(int $amount, bool $withSign = true): string
{
    $s = number_format($amount, 0, ',', "\u{00A0}");
    return $withSign ? $s . "\u{00A0}₽" : $s;
}

/** Только цифры телефона в международном формате: «79526221144». */
function phone_digits(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    } elseif (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }
    return $digits;
}

function phone_href(string $phone): string
{
    $digits = phone_digits($phone);
    return $digits === '' ? '' : 'tel:+' . $digits;
}

/** «2026-12-12» → «12 декабря 2026». */
function date_ru(string $ymd, bool $withYear = true): string
{
    $ts = strtotime($ymd);
    if ($ts === false) {
        return '';
    }
    $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    $s = (int) date('j', $ts) . "\u{00A0}" . $months[(int) date('n', $ts) - 1];
    return $withYear ? $s . ' ' . date('Y', $ts) : $s;
}

/** JSON, который безопасно вставлять внутрь <script> (в том числе application/ld+json). */
function json_for_script(mixed $data): string
{
    return (string) json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}

function json_decode_array(mixed $json): array
{
    if (is_array($json)) {
        return $json;
    }
    if (!is_string($json) || $json === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function json_store(mixed $data): string
{
    return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** Атрибуты тега из массива: ['class' => 'a', 'hidden' => true] → ' class="a" hidden'. */
function attrs(array $attributes): string
{
    $out = '';
    foreach ($attributes as $name => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        if ($value === true) {
            $out .= ' ' . $name;
            continue;
        }
        $out .= ' ' . $name . '="' . e($value) . '"';
    }
    return $out;
}

/** Склеивает CSS-классы, пропуская пустые. */
function classes(string|false|null ...$parts): string
{
    return implode(' ', array_filter($parts, static fn ($p): bool => is_string($p) && $p !== ''));
}

function redirect(string $to, int $status = 303): never
{
    header('Location: ' . $to, true, $status);
    exit;
}

/** Короткий текст для превью: обрезает по словам. */
function str_excerpt(string $text, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $length * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " ,.;:—-") . '…';
}

function current_year(): string
{
    return date('Y');
}
