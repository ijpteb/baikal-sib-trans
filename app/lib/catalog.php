<?php

declare(strict_types=1);

/*
 * Справочники: типы транспорта, удобства в салоне, диапазоны мест, наборы иконок.
 */

function vehicle_types(): array
{
    return [
        'car' => 'Легковой автомобиль',
        'minibus' => 'Микроавтобус',
        'bus' => 'Автобус',
    ];
}

/** Удобства в салоне: ключ → подпись и иконка. */
function vehicle_features(): array
{
    return [
        'ac' => ['label' => 'Кондиционер', 'icon' => 'snowflake'],
        'heating' => ['label' => 'Тёплый салон', 'icon' => 'flame'],
        'luggage' => ['label' => 'Багажное отделение', 'icon' => 'luggage'],
        'usb' => ['label' => 'USB-зарядка', 'icon' => 'usb'],
        'socket' => ['label' => 'Розетка 220 В', 'icon' => 'plug'],
        'multimedia' => ['label' => 'Мультимедиа', 'icon' => 'monitor'],
        'audio' => ['label' => 'Аудиосистема', 'icon' => 'music'],
        'mic' => ['label' => 'Микрофон', 'icon' => 'mic'],
        'wifi' => ['label' => 'Wi-Fi', 'icon' => 'wifi'],
        'recline' => ['label' => 'Откидные кресла', 'icon' => 'armchair'],
        'belts' => ['label' => 'Ремни безопасности', 'icon' => 'shield-check'],
        'child_seat' => ['label' => 'Детское кресло', 'icon' => 'child'],
    ];
}

/** Фильтр автопарка по количеству мест. */
function seat_ranges(): array
{
    return [
        'up-to-8' => ['label' => 'до 8', 'min' => 1, 'max' => 8],
        '9-20' => ['label' => '9–20', 'min' => 9, 'max' => 20],
        '21-35' => ['label' => '21–35', 'min' => 21, 'max' => 35],
        '36-plus' => ['label' => '36+', 'min' => 36, 'max' => 100000],
    ];
}

function seat_range_key(int $seats): string
{
    foreach (seat_ranges() as $key => $range) {
        if ($seats >= $range['min'] && $seats <= $range['max']) {
            return $key;
        }
    }
    return 'up-to-8';
}

/** Иконки, которые можно выбрать в админке для услуг и преимуществ. */
function pickable_icons(): array
{
    return [
        'briefcase', 'users', 'mountain', 'compass', 'camera', 'heart', 'gift', 'graduation-cap',
        'plane', 'train', 'bus', 'minibus', 'car', 'route', 'map-pin', 'presentation', 'ticket',
        'hard-hat', 'wrench', 'clipboard-check', 'shield-check', 'clock', 'calendar', 'armchair',
        'snowflake', 'star', 'award', 'handshake', 'flag', 'globe', 'sun', 'coffee', 'music',
        'phone', 'chat', 'check-circle', 'steering-wheel', 'luggage', 'child', 'sparkles',
    ];
}

/** Мессенджеры, которые можно включить в админке. */
function messenger_list(): array
{
    return [
        'max' => ['label' => 'MAX', 'icon' => 'chat'],
        'telegram' => ['label' => 'Telegram', 'icon' => 'send'],
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'chat-phone'],
    ];
}
