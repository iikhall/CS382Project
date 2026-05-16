<?php
declare(strict_types=1);

/**
 * AJAX login endpoint. Accepts POST { username, password }.
 * Returns JSON { ok, redirect } | { ok:false, error }.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$username = (string) ($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if (trim($username) === '' || $password === '') {
    json_response(['ok' => false, 'error' => 'Username and password are required.'], 422);
}

$user = (new User($db))->login($username, $password);

if ($user === null) {
    json_response(['ok' => false, 'error' => 'Invalid username or password.'], 401);
}

json_response(['ok' => true, 'redirect' => base_url('dashboard.php')]);
