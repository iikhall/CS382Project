<?php
declare(strict_types=1);

/**
 * Weekly archive: a denormalized JSON copy of every class
 * (scores + stars) frozen at save time.
 */
final class Snapshot
{
    /** Build the denormalized snapshot payload for all classes. */
    private static function buildPayload(Database $db): array
    {
        $classes = $db->query(
            'SELECT id, code, name, grade, section,
                    order_score, cleanliness_score, behavior_score,
                    discipline_leader, supervisor, motivation_notes
             FROM classes ORDER BY sort_order'
        )->fetchAll();

        $starsByClass = [];
        foreach ($db->query('SELECT class_id, awarded_by, awarded_by_name,
                                     reason, awarded_at FROM stars')->fetchAll() as $s) {
            $starsByClass[(int) $s['class_id']][] = $s;
        }

        foreach ($classes as &$c) {
            $cid = (int) $c['id'];
            $c['total'] = (int) $c['order_score']
                + (int) $c['cleanliness_score']
                + (int) $c['behavior_score'];
            $c['stars'] = $starsByClass[$cid] ?? [];
        }
        unset($c);

        return $classes;
    }

    public static function save(Database $db, string $date, array $savedBy): array
    {
        $week    = ClassModel::currentWeek();
        $payload = self::buildPayload($db);

        $db->query(
            'INSERT INTO week_snapshots
                (week, snapshot_date, saved_by_role, saved_by_name, classes_json)
             VALUES (?, ?, ?, ?, ?)',
            [
                $week,
                $date,
                $savedBy['role'] ?? '',
                $savedBy['display_name'] ?? '',
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]
        );

        $id = (int) $db->pdo()->lastInsertId();
        return self::find($db, $id);
    }

    public static function all(Database $db): array
    {
        return $db->query(
            'SELECT id, week, snapshot_date, saved_at, saved_by_role,
                    saved_by_name,
                    JSON_LENGTH(classes_json) AS class_count
             FROM week_snapshots
             ORDER BY saved_at DESC, id DESC'
        )->fetchAll();
    }

    public static function find(Database $db, int $id): ?array
    {
        $row = $db->query('SELECT * FROM week_snapshots WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    public static function delete(Database $db, int $id): void
    {
        $db->query('DELETE FROM week_snapshots WHERE id = ?', [$id]);
    }

    public static function deleteAll(Database $db): void
    {
        $db->pdo()->exec('DELETE FROM week_snapshots');
    }
}
