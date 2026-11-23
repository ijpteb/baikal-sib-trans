<?php

declare(strict_types=1);

/*
 * Проверка и подготовка данных из форм админки.
 * Каждая функция возвращает очищенные значения и список ошибок (поле → текст ошибки).
 */

/* ---------- Машина ---------- */

/** Пустая карточка новой машины. */
function admin_vehicle_defaults(): array
{
    return [
        'id' => 0,
        'name' => '',
        'type' => 'bus',
        'seats' => '',
        'features' => ['ac'],
        'custom_features' => [],
        'description' => '',
        'price_hour' => null,
        'min_hours' => null,
        'price_km' => null,
        'price_transfer' => null,
        'delivery_fee' => null,
        'price_on_request' => false,
        'is_visible' => true,
        'is_demo' => false,
        'photos' => [],
    ];
}

/** Цена из формы: пусто или 0 → нет цены. */
/**
 * Цена из поля формы: «2 500», «2500 ₽», «2500,00» → 2500; пусто или 0 → null.
 * Что-то другое («от 2000», «2000–3000», «договорная») — ошибка: лучше переспросить, чем исказить цену.
 * При ошибке возвращает введённый текст, чтобы показать его в форме рядом с подсказкой.
 */
function admin_price_input(mixed $raw, array &$errors, string $errorKey, int $max = 10000000): int|string|null
{
    $text = trim(is_scalar($raw) ? (string) $raw : '');
    $s = mb_strtolower(preg_replace('/[\s\x{00A0}\x{2009}\x{202F}]+/u', '', $text) ?? '');
    $s = preg_replace('/(?:₽|руб\.?|р\.?)$/u', '', $s) ?? '';
    if ($s === '' || $s === '—' || $s === '-') {
        return null;
    }
    if (!preg_match('/^\d{1,9}(?:[.,]\d{1,2})?$/', $s)) {
        $errors[$errorKey] = 'Только число, например 2500 — без слов и диапазонов.';
        return $text;
    }
    $n = (int) round((float) str_replace(',', '.', $s));
    if ($n > $max) {
        $errors[$errorKey] = 'Слишком большая сумма — проверьте число.';
        return $text;
    }
    return $n > 0 ? $n : null;
}

/**
 * @return array{0: array, 1: list<array>, 2: array<string, string>} [поля машины, фото, ошибки]
 */
function admin_vehicle_from_post(array $post): array
{
    $errors = [];
    $name = str_line($post['name'] ?? '', 120);
    if ($name === '') {
        $errors['name'] = 'Укажите название, например: «Mercedes Sprinter, 20 мест».';
    }
    $type = (string) ($post['type'] ?? 'bus');
    if (!isset(vehicle_types()[$type])) {
        $type = 'bus';
    }
    $seatsRaw = $post['seats'] ?? '';
    $seats = int_or_null($seatsRaw, 0, 999);
    if ($seats === null || $seats < 1 || $seats > 120) {
        $errors['seats'] = 'Укажите число пассажирских мест — от 1 до 120.';
    }
    $features = array_values(array_intersect(array_keys(vehicle_features()), array_map('strval', (array) ($post['features'] ?? []))));
    $custom = array_slice(array_map(
        static fn (string $line): string => mb_substr($line, 0, 40),
        lines_list(str_clean($post['custom_features'] ?? '', 800))
    ), 0, 8);

    $data = [
        'name' => $name,
        'type' => $type,
        'seats' => $seats ?? 0,
        'features' => $features,
        'custom_features' => $custom,
        'description' => str_clean($post['description'] ?? '', 600),
        'price_hour' => admin_price_input($post['price_hour'] ?? '', $errors, 'price_hour'),
        'min_hours' => null,
        'price_km' => admin_price_input($post['price_km'] ?? '', $errors, 'price_km'),
        'price_transfer' => admin_price_input($post['price_transfer'] ?? '', $errors, 'price_transfer'),
        'delivery_fee' => admin_price_input($post['delivery_fee'] ?? '', $errors, 'delivery_fee'),
        'price_on_request' => bool_from($post['price_on_request'] ?? '0'),
        'is_visible' => bool_from($post['is_visible'] ?? '0'),
    ];
    $minHours = int_or_null($post['min_hours'] ?? '', 0, 999);
    if ($minHours !== null && $minHours > 0) {
        if ($minHours > 24) {
            $errors['min_hours'] = 'Минимальный заказ — не больше 24 часов.';
        }
        $data['min_hours'] = min(24, $minHours);
    }

    $photos = [];
    $tokens = array_values((array) ($post['photo_token'] ?? []));
    $alts = array_values((array) ($post['photo_alt'] ?? []));
    foreach ($tokens as $i => $token) {
        $img = image_from_token((string) $token);
        if ($img === null || $img['ext'] === 'svg' || !$img['widths']) {
            continue;
        }
        $img['alt'] = str_line($alts[$i] ?? '', 150);
        $photos[] = $img;
    }
    if (count($photos) > 12) {
        $errors['photos'] = 'Не больше 12 фото у одной машины.';
        $photos = array_slice($photos, 0, 12);
    }
    return [$data, $photos, $errors];
}

/** Сохраняет машину вместе с фото. Возвращает id. */
function admin_vehicle_save(?int $id, array $data, array $photos): int
{
    return (int) Db::transaction(static function () use ($id, $data, $photos): int {
        $row = $data;
        $row['features'] = json_store($data['features']);
        $row['custom_features'] = json_store($data['custom_features']);
        $row['price_on_request'] = $data['price_on_request'] ? 1 : 0;
        $row['is_visible'] = $data['is_visible'] ? 1 : 0;
        $row['is_demo'] = 0; // после сохранения карточка считается настоящей
        $row['updated_at'] = time();
        if ($id === null) {
            $row['created_at'] = time();
            $row['sort'] = Db::nextSort('vehicles');
            $id = Db::insert('vehicles', $row);
        } else {
            Db::update('vehicles', $id, $row);
        }
        Db::run('DELETE FROM vehicle_photos WHERE vehicle_id = ?', [$id]);
        foreach ($photos as $i => $photo) {
            Db::insert('vehicle_photos', [
                'vehicle_id' => $id,
                'file' => $photo['file'],
                'ext' => $photo['ext'],
                'widths' => json_store($photo['widths']),
                'width' => $photo['w'],
                'height' => $photo['h'],
                'alt' => $photo['alt'] ?? '',
                'is_cover' => $i === 0 ? 1 : 0,
                'sort' => ($i + 1) * 10,
                'created_at' => time(),
            ]);
        }
        return $id;
    });
}

/* ---------- Контакты и мессенджеры ---------- */

/** Число из координат: «52,2803» → «52.2803». */
function admin_coordinate(string $value, float $limit): ?string
{
    $value = str_replace(',', '.', trim($value));
    if ($value === '') {
        return '';
    }
    if (!is_numeric($value) || abs((float) $value) > $limit) {
        return null;
    }
    return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
}

/**
 * @return array{0: array, 1: array, 2: array<string, string>, 3: array<string, string>} [контакты, мессенджеры, ошибки, предупреждения]
 */
function admin_contacts_from_post(array $post): array
{
    $errors = [];
    $warnings = [];

    $phones = [];
    $primary = (int) ($post['phone_primary'] ?? 0);
    foreach (array_values((array) ($post['phones'] ?? [])) as $i => $row) {
        if (!is_array($row) || $i > 5) {
            continue;
        }
        $number = str_line($row['number'] ?? '', 40);
        $label = str_line($row['label'] ?? '', 40);
        if ($number === '') {
            continue;
        }
        $digits = phone_digits($number);
        if (strlen($digits) < 11 || strlen($digits) > 15) {
            // Номер строки — как при повторном показе формы (пустые строки там убраны).
            $errors['phones.' . count($phones)] = 'Номер выглядит неполным. Укажите его с кодом: +7 952 622-11-44 или +7 (3952) 62-22-70.';
        }
        $phones[] = ['number' => $number, 'label' => $label, 'primary' => $i === $primary, 'row' => $i];
    }
    if (!$phones) {
        $errors['phones.0'] = 'Укажите хотя бы один телефон — без него посетители не смогут позвонить.';
    } elseif (!array_filter($phones, static fn (array $p): bool => $p['primary'])) {
        $phones[0]['primary'] = true;
    }

    $email = str_line($post['email'] ?? '', 120);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Адрес почты выглядит неверно. Пример: info@baikalst.ru';
    }

    // Координаты можно вставить одной строкой в первое поле: «52.281, 104.297»
    $latRaw = str_line($post['map_lat'] ?? '', 40);
    $lngRaw = str_line($post['map_lng'] ?? '', 40);
    if ($lngRaw === '' && preg_match('/^\s*(-?\d{1,2}[.,]\d+)\s*[,;\s]\s*(-?\d{1,3}[.,]\d+)\s*$/', $latRaw, $m)) {
        $latRaw = $m[1];
        $lngRaw = $m[2];
    }
    $lat = admin_coordinate($latRaw, 90);
    $lng = admin_coordinate($lngRaw, 180);
    if ($lat === null) {
        $errors['map_lat'] = 'Широта — число от −90 до 90, например 52.2812.';
    }
    if ($lng === null) {
        $errors['map_lng'] = 'Долгота — число от −180 до 180, например 104.2973.';
    }
    if ($lat !== null && $lng !== null && ($lat === '') !== ($lng === '')) {
        $errors[$lat === '' ? 'map_lat' : 'map_lng'] = 'Укажите обе координаты или оставьте оба поля пустыми.';
    }

    $contacts = [
        'phones' => array_map(static fn (array $p): array => ['number' => $p['number'], 'label' => $p['label'], 'primary' => $p['primary']], $phones),
        'email' => $email,
        'office_address' => str_line($post['office_address'] ?? '', 200),
        'inspection_address' => str_line($post['inspection_address'] ?? '', 200),
        'city' => str_line($post['city'] ?? '', 60),
        'region' => str_line($post['region'] ?? '', 80),
        'map_lat' => $lat ?? $latRaw,
        'map_lng' => $lng ?? $lngRaw,
        'map_zoom' => max(10, min(19, (int) ($post['map_zoom'] ?? 16))),
        'dispatcher_247' => bool_from($post['dispatcher_247'] ?? '0'),
        'dispatcher_hours' => str_line($post['dispatcher_hours'] ?? '', 120),
        'office_hours' => str_line($post['office_hours'] ?? '', 120),
    ];
    if ($contacts['office_address'] === '') {
        $errors['office_address'] = 'Укажите адрес офиса — он нужен для карты и поисковиков.';
    }
    if ($contacts['city'] === '') {
        $contacts['city'] = 'Иркутск';
    }

    $messengers = [];
    foreach (messenger_list() as $key => $meta) {
        $row = (array) ($post['messengers'][$key] ?? []);
        $enabled = bool_from($row['enabled'] ?? '0');
        $value = str_line($row['value'] ?? '', 300);
        $error = messenger_value_error($key, $value);
        if ($error !== null) {
            $errors['messengers.' . $key] = $error;
        } elseif ($enabled && $value === '') {
            $warnings['messengers.' . $key] = 'Пока ссылки нет, ' . $meta['label'] . ' не показывается на сайте.';
        }
        // Сохраняем ссылку MAX в едином виде
        if ($error === null && $key === 'max' && $value !== '') {
            $value = max_link_normalize($value) ?? $value;
        }
        $messengers[$key] = ['enabled' => $enabled, 'value' => $value];
    }
    return [$contacts, $messengers, $errors, $warnings];
}

/* ---------- Реквизиты ---------- */

function admin_requisites_from_post(array $post): array
{
    $errors = [];
    $values = [
        'full_name' => str_line($post['full_name'] ?? '', 200),
        'inn' => preg_replace('/\D+/', '', (string) ($post['inn'] ?? '')) ?? '',
        'kpp' => preg_replace('/\D+/', '', (string) ($post['kpp'] ?? '')) ?? '',
        'ogrn' => preg_replace('/\D+/', '', (string) ($post['ogrn'] ?? '')) ?? '',
        'legal_address' => str_line($post['legal_address'] ?? '', 250),
        'bank' => str_line($post['bank'] ?? '', 200),
        'account' => preg_replace('/\D+/', '', (string) ($post['account'] ?? '')) ?? '',
        'corr_account' => preg_replace('/\D+/', '', (string) ($post['corr_account'] ?? '')) ?? '',
        'bik' => preg_replace('/\D+/', '', (string) ($post['bik'] ?? '')) ?? '',
    ];
    $rules = [
        'inn' => [[10, 12], 'ИНН — 10 цифр у организации (12 — у ИП).'],
        'kpp' => [[9], 'КПП — 9 цифр.'],
        'ogrn' => [[13, 15], 'ОГРН — 13 цифр (ОГРНИП — 15).'],
        'account' => [[20], 'Расчётный счёт — 20 цифр.'],
        'corr_account' => [[20], 'Корреспондентский счёт — 20 цифр.'],
        'bik' => [[9], 'БИК — 9 цифр.'],
    ];
    foreach ($rules as $key => [$lengths, $message]) {
        if ($values[$key] !== '' && !in_array(strlen($values[$key]), $lengths, true)) {
            $errors[$key] = $message;
        }
    }
    return [$values, $errors];
}

/* ---------- Тексты, логотип, SEO ---------- */

/** Вкладки страницы «Тексты, логотип, SEO». */
function admin_text_tabs(): array
{
    return [
        'site' => 'Название и логотип',
        'hero' => 'Первый экран',
        'about' => 'О компании',
        'seo' => 'Поисковики и статистика',
        'privacy' => 'Политика конфиденциальности',
    ];
}

/**
 * @return array{0: array<string, array>, 1: array<string, string>} [группа → значения, ошибки]
 */
function admin_texts_from_post(string $tab, array $post): array
{
    $errors = [];
    $groups = [];
    $image = static fn (string $key): array => image_from_token((string) ($post[$key] ?? '')) ?? [];

    switch ($tab) {
        case 'site':
            $groups['site'] = [
                'name' => str_line($post['name'] ?? '', 60),
                'company' => str_line($post['company'] ?? '', 120),
                'tagline' => str_line($post['tagline'] ?? '', 60),
                'footer_text' => str_clean($post['footer_text'] ?? '', 300),
                'logo' => $image('logo'),
                'favicon' => $image('favicon'),
                'og_image' => $image('og_image'),
            ];
            if ($groups['site']['name'] === '') {
                $errors['name'] = 'Укажите название — оно видно в шапке и во вкладке браузера.';
            }
            if ($groups['site']['company'] === '') {
                $errors['company'] = 'Укажите название организации, например: ООО «Байкал Сиб-Транс».';
            }
            break;

        case 'hero':
            $points = array_slice(array_map(static fn (string $l): string => mb_substr($l, 0, 60), lines_list(str_clean($post['points'] ?? '', 400))), 0, 4);
            $groups['hero'] = [
                'title' => str_line($post['title'] ?? '', 120),
                'subtitle' => str_clean($post['subtitle'] ?? '', 300, false),
                'points' => implode("\n", $points),
                'photo' => $image('photo'),
                'photo_alt' => str_line($post['photo_alt'] ?? '', 150),
            ];
            if ($groups['hero']['title'] === '') {
                $errors['title'] = 'Заголовок первого экрана обязателен — это первое, что видит посетитель.';
            }
            break;

        case 'about':
            $groups['about'] = [
                'title' => str_line($post['title'] ?? '', 80),
                'text' => str_clean($post['text'] ?? '', 1200),
            ];
            break;

        case 'seo':
            $metrika = preg_replace('/\D+/', '', (string) ($post['metrika_id'] ?? '')) ?? '';
            if ($metrika !== '' && (strlen($metrika) < 5 || strlen($metrika) > 12)) {
                $errors['metrika_id'] = 'Номер счётчика Метрики — от 5 до 12 цифр, например 98765432.';
            }
            $verification = trim((string) ($post['yandex_verification'] ?? ''));
            // Можно вставить весь тег <meta name="yandex-verification" content="…"> — возьмём код из него.
            if (preg_match('/content=["\']?([A-Za-z0-9_-]+)/', $verification, $m)) {
                $verification = $m[1];
            }
            if ($verification !== '' && !preg_match('/^[A-Za-z0-9_-]{4,64}$/', $verification)) {
                $errors['yandex_verification'] = 'Код подтверждения — латинские буквы и цифры. Можно вставить весь тег из Яндекс Вебмастера.';
            }
            $groups['seo'] = [
                'home_title' => str_line($post['home_title'] ?? '', 120),
                'home_description' => str_line($post['home_description'] ?? '', 300),
                'tehosmotr_title' => str_line($post['tehosmotr_title'] ?? '', 120),
                'tehosmotr_description' => str_line($post['tehosmotr_description'] ?? '', 300),
                'privacy_title' => str_line($post['privacy_title'] ?? '', 120),
                'privacy_description' => str_line($post['privacy_description'] ?? '', 300),
                'metrika_id' => $metrika,
                'cookie_notice' => bool_from($post['cookie_notice'] ?? '0'),
                'yandex_verification' => $verification,
            ];
            foreach (['home_title', 'tehosmotr_title', 'privacy_title'] as $key) {
                if ($groups['seo'][$key] === '') {
                    $errors[$key] = 'Заголовок для поисковиков обязателен.';
                }
            }
            $url = str_line($post['url'] ?? '', 120);
            if ($url !== '') {
                $normalized = admin_normalize_url($url);
                $parts = $normalized !== null ? parse_url($normalized) : null;
                if ($normalized === null || (!empty($parts['path']) && $parts['path'] !== '/') || !empty($parts['query'])) {
                    $errors['url'] = 'Укажите только адрес сайта, без страниц: https://baikalst.ru';
                } else {
                    $url = strtolower(rtrim($normalized, '/'));
                }
            }
            $groups['site'] = ['url' => $url];
            break;

        case 'privacy':
            $groups['privacy'] = [
                'text' => str_clean($post['text'] ?? '', 30000),
                'reviewed' => bool_from($post['reviewed'] ?? '0'),
            ];
            if (trim($groups['privacy']['text']) === '') {
                $errors['text'] = 'Текст политики не может быть пустым. Чтобы вернуть стандартный текст, нажмите «Вернуть исходный текст».';
            }
            break;
    }
    return [$groups, $errors];
}

/* ---------- Техосмотр ---------- */

/**
 * @return array{0: array, 1: array<int, array>, 2: array<string, string>} [настройки страницы, категории, ошибки]
 */
function admin_inspection_from_post(array $post): array
{
    $errors = [];
    $page = [
        'title' => str_line($post['title'] ?? '', 120),
        'intro' => str_clean($post['intro'] ?? '', 600),
        'hours' => str_clean($post['hours'] ?? '', 300),
        'booking_text' => str_clean($post['booking_text'] ?? '', 300),
        'documents' => implode("\n", array_slice(lines_list(str_clean($post['documents'] ?? '', 1500)), 0, 12)),
        'documents_reviewed' => bool_from($post['documents_reviewed'] ?? '0'),
    ];
    if ($page['title'] === '') {
        $errors['title'] = 'Укажите заголовок страницы.';
    }
    $categories = [];
    foreach ((array) ($post['cats'] ?? []) as $id => $row) {
        if (!is_array($row) || (int) $id <= 0) {
            continue;
        }
        $title = str_line($row['title'] ?? '', 80);
        if ($title === '') {
            $errors['cats.' . (int) $id] = 'Название категории не может быть пустым.';
        }
        $categories[(int) $id] = [
            'title' => $title,
            'description' => str_line($row['description'] ?? '', 200),
            'price' => admin_price_input($row['price'] ?? '', $errors, 'cats.' . (int) $id . '.price', 1000000),
            'price_from' => bool_from($row['price_from'] ?? '0') ? 1 : 0,
            'is_visible' => bool_from($row['is_visible'] ?? '0') ? 1 : 0,
        ];
    }
    return [$page, $categories, $errors];
}

/* ---------- Цены (калькулятор) ---------- */

/**
 * @return array{0: array, 1: array<int, array>, 2: array<string, string>} [настройки, цены машин, ошибки]
 */
function admin_calculator_from_post(array $post): array
{
    $errors = [];
    $settings = [
        'enabled' => bool_from($post['enabled'] ?? '0'),
        'disclaimer' => str_clean($post['disclaimer'] ?? '', 300),
    ];
    $prices = [];
    foreach ((array) ($post['prices'] ?? []) as $id => $row) {
        if (!is_array($row) || (int) $id <= 0) {
            continue;
        }
        $minHours = int_or_null($row['min_hours'] ?? '', 0, 999);
        if ($minHours !== null && $minHours > 24) {
            $errors['prices.' . (int) $id] = 'Минимальный заказ — не больше 24 часов.';
        }
        $key = 'prices.' . (int) $id . '.';
        $prices[(int) $id] = [
            'price_hour' => admin_price_input($row['price_hour'] ?? '', $errors, $key . 'price_hour'),
            'min_hours' => $minHours !== null && $minHours > 0 ? min(24, $minHours) : null,
            'price_km' => admin_price_input($row['price_km'] ?? '', $errors, $key . 'price_km'),
            'price_transfer' => admin_price_input($row['price_transfer'] ?? '', $errors, $key . 'price_transfer'),
            'delivery_fee' => admin_price_input($row['delivery_fee'] ?? '', $errors, $key . 'delivery_fee'),
            'price_on_request' => bool_from($row['price_on_request'] ?? '0') ? 1 : 0,
        ];
    }
    return [$settings, $prices, $errors];
}

/** Число для поля ввода цены: null → пусто. */
function admin_price_value(mixed $value): string
{
    if (is_string($value) && $value !== '' && !ctype_digit($value)) {
        return $value; // неверный ввод — показываем как есть, рядом с ошибкой
    }
    return $value === null || $value === '' || (int) $value === 0 ? '' : (string) (int) $value;
}
