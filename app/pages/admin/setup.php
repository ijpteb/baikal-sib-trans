<?php

declare(strict_types=1);

/*
 * Первая настройка: создание учётной записи администратора.
 * Здесь же — новый пароль, если создан файл data/RESET_PASSWORD.
 * Для защиты нужен код из файла data/SETUP_CODE.txt (его видит только тот, у кого есть доступ к хостингу).
 */

$hasUsers = admin_has_users();
$reset = $hasUsers && password_reset_requested();
if ($hasUsers && !$reset) {
    redirect('/admin/login');
}

$meta = ['title' => $reset ? 'Новый пароль' : 'Настройка админки', 'bare' => true];
$code = setup_code();
if ($code === null) {
    admin_page('setup', ['fatal' => true, 'reset' => $reset, 'errors' => [], 'values' => [], 'wait' => 0], $meta, 500);
}

$ip = 'setup|' . client_ip();
$wait = login_wait_seconds($ip);
$existing = $reset ? Db::one('SELECT username FROM users ORDER BY id LIMIT 1') : null;
$values = ['username' => (string) ($existing['username'] ?? 'admin')];
$errors = [];

if (is_post()) {
    $values['username'] = str_line($_POST['username'] ?? '', 40);
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');
    $given = preg_replace('/\D+/', '', (string) ($_POST['code'] ?? '')) ?? '';

    if ($wait > 0) {
        $errors['form'] = 'Слишком много попыток. Подождите ' . max(1, (int) ceil($wait / 60)) . ' мин. и попробуйте снова.';
    } elseif (!hash_equals($code, $given)) {
        login_register_failure($ip);
        $errors['code'] = 'Код не подходит. Откройте файл SETUP_CODE.txt в папке data и введите 8 цифр из него.';
    } else {
        $problem = username_problem($values['username']);
        if ($problem !== null) {
            $errors['username'] = $problem;
        }
        $problem = password_problem($password, $values['username']);
        if ($problem !== null) {
            $errors['password'] = $problem;
        } elseif (!hash_equals($password, $password2)) {
            $errors['password2'] = 'Пароли не совпадают. Введите пароль ещё раз.';
        }
    }

    if (!$errors) {
        Db::transaction(static function () use ($values, $password): void {
            Db::run('DELETE FROM users');
            Db::insert('users', [
                'username' => $values['username'],
                'password_hash' => admin_password_hash($password),
                'created_at' => time(),
            ]);
        });
        login_clear_failures($ip);
        $resetFileRemoved = password_reset_finish();
        setup_code_clear();
        $user = Db::one('SELECT * FROM users WHERE username = ?', [$values['username']]);
        if ($user !== null) {
            admin_login($user);
        }
        flash('success', $reset
            ? 'Новый пароль сохранён. Вы вошли в админку.'
            : 'Готово! Учётная запись создана. Ниже — список того, что стоит заполнить перед запуском сайта.');
        if (!$resetFileRemoved) {
            flash('error', 'Не получилось удалить файл data/RESET_PASSWORD. Удалите его вручную в файловом менеджере хостинга — пока он есть, пароль можно сбросить заново.');
        }
        redirect('/admin');
    }
}

admin_page('setup', [
    'fatal' => false,
    'reset' => $reset,
    'errors' => $errors,
    'values' => $values,
    'wait' => $wait,
], $meta, $errors ? 422 : 200);
