<?php
declare(strict_types=1);

/**
 * Internal platform rating (1-5 stars).
 */
final class Feedback
{
    public static function submit(Database $db, int $score, ?int $userId): void
    {
        $db->query(
            'INSERT INTO feedback_ratings (score, submitted_by_user_id) VALUES (?, ?)',
            [$score, $userId]
        );
    }

    /** Aggregate { total, average } across all ratings. */
    public static function summary(Database $db): array
    {
        $row = $db->query(
            'SELECT COUNT(*) AS total, COALESCE(AVG(score), 0) AS average
             FROM feedback_ratings'
        )->fetch();

        return [
            'total'   => (int) $row['total'],
            'average' => round((float) $row['average'], 2),
        ];
    }
}
