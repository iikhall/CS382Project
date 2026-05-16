<?php
declare(strict_types=1);

/**
 * Internal staff contact messages (<= 10 words, allow-listed
 * recipient + category). Defense in depth: validated here AND
 * by DB CHECK constraints.
 */
final class Message
{
    public const RECIPIENTS = ['Student Affairs Deputy', 'Student Counselor'];
    public const CATEGORIES  = ['Complaint', 'Inquiry', 'Status Report', 'Consultation Request'];
    public const MAX_WORDS   = 10;

    public static function wordCount(string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }
        return count(preg_split('/\s+/', $text));
    }

    /**
     * Validate inputs. Returns an error string, or null if valid.
     */
    public static function validate(string $recipient, string $category, string $message): ?string
    {
        if (!in_array($recipient, self::RECIPIENTS, true)) {
            return 'Invalid recipient.';
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            return 'Invalid category.';
        }
        $message = trim($message);
        if ($message === '') {
            return 'Message cannot be empty.';
        }
        if (self::wordCount($message) > self::MAX_WORDS) {
            return 'Message exceeds the ' . self::MAX_WORDS . '-word limit.';
        }
        return null;
    }

    public static function create(
        Database $db,
        string $recipient,
        string $category,
        string $message,
        ?int $userId
    ): void {
        $db->query(
            'INSERT INTO internal_messages
                (recipient, category, message, submitted_by_user_id)
             VALUES (?, ?, ?, ?)',
            [$recipient, $category, trim($message), $userId]
        );
    }

    /** Newest-first list (admin Messages page). */
    public static function all(Database $db): array
    {
        return $db->query(
            'SELECT m.id, m.recipient, m.category, m.message, m.created_at,
                    u.display_name AS sender
             FROM internal_messages m
             LEFT JOIN users u ON u.id = m.submitted_by_user_id
             ORDER BY m.created_at DESC, m.id DESC'
        )->fetchAll();
    }

    public static function count(Database $db): int
    {
        return (int) $db->query('SELECT COUNT(*) AS n FROM internal_messages')
            ->fetch()['n'];
    }

    public static function clearAll(Database $db): void
    {
        $db->pdo()->exec('DELETE FROM internal_messages');
    }
}
