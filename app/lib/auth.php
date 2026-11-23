<?php

declare(strict_types=1);

/*
 * Вход в админку: сессия, защита форм (CSRF), ограничение попыток входа.
 */

const ADMIN_SESSION_NAME = 'bst_admin';
const LOGIN_MAX_ATTEMPTS = 5;          // попыток входа…
const LOGIN_WINDOW_SECONDS = 900;      // …за 15 минут с одного адреса
const ADMIN_IDLE_SECONDS = 8 * 3600;   // без действий 8 часов — нужно войти снова
const RESET_PASSWORD_FILE = 'RESET_PASSWORD';
const SETUP_CODE_PREFIX = 'SETUP_CODE';

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $dir = DATA_DIR . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
        // Своя папка для сессий — значит, и чистить старые сессии нужно самим.
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '50');
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string) ADMIN_IDLE_SECONDS);
    session_name(ADMIN_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/admin',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Вызывается для каждой страницы админки до её отрисовки. */
function admin_boot(bool $requireAuth, bool $json = false): void
{
    // Код админки (формы, картинки, резервная копия) нужен только здесь — публичные страницы его не загружают.
    $adminFiles = glob(APP_DIR . '/admin/*.php') ?: [];
    sort($adminFiles);
    foreach ($adminFiles as $adminFile) {
        require_once $adminFile;
    }

    admin_session_start();
    header('Cache-Control: no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');

    $path = request_path();

    // Файл больше, чем разрешает хостинг: PHP молча отбрасывает всю форму. Объясняем по-человечески.
    if (is_post() && !$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $message = 'Файл слишком большой: хостинг принимает не больше ' . format_bytes(upload_limit_bytes())
            . '. Уменьшите фото и попробуйте ещё раз.';
        if ($json) {
            send_json(['ok' => false, 'error' => $message], 413);
        }
        flash('error', $message);
        redirect($path . query_string_suffix());
    }
    if (!admin_has_users() && $path !== '/admin/setup') {
        if ($json) {
            send_json(['ok' => false, 'error' => 'Сначала создайте учётную запись администратора.'], 401);
        }
        redirect('/admin/setup');
    }

    if ($requireAuth && admin_user() === null) {
        if ($json) {
            send_json(['ok' => false, 'error' => 'Время входа истекло. Обновите страницу и войдите снова.'], 401);
        }
        if (request_method() === 'GET') {
            $_SESSION['after_login'] = $path . query_string_suffix();
        }
        redirect('/admin/login');
    }

    if (is_post() && !csrf_valid()) {
        if ($json) {
            send_json(['ok' => false, 'error' => 'Страница устарела. Обновите её и повторите действие.'], 419);
        }
        flash('error', 'Страница устарела, поэтому изменения не сохранены. Повторите, пожалуйста, ещё раз.');
        redirect($path . query_string_suffix());
    }
}

function admin_has_users(): bool
{
    static $has = null;
    if ($has === null) {
        $has = (int) Db::value('SELECT COUNT(*) FROM users') > 0;
    }
    return $has;
}

/** Текущий администратор или null. */
function admin_user(): ?array
{
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    $cached = null;
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['uid'])) {
        return null;
    }
    $last = (int) ($_SESSION['seen_at'] ?? 0);
    if ($last > 0 && time() - $last > ADMIN_IDLE_SECONDS) {
        admin_logout();
        return null;
    }
    $user = Db::one('SELECT id, username, password_hash, last_login_at FROM users WHERE id = ?', [(int) $_SESSION['uid']]);
    // Если пароль сменили на другом устройстве — старые входы перестают действовать.
    if ($user === null || !hash_equals((string) ($_SESSION['pwd'] ?? ''), admin_password_fingerprint((string) $user['password_hash']))) {
        admin_logout();
        return null;
    }
    $_SESSION['seen_at'] = time();
    unset($user['password_hash']);
    $cached = $user;
    return $cached;
}

function admin_password_fingerprint(string $hash): string
{
    return substr(hash('sha256', 'pwd|' . $hash), 0, 24);
}

function admin_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION = [
        'uid' => (int) $user['id'],
        'pwd' => admin_password_fingerprint((string) $user['password_hash']),
        'seen_at' => time(),
        'csrf' => bin2hex(random_bytes(32)),
    ];
    Db::run('UPDATE users SET last_login_at = ? WHERE id = ?', [time(), (int) $user['id']]);
}

function admin_logout(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'secure' => $params['secure'],
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_destroy();
}

/* ---------- Ограничение попыток входа ---------- */

/** Сколько секунд ждать до следующей попытки (0 — можно пробовать). */
function login_wait_seconds(string $ip): int
{
    $since = time() - LOGIN_WINDOW_SECONDS;
    $rows = Db::all(
        'SELECT created_at FROM login_attempts WHERE ip = ? AND created_at > ? ORDER BY created_at ASC',
        [$ip, $since]
    );
    if (count($rows) < LOGIN_MAX_ATTEMPTS) {
        return 0;
    }
    $oldestRelevant = (int) $rows[count($rows) - LOGIN_MAX_ATTEMPTS]['created_at'];
    return max(1, $oldestRelevant + LOGIN_WINDOW_SECONDS - time());
}

function login_register_failure(string $ip): void
{
    Db::run('INSERT INTO login_attempts (ip, created_at) VALUES (?, ?)', [$ip, time()]);
    Db::run('DELETE FROM login_attempts WHERE created_at < ?', [time() - 86400]);
}

function login_clear_failures(string $ip): void
{
    Db::run('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
}

/* ---------- Сброс пароля через файл data/RESET_PASSWORD ---------- */

function password_reset_requested(): bool
{
    return is_file(DATA_DIR . '/' . RESET_PASSWORD_FILE);
}

function password_reset_finish(): bool
{
    $file = DATA_DIR . '/' . RESET_PASSWORD_FILE;
    return !is_file($file) || @unlink($file);
}

/**
 * Код для первой настройки и для сброса пароля. Сайт кладёт его в папку data, в файл
 * SETUP_CODE-<случайные буквы и цифры>.txt: прочитать его может только тот, у кого есть доступ к хостингу.
 * Так чужой человек не займёт админку, даже если откроет /admin/setup раньше владельца.
 * Случайное имя — на случай, если папка data по ошибке окажется открытой из интернета:
 * файл с известным именем можно было бы скачать, а угадать случайное нельзя.
 */
function setup_code(): ?string
{
    foreach (setup_code_files() as $file) {
        $code = preg_replace('/\D+/', '', (string) @file_get_contents($file)) ?? '';
        if (strlen($code) === 8 && preg_match('/-[a-f0-9]{12}\.txt$/', $file) === 1) {
            return $code;
        }
    }
    setup_code_clear(); // старые и испорченные файлы с кодом убираем, чтобы не перепутать
    $code = (string) random_int(10000000, 99999999);
    $file = DATA_DIR . '/' . SETUP_CODE_PREFIX . '-' . bin2hex(random_bytes(6)) . '.txt';
    // В пояснении не должно быть других цифр: при чтении из файла берутся все цифры подряд.
    $text = 'Код для настройки админки сайта: ' . substr($code, 0, 4) . '-' . substr($code, 4) . "\n\n"
        . "Введите его на странице /admin/setup.\n"
        . "После настройки этот файл удалится сам.\n";
    if (@file_put_contents($file, $text, LOCK_EX) === false) {
        return null;
    }
    return $code;
}

/** Файлы с кодом настройки в папке data (обычно один). */
function setup_code_files(): array
{
    $files = [];
    foreach (is_dir(DATA_DIR) ? (scandir(DATA_DIR) ?: []) : [] as $name) {
        if (preg_match('/^' . SETUP_CODE_PREFIX . '(?:-[A-Za-z0-9]+)?\.txt$/', $name) === 1) {
            $files[] = DATA_DIR . '/' . $name;
        }
    }
    return $files;
}

function setup_code_clear(): void
{
    foreach (setup_code_files() as $file) {
        @unlink($file);
    }
}

/** Ограничение размера загрузки, которое задано на хостинге (upload_max_filesize и post_max_size). */
function upload_limit_bytes(): int
{
    $limits = array_filter([
        ini_bytes((string) ini_get('upload_max_filesize')),
        ini_bytes((string) ini_get('post_max_size')),
    ], static fn (int $v): bool => $v > 0);
    return $limits ? min($limits) : 0;
}

/** «16M» → 16777216. */
function ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '' || $value === '-1') {
        return 0;
    }
    $number = (int) $value;
    return match (strtolower(substr($value, -1))) {
        'g' => $number * 1024 * 1024 * 1024,
        'm' => $number * 1024 * 1024,
        'k' => $number * 1024,
        default => $number,
    };
}

/** 15728640 → «15 МБ». */
function format_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return 'неизвестно';
    }
    if ($bytes >= 1024 * 1024) {
        $mb = $bytes / 1024 / 1024;
        return ($mb >= 10 ? (string) round($mb) : str_replace('.', ',', (string) round($mb, 1))) . "\u{00A0}МБ";
    }
    return max(1, (int) round($bytes / 1024)) . "\u{00A0}КБ";
}

/** Требования к паролю. Возвращает текст ошибки или null. */
function password_problem(string $password, string $username = ''): ?string
{
    if (mb_strlen($password) < 10) {
        return 'Пароль слишком короткий: нужно не меньше 10 символов.';
    }
    if (mb_strlen($password) > 200) {
        return 'Пароль слишком длинный.';
    }
    if ($username !== '' && mb_strtolower($password) === mb_strtolower($username)) {
        return 'Пароль не должен совпадать с логином.';
    }
    $weak = ['1234567890', 'qwertyuiop', 'password12', 'administrator', '0987654321', 'йцукенгшщз'];
    if (in_array(mb_strtolower($password), $weak, true) || preg_match('/^(.)\1+$/u', $password)) {
        return 'Этот пароль слишком простой — его легко подобрать.';
    }
    return null;
}

function username_problem(string $username): ?string
{
    if (!preg_match('/^[A-Za-z0-9._@-]{3,40}$/', $username)) {
        return 'Логин: от 3 до 40 латинских букв, цифр или символов . _ - @';
    }
    return null;
}

function admin_password_hash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/* ---------- Защита форм (CSRF) ---------- */

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $expected = $_SESSION['csrf'] ?? '';
    $given = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($expected) || $expected === '' || !is_string($given) || $given === '') {
        return false;
    }
    if (!hash_equals($expected, $given)) {
        return false;
    }
    // Дополнительно: запрос должен прийти с этого же сайта.
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '' && $origin !== 'null') {
        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $port = parse_url($origin, PHP_URL_PORT);
        if ($port !== null && $port !== false) {
            $originHost .= ':' . $port;
        }
        $host = request_host();
        if ($originHost !== $host && $originHost !== preg_replace('/:(80|443)$/', '', $host)) {
            return false;
        }
    }
    return true;
}

/* ---------- Сообщения после сохранения («Сохранено») ---------- */

function flash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
}

/** @return list<array{type: string, message: string}> */
function flash_take(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? array_values($messages) : [];
}

/** Значения формы, введённые до ошибки (чтобы не набирать заново). */
function old_input(?array $values = null): array
{
    if ($values !== null) {
        $_SESSION['old'] = $values;
        return $values;
    }
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return is_array($old) ? $old : [];
}
