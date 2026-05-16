<?php
declare(strict_types=1);

/**
 * AJAX: create or update a course (subject) + its teacher. Admin only.
 * POST { id?, class_id, name, teacher,
 *        excellent, very_good, good, acceptable, fail }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$id      = (int) ($_POST['id'] ?? 0);
$classId = (int) ($_POST['class_id'] ?? 0);
$name    = trim((string) ($_POST['name'] ?? ''));
$teacher = trim((string) ($_POST['teacher'] ?? ''));

if ($name === '') {
    json_response(['ok' => false, 'error' => 'Course name is required.'], 422);
}
if (mb_strlen($teacher) > 100) {
    json_response(['ok' => false, 'error' => 'Teacher name is too long (max 100).'], 422);
}

$bands = [
    'excellent'  => (int) ($_POST['excellent']  ?? 0),
    'very_good'  => (int) ($_POST['very_good']  ?? 0),
    'good'       => (int) ($_POST['good']       ?? 0),
    'acceptable' => (int) ($_POST['acceptable'] ?? 0),
    'fail'       => (int) ($_POST['fail']       ?? 0),
];

if ($id > 0) {
    if (Subject::find($db, $id) === null) {
        json_response(['ok' => false, 'error' => 'Course not found.'], 404);
    }
    Subject::update($db, $id, $name, $teacher, $bands);
    json_response(['ok' => true, 'id' => $id]);
}

if ($classId <= 0 || ClassModel::find($db, $classId) === null) {
    json_response(['ok' => false, 'error' => 'Class not found.'], 404);
}
$newId = Subject::create($db, $classId, $name, $teacher, $bands);
json_response(['ok' => true, 'id' => $newId]);
