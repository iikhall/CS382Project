<?php
declare(strict_types=1);

/**
 * Snapshot payload. Admin only.
 *   GET ?id=N            -> JSON { ok, snapshot }
 *   GET ?id=N&download=1 -> JSON file attachment
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

$id   = (int) ($_GET['id'] ?? 0);
$snap = $id > 0 ? Snapshot::find($db, $id) : null;
if ($snap === null) {
    json_response(['ok' => false, 'error' => 'Snapshot not found.'], 404);
}

$classes = json_decode((string) $snap['classes_json'], true);

if ((string) ($_GET['download'] ?? '') === '1') {
    $fname = sprintf('snapshot-week%d-%s.json', (int) $snap['week'], $snap['snapshot_date']);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    echo json_encode([
        'week'          => (int) $snap['week'],
        'snapshot_date' => $snap['snapshot_date'],
        'saved_at'      => $snap['saved_at'],
        'saved_by_name' => $snap['saved_by_name'],
        'classes'       => $classes,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

json_response([
    'ok' => true,
    'snapshot' => [
        'week'          => (int) $snap['week'],
        'snapshot_date' => $snap['snapshot_date'],
        'saved_at'      => $snap['saved_at'],
        'saved_by_name' => $snap['saved_by_name'],
        'classes'       => $classes,
    ],
]);
