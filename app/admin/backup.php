<?php

declare(strict_types=1);

/*
 * Резервная копия: ZIP-архив с базой данных и загруженными картинками.
 * Архив не хранится на сервере — он собирается и сразу отдаётся в браузер.
 * Копия базы делается «на лету» безопасным способом (SQLite backup / VACUUM INTO),
 * поэтому сайт может работать во время скачивания.
 */

/** Снимок базы данных во временный файл. */
function backup_db_snapshot(string $target): void
{
    if (class_exists('SQLite3')) {
        try {
            $src = new SQLite3(Db::file(), SQLITE3_OPEN_READONLY);
            $src->busyTimeout(5000);
            $dst = new SQLite3($target);
            $ok = $src->backup($dst);
            $dst->close();
            $src->close();
            if ($ok) {
                return;
            }
        } catch (Throwable $e) {
            error_log('SQLite3 backup: ' . $e->getMessage());
        }
        if (is_file($target)) {
            @unlink($target);
        }
    }
    try {
        Db::run('VACUUM INTO ?', [$target]); // SQLite 3.27+
        return;
    } catch (Throwable $e) {
        error_log('VACUUM INTO: ' . $e->getMessage());
        if (is_file($target)) {
            @unlink($target);
        }
    }
    // Самый простой способ: копия файла, пока база заблокирована на запись.
    $pdo = Db::pdo();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        if (!copy(Db::file(), $target)) {
            throw new RuntimeException('Не удалось скопировать базу данных.');
        }
    } finally {
        $pdo->exec('ROLLBACK');
    }
}

/** Временная папка внутри data (системная временная папка на хостинге бывает недоступна). */
function backup_tmp_dir(): string
{
    $dir = DATA_DIR . '/tmp';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
        throw new RuntimeException('Не удалось создать папку data/tmp.');
    }
    // Остатки прошлых прерванных скачиваний
    foreach (glob($dir . '/backup-*') ?: [] as $old) {
        if ((int) @filemtime($old) < time() - 3600) {
            @unlink($old);
        }
    }
    return $dir;
}

/** Картинки, которые попадут в архив. */
function backup_upload_files(): array
{
    $files = [];
    foreach (scandir(UPLOADS_DIR) ?: [] as $name) {
        if (preg_match('/^[a-z0-9]{16,64}(?:-\d{1,4})?\.(?:webp|png|jpg|svg)$/', $name) && is_file(UPLOADS_DIR . '/' . $name)) {
            $files[] = $name;
        }
    }
    sort($files);
    return $files;
}

function backup_readme(): string
{
    return "Резервная копия сайта «" . setting('site.name', 'Байкал Сиб-Транс') . "»\n"
        . 'Создана: ' . date('d.m.Y H:i') . " (время иркутское)\n\n"
        . "Что внутри:\n"
        . "  site.sqlite — база данных: тексты, автопарк, цены, контакты, учётная запись администратора;\n"
        . "  uploads/    — загруженные фото и логотипы.\n\n"
        . "Как восстановить сайт из копии:\n"
        . "  1. Распакуйте архив на компьютере.\n"
        . "  2. В файловом менеджере хостинга замените файл data/site.sqlite файлом site.sqlite из архива.\n"
        . "  3. Загрузите содержимое папки uploads из архива в папку public_html/uploads на хостинге.\n"
        . "  4. Откройте сайт и проверьте, что всё на месте.\n\n"
        . "Подробнее — в файле README-RU.md, раздел «Резервная копия».\n";
}

/** Собирает архив и отдаёт его в браузер. */
function backup_send(): never
{
    @set_time_limit(600);
    $tmpDir = backup_tmp_dir();
    $snapshot = $tmpDir . '/backup-' . bin2hex(random_bytes(8)) . '.sqlite';
    register_shutdown_function(static function () use ($snapshot): void {
        if (is_file($snapshot)) {
            @unlink($snapshot);
        }
    });
    backup_db_snapshot($snapshot);
    $files = backup_upload_files();

    // Отмечаем дату копии до начала отправки: после первых байтов архива менять заголовки уже нельзя.
    admin_system_save(['last_backup' => time()]);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $host = preg_replace('/[^a-z0-9.-]+/', '', preg_replace('/:\d+$/', '', request_host()) ?? '') ?: 'site';
    $fileName = 'backup-' . $host . '-' . date('Y-m-d-Hi') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');

    $zip = new ZipStreamWriter();
    $zip->addString('README.txt', backup_readme());
    $zip->addFile('site.sqlite', $snapshot, true);
    foreach ($files as $name) {
        $zip->addFile('uploads/' . $name, UPLOADS_DIR . '/' . $name, false);
    }
    $zip->finish();
    @unlink($snapshot);
    exit;
}

/**
 * Простейшая запись ZIP-архива сразу в ответ сервера — без временного файла архива
 * и без расширения ZipArchive (на некоторых хостингах его нет).
 */
final class ZipStreamWriter
{
    private int $offset = 0;

    /** @var list<string> */
    private array $central = [];

    public function addString(string $name, string $content): void
    {
        $this->addEntry($name, $content, time(), true);
    }

    public function addFile(string $name, string $path, bool $compress): void
    {
        $mtime = (int) filemtime($path);
        if ($compress) {
            $this->addEntry($name, (string) file_get_contents($path), $mtime, true);
            return;
        }
        // Картинки уже сжаты — кладём как есть, читая файл по частям.
        $size = (int) filesize($path);
        $crc = (int) hexdec((string) hash_file('crc32b', $path));
        $this->writeHeader($name, 0, $crc, $size, $size, $mtime);
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('Не удалось прочитать ' . $name);
        }
        while (!feof($fh)) {
            $chunk = fread($fh, 1024 * 256);
            if ($chunk === false) {
                break;
            }
            echo $chunk;
        }
        fclose($fh);
        flush();
        $this->offset += $size;
    }

    private function addEntry(string $name, string $data, int $mtime, bool $compress): void
    {
        $crc = crc32($data);
        $size = strlen($data);
        $method = 0;
        if ($compress && function_exists('gzdeflate')) {
            $deflated = gzdeflate($data, 6);
            if ($deflated !== false && strlen($deflated) < $size) {
                $data = $deflated;
                $method = 8;
            }
        }
        $this->writeHeader($name, $method, $crc, strlen($data), $size, $mtime);
        echo $data;
        flush();
        $this->offset += strlen($data);
    }

    private function writeHeader(string $name, int $method, int $crc, int $compressedSize, int $size, int $mtime): void
    {
        [$dosTime, $dosDate] = self::dosDateTime($mtime);
        $flags = 0x0800; // имена файлов в UTF-8
        $local = pack('VvvvvvVVVvv', 0x04034b50, 20, $flags, $method, $dosTime, $dosDate, $crc, $compressedSize, $size, strlen($name), 0) . $name;
        $this->central[] = pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014b50, 20, 20, $flags, $method, $dosTime, $dosDate, $crc, $compressedSize, $size,
            strlen($name), 0, 0, 0, 0, 0, $this->offset
        ) . $name;
        echo $local;
        $this->offset += strlen($local);
    }

    public function finish(): void
    {
        $directory = implode('', $this->central);
        echo $directory;
        echo pack('VvvvvVVv', 0x06054b50, 0, 0, count($this->central), count($this->central), strlen($directory), $this->offset, 0);
        flush();
    }

    /** @return array{0: int, 1: int} время и дата в формате MS-DOS */
    private static function dosDateTime(int $timestamp): array
    {
        $d = getdate(max($timestamp, 315532800)); // не раньше 1980 года
        return [
            ($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2),
            (($d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'],
        ];
    }
}
