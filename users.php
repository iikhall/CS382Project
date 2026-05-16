<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_admin();

$users = (new User($db))->all();
$me    = User::current();

$pageTitle   = 'Users';
$activeNav   = 'users';
$pageScripts = [base_url('assets/js/users.js')];
require __DIR__ . '/includes/header.php';
?>

<div class="flex-between mb-4">
  <div>
    <h1 class="page-title">User Management</h1>
    <p class="subtle">Create and manage admin and supervisor accounts.</p>
  </div>
  <button type="button" id="addUserBtn" class="btn btn-primary">Add User</button>
</div>

<div class="card">
  <table class="user-table">
    <thead>
      <tr>
        <th>Username</th>
        <th>Display Name</th>
        <th>Role</th>
        <th>Created</th>
        <th class="ta-right">Actions</th>
      </tr>
    </thead>
    <tbody id="userTbody">
      <?php foreach ($users as $u): ?>
        <tr data-id="<?= (int) $u['id'] ?>">
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td class="u-display"><?= htmlspecialchars($u['display_name']) ?></td>
          <td>
            <span class="role-tag role-<?= htmlspecialchars($u['role']) ?>">
              <?= htmlspecialchars(ucwords(str_replace('_', ' ', $u['role']))) ?>
            </span>
          </td>
          <td class="subtle"><?= htmlspecialchars($u['created_at']) ?></td>
          <td class="ta-right">
            <button type="button" class="btn btn-secondary u-edit"
                    data-id="<?= (int) $u['id'] ?>"
                    data-username="<?= htmlspecialchars($u['username']) ?>"
                    data-display="<?= htmlspecialchars($u['display_name']) ?>"
                    data-role="<?= htmlspecialchars($u['role']) ?>">Edit</button>
            <?php if ((int) $u['id'] !== (int) $me['id']): ?>
              <button type="button" class="btn btn-danger u-delete"
                      data-id="<?= (int) $u['id'] ?>"
                      data-username="<?= htmlspecialchars($u['username']) ?>">Delete</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add/Edit modal -->
<div class="modal-backdrop" id="userModal" role="dialog" aria-modal="true"
     aria-labelledby="userModalTitle">
  <div class="modal">
    <h3 id="userModalTitle" class="card-title mb-4">Add User</h3>
    <form id="userForm">
      <input type="hidden" id="userId" value="0">
      <div class="form-group" id="usernameGroup">
        <label for="uUsername">Username</label>
        <input type="text" id="uUsername" class="form-control" autocomplete="off">
      </div>
      <div class="form-group">
        <label for="uDisplay">Display Name</label>
        <input type="text" id="uDisplay" class="form-control">
      </div>
      <div class="form-group">
        <label for="uRole">Role</label>
        <select id="uRole" class="form-control">
          <option value="supervisor">Supervisor</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label for="uPassword" id="uPasswordLabel">Password</label>
        <input type="password" id="uPassword" class="form-control" autocomplete="new-password">
      </div>
      <span class="field-error" id="userError"></span>
      <div class="modal-actions">
        <button type="submit" id="saveUserBtn" class="btn btn-primary">Save</button>
        <button type="button" id="cancelUserBtn" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm delete modal -->
<div class="modal-backdrop" id="delModal" role="dialog" aria-modal="true"
     aria-labelledby="delTitle">
  <div class="modal">
    <h3 id="delTitle" class="card-title mb-4">Delete user?</h3>
    <p class="subtle mb-4" id="delText">This cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" id="confirmDelBtn" class="btn btn-danger">Delete</button>
      <button type="button" id="cancelDelBtn" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
