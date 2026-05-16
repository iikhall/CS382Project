<?php
declare(strict_types=1);

/**
 * Single bootstrap entry point: session, autoload core classes, PDO handle.
 * Every page/endpoint requires this first.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $file = $root . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$db = Database::instance();
