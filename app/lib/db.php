<?php

declare(strict_types=1);

/*
 * База данных SQLite: один файл data/site.sqlite.
 * Все запросы — только через подготовленные выражения (prepared statements).
 */

final class Db
{
    private static ?PDO $pdo = null;

    public static function file(): string
    {
        return DATA_DIR . '/site.sqlite';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        if (!is_dir(DATA_DIR) && !@mkdir(DATA_DIR, 0775, true)) {
            fatal_page('Не удалось создать папку data рядом с папкой сайта. Создайте её вручную через файловый менеджер хостинга.');
        }
        if (!is_writable(DATA_DIR)) {
            fatal_page('Папка data недоступна для записи. В файловом менеджере хостинга поставьте для неё права 755 (или 775).');
        }
        $file = self::file();
        if (is_file($file) && !is_writable($file)) {
            fatal_page('Файл data/site.sqlite недоступен для записи. Поставьте для него права 644 (или 664) в файловом менеджере хостинга.');
        }

        $pdo = new PDO('sqlite:' . $file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA foreign_keys = ON');
        // Временные данные — в памяти: на некоторых хостингах во временную папку SQLite писать нельзя.
        $pdo->exec('PRAGMA temp_store = MEMORY');
        self::$pdo = $pdo;

        Schema::migrate($pdo);
        return $pdo;
    }

    /** @return list<array<string, mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return is_array($row) ? $row : null;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Вставка строки. Имена таблицы и колонок задаются только в коде (не из формы). */
    public static function insert(string $table, array $data): int
    {
        self::assertIdentifier($table);
        $cols = array_keys($data);
        foreach ($cols as $col) {
            self::assertIdentifier($col);
        }
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES ('
            . implode(', ', array_fill(0, count($cols), '?')) . ')';
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, int $id, array $data): void
    {
        if (!$data) {
            return;
        }
        self::assertIdentifier($table);
        $sets = [];
        foreach (array_keys($data) as $col) {
            self::assertIdentifier($col);
            $sets[] = $col . ' = ?';
        }
        $params = array_values($data);
        $params[] = $id;
        self::run('UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function delete(string $table, int $id): void
    {
        self::assertIdentifier($table);
        self::run('DELETE FROM ' . $table . ' WHERE id = ?', [$id]);
    }

    /** Новый порядок строк: список id сверху вниз. */
    public static function reorder(string $table, array $ids, string $where = '', array $whereParams = []): void
    {
        self::assertIdentifier($table);
        self::transaction(static function () use ($table, $ids, $where, $whereParams): void {
            $pos = 0;
            foreach ($ids as $id) {
                $id = (int) $id;
                if ($id <= 0) {
                    continue;
                }
                $pos += 10;
                self::run(
                    'UPDATE ' . $table . ' SET sort = ? WHERE id = ?' . ($where !== '' ? ' AND ' . $where : ''),
                    array_merge([$pos, $id], $whereParams)
                );
            }
        });
    }

    public static function nextSort(string $table, string $where = '', array $params = []): int
    {
        self::assertIdentifier($table);
        $max = self::value('SELECT MAX(sort) FROM ' . $table . ($where !== '' ? ' WHERE ' . $where : ''), $params);
        return ((int) $max) + 10;
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function assertIdentifier(string $name): void
    {
        if (!preg_match('/^[a-z_][a-z0-9_]{0,63}$/', $name)) {
            throw new InvalidArgumentException('Недопустимое имя в запросе: ' . $name);
        }
    }
}
