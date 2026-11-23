<?php

declare(strict_types=1);

/*
 * Структура базы данных и её обновления (миграции).
 * Номер текущей версии хранится в самой базе (PRAGMA user_version).
 * Чтобы изменить структуру в будущем — добавьте новый номер в migrations(), старые не трогайте.
 */

final class Schema
{
    /** @return array<int, callable(PDO): void> */
    private static function migrations(): array
    {
        return [
            1 => static function (PDO $db): void {
                $db->exec(<<<'SQL'
                    CREATE TABLE settings (
                        key TEXT PRIMARY KEY,
                        value TEXT NOT NULL DEFAULT '{}',
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE users (
                        id INTEGER PRIMARY KEY,
                        username TEXT NOT NULL UNIQUE COLLATE NOCASE,
                        password_hash TEXT NOT NULL,
                        created_at INTEGER NOT NULL DEFAULT 0,
                        last_login_at INTEGER
                    );

                    CREATE TABLE login_attempts (
                        id INTEGER PRIMARY KEY,
                        ip TEXT NOT NULL,
                        created_at INTEGER NOT NULL
                    );
                    CREATE INDEX login_attempts_ip ON login_attempts (ip, created_at);

                    CREATE TABLE vehicles (
                        id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        type TEXT NOT NULL DEFAULT 'bus',
                        seats INTEGER NOT NULL DEFAULT 0,
                        features TEXT NOT NULL DEFAULT '[]',
                        custom_features TEXT NOT NULL DEFAULT '[]',
                        description TEXT NOT NULL DEFAULT '',
                        price_hour INTEGER,
                        min_hours INTEGER,
                        price_km INTEGER,
                        price_transfer INTEGER,
                        delivery_fee INTEGER,
                        price_on_request INTEGER NOT NULL DEFAULT 0,
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        is_demo INTEGER NOT NULL DEFAULT 0,
                        sort INTEGER NOT NULL DEFAULT 0,
                        created_at INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE vehicle_photos (
                        id INTEGER PRIMARY KEY,
                        vehicle_id INTEGER NOT NULL REFERENCES vehicles (id) ON DELETE CASCADE,
                        file TEXT NOT NULL,
                        widths TEXT NOT NULL DEFAULT '[]',
                        width INTEGER NOT NULL DEFAULT 0,
                        height INTEGER NOT NULL DEFAULT 0,
                        alt TEXT NOT NULL DEFAULT '',
                        is_cover INTEGER NOT NULL DEFAULT 0,
                        sort INTEGER NOT NULL DEFAULT 0,
                        created_at INTEGER NOT NULL DEFAULT 0
                    );
                    CREATE INDEX vehicle_photos_vehicle ON vehicle_photos (vehicle_id, sort);

                    CREATE TABLE services (
                        id INTEGER PRIMARY KEY,
                        title TEXT NOT NULL,
                        text TEXT NOT NULL DEFAULT '',
                        icon TEXT NOT NULL DEFAULT 'bus',
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE advantages (
                        id INTEGER PRIMARY KEY,
                        title TEXT NOT NULL,
                        text TEXT NOT NULL DEFAULT '',
                        icon TEXT NOT NULL DEFAULT 'check-circle',
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE steps (
                        id INTEGER PRIMARY KEY,
                        title TEXT NOT NULL,
                        text TEXT NOT NULL DEFAULT '',
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE stats (
                        id INTEGER PRIMARY KEY,
                        number INTEGER NOT NULL DEFAULT 0,
                        suffix TEXT NOT NULL DEFAULT '',
                        label TEXT NOT NULL DEFAULT '',
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE reviews (
                        id INTEGER PRIMARY KEY,
                        author TEXT NOT NULL,
                        company TEXT NOT NULL DEFAULT '',
                        text TEXT NOT NULL DEFAULT '',
                        review_date TEXT NOT NULL DEFAULT '',
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE partners (
                        id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        logo TEXT NOT NULL DEFAULT '',
                        url TEXT NOT NULL DEFAULT '',
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE faq (
                        id INTEGER PRIMARY KEY,
                        question TEXT NOT NULL,
                        answer TEXT NOT NULL DEFAULT '',
                        needs_review INTEGER NOT NULL DEFAULT 0,
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );

                    CREATE TABLE inspection_categories (
                        id INTEGER PRIMARY KEY,
                        code TEXT NOT NULL,
                        title TEXT NOT NULL,
                        description TEXT NOT NULL DEFAULT '',
                        price INTEGER,
                        price_from INTEGER NOT NULL DEFAULT 0,
                        is_visible INTEGER NOT NULL DEFAULT 1,
                        sort INTEGER NOT NULL DEFAULT 0,
                        updated_at INTEGER NOT NULL DEFAULT 0
                    );
                    SQL);
                Seed::run($db);
            },
        ];
    }

    public static function migrate(PDO $db): void
    {
        $migrations = self::migrations();
        $latest = max(array_keys($migrations));
        if ((int) $db->query('PRAGMA user_version')->fetchColumn() >= $latest) {
            return;
        }

        // Блокируем базу на запись, чтобы два одновременных запроса не создали таблицы дважды.
        $db->exec('BEGIN IMMEDIATE');
        try {
            $current = (int) $db->query('PRAGMA user_version')->fetchColumn();
            foreach ($migrations as $version => $migration) {
                if ($version <= $current) {
                    continue;
                }
                $migration($db);
                $db->exec('PRAGMA user_version = ' . (int) $version);
            }
            $db->exec('COMMIT');
        } catch (Throwable $e) {
            $db->exec('ROLLBACK');
            throw $e;
        }
    }
}
