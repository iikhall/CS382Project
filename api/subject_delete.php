<?php
declare(strict_types=1);

/**
 * AJAX: delete a course (subject). Admin only.
 * POST { id }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 || Subject::find($db, $id) === null) {
    json_response(['ok' => false, 'error' => 'Course not found.'], 404);
}

Subject::delete($db, $id);
json_response(['ok' => true, 'id' => $id]);
