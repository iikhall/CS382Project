<?php
declare(strict_types=1);

/**
 * Monthly attendance (12 Hijri months). value 0 = no data / gap.
 */
final class Attendance
{
    public static function months(Database $db): array
    {
        return $db->query(
            'SELECT month, value FROM attendance_monthly ORDER BY sort_order'
        )->fetchAll();
    }
}
