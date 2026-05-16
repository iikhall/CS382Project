<?php
declare(strict_types=1);

/**
 * Maps a whole grade (e.g. "Grade 1") to one supervising user.
 * The admin assigns it; the supervisor then manages every class
 * in that grade.
 */
final class GradeSupervisor
{
    /** Make sure every grade that has classes has a mapping row. */
    public static function ensureGrades(Database $db): void
    {
        $db->pdo()->exec(
            'INSERT IGNORE INTO grade_supervisors (grade)
             SELECT DISTINCT grade FROM classes'
        );
    }

    /** All grades with their assigned supervisor (name may be null). */
    public static function all(Database $db): array
    {
        self::ensureGrades($db);
        return $db->query(
            'SELECT g.grade, g.supervisor_user_id,
                    u.display_name AS supervisor_name
             FROM grade_supervisors g
             LEFT JOIN users u ON u.id = g.supervisor_user_id
             ORDER BY g.grade'
        )->fetchAll();
    }

    /** Grades supervised by this user (usually one). */
    public static function gradesForSupervisor(Database $db, int $userId): array
    {
        $rows = $db->query(
            'SELECT grade FROM grade_supervisors WHERE supervisor_user_id = ?',
            [$userId]
        )->fetchAll();
        return array_map(static fn($r) => $r['grade'], $rows);
    }

    public static function supervisorFor(Database $db, string $grade): ?int
    {
        $row = $db->query(
            'SELECT supervisor_user_id FROM grade_supervisors WHERE grade = ?',
            [$grade]
        )->fetch();
        return $row && $row['supervisor_user_id'] !== null
            ? (int) $row['supervisor_user_id'] : null;
    }

    public static function assign(Database $db, string $grade, ?int $userId): void
    {
        $db->query(
            'INSERT INTO grade_supervisors (grade, supervisor_user_id)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE supervisor_user_id = VALUES(supervisor_user_id)',
            [$grade, $userId]
        );
    }
}
