<?php
declare(strict_types=1);

/**
 * Monthly attendance (12 Gregorian months). value 0 = no data / gap.
 */
final class Attendance
{
    public static function months(Database $db): array
    {
        return $db->query(
            'SELECT id, month, value, sort_order
             FROM attendance_monthly ORDER BY sort_order'
        )->fetchAll();
    }

    /** Average attendance across months that have data (value > 0). */
    public static function averageRate(Database $db): int
    {
        $row = $db->query(
            'SELECT COALESCE(ROUND(AVG(value)), 0) AS avg
             FROM attendance_monthly WHERE value > 0'
        )->fetch();
        return (int) $row['avg'];
    }

    /**
     * Update monthly rates. $values maps attendance_monthly.id => int
     * (0 = no data). Out-of-range numbers are clamped to 0..100;
     * unknown ids are ignored.
     */
    public static function updateValues(Database $db, array $values): void
    {
        $stmt = $db->pdo()->prepare(
            'UPDATE attendance_monthly SET value = ? WHERE id = ?'
        );
        foreach ($values as $id => $val) {
            $stmt->execute([max(0, min(100, (int) $val)), (int) $id]);
        }
    }
}
