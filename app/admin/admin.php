<?php

declare(strict_types=1);

/*
 * Админка: меню, макет страниц, поля форм, настройки разделов, чек-лист перед запуском.
 * Подключается только для адресов /admin (см. admin_boot в app/lib/auth.php).
 */

/** Ошибка, текст которой можно показать человеку как есть. */
final class UserFacingError extends RuntimeException
{
}

/* ---------- Меню ---------- */

function admin_nav(): array
{
    return [
        '' => [
            'dashboard' => ['label' => 'Главная админки', 'icon' => 'dashboard', 'href' => '/admin'],
        ],
        'Содержимое сайта' => [
            'vehicles' => ['label' => 'Автопарк', 'icon' => 'bus', 'href' => '/admin/vehicles'],
            'services' => ['label' => 'Типы поездок', 'icon' => 'route', 'href' => '/admin/services'],
            'advantages' => ['label' => 'Преимущества', 'icon' => 'award', 'href' => '/admin/advantages'],
            'steps' => ['label' => 'Как мы работаем', 'icon' => 'list', 'href' => '/admin/steps'],
            'stats' => ['label' => 'Цифры о компании', 'icon' => 'sparkles', 'href' => '/admin/stats'],
            'reviews' => ['label' => 'Отзывы', 'icon' => 'quote', 'href' => '/admin/reviews'],
            'partners' => ['label' => 'Нам доверяют', 'icon' => 'handshake', 'href' => '/admin/partners'],
            'faq' => ['label' => 'Вопросы и ответы', 'icon' => 'help-circle', 'href' => '/admin/faq'],
            'inspection' => ['label' => 'Техосмотр', 'icon' => 'clipboard-check', 'href' => '/admin/inspection'],
        ],
        'Настройки' => [
            'contacts' => ['label' => 'Контакты и мессенджеры', 'icon' => 'phone', 'href' => '/admin/contacts'],
            'calculator' => ['label' => 'Цены и калькулятор', 'icon' => 'calculator', 'href' => '/admin/calculator'],
            'texts' => ['label' => 'Тексты, логотип, SEO', 'icon' => 'file-text', 'href' => '/admin/texts'],
            'requisites' => ['label' => 'Реквизиты', 'icon' => 'building', 'href' => '/admin/requisites'],
            'backup' => ['label' => 'Резервная копия', 'icon' => 'download', 'href' => '/admin/backup'],
            'password' => ['label' => 'Логин и пароль', 'icon' => 'lock', 'href' => '/admin/password'],
        ],
    ];
}

/* ---------- Вывод страниц ---------- */

/**
 * Отрисовывает страницу админки и завершает запрос.
 * $meta: title — заголовок вкладки, section — активный пункт меню, bare — без меню (вход, настройка).
 */
function admin_page(string $template, array $vars = [], array $meta = [], int $status = 200): never
{
    $content = view('admin/' . $template, $vars);
    $html = view('admin/layout', ['content' => $content, 'meta' => $meta]);
    $html = str_replace('<!--icon-sprite-->', Icons::sprite(), $html);
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    echo $html;
    exit;
}

/** Ответ для кнопок, которые работают без перезагрузки страницы. */
function admin_wants_json(): bool
{
    return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

/** Заголовок страницы: название, пояснение, кнопки, ссылка «назад». */
function admin_head(string $title, string $lead = '', string $actions = '', ?array $back = null): string
{
    $html = '<header class="page-head">';
    $html .= '<div class="page-head__text">';
    if ($back !== null) {
        $html .= '<a class="page-head__back" href="' . e($back['href']) . '">' . icon('arrow-left') . e($back['label']) . '</a>';
    }
    $html .= '<h1 class="page-head__title">' . e($title) . '</h1>';
    if ($lead !== '') {
        $html .= '<p class="page-head__lead">' . $lead . '</p>';
    }
    $html .= '</div>';
    if ($actions !== '') {
        $html .= '<div class="page-head__actions">' . $actions . '</div>';
    }
    return $html . '</header>';
}

/** Плашка-сообщение: success, error, warning, info. Текст $html уже должен быть безопасным HTML. */
function admin_alert(string $type, string $html, string $extraClass = ''): string
{
    $icons = ['success' => 'check-circle', 'error' => 'alert-circle', 'warning' => 'alert-circle', 'info' => 'info'];
    $role = $type === 'error' ? ' role="alert"' : '';
    return '<div class="' . e(classes('alert', 'alert--' . $type, $extraClass)) . '"' . $role . '>'
        . icon($icons[$type] ?? 'info') . '<div class="alert__body">' . $html . '</div></div>';
}

/** Кнопки внизу формы: «Сохранить» и необязательные дополнительные. */
function admin_savebar(string $label = 'Сохранить', string $extra = ''): string
{
    return '<div class="savebar"><div class="savebar__inner">'
        . '<button class="btn btn--secondary" type="submit" name="action" value="save">' . icon('check') . e($label) . '</button>'
        . $extra
        . '<span class="savebar__hint" data-dirty-hint hidden>Есть несохранённые изменения</span>'
        . '</div></div>';
}

/* ---------- Поля форм ---------- */

function af_id(string $name): string
{
    return 'f-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? '', '-');
}

/** Общая обёртка поля: подпись, подсказка, текст ошибки. */
function af_wrap(string $id, string $label, string $control, array $o): string
{
    $error = (string) ($o['error'] ?? '');
    $classes = classes(
        'field',
        !empty($o['wide']) ? 'field--wide' : null,
        $error !== '' ? 'is-invalid' : null,
        (string) ($o['class'] ?? '')
    );
    $html = '<div class="' . e($classes) . '">';
    $html .= '<label class="field__label" for="' . e($id) . '">' . e($label);
    if (!empty($o['required'])) {
        $html .= ' <span class="req" aria-hidden="true">*</span>';
    }
    if (!empty($o['optional'])) {
        $html .= ' <span class="field__optional">— необязательно</span>';
    }
    $html .= '</label>';
    $html .= $control;
    $html .= af_hint_and_error($id, $o);
    return $html . '</div>';
}

function af_hint_and_error(string $id, array $o): string
{
    $html = '';
    if (!empty($o['hint_html'])) {
        $html .= '<p class="field__hint" id="' . e($id) . '-hint">' . $o['hint_html'] . '</p>';
    } elseif (!empty($o['hint'])) {
        $html .= '<p class="field__hint" id="' . e($id) . '-hint">' . e($o['hint']) . '</p>';
    }
    $error = (string) ($o['error'] ?? '');
    if ($error !== '') {
        $html .= '<p class="field__error" id="' . e($id) . '-error">' . icon('alert-circle') . '<span>' . e($error) . '</span></p>';
    }
    if (!empty($o['warning'])) {
        $html .= '<p class="field__warning">' . icon('info') . '<span>' . e($o['warning']) . '</span></p>';
    }
    return $html;
}

function af_described_by(string $id, array $o): ?string
{
    $ids = [];
    if (!empty($o['hint']) || !empty($o['hint_html'])) {
        $ids[] = $id . '-hint';
    }
    if (!empty($o['error'])) {
        $ids[] = $id . '-error';
    }
    return $ids ? implode(' ', $ids) : null;
}

/** Однострочное поле. Опции: type, hint, error, required, max, placeholder, inputmode, autocomplete, wide, counter, attrs. */
function af_input(string $name, string $label, mixed $value, array $o = []): string
{
    $id = (string) ($o['id'] ?? af_id($name));
    $attributes = array_replace([
        'class' => classes('input', (string) ($o['input_class'] ?? '')),
        'type' => $o['type'] ?? 'text',
        'id' => $id,
        'name' => $name,
        'value' => is_scalar($value) ? (string) $value : '',
        'maxlength' => isset($o['max']) ? (string) $o['max'] : null,
        'required' => !empty($o['required']),
        'placeholder' => $o['placeholder'] ?? null,
        'inputmode' => $o['inputmode'] ?? null,
        'autocomplete' => $o['autocomplete'] ?? 'off',
        'spellcheck' => $o['spellcheck'] ?? null,
        'aria-invalid' => !empty($o['error']) ? 'true' : null,
        'aria-describedby' => af_described_by($id, $o),
        'data-counter' => !empty($o['counter']) ? 'true' : null,
    ], (array) ($o['attrs'] ?? []));
    return af_wrap($id, $label, '<input' . attrs($attributes) . '>', $o);
}

function af_textarea(string $name, string $label, mixed $value, array $o = []): string
{
    $id = (string) ($o['id'] ?? af_id($name));
    $attributes = array_replace([
        'class' => classes('textarea', (string) ($o['input_class'] ?? '')),
        'id' => $id,
        'name' => $name,
        'rows' => (string) ($o['rows'] ?? 4),
        'maxlength' => isset($o['max']) ? (string) $o['max'] : null,
        'required' => !empty($o['required']),
        'placeholder' => $o['placeholder'] ?? null,
        'aria-invalid' => !empty($o['error']) ? 'true' : null,
        'aria-describedby' => af_described_by($id, $o),
        'data-counter' => !empty($o['counter']) ? 'true' : null,
    ], (array) ($o['attrs'] ?? []));
    $text = is_scalar($value) ? (string) $value : '';
    return af_wrap($id, $label, '<textarea' . attrs($attributes) . '>' . e($text) . '</textarea>', $o);
}

/** Флажок. Скрытое поле «0» нужно, чтобы снятая галочка тоже сохранялась. */
function af_check(string $name, string $label, bool $checked, array $o = []): string
{
    $id = (string) ($o['id'] ?? af_id($name));
    $hint = '';
    if (!empty($o['hint'])) {
        $hint = '<span class="check__hint">' . e($o['hint']) . '</span>';
    }
    return '<div class="' . e(classes('field', 'field--check', !empty($o['wide']) ? 'field--wide' : null)) . '">'
        . '<input type="hidden" name="' . e($name) . '" value="0">'
        . '<label class="check check--box" for="' . e($id) . '">'
        . '<input' . attrs(['type' => 'checkbox', 'id' => $id, 'name' => $name, 'value' => '1', 'checked' => $checked] + (array) ($o['attrs'] ?? [])) . '>'
        . '<span class="check__text"><span class="check__label">' . e($label) . '</span>' . $hint . '</span>'
        . '</label>'
        . (!empty($o['error']) ? af_hint_and_error($id, ['error' => $o['error']]) : '')
        . '</div>';
}

/** Выпадающий список. $options: значение → подпись. */
function af_select(string $name, string $label, mixed $value, array $options, array $o = []): string
{
    $id = (string) ($o['id'] ?? af_id($name));
    $html = '<select' . attrs([
        'class' => 'select',
        'id' => $id,
        'name' => $name,
        'required' => !empty($o['required']),
        'aria-invalid' => !empty($o['error']) ? 'true' : null,
        'aria-describedby' => af_described_by($id, $o),
    ]) . '>';
    foreach ($options as $optValue => $optLabel) {
        $html .= '<option value="' . e($optValue) . '"' . ((string) $optValue === (string) $value ? ' selected' : '') . '>' . e($optLabel) . '</option>';
    }
    $html .= '</select>';
    return af_wrap($id, $label, $html, $o);
}

/** Выбор значка из набора (для типов поездок и преимуществ). */
function af_icons(string $name, string $label, string $value, array $o = []): string
{
    $names = admin_icon_names();
    if (!isset($names[$value])) {
        $value = array_key_first($names);
    }
    $html = '<fieldset class="field field--wide"><legend class="field__label">' . e($label) . '</legend><div class="icon-picker">';
    foreach ($names as $icon => $title) {
        $id = af_id($name . '-' . $icon);
        $html .= '<label class="icon-option" for="' . e($id) . '" title="' . e($title) . '">'
            . '<input type="radio" id="' . e($id) . '" name="' . e($name) . '" value="' . e($icon) . '"' . ($icon === $value ? ' checked' : '') . '>'
            . '<span class="icon-option__tile">' . icon($icon) . '</span>'
            . '<span class="visually-hidden">' . e($title) . '</span>'
            . '</label>';
    }
    return $html . '</div>' . af_hint_and_error(af_id($name), $o) . '</fieldset>';
}

/** Значки, которые можно выбрать, с русскими названиями (для подсказки и экранного диктора). */
function admin_icon_names(): array
{
    $titles = [
        'briefcase' => 'Портфель', 'users' => 'Группа людей', 'mountain' => 'Горы', 'compass' => 'Компас',
        'camera' => 'Фотоаппарат', 'heart' => 'Сердце', 'gift' => 'Подарок', 'graduation-cap' => 'Выпускной',
        'plane' => 'Самолёт', 'train' => 'Поезд', 'bus' => 'Автобус', 'minibus' => 'Микроавтобус',
        'car' => 'Легковой автомобиль', 'route' => 'Маршрут', 'map-pin' => 'Метка на карте', 'presentation' => 'Презентация',
        'ticket' => 'Билет', 'hard-hat' => 'Каска', 'wrench' => 'Гаечный ключ', 'clipboard-check' => 'Чек-лист',
        'shield-check' => 'Щит', 'clock' => 'Часы', 'calendar' => 'Календарь', 'armchair' => 'Кресло',
        'snowflake' => 'Снежинка', 'star' => 'Звезда', 'award' => 'Награда', 'handshake' => 'Рукопожатие',
        'flag' => 'Флаг', 'globe' => 'Глобус', 'sun' => 'Солнце', 'coffee' => 'Кофе', 'music' => 'Музыка',
        'phone' => 'Телефон', 'chat' => 'Сообщение', 'check-circle' => 'Галочка', 'steering-wheel' => 'Руль',
        'luggage' => 'Багаж', 'child' => 'Ребёнок', 'sparkles' => 'Звёздочки',
    ];
    $out = [];
    foreach (pickable_icons() as $icon) {
        if (Icons::exists($icon)) {
            $out[$icon] = $titles[$icon] ?? $icon;
        }
    }
    return $out;
}

/**
 * Загрузка одной картинки (логотип, фото первого экрана, значок…).
 * Файл отправляется сразу после выбора; в форму попадает подписанная ссылка на уже обработанную картинку.
 */
function af_image(string $name, string $label, mixed $img, string $purpose, array $o = []): string
{
    $id = (string) ($o['id'] ?? af_id($name));
    $img = is_array($img) ? image_normalize($img) : null;
    $purposeInfo = image_purposes()[$purpose];
    $token = $img !== null ? image_token($img) : '';
    $preview = $img !== null ? image_url($img, 480) : '';
    $shape = (string) ($purposeInfo['shape'] ?? 'wide');
    $hasError = !empty($o['error']);

    $html = '<div class="' . e(classes('field', 'field--wide', 'uploader', 'uploader--' . $shape, $hasError ? 'is-invalid' : null)) . '"'
        . ' data-uploader data-purpose="' . e($purpose) . '">';
    $html .= '<span class="field__label" id="' . e($id) . '-label">' . e($label);
    if (!empty($o['optional'])) {
        $html .= ' <span class="field__optional">— необязательно</span>';
    }
    $html .= '</span>';
    $html .= '<div class="uploader__row">';
    $html .= '<div class="uploader__box">'
        . '<img class="uploader__img" src="' . e($preview) . '" alt=""' . ($img === null ? ' hidden' : '') . ' data-uploader-img>'
        . '<span class="uploader__empty"' . ($img !== null ? ' hidden' : '') . ' data-uploader-empty>' . icon('image') . '<span>Нет картинки</span></span>'
        . '<span class="uploader__busy" hidden data-uploader-busy><span class="spinner" aria-hidden="true"></span><span>Загружаем…</span></span>'
        . '</div>';
    $html .= '<div class="uploader__side">';
    $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($token) . '" data-uploader-value>';
    $html .= '<div class="uploader__actions">'
        . '<label class="btn btn--outline btn--sm uploader__pick">' . icon('upload')
        . '<span data-uploader-pick-label>' . ($img === null ? 'Загрузить' : 'Заменить') . '</span>'
        . '<input class="visually-hidden" type="file" id="' . e($id) . '" accept="' . e(image_accept($purpose)) . '"'
        . ' aria-labelledby="' . e($id) . '-label" aria-describedby="' . e($id) . '-hint" data-uploader-input>'
        . '</label>'
        . '<button class="btn btn--ghost btn--sm uploader__remove" type="button"' . ($img === null ? ' hidden' : '') . ' data-uploader-remove>' . icon('trash') . 'Убрать</button>'
        . '</div>';
    $html .= '<p class="field__hint" id="' . e($id) . '-hint">' . e((string) ($o['hint'] ?? $purposeInfo['hint'])) . '</p>';
    $html .= '<p class="field__error" data-uploader-error' . ($hasError ? '' : ' hidden') . '>' . icon('alert-circle') . '<span>' . e((string) ($o['error'] ?? '')) . '</span></p>';
    $html .= '<noscript><p class="field__hint">Чтобы загружать картинки, включите JavaScript в браузере.</p></noscript>';
    $html .= '</div></div></div>';
    return $html;
}

/* ---------- Простые списки (типы поездок, преимущества, FAQ…) ---------- */

/** Настройки разделов-списков: таблица, подписи, поля формы. */
function admin_tables(): array
{
    return [
        'vehicles' => [
            'table' => 'vehicles',
            'title' => 'Автопарк',
            'lead' => 'Машины в разделе «Автопарк» на главной странице. Порядок здесь — такой же, как на сайте.',
            'add' => 'Добавить машину',
            'new_title' => 'Новая машина',
            'empty' => 'Машин пока нет — добавьте первую.',
            'fields' => [],
        ],
        'services' => [
            'table' => 'services',
            'title' => 'Типы поездок',
            'lead' => 'Карточки в разделе «Для каких поездок». Их названия также появляются в форме заявки — посетитель выбирает тип поездки.',
            'add' => 'Добавить тип поездки',
            'new_title' => 'Новый тип поездки',
            'empty' => 'Типов поездок пока нет.',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Название', 'required' => true, 'max' => 60, 'hint' => 'Коротко, например: «Трансфер в аэропорт».'],
                'text' => ['type' => 'textarea', 'label' => 'Описание', 'max' => 300, 'rows' => 4, 'counter' => true, 'hint' => 'Одно-два предложения.'],
                'icon' => ['type' => 'icon', 'label' => 'Значок'],
            ],
        ],
        'advantages' => [
            'table' => 'advantages',
            'title' => 'Преимущества',
            'lead' => 'Карточки в разделе «Почему выбирают нас». Карточка, в названии которой есть слово «техосмотр», ведёт на страницу техосмотра.',
            'add' => 'Добавить преимущество',
            'new_title' => 'Новое преимущество',
            'empty' => 'Преимуществ пока нет.',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Название', 'required' => true, 'max' => 60],
                'text' => ['type' => 'textarea', 'label' => 'Описание', 'max' => 300, 'rows' => 3, 'counter' => true],
                'icon' => ['type' => 'icon', 'label' => 'Значок'],
            ],
        ],
        'steps' => [
            'table' => 'steps',
            'title' => 'Как мы работаем',
            'lead' => 'Шаги от заявки до поездки. Номера шагов сайт ставит сам — по порядку.',
            'add' => 'Добавить шаг',
            'new_title' => 'Новый шаг',
            'empty' => 'Шагов пока нет — раздел скрыт на сайте.',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Название шага', 'required' => true, 'max' => 40],
                'text' => ['type' => 'textarea', 'label' => 'Пояснение', 'max' => 200, 'rows' => 3, 'counter' => true],
            ],
        ],
        'stats' => [
            'table' => 'stats',
            'title' => 'Цифры о компании',
            'lead' => 'Например: «12 лет на рынке», «40 машин в автопарке». Указывайте только реальные цифры. Пока цифр нет, блок на сайте не показывается.',
            'add' => 'Добавить цифру',
            'new_title' => 'Новая цифра',
            'empty' => 'Цифр пока нет — блок скрыт на сайте.',
            'fields' => [
                'number' => ['type' => 'int', 'label' => 'Число', 'required' => true, 'min' => 0, 'max' => 9999999, 'hint' => 'Только цифры: 12'],
                'suffix' => ['type' => 'text', 'label' => 'После числа', 'max' => 8, 'optional' => true, 'hint' => 'Например: «+» или «%». Можно оставить пустым.'],
                'label' => ['type' => 'text', 'label' => 'Подпись', 'required' => true, 'max' => 60, 'hint' => 'Например: «лет на рынке перевозок».'],
            ],
        ],
        'reviews' => [
            'table' => 'reviews',
            'title' => 'Отзывы',
            'lead' => 'Публикуйте только настоящие отзывы — с разрешения клиента. Пока отзывов нет, раздел на сайте скрыт.',
            'add' => 'Добавить отзыв',
            'new_title' => 'Новый отзыв',
            'empty' => 'Отзывов пока нет — раздел скрыт на сайте.',
            'fields' => [
                'author' => ['type' => 'text', 'label' => 'Имя автора', 'required' => true, 'max' => 80, 'hint' => 'Например: «Анна Петрова» или «Анна П.».'],
                'company' => ['type' => 'text', 'label' => 'Компания или должность', 'max' => 120, 'optional' => true],
                'text' => ['type' => 'textarea', 'label' => 'Текст отзыва', 'required' => true, 'max' => 1200, 'rows' => 6, 'counter' => true],
                'review_date' => ['type' => 'date', 'label' => 'Дата отзыва', 'optional' => true],
            ],
        ],
        'partners' => [
            'table' => 'partners',
            'title' => 'Нам доверяют',
            'lead' => 'Компании-клиенты в блоке «Нам доверяют». Показывайте только тех, кто разрешил. Без логотипа выводится название.',
            'add' => 'Добавить компанию',
            'new_title' => 'Новая компания',
            'empty' => 'Компаний пока нет — блок скрыт на сайте.',
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Название компании', 'required' => true, 'max' => 80],
                'logo' => ['type' => 'image', 'label' => 'Логотип', 'purpose' => 'partner', 'optional' => true],
                'url' => ['type' => 'url', 'label' => 'Сайт компании', 'max' => 300, 'optional' => true, 'hint' => 'Если указать, логотип станет ссылкой. Например: https://example.ru'],
            ],
        ],
        'faq' => [
            'table' => 'faq',
            'title' => 'Вопросы и ответы',
            'lead' => 'Раздел «Частые вопросы» на главной. Ответы отмечены как «не проверено», пока вы их не откроете и не сохраните.',
            'add' => 'Добавить вопрос',
            'new_title' => 'Новый вопрос',
            'empty' => 'Вопросов пока нет — раздел скрыт на сайте.',
            'fields' => [
                'question' => ['type' => 'text', 'label' => 'Вопрос', 'required' => true, 'max' => 200],
                'answer' => ['type' => 'textarea', 'label' => 'Ответ', 'required' => true, 'max' => 1500, 'rows' => 6, 'counter' => true, 'hint' => 'Пустая строка — новый абзац. **Слово** в двойных звёздочках будет жирным.'],
            ],
        ],
    ];
}

function admin_table(string $section): ?array
{
    return admin_tables()[$section] ?? null;
}

/** Заголовок и подпись строки списка. */
function admin_row_summary(string $section, array $row): array
{
    $title = '';
    $meta = '';
    $badges = [];
    switch ($section) {
        case 'services':
        case 'advantages':
        case 'steps':
            $title = (string) $row['title'];
            $meta = str_excerpt((string) $row['text'], 110);
            break;
        case 'stats':
            $title = trim($row['number'] . (string) $row['suffix']) . ' — ' . $row['label'];
            break;
        case 'reviews':
            $title = (string) $row['author'] . (trim((string) $row['company']) !== '' ? ', ' . $row['company'] : '');
            $meta = str_excerpt((string) $row['text'], 110);
            break;
        case 'partners':
            $title = (string) $row['name'];
            $logo = json_decode_array($row['logo']);
            $meta = !empty($logo['file']) ? 'Логотип загружен' : 'Без логотипа — на сайте показывается название';
            break;
        case 'faq':
            $title = (string) $row['question'];
            $meta = str_excerpt((string) $row['answer'], 110);
            if (!empty($row['needs_review'])) {
                $badges[] = ['label' => 'Не проверено', 'type' => 'warning'];
            }
            break;
    }
    return ['title' => $title, 'meta' => $meta, 'badges' => $badges];
}

/**
 * Проверка и очистка полей формы по описанию из admin_tables().
 *
 * @return array{0: array<string, mixed>, 1: array<string, string>} [значения, ошибки]
 */
function admin_validate_fields(array $fields, array $input): array
{
    $values = [];
    $errors = [];
    foreach ($fields as $key => $f) {
        $raw = $input[$key] ?? '';
        $max = (int) ($f['max'] ?? 255);
        switch ($f['type']) {
            case 'textarea':
                $value = str_clean($raw, $max);
                break;
            case 'int':
                $value = int_or_null($raw, (int) ($f['min'] ?? 0), (int) ($f['max'] ?? 100000000));
                break;
            case 'date':
                $value = str_line($raw, 10);
                if ($value !== '' && !admin_valid_date($value)) {
                    $errors[$key] = 'Укажите дату в формате ДД.ММ.ГГГГ.';
                }
                break;
            case 'url':
                $value = str_line($raw, $max);
                if ($value !== '') {
                    $normalized = admin_normalize_url($value);
                    if ($normalized === null) {
                        $errors[$key] = 'Укажите адрес сайта полностью, например: https://example.ru';
                    } else {
                        $value = $normalized;
                    }
                }
                break;
            case 'icon':
                $value = str_line($raw, 40);
                if (!isset(admin_icon_names()[$value])) {
                    $value = (string) array_key_first(admin_icon_names());
                }
                break;
            case 'image':
                $value = image_from_token((string) $raw) ?? [];
                break;
            default:
                $value = str_line($raw, $max);
        }
        if (!empty($f['required']) && ($value === '' || $value === null || $value === [])) {
            $errors[$key] = 'Заполните это поле.';
        }
        $values[$key] = $value;
    }
    return [$values, $errors];
}

function admin_valid_date(string $ymd): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m)) {
        return false;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) && (int) $m[1] >= 1990 && (int) $m[1] <= 2100;
}

/** «example.ru» → «https://example.ru». Только http(s), без пробелов. */
function admin_normalize_url(string $url): ?string
{
    $url = trim($url);
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    if (preg_match('/\s/', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return null;
    }
    $host = (string) parse_url($url, PHP_URL_HOST);
    if ($host === '' || !str_contains($host, '.')) {
        return null;
    }
    return $url;
}

/* ---------- Чек-лист перед запуском ---------- */

/**
 * Что осталось заполнить или проверить. required = false — желательно, но не обязательно.
 *
 * @return list<array{key: string, done: bool, required: bool, title: string, text: string, href: string, action: string}>
 */
function admin_checklist(): array
{
    $items = [];
    $add = static function (string $key, bool $done, bool $required, string $title, string $text, string $href, string $action = 'Перейти') use (&$items): void {
        $items[] = compact('key', 'done', 'required', 'title', 'text', 'href', 'action');
    };

    $vehicles = Content::vehicles(false);
    $demo = count(array_filter($vehicles, static fn (array $v): bool => $v['is_demo']));
    $visible = array_filter($vehicles, static fn (array $v): bool => $v['is_visible']);
    $noPhotos = count(array_filter($visible, static fn (array $v): bool => !$v['photos']));

    $add('vehicles', $vehicles && $demo === 0, true,
        'Замените примеры машин на ваш автопарк',
        $demo > 0
            ? 'Сейчас на сайте ' . $demo . ' ' . plural_ru($demo, 'машина-пример', 'машины-примера', 'машин-примеров') . '. Укажите настоящие машины: модель, число мест, удобства.'
            : ($vehicles ? 'Готово.' : 'Добавьте хотя бы одну машину.'),
        '/admin/vehicles', 'Открыть автопарк');
    $add('photos', $visible && $noPhotos === 0, true,
        'Добавьте фотографии машин',
        $noPhotos > 0
            ? $noPhotos . ' ' . plural_ru($noPhotos, 'машина показывается', 'машины показываются', 'машин показываются') . ' без фото — с фото заявок обычно больше.'
            : 'Готово.',
        '/admin/vehicles', 'Добавить фото');

    $messengers = messengers_enabled();
    $add('messengers', $messengers !== [], true,
        'Подключите MAX или другой мессенджер',
        $messengers
            ? 'Подключено: ' . implode(', ', array_column($messengers, 'label')) . '.'
            : 'Пока посетители могут только позвонить. Вставьте ссылку на ваш чат в MAX — и заявки можно будет отправлять сообщением.',
        '/admin/contacts#messengers', 'Указать ссылку');

    $hours = dispatcher_hours();
    $add('hours', $hours !== '', true,
        'Укажите часы работы диспетчера',
        $hours !== '' ? 'Сейчас: ' . $hours . '.' : 'Посетителю важно знать, когда ему ответят.',
        '/admin/contacts', 'Указать часы');

    $faqUnchecked = (int) Db::value('SELECT COUNT(*) FROM faq WHERE needs_review = 1');
    $add('faq', $faqUnchecked === 0, true,
        'Проверьте ответы на частые вопросы',
        $faqUnchecked > 0
            ? 'Ответы составлены заранее. Не проверено: ' . $faqUnchecked . '. Откройте каждый вопрос, поправьте ответ и сохраните.'
            : 'Готово.',
        '/admin/faq', 'Проверить');

    $req = company_requisites();
    $add('requisites', isset($req['inn'], $req['ogrn']), true,
        'Заполните реквизиты компании',
        isset($req['inn'], $req['ogrn']) ? 'Готово.' : 'ИНН и ОГРН показываются внизу сайта — так компании больше доверяют.',
        '/admin/requisites', 'Заполнить');

    $add('privacy', (bool) setting('privacy.reviewed', false), true,
        'Проверьте политику конфиденциальности',
        'Текст составлен заранее. Прочитайте его (лучше вместе с юристом) и отметьте, что проверили.',
        '/admin/texts?tab=privacy', 'Проверить');

    $add('inspection_docs', (bool) setting('inspection.documents_reviewed', false), true,
        'Проверьте список документов для техосмотра',
        'Список на странице техосмотра составлен заранее — уточните его.',
        '/admin/inspection', 'Проверить');

    $siteUrl = trim((string) setting('site.url', ''));
    $add('site_url', $siteUrl !== '', true,
        'Укажите адрес сайта',
        $siteUrl !== '' ? 'Сейчас: ' . $siteUrl . '.' : 'Нужен поисковикам и для ссылок, которые отправляют в мессенджерах. Например: https://baikalst.ru',
        '/admin/texts?tab=seo', 'Указать');

    $add('backup', (time() - (int) admin_system('last_backup', 0)) < 30 * 86400, true,
        'Скачайте резервную копию',
        (int) admin_system('last_backup', 0) > 0
            ? 'Последняя копия: ' . date_ru(date('Y-m-d', (int) admin_system('last_backup', 0))) . '. Делайте копию раз в месяц и после больших изменений.'
            : 'Копия ещё не скачивалась. Сохраните её на компьютер — на всякий случай.',
        '/admin/backup', 'Скачать');

    // Желательно, но не обязательно
    $add('prices', Content::calculatorVisible(), false,
        'Укажите цены — на сайте появится калькулятор',
        Content::calculatorVisible() ? 'Калькулятор показан на сайте.' : 'Пока цен нет, калькулятор скрыт, а в карточках машин нет цены.',
        '/admin/calculator', 'Указать цены');

    $inspectionPrices = (int) Db::value('SELECT COUNT(*) FROM inspection_categories WHERE price > 0 AND is_visible = 1');
    $add('inspection_prices', $inspectionPrices > 0, false,
        'Укажите цены техосмотра',
        $inspectionPrices > 0 ? 'Цены указаны.' : 'Сейчас вместо цен написано «Цену уточняйте».',
        '/admin/inspection#categories', 'Указать');

    $lat = trim((string) setting('contacts.map_lat', ''));
    $add('map', $lat !== '', false,
        'Поставьте точную метку на карте',
        $lat !== '' ? 'Метка стоит по координатам.' : 'Сейчас карта ищет офис по адресу. Координаты дадут точную метку.',
        '/admin/contacts#map', 'Указать');

    $reviews = (int) Db::value('SELECT COUNT(*) FROM reviews WHERE is_visible = 1');
    $add('reviews', $reviews > 0, false,
        'Добавьте отзывы клиентов',
        $reviews > 0 ? 'Отзывов на сайте: ' . $reviews . '.' : 'Раздел отзывов скрыт, пока их нет. Только настоящие отзывы — с согласия клиента.',
        '/admin/reviews', 'Добавить');

    $partnersNoLogo = 0;
    foreach (Db::all('SELECT logo FROM partners WHERE is_visible = 1') as $p) {
        if (empty(json_decode_array($p['logo'])['file'])) {
            $partnersNoLogo++;
        }
    }
    $add('partner_logos', $partnersNoLogo === 0, false,
        'Загрузите логотипы компаний-клиентов',
        $partnersNoLogo > 0 ? 'Без логотипа: ' . $partnersNoLogo . ' — показывается только название.' : 'Готово.',
        '/admin/partners', 'Загрузить');

    $add('logo', site_logo() !== null, false,
        'Загрузите логотип компании',
        site_logo() !== null ? 'Логотип загружен.' : 'Сейчас в шапке стандартный знак с названием.',
        '/admin/texts', 'Загрузить');

    $ogCustom = is_array(setting('site.og_image', [])) && !empty(setting('site.og_image', [])['file']);
    $add('og', $ogCustom, false,
        'Картинка для ссылок в мессенджерах',
        $ogCustom
            ? 'Загружена ваша картинка.'
            : 'Её видно, когда ссылку на сайт отправляют в чат. Сейчас показывается стандартная — с названием компании и автобусом. Можно заменить её фото вашего автобуса.',
        '/admin/texts', 'Загрузить');

    $add('metrika', metrika_id() !== '', false,
        'Подключите Яндекс Метрику',
        metrika_id() !== '' ? 'Счётчик № ' . metrika_id() . '.' : 'Покажет, сколько людей заходит на сайт и откуда.',
        '/admin/texts?tab=seo', 'Подключить');

    return $items;
}

/* ---------- Служебные значения и проверки ---------- */

/** Служебные значения админки (дата резервной копии и т. п.). */
function admin_system(string $key, mixed $default = null): mixed
{
    $group = Settings::group('system');
    return array_key_exists($key, $group) ? $group[$key] : $default;
}

function admin_system_save(array $values): void
{
    Settings::save('system', $values);
}

/** Папка data лежит внутри папки, открытой из интернета? */
function data_dir_is_public(): bool
{
    $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $data = realpath(DATA_DIR);
    if ($docRoot === false || $data === false || $docRoot === '') {
        return false;
    }
    return str_starts_with($data . DIRECTORY_SEPARATOR, rtrim($docRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}

/**
 * Проверка: не выполняет ли сервер PHP-файлы из папки uploads.
 * Кладём туда безобидный файл-пробник, запрашиваем его по сети и сразу удаляем.
 *
 * @return string 'safe' | 'unsafe' | 'unknown'
 */
function uploads_php_probe(): string
{
    if (!is_dir(UPLOADS_DIR) || !is_writable(UPLOADS_DIR)) {
        return 'unknown';
    }
    $name = 'probe-' . bin2hex(random_bytes(8)) . '.php';
    $path = UPLOADS_DIR . '/' . $name;
    $marker = 'BST-' . bin2hex(random_bytes(6));
    if (@file_put_contents($path, '<?php echo "' . $marker . '" . "-EXEC";') === false) {
        return 'unknown';
    }
    try {
        $body = admin_http_get(base_url() . '/uploads/' . $name);
    } finally {
        @unlink($path);
    }
    if ($body === null) {
        return 'unknown';
    }
    return str_contains($body, $marker . '-EXEC') ? 'unsafe' : 'safe';
}

/** Запрос к своему же сайту (для проверок). null — сайт не ответил. */
function admin_http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'BaikalSite-SelfCheck',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return is_string($body) && $status > 0 ? $body : null;
    }
    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        return null;
    }
    $context = stream_context_create([
        'http' => ['timeout' => 5, 'ignore_errors' => true, 'follow_location' => 0, 'user_agent' => 'BaikalSite-SelfCheck'],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $body = @file_get_contents($url, false, $context);
    return is_string($body) ? $body : null;
}

/**
 * Состояние сервера для главной страницы админки.
 *
 * @return list<array{status: string, title: string, text: string}>
 */
function admin_system_checks(): array
{
    $checks = [];

    $checks[] = PHP_VERSION_ID >= 80300
        ? ['status' => 'ok', 'title' => 'PHP ' . PHP_VERSION, 'text' => 'Версия подходит.']
        : ['status' => 'warn', 'title' => 'PHP ' . PHP_VERSION, 'text' => 'Сайт работает, но лучше выбрать в панели хостинга PHP 8.3 или новее — он быстрее и дольше получает обновления безопасности.'];

    if (!extension_loaded('gd')) {
        $checks[] = ['status' => 'bad', 'title' => 'Нет обработки картинок', 'text' => 'На хостинге не включено расширение PHP «GD» — фото загрузить не получится. Включите его в настройках PHP или напишите в поддержку хостинга.'];
    } elseif (!image_webp_supported()) {
        $checks[] = ['status' => 'warn', 'title' => 'Нет формата WebP', 'text' => 'Фото будут сохраняться в JPEG — сайт откроется чуть медленнее. Попросите поддержку хостинга включить WebP в расширении GD.'];
    } else {
        $checks[] = ['status' => 'ok', 'title' => 'Обработка фото', 'text' => 'Фото уменьшаются и сохраняются в лёгком формате WebP.'];
    }

    if (!function_exists('exif_read_data')) {
        $checks[] = ['status' => 'warn', 'title' => 'Нет расширения EXIF', 'text' => 'Некоторые фото с телефона могут загрузиться повёрнутыми. Включите расширение «exif» в настройках PHP.'];
    }

    // Фото с телефона весят 3–12 МБ, сайт принимает до 15 МБ — но хостинг может ограничить размер сильнее.
    $uploadLimit = upload_limit_bytes();
    if ($uploadLimit > 0 && $uploadLimit < IMAGE_MAX_BYTES) {
        $checks[] = ['status' => 'warn', 'title' => 'Фото — не больше ' . format_bytes($uploadLimit), 'text' => 'Хостинг не примет фото крупнее ' . format_bytes($uploadLimit) . '. Чтобы загружать снимки с телефона до 15 МБ, в настройках PHP на хостинге поставьте upload_max_filesize = 16M и post_max_size = 20M (как это сделать — в README-RU.md, раздел «Если что-то не работает»).'];
    }

    $checks[] = is_https()
        ? ['status' => 'ok', 'title' => 'Защищённое соединение (HTTPS)', 'text' => 'Сайт открывается по https.']
        : ['status' => 'warn', 'title' => 'Нет HTTPS', 'text' => 'Подключите бесплатный SSL-сертификат в панели хостинга, когда сайт будет на домене. На своём компьютере это нормально.'];

    if (data_dir_is_public()) {
        $checks[] = ['status' => 'bad', 'title' => 'Папка data открыта из интернета', 'text' => 'База данных лежит внутри папки сайта. Перенесите папки app и data на уровень выше папки public_html — так, как описано в README-RU.md.'];
    } else {
        $checks[] = ['status' => 'ok', 'title' => 'База данных закрыта', 'text' => 'Папка data находится вне папки, открытой из интернета.'];
    }

    $probe = (array) admin_system('uploads_probe', []);
    $result = (string) ($probe['result'] ?? '');
    $when = !empty($probe['at']) ? ' Проверено ' . date_ru(date('Y-m-d', (int) $probe['at'])) . '.' : '';
    $checks[] = match ($result) {
        'safe' => ['status' => 'ok', 'title' => 'Папка с картинками защищена', 'text' => 'Сервер не запускает программы из папки uploads.' . $when, 'probe' => true],
        'unsafe' => ['status' => 'bad', 'title' => 'Папка uploads не защищена', 'text' => 'Сервер запускает PHP-файлы из папки с картинками. Напишите в поддержку хостинга: «Запретите выполнение PHP в папке uploads».' . $when, 'probe' => true],
        'unknown' => ['status' => 'warn', 'title' => 'Папку uploads проверить не удалось', 'text' => 'Сайт не смог обратиться сам к себе. Это не страшно, если сайт загружен по инструкции.' . $when, 'probe' => true],
        default => ['status' => 'warn', 'title' => 'Папка с картинками не проверена', 'text' => 'Нажмите «Проверить» — займёт пару секунд.', 'probe' => true],
    };

    return $checks;
}

/** Удаляет загруженные картинки, которые больше нигде не используются (старше двух суток). */
function uploads_gc(int $minAgeSeconds = 172800): int
{
    if (!is_dir(UPLOADS_DIR)) {
        return 0;
    }
    $used = [];
    foreach (Db::all('SELECT file FROM vehicle_photos') as $row) {
        $used[(string) $row['file']] = true;
    }
    foreach (Db::all('SELECT logo FROM partners') as $row) {
        $logo = json_decode_array($row['logo']);
        if (!empty($logo['file'])) {
            $used[(string) $logo['file']] = true;
        }
    }
    foreach (['site.logo', 'site.favicon', 'site.og_image', 'hero.photo'] as $key) {
        $img = setting($key, []);
        if (is_array($img) && !empty($img['file'])) {
            $used[(string) $img['file']] = true;
        }
    }
    $removed = 0;
    foreach (scandir(UPLOADS_DIR) ?: [] as $name) {
        if (!preg_match('/^([a-z0-9]{16,64})(?:-\d{1,4})?\.(?:webp|png|jpg|svg)$/', $name, $m)) {
            continue;
        }
        $path = UPLOADS_DIR . '/' . $name;
        if (isset($used[$m[1]]) || (int) @filemtime($path) > time() - $minAgeSeconds) {
            continue;
        }
        if (@unlink($path)) {
            $removed++;
        }
    }
    return $removed;
}

/** Раз в сутки убираем «осиротевшие» картинки (загрузили, но не сохранили или заменили). */
function uploads_gc_daily(): void
{
    if (time() - (int) admin_system('gc_at', 0) < 86400) {
        return;
    }
    try {
        uploads_gc();
    } catch (Throwable $e) {
        error_log('uploads_gc: ' . $e->getMessage());
    }
    admin_system_save(['gc_at' => time()]);
}

/** Сколько места занимают загруженные картинки. */
function uploads_usage(): array
{
    $count = 0;
    $bytes = 0;
    foreach (scandir(UPLOADS_DIR) ?: [] as $name) {
        if (preg_match('/^[a-z0-9]{16,64}(?:-\d{1,4})?\.(?:webp|png|jpg|svg)$/', $name)) {
            $count++;
            $bytes += (int) @filesize(UPLOADS_DIR . '/' . $name);
        }
    }
    return ['count' => $count, 'bytes' => $bytes];
}
