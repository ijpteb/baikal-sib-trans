<?php

declare(strict_types=1);

/*
 * Смена логина и пароля администратора. Нужен текущий пароль.
 */

$user = admin_user();
$full = Db::one('SELECT * FROM users WHERE id = ?', [(int) $user['id']]);
$values = ['username' => (string) $full['username']];
$errors = [];

if (is_post()) {
    $current = (string) ($_POST['current_password'] ?? '');
    $values['username'] = str_line($_POST['username'] ?? '', 40);
    $new = (string) ($_POST['new_password'] ?? '');
    $new2 = (string) ($_POST['new_password2'] ?? '');
    $ip = 'password|' . client_ip();

    if (login_wait_seconds($ip) > 0) {
        $errors['current_password'] = 'Слишком много неверных попыток. Подождите 15 минут.';
    } elseif (!password_verify($current, (string) $full['password_hash'])) {
        login_register_failure($ip);
        $errors['current_password'] = 'Текущий пароль введён неверно.';
    }
    $problem = username_problem($values['username']);
    if ($problem !== null) {
        $errors['username'] = $problem;
    }
    if ($new !== '' || $new2 !== '') {
        $problem = password_problem($new, $values['username']);
        if ($problem !== null) {
            $errors['new_password'] = $problem;
        } elseif (!hash_equals($new, $new2)) {
            $errors['new_password2'] = 'Пароли не совпадают. Введите новый пароль ещё раз.';
        }
    }

    if (!$errors) {
        $data = ['username' => $values['username']];
        if ($new !== '') {
            $data['password_hash'] = admin_password_hash($new);
        }
        Db::update('users', (int) $full['id'], $data);
        login_clear_failures($ip);
        // Новый пароль закрывает все прежние входы; текущий вход обновляем, чтобы не выкинуло.
        admin_login(Db::one('SELECT * FROM users WHERE id = ?', [(int) $full['id']]));
        flash('success', $new !== '' ? 'Новый пароль сохранён. На других устройствах нужно будет войти заново.' : 'Логин сохранён.');
        redirect('/admin/password');
    }
}

admin_page('password', [
    'values' => $values,
    'errors' => $errors,
], ['title' => 'Логин и пароль', 'section' => 'password'], $errors ? 422 : 200);
