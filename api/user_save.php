<?php
declare(strict_types=1);

/**
 * AJAX: create or update a user. Admin only.
 * POST { id?, username, display_name, role, password? }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$users    = new User($db);
$id       = (int) ($_POST['id'] ?? 0);
$username = trim((string) ($_POST['username'] ?? ''));
$display  = trim((string) ($_POST['display_name'] ?? ''));
$role     = (string) ($_POST['role'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$isCreate = $id === 0;

if ($display === '') {
    json_response(['ok' => false, 'error' => 'Display name is required.'], 422);
}
if (!in_array($role, User::ROLES, true)) {
    json_response(['ok' => false, 'error' => 'Invalid role.'], 422);
}

if ($isCreate) {
    if ($username === '') {
        json_response(['ok' => false, 'error' => 'Username is required.'], 422);
    }
    if ($users->usernameExists($username)) {
        json_response(['ok' => false, 'error' => 'Username already taken.'], 422);
    }
    if (strlen($password) < 6) {
        json_response(['ok' => false, 'error' => 'Password must be at least 6 characters.'], 422);
    }
    $users->create($username, $display, $role, $password);
    json_response(['ok' => true]);
}

// Update path
$existing = $users->findById($id);
if ($existing === null) {
    json_response(['ok' => false, 'error' => 'User not found.'], 404);
}
if ($password !== '' && strlen($password) < 6) {
    json_response(['ok' => false, 'error' => 'Password must be at least 6 characters.'], 422);
}
// Don't allow demoting the last remaining admin.
if ($existing['role'] === 'admin' && $role !== 'admin' && $users->adminCount() <= 1) {
    json_response(['ok' => false, 'error' => 'Cannot demote the only admin.'], 422);
}

$users->update($id, $display, $role, $password);
json_response(['ok' => true]);
