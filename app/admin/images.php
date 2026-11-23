<?php

declare(strict_types=1);

/*
 * Загрузка картинок через админку.
 *  - тип файла проверяется по содержимому (finfo), а не по имени;
 *  - не больше 15 МБ и 50 мегапикселей;
 *  - фото поворачивается по данным EXIF, затем пересохраняется в WebP — EXIF (в том числе GPS) при этом теряется;
 *  - имена файлов случайные, расширение задаёт сайт;
 *  - SVG-логотипы очищаются по «белому списку»: остаются только безопасные элементы и атрибуты.
 */

const IMAGE_MAX_BYTES = 15 * 1024 * 1024;
const IMAGE_MAX_PIXELS = 50000000;
const SVG_MAX_BYTES = 1024 * 1024;
const SVG_NS = 'http://www.w3.org/2000/svg';
const XLINK_NS = 'http://www.w3.org/1999/xlink';

/** Для чего загружается картинка: размеры, формат, подсказка. */
function image_purposes(): array
{
    return [
        'vehicle' => [
            'kind' => 'photo', 'widths' => [480, 960, 1600], 'min' => 320, 'shape' => 'photo',
            'hint' => 'JPEG, PNG или WebP до 15 МБ. Лучше горизонтальное фото от 1600 пикселей по ширине.',
        ],
        'hero' => [
            'kind' => 'photo', 'widths' => [480, 960, 1600], 'min' => 800, 'shape' => 'wide',
            'hint' => 'Горизонтальное фото от 1600 пикселей по ширине. Поверх фото будет тёмная подложка, чтобы текст читался.',
        ],
        'logo' => [
            'kind' => 'logo', 'widths' => [240, 480, 720], 'min' => 64, 'svg' => true, 'shape' => 'logo',
            'hint' => 'SVG, PNG или WebP на прозрачном фоне. Логотип будет высотой около 44 пикселей.',
        ],
        'partner' => [
            'kind' => 'logo', 'widths' => [240, 480], 'min' => 48, 'svg' => true, 'shape' => 'logo',
            'hint' => 'SVG, PNG или WebP, лучше на прозрачном фоне. На сайте логотип серый, при наведении — цветной.',
        ],
        'favicon' => [
            'kind' => 'favicon', 'sizes' => [32, 180, 192, 512], 'min' => 64, 'shape' => 'square',
            'hint' => 'Квадратная картинка PNG от 512×512 пикселей. Видна на вкладке браузера и на экране телефона.',
        ],
        'og' => [
            'kind' => 'og', 'w' => 1200, 'h' => 630, 'min' => 600, 'shape' => 'wide',
            'hint' => 'Картинка 1200×630 пикселей. Её видно, когда ссылку на сайт отправляют в мессенджер. Края могут обрезаться.',
        ],
    ];
}

/** Что показывать в окне выбора файла. */
function image_accept(string $purpose): string
{
    $accept = 'image/jpeg,image/png,image/webp';
    if (!empty(image_purposes()[$purpose]['svg'])) {
        $accept .= ',image/svg+xml,.svg';
    }
    // HEIC сюда специально не добавлен: тогда iPhone сам переводит фото в JPEG при загрузке.
    if (image_purposes()[$purpose]['kind'] === 'photo') {
        $accept .= ',image/avif,image/gif';
    }
    return $accept;
}

function image_webp_supported(): bool
{
    return extension_loaded('gd') && function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
}

/* ---------- Подписанные ссылки на картинки ---------- */

/** Секретный ключ сайта (создаётся один раз в data/secret.key). */
function app_secret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }
    $file = DATA_DIR . '/secret.key';
    $value = is_file($file) ? trim((string) file_get_contents($file)) : '';
    if (strlen($value) < 32) {
        $value = bin2hex(random_bytes(32));
        if (@file_put_contents($file, $value, LOCK_EX) === false) {
            throw new RuntimeException('Не удалось записать data/secret.key');
        }
    }
    $secret = $value;
    return $secret;
}

/** Проверенное описание картинки (или null, если данные неверные). */
function image_normalize(array $img): ?array
{
    $file = (string) ($img['file'] ?? '');
    $ext = (string) ($img['ext'] ?? 'webp');
    if (!preg_match('/^[a-z0-9]{16,64}$/', $file) || !in_array($ext, ['webp', 'png', 'jpg', 'svg'], true)) {
        return null;
    }
    $widths = array_values(array_unique(array_filter(
        array_map('intval', (array) ($img['widths'] ?? [])),
        static fn (int $w): bool => $w > 0 && $w <= 4000
    )));
    sort($widths);
    return [
        'file' => $file,
        'ext' => $ext,
        'widths' => $widths,
        'w' => max(0, min(20000, (int) ($img['w'] ?? 0))),
        'h' => max(0, min(20000, (int) ($img['h'] ?? 0))),
    ];
}

/**
 * Картинка → строка для скрытого поля формы. Подпись не даёт подменить данные:
 * сервер принимает назад только то, что сам выдал.
 */
function image_token(array $img): string
{
    $img = image_normalize($img);
    if ($img === null) {
        return '';
    }
    $payload = rtrim(strtr(base64_encode(json_store($img)), '+/', '-_'), '=');
    return $payload . '.' . substr(hash_hmac('sha256', 'img|' . $payload, app_secret()), 0, 32);
}

function image_from_token(string $token): ?array
{
    if (!preg_match('/^([A-Za-z0-9_-]{10,3000})\.([a-f0-9]{32})$/', $token, $m)) {
        return null;
    }
    $expected = substr(hash_hmac('sha256', 'img|' . $m[1], app_secret()), 0, 32);
    if (!hash_equals($expected, $m[2])) {
        return null;
    }
    $data = json_decode((string) base64_decode(strtr($m[1], '-_', '+/')), true);
    return is_array($data) ? image_normalize($data) : null;
}

/** Все файлы картинки на диске (все размеры). */
function image_files(array $img): array
{
    $img = image_normalize($img);
    if ($img === null) {
        return [];
    }
    if (!$img['widths']) {
        return [UPLOADS_DIR . '/' . $img['file'] . '.' . $img['ext']];
    }
    return array_map(
        static fn (int $w): string => UPLOADS_DIR . '/' . $img['file'] . '-' . $w . '.' . $img['ext'],
        $img['widths']
    );
}

function image_delete(array $img): void
{
    foreach (image_files($img) as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/* ---------- Обработка загрузки ---------- */

/**
 * Принимает файл из формы, проверяет и сохраняет нужные размеры.
 *
 * @throws UserFacingError понятная ошибка для человека
 */
function image_process_upload(mixed $file, string $purpose): array
{
    $config = image_purposes()[$purpose] ?? null;
    if ($config === null) {
        throw new UserFacingError('Неизвестный тип картинки.');
    }
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        throw new UserFacingError('Файл не получен. Выберите картинку ещё раз.');
    }
    switch ((int) $file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new UserFacingError('Файл слишком большой: хостинг принимает не больше ' . format_bytes(upload_limit_bytes()) . '.');
        case UPLOAD_ERR_PARTIAL:
            throw new UserFacingError('Файл загрузился не полностью. Проверьте интернет и попробуйте ещё раз.');
        case UPLOAD_ERR_NO_FILE:
            throw new UserFacingError('Выберите файл.');
        default:
            throw new UserFacingError('Хостинг не принял файл (ошибка ' . (int) $file['error'] . '). Попробуйте ещё раз или напишите в поддержку хостинга.');
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new UserFacingError('Файл не получен. Выберите картинку ещё раз.');
    }
    $size = (int) filesize($tmp);
    if ($size <= 0) {
        throw new UserFacingError('Файл пустой.');
    }
    if ($size > IMAGE_MAX_BYTES) {
        throw new UserFacingError('Файл больше 15 МБ. Уменьшите фото и загрузите снова.');
    }
    if (!is_dir(UPLOADS_DIR) && !@mkdir(UPLOADS_DIR, 0755, true)) {
        throw new UserFacingError('Не удалось создать папку uploads. Создайте её внутри папки сайта (public_html/uploads).');
    }
    if (!is_writable(UPLOADS_DIR)) {
        throw new UserFacingError('Папка uploads недоступна для записи. Поставьте для неё права 755 в файловом менеджере хостинга.');
    }

    $mime = image_detect_mime($tmp, (string) ($file['name'] ?? ''));

    if ($mime === 'image/svg+xml') {
        if (empty($config['svg'])) {
            throw new UserFacingError($config['kind'] === 'favicon'
                ? 'Для значка сайта нужен PNG, JPEG или WebP (квадратный, от 512×512 пикселей).'
                : 'Для фото нужен JPEG, PNG или WebP.');
        }
        return image_store_svg($tmp);
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/heic', 'image/heif', 'image/bmp', 'image/x-ms-bmp'];
    if (!in_array($mime, $allowed, true)) {
        throw new UserFacingError('Это не картинка или неподдерживаемый формат. Подойдут JPEG, PNG и WebP'
            . (!empty($config['svg']) ? ', а для логотипов ещё SVG.' : '.'));
    }
    if (!extension_loaded('gd')) {
        throw new UserFacingError('На хостинге не включено расширение PHP «GD», без него фото не обработать. Включите его в настройках PHP или напишите в поддержку хостинга.');
    }

    $image = image_decode($tmp, $mime);
    try {
        $w = imagesx($image);
        $h = imagesy($image);
        $min = (int) ($config['min'] ?? 1);
        if ($w < $min || ($config['kind'] !== 'logo' && $h < min($min, 200))) {
            throw new UserFacingError('Картинка слишком маленькая (' . $w . '×' . $h . '). Нужна хотя бы ' . $min . ' пикселей по ширине.');
        }
        return match ($config['kind']) {
            'favicon' => image_store_favicon($image, $config),
            'og' => image_store_og($image, $config),
            default => image_store_widths($image, $config),
        };
    } finally {
        imagedestroy($image);
    }
}

/** Тип файла по содержимому. SVG узнаём отдельно: finfo часто называет его text/plain или text/xml. */
function image_detect_mime(string $path, string $originalName): string
{
    $mime = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($path);
    } elseif (function_exists('mime_content_type')) {
        $mime = (string) mime_content_type($path);
    }
    if ($mime === '' || $mime === 'application/octet-stream') {
        // Запасной путь: сигнатура картинки
        $info = @getimagesize($path);
        $mime = is_array($info) ? (string) $info['mime'] : $mime;
    }
    $textual = ['image/svg+xml', 'image/svg', 'text/xml', 'application/xml', 'text/plain', 'text/html'];
    if (in_array($mime, $textual, true)) {
        $head = (string) file_get_contents($path, false, null, 0, 4096);
        $looksSvg = stripos($head, '<svg') !== false;
        if ($looksSvg && ($mime === 'image/svg+xml' || $mime === 'image/svg' || preg_match('/\.svg$/i', $originalName))) {
            return 'image/svg+xml';
        }
    }
    return $mime;
}

/** Читает картинку в память, учитывая поворот из EXIF. */
function image_decode(string $path, string $mime): GdImage
{
    $info = @getimagesize($path);
    if (is_array($info) && $info[0] > 0 && $info[1] > 0) {
        $pixels = $info[0] * $info[1];
        if ($pixels > IMAGE_MAX_PIXELS) {
            throw new UserFacingError('Фото слишком большое: ' . round($pixels / 1000000) . ' мегапикселей. Уменьшите его до 20 мегапикселей и загрузите снова.');
        }
        image_reserve_memory($pixels);
    }

    $image = false;
    $gdReaders = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp',
        'image/gif' => 'imagecreatefromgif',
        'image/avif' => 'imagecreatefromavif',
        'image/bmp' => 'imagecreatefrombmp',
        'image/x-ms-bmp' => 'imagecreatefrombmp',
    ];
    $reader = $gdReaders[$mime] ?? null;
    if ($reader !== null && function_exists($reader)) {
        $image = @$reader($path);
    }
    // HEIC, AVIF, анимированный WebP и т. п. — пробуем через Imagick, если он есть на хостинге.
    if (!$image instanceof GdImage) {
        $image = image_decode_imagick($path);
    }
    if (!$image instanceof GdImage) {
        if (in_array($mime, ['image/heic', 'image/heif'], true)) {
            throw new UserFacingError('Формат HEIC (фото с iPhone) сервер не понимает. Сохраните фото как JPEG или отправьте его с телефона через браузер — он сам переведёт фото в JPEG.');
        }
        throw new UserFacingError('Не удалось прочитать картинку — возможно, файл повреждён. Попробуйте другой файл.');
    }
    if (!imageistruecolor($image)) {
        imagepalettetotruecolor($image);
    }
    imagealphablending($image, true);
    imagesavealpha($image, true);

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? ($exif['IFD0']['Orientation'] ?? 1));
        $image = image_apply_orientation($image, $orientation);
    }
    return $image;
}

/** Запасной способ прочитать необычный формат: Imagick → PNG → GD. */
function image_decode_imagick(string $path): GdImage|false
{
    if (!class_exists('Imagick')) {
        return false;
    }
    try {
        $im = new Imagick();
        $im->readImage($path);
        $im->setIteratorIndex(0);
        if (method_exists($im, 'autoOrient')) {
            $im->autoOrient();
        }
        $im->stripImage();
        $im->setImageFormat('png');
        $blob = $im->getImageBlob();
        $im->clear();
        $image = @imagecreatefromstring($blob);
        return $image instanceof GdImage ? $image : false;
    } catch (Throwable $e) {
        error_log('Imagick: ' . $e->getMessage());
        return false;
    }
}

/** Поднимаем лимит памяти, если большое фото в него не помещается. */
function image_reserve_memory(int $pixels): void
{
    $needed = $pixels * 5 + 48 * 1024 * 1024;
    $limit = ini_bytes((string) ini_get('memory_limit'));
    if ($limit <= 0) {
        return; // без ограничения
    }
    $free = $limit - memory_get_usage(true);
    if ($free >= $needed) {
        return;
    }
    $want = memory_get_usage(true) + $needed;
    @ini_set('memory_limit', (string) (int) ceil($want / 1024 / 1024) . 'M');
    $limit = ini_bytes((string) ini_get('memory_limit'));
    if ($limit > 0 && $limit - memory_get_usage(true) < $needed) {
        throw new UserFacingError('Фото слишком большое для этого хостинга (' . round($pixels / 1000000) . ' мегапикселей). Уменьшите его до 12 мегапикселей и загрузите снова.');
    }
}

/** Поворот и отражение по метке Orientation из EXIF (значения 1–8). */
function image_apply_orientation(GdImage $image, int $orientation): GdImage
{
    $rotate = static function (GdImage $img, int $angle): GdImage {
        $rotated = imagerotate($img, $angle, 0);
        if ($rotated instanceof GdImage) {
            imagedestroy($img);
            return $rotated;
        }
        return $img;
    };
    switch ($orientation) {
        case 2:
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 3:
            $image = $rotate($image, 180);
            break;
        case 4:
            imageflip($image, IMG_FLIP_VERTICAL);
            break;
        case 5:
            $image = $rotate($image, -90);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 6:
            $image = $rotate($image, -90);
            break;
        case 7:
            $image = $rotate($image, 90);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 8:
            $image = $rotate($image, 90);
            break;
    }
    return $image;
}

/** Пустой холст с прозрачным фоном (или заливкой цветом). */
function image_canvas(int $w, int $h, ?array $fill = null): GdImage
{
    $canvas = imagecreatetruecolor(max(1, $w), max(1, $h));
    if ($fill !== null) {
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, $fill[0], $fill[1], $fill[2]));
        imagealphablending($canvas, true);
    } else {
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagesavealpha($canvas, true);
    }
    return $canvas;
}

/** Уменьшенная копия всей картинки. */
function image_resized(GdImage $src, int $w, int $h): GdImage
{
    $dst = image_canvas($w, $h);
    imagealphablending($dst, false);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
    imagesavealpha($dst, true);
    return $dst;
}

function image_new_name(): string
{
    return bin2hex(random_bytes(12));
}

/** Сохраняет в WebP (или JPEG/PNG, если WebP на хостинге нет). */
function image_save(GdImage $image, string $pathWithoutExt, string $ext, int $quality): void
{
    $ok = match ($ext) {
        'webp' => imagewebp($image, $pathWithoutExt . '.webp', $quality),
        'jpg' => imagejpeg($image, $pathWithoutExt . '.jpg', $quality),
        'png' => imagepng($image, $pathWithoutExt . '.png', 9),
        default => false,
    };
    if (!$ok) {
        throw new UserFacingError('Не удалось сохранить картинку. Проверьте, что на хостинге хватает места.');
    }
}

/** Есть ли в картинке прозрачные места (для логотипов без WebP — тогда PNG). */
function image_has_alpha(GdImage $image): bool
{
    $w = imagesx($image);
    $h = imagesy($image);
    $step = max(1, (int) floor(min($w, $h) / 40));
    for ($y = 0; $y < $h; $y += $step) {
        for ($x = 0; $x < $w; $x += $step) {
            if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 0) {
                return true;
            }
        }
    }
    return false;
}

/** Фото и логотипы: несколько ширин (480, 960, 1600…), не больше исходной. */
function image_store_widths(GdImage $image, array $config): array
{
    $srcW = imagesx($image);
    $srcH = imagesy($image);
    $isLogo = $config['kind'] === 'logo';
    if (image_webp_supported()) {
        $ext = 'webp';
        $quality = $isLogo ? 90 : 80;
    } else {
        $ext = $isLogo && image_has_alpha($image) ? 'png' : 'jpg';
        $quality = 82;
    }
    if ($ext === 'jpg') {
        // В JPEG нет прозрачности — кладём на белый фон.
        $flat = image_canvas($srcW, $srcH, [255, 255, 255]);
        imagecopy($flat, $image, 0, 0, 0, 0, $srcW, $srcH);
        $image = $flat;
    }

    $widths = array_values(array_filter($config['widths'], static fn (int $w): bool => $w < $srcW));
    $widths[] = min($srcW, max($config['widths']));
    $widths = array_values(array_unique($widths));
    sort($widths);

    $name = image_new_name();
    $saved = [];
    try {
        foreach ($widths as $w) {
            $h = max(1, (int) round($srcH * $w / $srcW));
            $copy = $w === $srcW ? $image : image_resized($image, $w, $h);
            image_save($copy, UPLOADS_DIR . '/' . $name . '-' . $w, $ext, $quality);
            $saved[] = UPLOADS_DIR . '/' . $name . '-' . $w . '.' . $ext;
            if ($copy !== $image) {
                imagedestroy($copy);
            }
        }
    } catch (Throwable $e) {
        array_map('unlink', array_filter($saved, 'is_file'));
        throw $e;
    }
    $maxW = max($widths);
    return [
        'file' => $name,
        'ext' => $ext,
        'widths' => $widths,
        'w' => $maxW,
        'h' => max(1, (int) round($srcH * $maxW / $srcW)),
    ];
}

/** Значок сайта: квадраты 32, 180, 192, 512 в PNG. Картинка вписывается целиком. */
function image_store_favicon(GdImage $image, array $config): array
{
    $name = image_new_name();
    $srcW = imagesx($image);
    $srcH = imagesy($image);
    foreach ($config['sizes'] as $size) {
        // Для iPhone (180) — белый фон: прозрачные места он закрашивает чёрным.
        $canvas = image_canvas($size, $size, $size === 180 ? [255, 255, 255] : null);
        $scale = min($size / $srcW, $size / $srcH);
        $w = max(1, (int) round($srcW * $scale));
        $h = max(1, (int) round($srcH * $scale));
        imagealphablending($canvas, $size === 180);
        imagecopyresampled($canvas, $image, (int) (($size - $w) / 2), (int) (($size - $h) / 2), 0, 0, $w, $h, $srcW, $srcH);
        imagesavealpha($canvas, true);
        image_save($canvas, UPLOADS_DIR . '/' . $name . '-' . $size, 'png', 9);
        imagedestroy($canvas);
    }
    return ['file' => $name, 'ext' => 'png', 'widths' => $config['sizes'], 'w' => 512, 'h' => 512];
}

/** Картинка для соцсетей: ровно 1200×630, лишнее по краям обрезается. JPEG — его понимают все мессенджеры. */
function image_store_og(GdImage $image, array $config): array
{
    $tw = (int) $config['w'];
    $th = (int) $config['h'];
    $srcW = imagesx($image);
    $srcH = imagesy($image);
    $scale = max($tw / $srcW, $th / $srcH);
    $cropW = (int) round($tw / $scale);
    $cropH = (int) round($th / $scale);
    $canvas = image_canvas($tw, $th, [255, 255, 255]);
    imagecopyresampled($canvas, $image, 0, 0, (int) (($srcW - $cropW) / 2), (int) (($srcH - $cropH) / 2), $tw, $th, $cropW, $cropH);
    $name = image_new_name();
    image_save($canvas, UPLOADS_DIR . '/' . $name . '-' . $tw, 'jpg', 85);
    imagedestroy($canvas);
    return ['file' => $name, 'ext' => 'jpg', 'widths' => [$tw], 'w' => $tw, 'h' => $th];
}

/* ---------- SVG ---------- */

function image_store_svg(string $path): array
{
    if ((int) filesize($path) > SVG_MAX_BYTES) {
        throw new UserFacingError('SVG-файл больше 1 МБ. Для логотипа это слишком много — сохраните его заново или загрузите PNG.');
    }
    $clean = svg_sanitize((string) file_get_contents($path));
    if ($clean === null) {
        throw new UserFacingError('Этот SVG-файл не получилось безопасно обработать. Загрузите логотип в формате PNG или WebP.');
    }
    $name = image_new_name();
    if (@file_put_contents(UPLOADS_DIR . '/' . $name . '.svg', $clean['svg'], LOCK_EX) === false) {
        throw new UserFacingError('Не удалось сохранить файл. Проверьте, что на хостинге хватает места.');
    }
    return ['file' => $name, 'ext' => 'svg', 'widths' => [], 'w' => $clean['w'], 'h' => $clean['h']];
}

/**
 * Очистка SVG по «белому списку». Удаляется всё, что может выполнить код или загрузить что-то извне:
 * скрипты, обработчики событий (onload…), внешние ссылки, <style>, <foreignObject>, сущности XML.
 *
 * @return array{svg: string, w: int, h: int}|null
 */
function svg_sanitize(string $raw): ?array
{
    if ($raw === '' || strlen($raw) > SVG_MAX_BYTES) {
        return null;
    }
    // Сущности XML (в том числе «миллион смехов») не принимаем вовсе; простой DOCTYPE из редакторов убираем.
    if (preg_match('/<!ENTITY/i', $raw)) {
        return null;
    }
    $raw = preg_replace('/<!DOCTYPE[^>\[]*(\[[^\]]*\])?\s*>/i', '', $raw) ?? '';
    if (stripos($raw, '<!DOCTYPE') !== false) {
        return null;
    }
    if (!class_exists('DOMDocument')) {
        return null;
    }

    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadXML($raw, LIBXML_NONET | LIBXML_NOBLANKS);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded || !$doc->documentElement instanceof DOMElement) {
        return null;
    }
    $root = $doc->documentElement;
    if ($root->localName !== 'svg' || $root->namespaceURI !== SVG_NS) {
        return null;
    }

    // Узлы до и после корня (комментарии, инструкции обработки)
    foreach (iterator_to_array($doc->childNodes) as $node) {
        if ($node !== $root) {
            $doc->removeChild($node);
        }
    }
    svg_inline_class_styles($doc);
    svg_clean_attributes($root);
    svg_clean_children($root);

    // Размер: из viewBox или width/height
    $w = 0.0;
    $h = 0.0;
    $viewBox = trim($root->getAttribute('viewBox'));
    if ($viewBox !== '') {
        $parts = preg_split('/[\s,]+/', $viewBox) ?: [];
        if (count($parts) === 4 && is_numeric($parts[2]) && is_numeric($parts[3])) {
            $w = (float) $parts[2];
            $h = (float) $parts[3];
        }
    }
    if ($w <= 0 || $h <= 0) {
        $aw = svg_length($root->getAttribute('width'));
        $ah = svg_length($root->getAttribute('height'));
        if ($aw > 0 && $ah > 0) {
            $w = $aw;
            $h = $ah;
            $root->setAttribute('viewBox', '0 0 ' . rtrim(rtrim(number_format($aw, 3, '.', ''), '0'), '.') . ' ' . rtrim(rtrim(number_format($ah, 3, '.', ''), '0'), '.'));
        }
    }
    if ($w <= 0 || $h <= 0) {
        return null;
    }

    $svg = $doc->saveXML($root);
    if (!is_string($svg) || $svg === '') {
        return null;
    }
    // Масштабируем размеры так, чтобы большая сторона была около 480 — для атрибутов width/height в <img>.
    $scale = 480 / max($w, $h);
    return [
        'svg' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $svg . "\n",
        'w' => max(1, (int) round($w * $scale)),
        'h' => max(1, (int) round($h * $scale)),
    ];
}

/**
 * Логотипы из Illustrator и CorelDRAW задают цвета через <style> и классы (.st0{fill:#0B4F8A}).
 * Сам <style> удаляется при очистке, поэтому простые правила по классам переносим в атрибут style,
 * иначе логотип стал бы чёрным. Переносятся только безопасные свойства оформления.
 */
function svg_inline_class_styles(DOMDocument $doc): void
{
    $styles = [];
    foreach (iterator_to_array($doc->getElementsByTagNameNS(SVG_NS, 'style')) as $styleEl) {
        $styles[] = (string) $styleEl->textContent;
        $styleEl->parentNode?->removeChild($styleEl);
    }
    if (!$styles) {
        return;
    }
    $css = preg_replace('#/\*.*?\*/#s', '', implode("\n", $styles)) ?? '';
    $allowedProps = array_fill_keys([
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'clip-rule',
        'display', 'visibility', 'font-family', 'font-size', 'font-weight', 'font-style', 'letter-spacing',
        'text-anchor', 'isolation', 'mix-blend-mode', 'stop-color', 'stop-opacity',
    ], true);

    $rules = []; // класс → список «свойство:значение» в порядке появления
    if (preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $declarations = [];
            foreach (explode(';', $m[2]) as $decl) {
                [$prop, $value] = array_map('trim', explode(':', $decl, 2) + [1 => '']);
                $prop = strtolower($prop);
                if ($prop !== '' && $value !== '' && isset($allowedProps[$prop]) && !svg_value_unsafe($value)) {
                    $declarations[] = $prop . ':' . $value;
                }
            }
            if (!$declarations) {
                continue;
            }
            foreach (explode(',', $m[1]) as $selector) {
                if (preg_match('/^\s*\.([A-Za-z_][\w-]*)\s*$/', $selector, $cls)) {
                    $rules[] = [$cls[1], $declarations];
                }
            }
        }
    }
    if (!$rules) {
        return;
    }
    $xpath = new DOMXPath($doc);
    foreach ($xpath->query('//*[@class]') ?: [] as $el) {
        if (!$el instanceof DOMElement) {
            continue;
        }
        $classes = preg_split('/\s+/', trim($el->getAttribute('class'))) ?: [];
        $add = [];
        foreach ($rules as [$class, $declarations]) {
            if (in_array($class, $classes, true)) {
                array_push($add, ...$declarations);
            }
        }
        if ($add) {
            $own = trim($el->getAttribute('style'));
            $el->setAttribute('style', implode(';', $add) . ($own !== '' ? ';' . $own : ''));
        }
    }
}

/** «120px» → 120. Проценты и пустые значения не подходят. */
function svg_length(string $value): float
{
    $value = trim($value);
    if (preg_match('/^(\d+(?:\.\d+)?)(px|pt)?$/i', $value, $m)) {
        return (float) $m[1] * (strtolower($m[2] ?? '') === 'pt' ? 4 / 3 : 1);
    }
    return 0.0;
}

function svg_allowed_elements(): array
{
    static $list = null;
    return $list ??= array_fill_keys([
        'svg', 'g', 'defs', 'title', 'desc', 'symbol', 'use', 'path', 'rect', 'circle', 'ellipse', 'line',
        'polyline', 'polygon', 'text', 'tspan', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask',
        'pattern', 'image',
    ], true);
}

function svg_allowed_attributes(): array
{
    static $list = null;
    return $list ??= array_fill_keys([
        'id', 'class', 'style', 'transform', 'viewbox', 'preserveaspectratio', 'width', 'height', 'version',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'fx', 'fy', 'fr', 'd', 'points', 'pathlength',
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'clip-path',
        'clip-rule', 'mask', 'color', 'display', 'visibility', 'overflow', 'gradientunits', 'gradienttransform',
        'spreadmethod', 'offset', 'stop-color', 'stop-opacity', 'clippathunits', 'maskunits', 'maskcontentunits',
        'patternunits', 'patterncontentunits', 'patterntransform', 'font-family', 'font-size', 'font-weight',
        'font-style', 'text-anchor', 'dominant-baseline', 'letter-spacing', 'word-spacing', 'text-decoration',
        'dx', 'dy', 'rotate', 'textlength', 'lengthadjust', 'shape-rendering', 'vector-effect', 'paint-order',
        'isolation', 'mix-blend-mode', 'href',
    ], true);
}

/** Опасное содержимое в значении атрибута или стиля. url(#id) — ссылка внутри файла — разрешена. */
function svg_value_unsafe(string $value): bool
{
    $v = strtolower(preg_replace('/[\s\x00-\x1F]+/', '', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    if (str_contains($v, 'javascript:') || str_contains($v, 'vbscript:') || str_contains($v, 'expression(')
        || str_contains($v, '@import') || str_contains($v, '<') || str_contains($v, '\\')) {
        return true;
    }
    if (str_contains($v, 'data:')) {
        return true;
    }
    if (preg_match_all('/url\((.*?)\)/', $v, $m)) {
        foreach ($m[1] as $target) {
            if (!str_starts_with(trim($target, '\'"'), '#')) {
                return true;
            }
        }
    }
    return false;
}

function svg_clean_attributes(DOMElement $el): void
{
    $isImage = strtolower($el->localName) === 'image';
    foreach (iterator_to_array($el->attributes) as $attr) {
        /** @var DOMAttr $attr */
        $name = strtolower((string) $attr->localName);
        $value = (string) $attr->value;
        $ns = $attr->namespaceURI;

        if ($name === 'href' && ($ns === null || $ns === XLINK_NS)) {
            $value = trim($value);
            $internal = str_starts_with($value, '#') && preg_match('/^#[A-Za-z_][\w.:-]*$/', $value);
            $embedded = $isImage && preg_match('#^data:image/(png|jpeg|gif|webp);base64,[A-Za-z0-9+/=\s]+$#', $value);
            if (!$internal && !$embedded) {
                $el->removeAttributeNode($attr);
            }
            continue;
        }
        if ($ns === 'http://www.w3.org/XML/1998/namespace' && $name === 'space') {
            continue;
        }
        if ($ns !== null || !isset(svg_allowed_attributes()[$name]) || svg_value_unsafe($value)) {
            $el->removeAttributeNode($attr);
        }
    }
    // <image> без безопасного встроенного содержимого не нужен
    if ($isImage && !$el->hasAttribute('href') && !$el->hasAttributeNS(XLINK_NS, 'href')) {
        $el->parentNode?->removeChild($el);
    }
}

function svg_clean_children(DOMElement $el): void
{
    foreach (iterator_to_array($el->childNodes) as $child) {
        if ($child instanceof DOMElement) {
            if ($child->namespaceURI !== SVG_NS || !isset(svg_allowed_elements()[strtolower($child->localName)])) {
                $el->removeChild($child);
                continue;
            }
            svg_clean_attributes($child);
            if ($child->parentNode !== null) {
                svg_clean_children($child);
            }
        } elseif ($child instanceof DOMText) {
            // Текст оставляем только внутри текстовых элементов
            if (!in_array(strtolower($el->localName), ['text', 'tspan', 'title', 'desc'], true) && trim((string) $child->nodeValue) !== '') {
                $el->removeChild($child);
            }
        } else {
            $el->removeChild($child); // комментарии, инструкции обработки, ссылки на сущности
        }
    }
}
