<?php
declare(strict_types=1);

/**
 * Dashboard stat cards + reserved `_`-prefixed system meta.
 */
final class Stat
{
    /** Public stat cards only (reserved `_` keys excluded). */
    public static function cards(Database $db): array
    {
        return $db->query(
            "SELECT stat_key, value, label, sublabel
             FROM stats
             WHERE stat_key NOT LIKE '\\_%'
             ORDER BY id"
        )->fetchAll();
    }

    public static function meta(Database $db, string $key, ?string $default = null): ?string
    {
        $row = $db->query('SELECT value FROM stats WHERE stat_key = ?', [$key])->fetch();
        return $row ? (string) $row['value'] : $default;
    }

    public static function setMeta(Database $db, string $key, string $value): void
    {
        $db->query(
            'INSERT INTO stats (stat_key, value, label, sublabel)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$key, $value, 'system', '']
        );
    }
}
