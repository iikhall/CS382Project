<?php
declare(strict_types=1);

/**
 * Unified classes: discipline scores, stars, academic subjects,
 * plus the Sunday-based weekly auto-reset.
 */
final class ClassModel
{
    /** Sunday-based week-of-year number (weeks start on Sunday). */
    public static function currentWeek(?int $ts = null): int
    {
        $ts   = $ts ?? time();
        $year = (int) date('Y', $ts);
        $jan1 = (int) strtotime($year . '-01-01');
        $jan1Dow     = (int) date('w', $jan1);              // 0 = Sunday
        $firstSunday = $jan1 - $jan1Dow * 86400;            // Sunday on/before Jan 1
        $diffDays    = (int) floor(($ts - $firstSunday) / 86400);
        return (int) floor($diffDays / 7) + 1;
    }

    /**
     * If a new Sunday-based week has started, zero all discipline
     * scores + notes and clear every star. Snapshots are untouched.
     */
    public static function autoResetIfNewWeek(Database $db): void
    {
        $current = self::currentWeek();
        $stored  = (int) (Stat::meta($db, '_last_reset_week') ?? 0);

        if ($current !== $stored) {
            self::resetCurrent($db);
        }
    }

    /**
     * Zero all discipline scores + notes and clear every star,
     * then stamp the current Sunday-based week. Snapshots untouched.
     */
    public static function resetCurrent(Database $db): void
    {
        $pdo = $db->pdo();
        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE classes SET order_score = 0,
                        cleanliness_score = 0, behavior_score = 0,
                        motivation_notes = NULL');
            $pdo->exec('DELETE FROM stars');
            Stat::setMeta($db, '_last_reset_week', (string) self::currentWeek());
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Single class row, or null if not found. */
    public static function find(Database $db, int $id): ?array
    {
        $row = $db->query('SELECT * FROM classes WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Update discipline scores + notes for one class.
     * Scores are clamped to 0..10. Returns the new total (0..30).
     */
    public static function updateScores(
        Database $db,
        int $id,
        int $order,
        int $cleanliness,
        int $behavior,
        string $leader,
        string $supervisor,
        string $notes
    ): int {
        $clamp = static fn(int $n): int => max(0, min(10, $n));
        $order       = $clamp($order);
        $cleanliness = $clamp($cleanliness);
        $behavior    = $clamp($behavior);

        $db->query(
            'UPDATE classes
             SET order_score = ?, cleanliness_score = ?, behavior_score = ?,
                 discipline_leader = ?, supervisor = ?, motivation_notes = ?
             WHERE id = ?',
            [$order, $cleanliness, $behavior, $leader, $supervisor, $notes, $id]
        );
        return $order + $cleanliness + $behavior;
    }

    /** SQL `IN (...)` placeholders + params for a grade-scope filter. */
    private static function gradeFilter(?array $grades): array
    {
        if ($grades === null) {
            return ['', []];
        }
        if ($grades === []) {
            // Supervisor with no grade -> match nothing.
            return [' AND 1 = 0', []];
        }
        $ph = implode(',', array_fill(0, count($grades), '?'));
        return [" AND c.grade IN ($ph)", array_values($grades)];
    }

    /**
     * All classes (with total score + star count) grouped by grade.
     * Pass $grades (list of grade names) to restrict to a supervisor.
     */
    public static function allGroupedByGrade(Database $db, ?array $grades = null): array
    {
        [$where, $params] = self::gradeFilter($grades);
        $sql = 'SELECT c.*,
                    (c.order_score + c.cleanliness_score + c.behavior_score) AS total_score,
                    COUNT(st.id) AS star_count
             FROM classes c
             LEFT JOIN stars st ON st.class_id = c.id
             WHERE 1 = 1' . $where . '
             GROUP BY c.id ORDER BY c.sort_order';

        $grouped = [];
        foreach ($db->query($sql, $params)->fetchAll() as $r) {
            $grouped[$r['grade']][] = $r;
        }
        return $grouped;
    }

    /**
     * May the logged-in user edit/evaluate this class?
     * Admin → any class. Supervisor → only classes in a grade
     * the admin assigned to them.
     */
    public static function canEvaluate(Database $db, array $class): bool
    {
        if (User::isAdmin()) {
            return true;
        }
        if (!User::isSupervisor()) {
            return false;
        }
        $grades = GradeSupervisor::gradesForSupervisor($db, User::id());
        return in_array($class['grade'], $grades, true);
    }

    /**
     * Classes that have academic subjects, grouped by
     * "Semester - Grade", each with its subject distribution rows.
     * Pass $grades to restrict to a supervisor's grade(s).
     */
    public static function academic(Database $db, ?array $grades = null): array
    {
        [$where, $params] = self::gradeFilter($grades);
        $sql = 'SELECT c.id AS class_id, c.name AS class_name,
                    c.grade, c.semester,
                    s.name AS subject, s.teacher,
                    s.excellent, s.very_good, s.good, s.acceptable, s.fail
             FROM subjects s
             JOIN classes c ON c.id = s.class_id
             WHERE 1 = 1' . $where . '
             ORDER BY c.semester, c.sort_order, s.sort_order';
        $rows = $db->query($sql, $params)->fetchAll();

        $grouped = [];
        foreach ($rows as $r) {
            $key = $r['semester'] . ' - ' . $r['grade'] . ' - ' . $r['class_name'];
            $grouped[$key][] = $r;
        }
        return $grouped;
    }

    /** Flat list with subject count (admin management page). */
    public static function allWithCounts(Database $db): array
    {
        return $db->query(
            'SELECT c.*, COUNT(s.id) AS subject_count
             FROM classes c
             LEFT JOIN subjects s ON s.class_id = c.id
             GROUP BY c.id
             ORDER BY c.sort_order, c.id'
        )->fetchAll();
    }

    /**
     * Ranking: total score DESC, then star count DESC (tiebreaker).
     */
    public static function ranked(Database $db): array
    {
        return $db->query(
            'SELECT c.id, c.name, c.grade,
                    (c.order_score + c.cleanliness_score + c.behavior_score) AS total_score,
                    COUNT(s.id) AS star_count
             FROM classes c
             LEFT JOIN stars s ON s.class_id = c.id
             GROUP BY c.id
             ORDER BY total_score DESC, star_count DESC, c.sort_order'
        )->fetchAll();
    }

    public static function codeExists(Database $db, string $code): bool
    {
        return (bool) $db->query('SELECT id FROM classes WHERE code = ?', [$code])->fetch();
    }

    public static function create(
        Database $db,
        string $code,
        string $grade,
        int $section,
        string $name,
        string $semester,
        string $supervisor
    ): int {
        $next = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM classes')
            ->fetch()['n'];
        $db->query(
            'INSERT INTO classes (code, grade, section, name, semester, supervisor, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$code, $grade, $section, $name, $semester, $supervisor, $next]
        );
        return (int) $db->pdo()->lastInsertId();
    }

    /** Deletes the class; subjects and stars cascade via FK. */
    public static function delete(Database $db, int $id): void
    {
        $db->query('DELETE FROM classes WHERE id = ?', [$id]);
    }
}
