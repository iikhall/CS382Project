<?php
declare(strict_types=1);

/**
 * Academic courses (subjects) inside a class. Each course has a
 * teacher *name* (text — teachers are not system users) plus the
 * grade-band distribution used by the donut charts.
 */
final class Subject
{
    public static function forClass(Database $db, int $classId): array
    {
        return $db->query(
            'SELECT id, name, teacher, excellent, very_good, good,
                    acceptable, fail, sort_order
             FROM subjects WHERE class_id = ? ORDER BY sort_order, id',
            [$classId]
        )->fetchAll();
    }

    public static function find(Database $db, int $id): ?array
    {
        $row = $db->query('SELECT * FROM subjects WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    private static function clampBand(int $n): int
    {
        return max(0, min(1000, $n));
    }

    public static function create(
        Database $db,
        int $classId,
        string $name,
        string $teacher,
        array $bands
    ): int {
        $next = (int) $db->query(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM subjects WHERE class_id = ?',
            [$classId]
        )->fetch()['n'];

        $db->query(
            'INSERT INTO subjects
                (class_id, name, teacher, excellent, very_good, good,
                 acceptable, fail, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $classId, $name, $teacher,
                self::clampBand($bands['excellent']),
                self::clampBand($bands['very_good']),
                self::clampBand($bands['good']),
                self::clampBand($bands['acceptable']),
                self::clampBand($bands['fail']),
                $next,
            ]
        );
        return (int) $db->pdo()->lastInsertId();
    }

    public static function update(
        Database $db,
        int $id,
        string $name,
        string $teacher,
        array $bands
    ): void {
        $db->query(
            'UPDATE subjects
             SET name = ?, teacher = ?, excellent = ?, very_good = ?,
                 good = ?, acceptable = ?, fail = ?
             WHERE id = ?',
            [
                $name, $teacher,
                self::clampBand($bands['excellent']),
                self::clampBand($bands['very_good']),
                self::clampBand($bands['good']),
                self::clampBand($bands['acceptable']),
                self::clampBand($bands['fail']),
                $id,
            ]
        );
    }

    /** Supervisor/admin sets just the course's teacher name. */
    public static function assignTeacher(Database $db, int $id, string $teacher): void
    {
        $db->query('UPDATE subjects SET teacher = ? WHERE id = ?', [$teacher, $id]);
    }

    public static function delete(Database $db, int $id): void
    {
        $db->query('DELETE FROM subjects WHERE id = ?', [$id]);
    }
}
