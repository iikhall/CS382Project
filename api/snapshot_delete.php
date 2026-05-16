<?php
declare(strict_types=1);

/**
 * AJAX: delete one snapshot, or all. Admin only (destructive).
 * POST { id } | { all: 1 }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

if ((string) ($_POST['all'] ?? '') === '1') {
    Snapshot::deleteAll($db);
    json_response(['ok' => true, 'all' => true]);
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 || Snapshot::find($db, $id) === null) {
    json_response(['ok' => false, 'error' => 'Snapshot not found.'], 404);
}

Snapshot::delete($db, $id);
json_response(['ok' => true, 'id' => $id]);
