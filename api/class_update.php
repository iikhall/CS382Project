<?php
declare(strict_types=1);

/**
 * AJAX: update one class's discipline scores + notes.
 * Admin & Vice Principal: any class. Teacher: only their assigned class.
 * POST { id, order, cleanliness, behavior, leader, supervisor, notes }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$id = (int) ($_POST['id'] ?? 0);
$cl = $id > 0 ? ClassModel::find($db, $id) : null;
if ($cl === null) {
    json_response(['ok' => false, 'error' => 'Class not found.'], 404);
}
if (!ClassModel::canEvaluate($db, $cl)) {
    json_response(['ok' => false, 'error' => 'You cannot evaluate this class.'], 403);
}

$total = ClassModel::updateScores(
    $db,
    $id,
    (int) ($_POST['order'] ?? 0),
    (int) ($_POST['cleanliness'] ?? 0),
    (int) ($_POST['behavior'] ?? 0),
    trim((string) ($_POST['leader'] ?? '')),
    trim((string) ($_POST['supervisor'] ?? '')),
    trim((string) ($_POST['notes'] ?? ''))
);

json_response(['ok' => true, 'total' => $total]);
