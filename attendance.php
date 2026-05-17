<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Both admin and supervisor may manage the attendance rate.
require_login();

$months = Attendance::months($db);

$pageTitle   = 'Attendance';
$activeNav   = 'attendance';
$pageScripts = [base_url('assets/js/attendance.js')];
require __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
  <h1 class="page-title">Disciplined Attendance Rate (Academic Year)</h1>
  <p class="subtle">Set the monthly attendance percentage. Use 0 for a month with no data.</p>
</div>

<div class="card">
  <form id="attendanceForm">
    <table class="user-table">
      <thead>
        <tr><th>Month</th><th>Attendance Rate (%)</th></tr>
      </thead>
      <tbody>
        <?php foreach ($months as $m): ?>
          <tr>
            <td><?= htmlspecialchars($m['month']) ?></td>
            <td>
              <label class="sr-only" for="m<?= (int) $m['id'] ?>">
                <?= htmlspecialchars($m['month']) ?> attendance rate
              </label>
              <input type="number" id="m<?= (int) $m['id'] ?>"
                     name="value[<?= (int) $m['id'] ?>]"
                     class="form-control" min="0" max="100" step="1"
                     value="<?= (int) $m['value'] ?>">
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <span class="field-error" id="attErr"></span>
    <button type="submit" id="saveAttBtn" class="btn btn-primary">Save Attendance</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
