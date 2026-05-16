<?php
declare(strict_types=1);

/**
 * Motivational stars awarded to classes.
 */
final class Star
{
    public const AWARDERS = ['principal', 'vice_principal'];

    /** Stars for one class, newest first. */
    public static function forClass(Database $db, int $classId): array
    {
        return $db->query(
            'SELECT awarded_by, awarded_by_name, reason, awarded_at
             FROM stars WHERE class_id = ? ORDER BY awarded_at DESC, id DESC',
            [$classId]
        )->fetchAll();
    }

    public static function countForClass(Database $db, int $classId): int
    {
        $row = $db->query(
            'SELECT COUNT(*) AS n FROM stars WHERE class_id = ?',
            [$classId]
        )->fetch();
        return (int) $row['n'];
    }

    /** Insert a star. $awardedBy must be one of self::AWARDERS. */
    public static function award(
        Database $db,
        int $classId,
        string $awardedBy,
        string $awardedByName,
        string $reason
    ): void {
        $db->query(
            'INSERT INTO stars (class_id, awarded_by, awarded_by_name, reason)
             VALUES (?, ?, ?, ?)',
            [$classId, $awardedBy, $awardedByName, $reason]
        );
    }
}
