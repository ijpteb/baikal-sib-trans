<?php

declare(strict_types=1);

/*
 * Ссылки на мессенджеры (MAX, Telegram, WhatsApp).
 * Для мессенджеров, которые умеют принимать готовый текст в ссылке, текст заявки подставляется сразу;
 * для остальных текст копируется в буфер обмена и открывается чат.
 */

/**
 * Включённые мессенджеры, у которых указан корректный контакт.
 *
 * @return list<array{key: string, label: string, icon: string, url: string, prefill: bool, param: string}>
 */
function messengers_enabled(): array
{
    $out = [];
    foreach (messenger_list() as $key => $meta) {
        $config = setting('messengers.' . $key, []);
        if (!is_array($config) || empty($config['enabled'])) {
            continue;
        }
        $link = messenger_link($key, (string) ($config['value'] ?? ''));
        if ($link === null) {
            continue;
        }
        $out[] = [
            'key' => $key,
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'url' => $link['url'],
            'prefill' => $link['prefill'],
            'param' => $link['param'],
        ];
    }
    return $out;
}

/**
 * Ссылка на чат по значению из админки.
 *
 * @return array{url: string, prefill: bool, param: string}|null
 */
function messenger_link(string $key, string $value): ?array
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if ($key === 'telegram') {
        // Готовый текст Telegram подставляет только в личный чат: нужен логин человека, бизнес-аккаунта или бота.
        $user = null;
        if (preg_match('#^(?:https?://)?(?:www\.)?(?:t\.me|telegram\.me|telegram\.dog)/([A-Za-z0-9_]{4,32})/?$#i', $value, $m)) {
            $user = $m[1];
        } elseif (preg_match('#^@?([A-Za-z][A-Za-z0-9_]{3,31})$#', $value, $m)) {
            $user = $m[1];
        }
        if ($user === null || preg_match('/^(joinchat|addstickers|share|proxy|socks|s|c|iv)$/i', $user)) {
            return null;
        }
        return ['url' => 'https://t.me/' . $user, 'prefill' => true, 'param' => 'text'];
    }

    if ($key === 'whatsapp') {
        $digits = phone_digits($value);
        if (!preg_match('/^[1-9]\d{10,14}$/', $digits)) {
            return null;
        }
        return ['url' => 'https://wa.me/' . $digits, 'prefill' => true, 'param' => 'text'];
    }

    if ($key === 'max') {
        $url = max_link_normalize($value);
        // В MAX нельзя передать готовый текст в ссылке на конкретный чат — текст копируется в буфер обмена.
        return $url === null ? null : ['url' => $url, 'prefill' => false, 'param' => ''];
    }

    return null;
}

/**
 * Ссылка MAX в едином виде https://max.ru/…
 * Подходят: профиль https://max.ru/u/<код>, бот или канал https://max.ru/<имя>, приглашение https://max.ru/join/<код>.
 * Принимает также ссылки без https://, с web.max.ru, max://, «@имя». Регистр букв в коде ссылки сохраняется.
 */
function max_link_normalize(string $raw): ?string
{
    $s = preg_replace('/\s+/u', '', $raw) ?? '';
    if ($s === '' || preg_match('/^\+?[\d()\-]{10,20}$/', $s)) {
        return null; // пусто или номер телефона — в MAX нет ссылок по номеру
    }
    if (preg_match('/^@?([A-Za-z][A-Za-z0-9_]{1,63})$/', $s, $m)) {
        $s = 'https://max.ru/' . $m[1];
    }
    if (stripos($s, 'max://') === 0) {
        $s = 'https://' . substr($s, 6);
    } elseif (!preg_match('#^https?://#i', $s)) {
        $s = 'https://' . ltrim($s, '/');
    }
    $parts = parse_url($s);
    if (!is_array($parts) || !isset($parts['host'])) {
        return null;
    }
    if (!in_array(strtolower($parts['host']), ['max.ru', 'www.max.ru', 'web.max.ru'], true)) {
        return null;
    }
    $path = ltrim(trim((string) ($parts['path'] ?? ''), '/'), '@');
    if ($path === '' || preg_match('/^[+-]?\d+$/', $path)) {
        return null;
    }
    $reserved = ['u', 'join', 'joincall', 'c', 'stickerset', 'download', 'go', 'legal', 'index', '_app', '_api_'];
    if (preg_match('#^u/[A-Za-z0-9_-]{4,200}$#', $path) || preg_match('#^join/[A-Za-z0-9_-]{4,200}$#', $path)) {
        $kind = 'link';
    } elseif (preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{1,63}$/', $path) && !in_array(strtolower($path), $reserved, true)) {
        $kind = str_ends_with(strtolower($path), 'bot') ? 'bot' : 'nickname';
    } else {
        return null; // :share, joincall/, c/ и прочие служебные ссылки не подходят
    }
    $url = 'https://max.ru/' . $path;
    parse_str((string) ($parts['query'] ?? ''), $query);
    $start = $query['start'] ?? null;
    if ($kind === 'bot' && is_string($start) && preg_match('/^[A-Za-z0-9_-]{1,128}$/', $start)) {
        $url .= '?start=' . $start;
    }
    return $url;
}

/** Понятная ошибка для админки, если значение не подходит (или null, если всё в порядке). */
function messenger_value_error(string $key, string $value): ?string
{
    if (trim($value) === '') {
        return null;
    }
    if (messenger_link($key, $value) !== null) {
        return null;
    }
    if ($key === 'max' && preg_match('/^\+?[\d\s()\-]{10,20}$/', trim($value))) {
        return 'В MAX нет ссылок по номеру телефона. Вставьте ссылку из приложения MAX: «Контакты» → «+» → «Пригласить по ссылке».';
    }
    return match ($key) {
        'telegram' => 'Укажите имя пользователя Telegram (например, @baikal_trans) или ссылку вида https://t.me/имя. Ссылка на канал или группу не подойдёт.',
        'whatsapp' => 'Укажите номер телефона WhatsApp полностью, с кодом страны: +7 900 000-00-00.',
        'max' => 'Вставьте ссылку из приложения MAX — она выглядит так: https://max.ru/u/… (или https://max.ru/имя_бота).',
        default => 'Неверное значение.',
    };
}
