<?php
declare(strict_types=1);

/**
 * AJAX: delete a user. Admin only.
 * Guards: cannot delete yourself; cannot delete the last admin.
 * POST { id }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$users = new User($db);
$id    = (int) ($_POST['id'] ?? 0);
$me    = User::current();

if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'Invalid user.'], 422);
}
if ($id === (int) ($me['id'] ?? 0)) {
    json_response(['ok' => false, 'error' => 'You cannot delete your own account.'], 422);
}

$target = $users->findById($id);
if ($target === null) {
    json_response(['ok' => false, 'error' => 'User not found.'], 404);
}
if ($target['role'] === 'admin' && $users->adminCount() <= 1) {
    json_response(['ok' => false, 'error' => 'Cannot delete the only admin.'], 422);
}

$users->delete($id);
json_response(['ok' => true, 'id' => $id]);
