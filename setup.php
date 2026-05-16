<?php
declare(strict_types=1);

/**
 * One-time initializer: seeds the admin + staff users with
 * bcrypt-hashed passwords. Idempotent (skips users that exist).
 * Run once in the browser after importing schema.sql, then it
 * is safe to leave in place (it will report "already seeded").
 */

require_once __DIR__ . '/includes/db.php';

$seed = [
    ['username' => 'admin',  'password' => 'Admin@123', 'role' => 'admin',      'display_name' => 'Admin'],
    ['username' => 'super1', 'password' => 'Super@123', 'role' => 'supervisor', 'display_name' => 'Supervisor One'],
];

$created = [];
$skipped = [];
$pdo = $db->pdo();

foreach ($seed as $u) {
    $exists = $db->query('SELECT id FROM users WHERE username = ?', [$u['username']])->fetch();
    if ($exists) {
        $skipped[] = $u['username'];
        continue;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, role, display_name)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $u['username'],
        password_hash($u['password'], PASSWORD_BCRYPT),
        $u['role'],
        $u['display_name'],
    ]);
    $created[] = $u['username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup &middot; School Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="logo" aria-hidden="true">SD</div>
      <h1>Setup Complete</h1>
      <p class="subtle">Seed users for the School Dashboard</p>

      <div class="form-group">
        <label>Created</label>
        <div class="form-control is-readonly">
          <?= $created ? htmlspecialchars(implode(', ', $created)) : 'none' ?>
        </div>
      </div>
      <div class="form-group">
        <label>Already existed (skipped)</label>
        <div class="form-control is-readonly">
          <?= $skipped ? htmlspecialchars(implode(', ', $skipped)) : 'none' ?>
        </div>
      </div>

      <p class="subtle mb-4">
        Admin: <strong>admin / Admin@123</strong><br>
        Supervisor: <strong>super1 / Super@123</strong>
      </p>
      <a class="btn btn-primary btn-block" href="login.php">Go to Login</a>
    </div>
  </div>
</body>
</html>
