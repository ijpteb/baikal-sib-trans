<?php

declare(strict_types=1);

/*
 * Вход в админку. Не больше 5 неудачных попыток за 15 минут с одного адреса.
 */

if (admin_user() !== null) {
    redirect('/admin');
}

$ip = client_ip();
$wait = login_wait_seconds($ip);
$errors = [];
$username = '';
$notice = '';
if (isset($_GET['bye'])) {
    $notice = 'Вы вышли из админки.';
} elseif (!empty($_SESSION['after_login'])) {
    $notice = 'Войдите, чтобы продолжить.';
}

if (is_post()) {
    $username = str_line($_POST['username'] ?? '', 40);
    $password = (string) ($_POST['password'] ?? '');

    if ($wait > 0) {
        $errors['form'] = 'Слишком много неудачных попыток. Попробуйте снова через ' . max(1, (int) ceil($wait / 60)) . ' мин.';
    } else {
        $user = $username !== '' ? Db::one('SELECT * FROM users WHERE username = ?', [$username]) : null;
        // Если логина нет, всё равно проверяем пароль (по образцу) — чтобы по времени ответа нельзя было угадать логин.
        $hash = $user !== null ? (string) $user['password_hash'] : '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $valid = password_verify($password, $hash) && $user !== null;

        if ($valid) {
            if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
                $user['password_hash'] = admin_password_hash($password);
                Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [$user['password_hash'], (int) $user['id']]);
            }
            login_clear_failures($ip);
            $after = (string) ($_SESSION['after_login'] ?? '');
            admin_login($user);
            $safe = preg_match('#^/admin(?:/[A-Za-z0-9/_-]*)?(?:\?[A-Za-z0-9=&_%-]*)?$#', $after) === 1;
            redirect($safe ? $after : '/admin');
        }

        login_register_failure($ip);
        $wait = login_wait_seconds($ip);
        if ($wait > 0) {
            $errors['form'] = 'Неверный логин или пароль. Вход временно заблокирован на ' . max(1, (int) ceil($wait / 60)) . ' мин.';
        } else {
            $used = (int) Db::value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?', [$ip, time() - LOGIN_WINDOW_SECONDS]);
            $left = max(0, LOGIN_MAX_ATTEMPTS - $used);
            $errors['form'] = 'Неверный логин или пароль.'
                . ($left <= 2 ? ' Осталось попыток: ' . $left . ', потом вход заблокируется на 15 минут.' : '');
        }
    }
}

admin_page('login', [
    'errors' => $errors,
    'username' => $username,
    'wait' => $wait,
    'notice' => $notice,
], ['title' => 'Вход', 'bare' => true], $errors ? 422 : 200);
