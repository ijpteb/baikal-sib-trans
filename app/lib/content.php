<?php

declare(strict_types=1);

/*
 * Данные для страниц сайта: автопарк, услуги, отзывы, FAQ, контакты…
 */

final class Content
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    private static function cached(string $key, callable $fn): mixed
    {
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = $fn();
        }
        return self::$cache[$key];
    }

    public static function forget(): void
    {
        self::$cache = [];
    }

    /** Автопарк с фотографиями. */
    public static function vehicles(bool $onlyVisible = true): array
    {
        return self::cached('vehicles:' . (int) $onlyVisible, static function () use ($onlyVisible): array {
            $rows = Db::all('SELECT * FROM vehicles' . ($onlyVisible ? ' WHERE is_visible = 1' : '') . ' ORDER BY sort, id');
            if (!$rows) {
                return [];
            }
            $ids = array_map('intval', array_column($rows, 'id'));
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $photos = [];
            foreach (Db::all(
                'SELECT * FROM vehicle_photos WHERE vehicle_id IN (' . $placeholders . ') ORDER BY is_cover DESC, sort, id',
                $ids
            ) as $photo) {
                $photos[(int) $photo['vehicle_id']][] = photo_from_row($photo);
            }
            return array_map(
                static fn (array $row): array => vehicle_hydrate($row, $photos[(int) $row['id']] ?? []),
                $rows
            );
        });
    }

    public static function vehicle(int $id): ?array
    {
        foreach (self::vehicles(false) as $vehicle) {
            if ($vehicle['id'] === $id) {
                return $vehicle;
            }
        }
        return null;
    }

    /** Простые списки: только видимые записи по порядку. */
    public static function list(string $table): array
    {
        $allowed = ['services', 'advantages', 'steps', 'stats', 'reviews', 'partners', 'faq', 'inspection_categories'];
        if (!in_array($table, $allowed, true)) {
            throw new InvalidArgumentException('Неизвестный список: ' . $table);
        }
        return self::cached('list:' . $table, static function () use ($table): array {
            $rows = Db::all('SELECT * FROM ' . $table . ' WHERE is_visible = 1 ORDER BY sort, id');
            if ($table === 'partners') {
                foreach ($rows as &$row) {
                    $row['logo'] = json_decode_array($row['logo']);
                }
                unset($row);
            }
            if ($table === 'reviews') {
                usort($rows, static fn (array $a, array $b): int => [(int) $a['sort'], (int) $a['id']] <=> [(int) $b['sort'], (int) $b['id']]);
            }
            return $rows;
        });
    }

    /** Данные для калькулятора: только машины, у которых указана хотя бы одна цена. */
    public static function calculatorVehicles(): array
    {
        $out = [];
        foreach (self::vehicles() as $v) {
            if ($v['price_on_request'] || !$v['has_prices']) {
                continue;
            }
            $out[] = [
                'id' => $v['id'],
                'name' => $v['name'],
                'seats' => $v['seats'],
                'hour' => $v['price_hour'],
                'minHours' => $v['min_hours'] ?: 1,
                'km' => $v['price_km'],
                'transfer' => $v['price_transfer'],
                'delivery' => $v['delivery_fee'],
            ];
        }
        return $out;
    }

    public static function calculatorVisible(): bool
    {
        return (bool) setting('calculator.enabled', true) && self::calculatorVehicles() !== [];
    }
}

function photo_from_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'file' => (string) $row['file'],
        'ext' => in_array($row['ext'] ?? 'webp', ['webp', 'jpg', 'png'], true) ? (string) ($row['ext'] ?? 'webp') : 'webp',
        'widths' => array_values(array_map('intval', json_decode_array($row['widths']))),
        'w' => (int) $row['width'],
        'h' => (int) $row['height'],
        'alt' => (string) $row['alt'],
        'is_cover' => (bool) $row['is_cover'],
        'sort' => (int) $row['sort'],
    ];
}

function vehicle_hydrate(array $row, array $photos): array
{
    $intOrNull = static fn ($v): ?int => $v === null || $v === '' ? null : (int) $v;
    $v = [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'type' => (string) $row['type'],
        'type_label' => vehicle_types()[$row['type']] ?? '',
        'seats' => (int) $row['seats'],
        'features' => array_values(array_filter(
            json_decode_array($row['features']),
            static fn ($f): bool => is_string($f) && isset(vehicle_features()[$f])
        )),
        'custom_features' => array_values(array_filter(
            array_map('strval', json_decode_array($row['custom_features'])),
            static fn (string $f): bool => trim($f) !== ''
        )),
        'description' => (string) $row['description'],
        'price_hour' => $intOrNull($row['price_hour']),
        'min_hours' => $intOrNull($row['min_hours']),
        'price_km' => $intOrNull($row['price_km']),
        'price_transfer' => $intOrNull($row['price_transfer']),
        'delivery_fee' => $intOrNull($row['delivery_fee']),
        'price_on_request' => (bool) $row['price_on_request'],
        'is_visible' => (bool) $row['is_visible'],
        'is_demo' => (bool) $row['is_demo'],
        'sort' => (int) $row['sort'],
        'photos' => $photos,
    ];
    $v['has_prices'] = ($v['price_hour'] ?? 0) > 0 || ($v['price_km'] ?? 0) > 0 || ($v['price_transfer'] ?? 0) > 0;
    $v['range'] = seat_range_key($v['seats']);
    $v['price_label'] = vehicle_price_label($v);
    return $v;
}

/** Подпись цены для карточки: «от 2 500 ₽/час», «Цена по запросу» или null. */
function vehicle_price_label(array $v): ?string
{
    if ($v['price_on_request']) {
        return 'Цена по запросу';
    }
    if (($v['price_hour'] ?? 0) > 0) {
        return 'от ' . money((int) $v['price_hour']) . '/час';
    }
    if (($v['price_km'] ?? 0) > 0) {
        return 'от ' . money((int) $v['price_km']) . '/км';
    }
    if (($v['price_transfer'] ?? 0) > 0) {
        return 'трансфер от ' . money((int) $v['price_transfer']);
    }
    return null;
}

/** Телефоны компании: основной первым. */
function company_phones(): array
{
    $phones = [];
    foreach ((array) setting('contacts.phones', []) as $p) {
        if (!is_array($p)) {
            continue;
        }
        $number = trim((string) ($p['number'] ?? ''));
        if ($number === '' || phone_digits($number) === '') {
            continue;
        }
        $phones[] = [
            'number' => $number,
            'label' => trim((string) ($p['label'] ?? '')),
            'primary' => !empty($p['primary']),
            'href' => phone_href($number),
        ];
    }
    usort($phones, static fn (array $a, array $b): int => (int) $b['primary'] <=> (int) $a['primary']);
    return $phones;
}

function primary_phone(): ?array
{
    return company_phones()[0] ?? null;
}

/** Часы работы диспетчера одной строкой (или пустая строка). */
function dispatcher_hours(): string
{
    if (setting('contacts.dispatcher_247')) {
        return 'Круглосуточно';
    }
    return trim((string) setting('contacts.dispatcher_hours', ''));
}

/** Реквизиты: только заполненные поля, с подписями. */
function company_requisites(): array
{
    $labels = [
        'full_name' => 'Полное наименование',
        'inn' => 'ИНН',
        'kpp' => 'КПП',
        'ogrn' => 'ОГРН',
        'legal_address' => 'Юридический адрес',
        'bank' => 'Банк',
        'account' => 'Р/с',
        'corr_account' => 'К/с',
        'bik' => 'БИК',
    ];
    $out = [];
    foreach ($labels as $key => $label) {
        $value = trim((string) setting('requisites.' . $key, ''));
        if ($value !== '') {
            $out[$key] = ['label' => $label, 'value' => $value];
        }
    }
    return $out;
}
