<?php
declare(strict_types=1);

/**
 * Shared page shell (top). Expects (optional) before include:
 *   $pageTitle   string  - <title> + nothing visual
 *   $activeNav   string  - one of: dashboard|rankings|reports|attendance|manage|settings|users
 *   $contextPill string  - right-side header badge (defaults to "Week N")
 *
 * Requires includes/db.php + includes/auth_check.php already loaded,
 * and the page to have called require_login().
 */

$pageTitle   = $pageTitle   ?? 'Smart School Dashboard';
$activeNav   = $activeNav   ?? '';
$contextPill = $contextPill ?? ('Week ' . ClassModel::currentWeek());
$me          = User::current();
$isAdmin     = User::isAdmin();

// visibility: 'all' (admin + supervisor) | 'admin'
$nav = [
    'dashboard' => ['Dashboard', 'dashboard.php', 'all'],
    'rankings'  => ['Rankings',  'rankings.php',  'admin'],
    'reports'    => ['Reports',    'report.php',     'all'],
    'attendance' => ['Attendance', 'attendance.php', 'all'],
    'manage'     => ['Classes',    'manage.php',     'admin'],
    'settings'  => ['Settings',  'settings.php',  'admin'],
    'users'     => ['Users',     'users.php',     'admin'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> &middot; Smart School Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(base_url('assets/css/main.css')) ?>">
</head>
<body class="<?= $isAdmin ? 'theme-admin' : '' ?>">
  <header class="app-header">
    <div class="container">
      <div class="brand">
        <div class="logo" aria-hidden="true">SD</div>
        <div class="brand-text">
          <strong>Smart School Dashboard</strong>
          <span>Discipline &amp; Student Indicators</span>
        </div>
      </div>
      <div class="header-spacer"></div>
      <span class="pill-badge"><?= htmlspecialchars($contextPill) ?></span>
    </div>
  </header>

  <nav class="app-nav" aria-label="Primary">
    <div class="container">
      <?php foreach ($nav as $key => [$label, $file, $vis]): ?>
        <?php
          if ($vis === 'admin' && !$isAdmin) continue;
        ?>
        <a class="nav-link<?= $activeNav === $key ? ' active' : '' ?>"
           href="<?= htmlspecialchars(base_url($file)) ?>"
           <?= $activeNav === $key ? 'aria-current="page"' : '' ?>>
          <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
      <div class="header-spacer"></div>
      <span class="nav-link text-muted"><?= htmlspecialchars($me['display_name'] ?? '') ?></span>
      <a class="nav-link" href="<?= htmlspecialchars(base_url('logout.php')) ?>">Logout</a>
    </div>
  </nav>

  <main>
    <div class="container">
