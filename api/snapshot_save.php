<?php
declare(strict_types=1);

/**
 * AJAX: save the current week's scores/stars to the archive.
 * Admin only. POST { date: YYYY-MM-DD }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$date = trim((string) ($_POST['date'] ?? ''));
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    json_response(['ok' => false, 'error' => 'A valid date is required.'], 422);
}

$snap = Snapshot::save($db, $date, User::current() ?? []);

json_response([
    'ok'   => true,
    'snapshot' => [
        'id'            => (int) $snap['id'],
        'week'          => (int) $snap['week'],
        'snapshot_date' => $snap['snapshot_date'],
        'saved_at'      => $snap['saved_at'],
        'saved_by_name' => $snap['saved_by_name'],
    ],
]);
