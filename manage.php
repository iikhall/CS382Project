<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_admin();

$classes  = ClassModel::allWithCounts($db);
$userRepo = new User($db);
$supervisors      = $userRepo->supervisors();
$gradeSupervisors = GradeSupervisor::all($db);
$subjectsByClass = [];
foreach ($classes as $c) {
    $subjectsByClass[(int) $c['id']] = Subject::forClass($db, (int) $c['id']);
}

$pageTitle   = 'Classes';
$activeNav   = 'manage';
$pageScripts = [base_url('assets/js/manage.js')];
require __DIR__ . '/includes/header.php';
?>

<div class="flex-between mb-4">
  <div>
    <h1 class="page-title">Class &amp; Course Management</h1>
    <p class="subtle">Add or remove classes, manage courses and assign teachers.</p>
  </div>
  <button type="button" id="addClassBtn" class="btn btn-primary">Add Class</button>
</div>

<div class="card mb-4">
  <h2 class="card-title mb-4">Grade Supervisors</h2>
  <p class="subtle">Assign one supervisor to each whole grade.</p>
  <table class="user-table">
    <thead>
      <tr><th>Grade</th><th>Supervisor</th><th class="ta-right">Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($gradeSupervisors as $gs): ?>
        <tr>
          <td><?= htmlspecialchars($gs['grade']) ?></td>
          <td>
            <select class="form-control grade-sup-select"
                    data-grade="<?= htmlspecialchars($gs['grade']) ?>">
              <option value="0">— Unassigned —</option>
              <?php foreach ($supervisors as $sv): ?>
                <option value="<?= (int) $sv['id'] ?>"
                  <?= (int) ($gs['supervisor_user_id'] ?? 0) === (int) $sv['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($sv['display_name']) ?> (<?= htmlspecialchars($sv['username']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </td>
          <td class="ta-right">
            <button type="button" class="btn btn-secondary grade-sup-save"
                    data-grade="<?= htmlspecialchars($gs['grade']) ?>">Save</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div id="classWrap">
  <?php if (!$classes): ?>
    <p class="subtle" id="noClasses">No classes yet. Add one to get started.</p>
  <?php endif; ?>

  <?php foreach ($classes as $c): $cid = (int) $c['id']; ?>
    <section class="card mb-4 class-block" data-id="<?= $cid ?>">
      <div class="flex-between mb-4">
        <div>
          <h2 class="card-title"><?= htmlspecialchars($c['name']) ?></h2>
          <p class="subtle">
            <?= htmlspecialchars($c['grade']) ?> &middot; Section <?= (int) $c['section'] ?>
            &middot; <?= htmlspecialchars($c['semester']) ?>
            &middot; Supervisor: <?= htmlspecialchars($c['supervisor'] !== '' ? $c['supervisor'] : '—') ?>
          </p>
        </div>
        <div class="snap-actions">
          <button type="button" class="btn btn-secondary add-course"
                  data-class-id="<?= $cid ?>" data-class-name="<?= htmlspecialchars($c['name']) ?>">
            Add Course
          </button>
          <button type="button" class="btn btn-danger del-class"
                  data-id="<?= $cid ?>" data-name="<?= htmlspecialchars($c['name']) ?>">
            Delete Class
          </button>
        </div>
      </div>

      <table class="user-table course-table">
        <thead>
          <tr>
            <th>Course</th><th>Teacher</th>
            <th>Exc</th><th>V.Good</th><th>Good</th><th>Acc</th><th>Fail</th>
            <th class="ta-right">Actions</th>
          </tr>
        </thead>
        <tbody data-class-id="<?= $cid ?>">
          <?php if (empty($subjectsByClass[$cid])): ?>
            <tr class="course-empty"><td colspan="8" class="subtle">No courses yet.</td></tr>
          <?php else: foreach ($subjectsByClass[$cid] as $s): ?>
            <tr data-id="<?= (int) $s['id'] ?>">
              <td><?= htmlspecialchars($s['name']) ?></td>
              <td><?= htmlspecialchars($s['teacher'] !== '' ? $s['teacher'] : '—') ?></td>
              <td><?= (int) $s['excellent'] ?></td>
              <td><?= (int) $s['very_good'] ?></td>
              <td><?= (int) $s['good'] ?></td>
              <td><?= (int) $s['acceptable'] ?></td>
              <td><?= (int) $s['fail'] ?></td>
              <td class="ta-right">
                <button type="button" class="btn btn-secondary edit-course"
                        data-id="<?= (int) $s['id'] ?>"
                        data-class-id="<?= $cid ?>"
                        data-name="<?= htmlspecialchars($s['name']) ?>"
                        data-teacher="<?= htmlspecialchars($s['teacher']) ?>"
                        data-excellent="<?= (int) $s['excellent'] ?>"
                        data-very_good="<?= (int) $s['very_good'] ?>"
                        data-good="<?= (int) $s['good'] ?>"
                        data-acceptable="<?= (int) $s['acceptable'] ?>"
                        data-fail="<?= (int) $s['fail'] ?>">Edit</button>
                <button type="button" class="btn btn-danger del-course"
                        data-id="<?= (int) $s['id'] ?>"
                        data-name="<?= htmlspecialchars($s['name']) ?>">Delete</button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </section>
  <?php endforeach; ?>
</div>

<!-- Add class modal -->
<div class="modal-backdrop" id="classModal" role="dialog" aria-modal="true"
     aria-labelledby="classModalTitle">
  <div class="modal">
    <h3 id="classModalTitle" class="card-title mb-4">Add Class</h3>
    <form id="classForm">
      <div class="grid grid-2">
        <div class="form-group">
          <label for="cCode">Code</label>
          <input type="text" id="cCode" class="form-control" placeholder="e.g. g1-5">
        </div>
        <div class="form-group">
          <label for="cName">Display Name</label>
          <input type="text" id="cName" class="form-control" placeholder="e.g. Class 1-E">
        </div>
        <div class="form-group">
          <label for="cGrade">Grade</label>
          <input type="text" id="cGrade" class="form-control" placeholder="e.g. Grade 1">
        </div>
        <div class="form-group">
          <label for="cSection">Section</label>
          <input type="number" id="cSection" class="form-control" min="1" max="99" value="1">
        </div>
        <div class="form-group">
          <label for="cSemester">Semester</label>
          <input type="text" id="cSemester" class="form-control" value="Semester 1">
        </div>
        <div class="form-group">
          <label for="cSupervisor">Supervisor</label>
          <input type="text" id="cSupervisor" class="form-control">
        </div>
      </div>
      <span class="field-error" id="classError"></span>
      <div class="modal-actions">
        <button type="submit" class="btn btn-primary">Create</button>
        <button type="button" id="cancelClassBtn" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit course modal -->
<div class="modal-backdrop" id="courseModal" role="dialog" aria-modal="true"
     aria-labelledby="courseModalTitle">
  <div class="modal">
    <h3 id="courseModalTitle" class="card-title mb-4">Add Course</h3>
    <form id="courseForm">
      <input type="hidden" id="sId" value="0">
      <input type="hidden" id="sClassId" value="0">
      <div class="grid grid-2">
        <div class="form-group">
          <label for="sName">Course Name</label>
          <input type="text" id="sName" class="form-control">
        </div>
        <div class="form-group">
          <label for="sTeacher">Teacher</label>
          <input type="text" id="sTeacher" class="form-control"
                 placeholder="e.g. Teacher A">
        </div>
      </div>
      <p class="subtle">Grade-band distribution</p>
      <div class="grid grid-3">
        <div class="form-group">
          <label for="sExcellent">Excellent</label>
          <input type="number" id="sExcellent" class="form-control" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="sVeryGood">Very Good</label>
          <input type="number" id="sVeryGood" class="form-control" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="sGood">Good</label>
          <input type="number" id="sGood" class="form-control" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="sAcceptable">Acceptable</label>
          <input type="number" id="sAcceptable" class="form-control" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="sFail">Fail</label>
          <input type="number" id="sFail" class="form-control" min="0" value="0">
        </div>
      </div>
      <span class="field-error" id="courseError"></span>
      <div class="modal-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <button type="button" id="cancelCourseBtn" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm delete modal -->
<div class="modal-backdrop" id="confirmModal" role="dialog" aria-modal="true"
     aria-labelledby="confirmTitle">
  <div class="modal">
    <h3 id="confirmTitle" class="card-title mb-4">Are you sure?</h3>
    <p class="subtle mb-4" id="confirmText">This cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" id="confirmYesBtn" class="btn btn-danger">Delete</button>
      <button type="button" id="confirmNoBtn" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
