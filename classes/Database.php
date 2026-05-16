<?php
declare(strict_types=1);

/**
 * PDO singleton. Laragon defaults: 127.0.0.1 / root / no password.
 */
final class Database
{
    private const HOST    = '127.0.0.1';
    private const DBNAME  = 'cs382project';
    private const USER    = 'root';
    private const PASS    = 'mysqlkhaled900';
    private const CHARSET = 'utf8mb4';

    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::HOST,
            self::DBNAME,
            self::CHARSET
        );

        $this->pdo = new PDO($dsn, self::USER, self::PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function instance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Prepared SELECT helper. */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
