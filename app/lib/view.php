<?php

declare(strict_types=1);

/*
 * Шаблоны, иконки, картинки, стили.
 */

/** Отрисовывает шаблон из app/views и возвращает HTML. */
function view(string $name, array $vars = []): string
{
    $file = APP_DIR . '/views/' . $name . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Шаблон не найден: ' . $name);
    }
    return (static function (string $__file, array $__vars): string {
        extract($__vars, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    })($file, $vars);
}

/** Страница публичной части: содержимое + общий макет (шапка, подвал, SEO). */
function render_public_page(string $template, array $vars = [], array $meta = []): string
{
    $content = view($template, $vars);
    $html = view('layout/public', ['content' => $content, 'meta' => $meta]);
    return str_replace('<!--icon-sprite-->', Icons::sprite(), $html);
}

/**
 * Иконки берутся из app/icons.svg. На страницу попадают только использованные —
 * одним встроенным спрайтом, без лишних запросов к серверу.
 */
final class Icons
{
    /** @var array<string, string>|null */
    private static ?array $symbols = null;

    /** @var array<string, true> */
    private static array $used = [];

    private static function symbols(): array
    {
        if (self::$symbols === null) {
            self::$symbols = [];
            $file = APP_DIR . '/icons.svg';
            $svg = is_file($file) ? (string) file_get_contents($file) : '';
            if (preg_match_all('#<symbol\b[^>]*\bid="i-([a-z0-9-]+)"[^>]*>.*?</symbol>#s', $svg, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    self::$symbols[$m[1]] = $m[0];
                }
            }
        }
        return self::$symbols;
    }

    public static function exists(string $name): bool
    {
        return isset(self::symbols()[$name]);
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::symbols());
    }

    public static function markUsed(string $name): bool
    {
        if (!self::exists($name)) {
            return false;
        }
        self::$used[$name] = true;
        return true;
    }

    public static function sprite(): string
    {
        if (!self::$used) {
            return '';
        }
        $symbols = self::symbols();
        $out = '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" class="icon-sprite">';
        foreach (array_keys(self::$used) as $name) {
            $out .= $symbols[$name];
        }
        return $out . '</svg>';
    }
}

/** Иконка из набора. С $label — для экранного диктора, без — декоративная. */
function icon(string $name, string $class = '', ?string $label = null): string
{
    if (!Icons::markUsed($name)) {
        return '';
    }
    $a11y = $label !== null && $label !== ''
        ? ' role="img" aria-label="' . e($label) . '"'
        : ' aria-hidden="true"';
    return '<svg class="' . e(classes('icon', $class)) . '" width="24" height="24" focusable="false"' . $a11y
        . '><use href="#i-' . e($name) . '"></use></svg>';
}

/**
 * Поле даты (дд.мм.гггг) или времени (чч:мм, 24 часа) — одинаковое в любом браузере.
 * Встроенное поле type="date" показывает дату так, как настроен браузер: в английском —
 * «месяц/день/год», а время — с AM/PM. Поэтому вводим текстом: точки и двоеточие ставятся сами.
 * Значок справа — прозрачное встроенное поле поверх него: открывает календарь или выбор
 * времени телефона и браузера, выбранное значение переносится в текст.
 *
 * $kind: 'date' или 'time'.
 * $o: name, value, min, max, step (для встроенного выбора), describedby, required, attrs.
 */
function dt_input(string $kind, string $id, array $o = []): string
{
    $isDate = $kind !== 'time';
    $text = array_replace([
        'class' => 'input dt-input__text',
        'type' => 'text',
        'id' => $id,
        'name' => $o['name'] ?? ($isDate ? 'date' : 'time'),
        'value' => isset($o['value']) && is_scalar($o['value']) ? (string) $o['value'] : null,
        'inputmode' => 'numeric',
        'autocomplete' => 'off',
        'spellcheck' => 'false',
        'maxlength' => $isDate ? '10' : '5',
        'placeholder' => $isDate ? 'дд.мм.гггг' : 'чч:мм',
        'required' => !empty($o['required']),
        'aria-describedby' => $o['describedby'] ?? null,
        'aria-invalid' => !empty($o['invalid']) ? 'true' : null,
        'data-dt-text' => true,
    ], (array) ($o['attrs'] ?? []));
    $native = [
        'class' => 'dt-input__native',
        'type' => $isDate ? 'date' : 'time',
        'tabindex' => '-1',
        'min' => $o['min'] ?? null,
        'max' => $o['max'] ?? null,
        'step' => $o['step'] ?? null,
        'data-dt-native' => true,
    ];
    $title = $isDate ? 'Выбрать дату в календаре' : 'Выбрать время';
    return '<div class="dt-input" data-dt="' . ($isDate ? 'date' : 'time') . '">'
        . '<input' . attrs($text) . '>'
        . '<span class="dt-input__picker" title="' . e($title) . '" aria-hidden="true">'
        . icon($isDate ? 'calendar' : 'clock')
        . '<input' . attrs($native) . '>'
        . '</span>'
        . '</div>';
}

/* ---------- Картинки ---------- */

/** Адрес загруженной картинки нужной ширины (или ближайшей большей). */
function image_url(array $img, ?int $width = null): string
{
    $file = (string) ($img['file'] ?? '');
    if ($file === '') {
        return '';
    }
    $ext = (string) ($img['ext'] ?? 'webp');
    $widths = array_values(array_filter(array_map('intval', (array) ($img['widths'] ?? []))));
    if (!$widths) {
        return upload_url($file . '.' . $ext);
    }
    sort($widths);
    $chosen = end($widths);
    if ($width !== null) {
        foreach ($widths as $w) {
            if ($w >= $width) {
                $chosen = $w;
                break;
            }
        }
    }
    return upload_url($file . '-' . $chosen . '.' . $ext);
}

function image_srcset(array $img): string
{
    $widths = array_values(array_filter(array_map('intval', (array) ($img['widths'] ?? []))));
    sort($widths);
    $ext = (string) ($img['ext'] ?? 'webp');
    $parts = [];
    foreach ($widths as $w) {
        $parts[] = upload_url($img['file'] . '-' . $w . '.' . $ext) . ' ' . $w . 'w';
    }
    return implode(', ', $parts);
}

/** <img> с srcset, размерами (чтобы страница не прыгала) и ленивой загрузкой. */
function image_tag(array $img, string $sizes, array $attributes = []): string
{
    if (empty($img['file'])) {
        return '';
    }
    $srcset = image_srcset($img);
    return '<img' . attrs(array_replace([
        'src' => image_url($img, 960),
        'srcset' => $srcset !== '' ? $srcset : null,
        'sizes' => $srcset !== '' ? $sizes : null,
        'width' => !empty($img['w']) ? (int) $img['w'] : null,
        'height' => !empty($img['h']) ? (int) $img['h'] : null,
        'alt' => (string) ($img['alt'] ?? ''),
        'loading' => 'lazy',
        'decoding' => 'async',
    ], $attributes)) . '>';
}

/** Логотип партнёра (SVG или WebP). */
function partner_logo_tag(array $logo, string $name): string
{
    return image_tag($logo, '200px', [
        'class' => 'partner__logo',
        'alt' => $name,
        'src' => image_url($logo, 480),
    ]);
}

/** Содержимое нашего SVG-файла для вставки прямо в страницу (без лишнего запроса к серверу). */
function inline_svg_file(string $path): string
{
    static $cache = [];
    if (!isset($cache[$path])) {
        $svg = is_file($path) ? (string) file_get_contents($path) : '';
        // Убираем XML-заголовок и комментарии — внутри HTML они не нужны.
        $svg = preg_replace('/<\?xml.*?\?>\s*/s', '', $svg) ?? '';
        $svg = preg_replace('/<!--.*?-->\s*/s', '', $svg) ?? '';
        $cache[$path] = trim($svg);
    }
    return $cache[$path];
}

/* ---------- Стили ---------- */

/** Сжатый CSS-файл для вставки прямо в страницу (быстрее первая отрисовка). Кэшируется в data/cache. */
function inline_css(string $fileName): string
{
    $path = PUBLIC_DIR . '/assets/css/' . basename($fileName);
    if (!is_file($path)) {
        return '';
    }
    $mtime = (int) filemtime($path);
    $cacheDir = DATA_DIR . '/cache';
    $base = basename($fileName, '.css');
    $cacheFile = $cacheDir . '/' . $base . '-' . $mtime . '.min.css';
    if (is_file($cacheFile)) {
        return (string) file_get_contents($cacheFile);
    }
    $css = minify_css((string) file_get_contents($path));
    if ((is_dir($cacheDir) || @mkdir($cacheDir, 0775, true)) && is_writable($cacheDir)) {
        foreach (glob($cacheDir . '/' . $base . '-*.min.css') ?: [] as $old) {
            @unlink($old);
        }
        @file_put_contents($cacheFile, $css, LOCK_EX);
    }
    return $css;
}

/** Осторожное сжатие CSS: убираем комментарии и лишние пробелы, не трогая calc() и селекторы. */
function minify_css(string $css): string
{
    $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
    $css = preg_replace('/\s+/', ' ', $css) ?? $css;
    $css = preg_replace('/\s*([{};])\s*/', '$1', $css) ?? $css;
    $css = preg_replace('/,\s+/', ',', $css) ?? $css;
    $css = preg_replace('/:\s+/', ':', $css) ?? $css;
    $css = str_replace(';}', '}', $css);
    return trim($css);
}
