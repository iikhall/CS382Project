<?php
declare(strict_types=1);

/**
 * AJAX: set the teacher name for a course.
 * Allowed: admin, or the supervisor of that course's grade.
 * POST { subject_id, teacher }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$subjectId = (int) ($_POST['subject_id'] ?? 0);
$teacher   = trim((string) ($_POST['teacher'] ?? ''));

$subject = $subjectId > 0 ? Subject::find($db, $subjectId) : null;
if ($subject === null) {
    json_response(['ok' => false, 'error' => 'Course not found.'], 404);
}

$class = ClassModel::find($db, (int) $subject['class_id']);
if ($class === null || !ClassModel::canEvaluate($db, $class)) {
    json_response(['ok' => false,
        'error' => 'Only an admin or this grade\'s supervisor can assign teachers.'], 403);
}

if (mb_strlen($teacher) > 100) {
    json_response(['ok' => false, 'error' => 'Teacher name is too long (max 100).'], 422);
}

Subject::assignTeacher($db, $subjectId, $teacher);
json_response(['ok' => true]);
