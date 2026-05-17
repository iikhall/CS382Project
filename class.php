<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$cl = $id > 0 ? ClassModel::find($db, $id) : null;
if ($cl === null) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

// A supervisor may only open a class in a grade they supervise.
if (!ClassModel::canEvaluate($db, $cl)) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$canEdit = ClassModel::canEvaluate($db, $cl);

// Admin or the grade's supervisor may assign teacher names to courses.
$canAssignTeachers = $canEdit;
$courses = $canAssignTeachers ? Subject::forClass($db, $id) : [];

$stars    = Star::forClass($db, $id);
$total    = (int) $cl['order_score'] + (int) $cl['cleanliness_score'] + (int) $cl['behavior_score'];
$gradeSupId   = GradeSupervisor::supervisorFor($db, $cl['grade']);
$gradeSupName = $gradeSupId !== null
    ? ((new User($db))->findById($gradeSupId)['display_name'] ?? 'Supervisor')
    : 'this class\'s supervisor';

$pageTitle   = $cl['name'];
$activeNav   = 'dashboard';
$pageScripts = [base_url('assets/js/class.js')];
require __DIR__ . '/includes/header.php';
?>

<div class="flex-between mb-4">
  <div>
    <h1 class="page-title"><?= htmlspecialchars($cl['name']) ?></h1>
    <p class="subtle">Manage weekly scores, notes and motivational stars.</p>
  </div>
  <div class="snap-actions">
    <a class="btn btn-secondary"
       href="<?= htmlspecialchars(base_url('report.php?id=' . (int) $cl['id'])) ?>">PDF Report</a>
    <a class="btn btn-secondary" href="<?= htmlspecialchars(base_url('dashboard.php')) ?>">Back</a>
  </div>
</div>

<div class="class-detail-grid" data-class-id="<?= (int) $cl['id'] ?>">

  <!-- Stars panel -->
  <div class="card stars-panel">
    <h2 class="card-title">Motivational Stars</h2>
    <p class="subtle mb-4">Stars granted by school leadership.</p>
    <?php if ($canEdit): ?>
    <button type="button" id="awardStarBtn" class="btn btn-primary btn-block">
      &#9733; Award Star
    </button>
    <?php endif; ?>
    <ul class="star-list" id="starList">
      <?php if (!$stars): ?>
        <li class="empty-state" id="starsEmpty">
          <span class="empty-star" aria-hidden="true">&#9734;</span>
          No stars yet
        </li>
      <?php else: foreach ($stars as $s): ?>
        <li class="star-item">
          <span class="star-glyph" aria-hidden="true">&#9733;</span>
          <div>
            <strong><?= htmlspecialchars($s['awarded_by_name']) ?></strong>
            <span class="subtle"><?= htmlspecialchars($s['awarded_at']) ?></span>
            <?php if ($s['reason'] !== '' && $s['reason'] !== null): ?>
              <p class="star-reason"><?= htmlspecialchars($s['reason']) ?></p>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>

  <!-- Score / evaluation panel -->
  <div class="card eval-panel">
    <div class="flex-between mb-4">
      <h2 class="card-title">Weekly Evaluation</h2>
      <div class="score-ring">
        <span class="score-ring-total" id="totalScore"><?= $total ?></span>
        <span class="score-ring-max">/30</span>
      </div>
    </div>

    <?php if (!$canEdit): ?>
      <p class="subtle mb-4">Read-only — your role cannot edit evaluation scores.</p>
    <?php endif; ?>

    <form id="evalForm" class="<?= $canEdit ? '' : 'is-locked' ?>">
      <?php
      $rows = [
          ['order',       'Discipline',  (int) $cl['order_score']],
          ['cleanliness', 'Cleanliness', (int) $cl['cleanliness_score']],
          ['behavior',    'Behavior',    (int) $cl['behavior_score']],
      ];
      foreach ($rows as [$key, $label, $val]): ?>
        <div class="score-edit-row">
          <label for="<?= $key ?>"><?= $label ?></label>
          <output class="score-out" id="<?= $key ?>Out"><?= $val ?> / 10</output>
          <input type="range" id="<?= $key ?>" name="<?= $key ?>"
                 class="score-slider" min="0" max="10" step="1" value="<?= $val ?>"
                 <?= $canEdit ? '' : 'disabled' ?>>
        </div>
      <?php endforeach; ?>

      <div class="grid grid-2">
        <div class="form-group">
          <label for="leader">Discipline Leader</label>
          <input type="text" id="leader" name="leader" class="form-control"
                 value="<?= htmlspecialchars($cl['discipline_leader']) ?>"
                 <?= $canEdit ? '' : 'disabled' ?>>
        </div>
        <div class="form-group">
          <label for="supervisor">Supervising Admin</label>
          <input type="text" id="supervisor" name="supervisor" class="form-control"
                 value="<?= htmlspecialchars($cl['supervisor']) ?>"
                 <?= $canEdit ? '' : 'disabled' ?>>
        </div>
      </div>

      <div class="form-group">
        <label for="notes">Motivation Notes</label>
        <textarea id="notes" name="notes" class="form-control" rows="3"
                  <?= $canEdit ? '' : 'disabled' ?>><?= htmlspecialchars((string) $cl['motivation_notes']) ?></textarea>
      </div>

      <?php if ($canEdit): ?>
        <button type="submit" id="saveBtn" class="btn btn-primary btn-block">Save Changes</button>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($canAssignTeachers): ?>
<div class="card mt-0">
  <h2 class="card-title mb-4">Courses &amp; Teachers</h2>
  <p class="subtle">Assign a teacher to each course in this class.</p>
  <table class="user-table course-table">
    <thead>
      <tr><th>Course</th><th>Teacher</th><th class="ta-right">Action</th></tr>
    </thead>
    <tbody>
      <?php if (!$courses): ?>
        <tr><td colspan="3" class="subtle">No courses in this class yet.</td></tr>
      <?php else: foreach ($courses as $co): ?>
        <tr>
          <td><?= htmlspecialchars($co['name']) ?></td>
          <td>
            <input type="text" class="form-control course-teacher"
                   data-subject-id="<?= (int) $co['id'] ?>"
                   value="<?= htmlspecialchars($co['teacher']) ?>"
                   placeholder="Teacher name">
          </td>
          <td class="ta-right">
            <button type="button" class="btn btn-secondary course-teacher-save"
                    data-subject-id="<?= (int) $co['id'] ?>">Save</button>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Award star modal -->
<div class="modal-backdrop" id="starModal" role="dialog" aria-modal="true"
     aria-labelledby="starModalTitle">
  <div class="modal">
    <h3 id="starModalTitle" class="card-title mb-4">
      Award a Star to <?= htmlspecialchars($cl['name']) ?>
    </h3>
    <p class="subtle">
      Awarded by the class supervisor:
      <strong><?= htmlspecialchars((string) $gradeSupName) ?></strong>
    </p>
    <div class="form-group">
      <label for="reason">Reason (optional)</label>
      <input type="text" id="reason" class="form-control" maxlength="255"
             placeholder="Why is this star awarded?">
      <span class="field-error" id="reasonError"></span>
    </div>
    <div class="modal-actions">
      <button type="button" id="confirmStarBtn" class="btn btn-primary">Confirm</button>
      <button type="button" id="cancelStarBtn" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
