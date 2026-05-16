<?php
declare(strict_types=1);

/**
 * Authentication + RBAC. Session-based (PHP $_SESSION).
 */
final class User
{
    public function __construct(private Database $db) {}

    /**
     * Verify credentials and start an authenticated session.
     * Returns the public user row on success, null on failure.
     */
    public function login(string $username, string $password): ?array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return null;
        }

        $row = $this->db
            ->query('SELECT id, username, password_hash, role, display_name
                     FROM users WHERE username = ? LIMIT 1', [$username])
            ->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return null;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'           => (int) $row['id'],
            'username'     => $row['username'],
            'role'         => $row['role'],
            'display_name' => $row['display_name'],
        ];
        return $_SESSION['user'];
    }

    public static function current(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function role(): string
    {
        return (string) ($_SESSION['user']['role'] ?? '');
    }

    public static function id(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isSupervisor(): bool
    {
        return self::role() === 'supervisor';
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /* ---------------- Admin CRUD ---------------- */

    public const ROLES = ['admin', 'supervisor'];

    public function all(): array
    {
        return $this->db->query(
            'SELECT id, username, role, display_name, created_at
             FROM users ORDER BY id'
        )->fetchAll();
    }

    /** Users with the supervisor role (for grade-assignment dropdowns). */
    public function supervisors(): array
    {
        return $this->db->query(
            "SELECT id, display_name, username
             FROM users WHERE role = 'supervisor' ORDER BY display_name"
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT id, username, role, display_name FROM users WHERE id = ?',
            [$id]
        )->fetch();
        return $row ?: null;
    }

    public function usernameExists(string $username, int $exceptId = 0): bool
    {
        $row = $this->db->query(
            'SELECT id FROM users WHERE username = ? AND id <> ?',
            [$username, $exceptId]
        )->fetch();
        return (bool) $row;
    }

    public function adminCount(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) AS n FROM users WHERE role = 'admin'"
        )->fetch()['n'];
    }

    public function create(string $username, string $displayName, string $role, string $password): void
    {
        $this->db->query(
            'INSERT INTO users (username, password_hash, role, display_name)
             VALUES (?, ?, ?, ?)',
            [$username, password_hash($password, PASSWORD_BCRYPT), $role, $displayName]
        );
    }

    /** Update profile/role; password only changed when non-empty. */
    public function update(int $id, string $displayName, string $role, string $password = ''): void
    {
        if ($password !== '') {
            $this->db->query(
                'UPDATE users SET display_name = ?, role = ?, password_hash = ? WHERE id = ?',
                [$displayName, $role, password_hash($password, PASSWORD_BCRYPT), $id]
            );
        } else {
            $this->db->query(
                'UPDATE users SET display_name = ?, role = ? WHERE id = ?',
                [$displayName, $role, $id]
            );
        }
    }

    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM users WHERE id = ?', [$id]);
    }
}
