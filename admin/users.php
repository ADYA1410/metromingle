<?php
require_once __DIR__ . '/../auth/check.php';
$admin = require_role('admin');

$role = in_array($_GET['role'] ?? '', ['user', 'owner', 'admin'], true) ? $_GET['role'] : '';
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$query = http_build_query(array_filter(['role' => $role, 'q' => $q]));

if (is_post()) {
    csrf_check();
    $userId = (int)($_POST['user_id'] ?? 0);
    $target = db_one('SELECT id, username, role, status FROM users WHERE id = ?', [$userId]);
    if (!$target || $target['role'] === 'admin' || $userId === (int)$admin['id']) {
        flash('danger', 'Administrator accounts cannot be deactivated here.');
    } else {
        $new = $target['status'] === 'active' ? 'inactive' : 'active';
        db_run('UPDATE users SET status = ? WHERE id = ?', [$new, $userId]);
        flash('success', 'User "' . $target['username'] . '" is now ' . $new . '.' . ($new === 'inactive' && $target['role'] === 'owner' ? ' Their spaces are hidden from customers.' : ''));
    }
    redirect('admin/users.php' . ($query ? '?' . $query : ''));
}

$sql = "SELECT u.*,
          (SELECT COUNT(*) FROM spaces s WHERE s.owner_id = u.id) AS space_count,
          (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS booking_count
        FROM users u WHERE 1 = 1";
$params = [];
if ($role !== '') {
    $sql .= ' AND u.role = ?';
    $params[] = $role;
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $sql .= ' AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    array_push($params, $like, $like, $like);
}
$users = db_all($sql . ' ORDER BY u.created_at DESC', $params);

$pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>Users</h1>
  <p>Customers, space owners and administrators.</p>
</div>

<form class="card filter-bar filter-bar-compact" method="get">
  <div class="form-group">
    <label class="form-label" for="q">Search</label>
    <input class="form-control" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Name, username or email">
  </div>
  <div class="form-group">
    <label class="form-label" for="role">Role</label>
    <select class="form-control" id="role" name="role">
      <option value="">All roles</option>
      <?php foreach (['user' => 'Customers', 'owner' => 'Owners', 'admin' => 'Admins'] as $k => $label): ?>
        <option value="<?php echo e($k); ?>" <?php echo $role === $k ? 'selected' : ''; ?>><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button class="btn btn-primary" type="submit">Filter</button>
    <a class="btn btn-outline" href="<?php echo e(url('admin/users.php')); ?>">Reset</a>
  </div>
</form>

<?php if ($users): ?>
<div class="card table-card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Activity</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td data-label="#"><?php echo (int)$u['id']; ?></td>
          <td data-label="Name"><?php echo e($u['full_name'] ?: '—'); ?></td>
          <td data-label="Username"><?php echo e($u['username']); ?></td>
          <td data-label="Email"><?php echo e($u['email'] ?: '—'); ?></td>
          <td data-label="Role"><?php echo status_badge($u['role']); ?></td>
          <td data-label="Activity"><?php echo $u['role'] === 'owner' ? (int)$u['space_count'] . ' spaces' : ($u['role'] === 'user' ? (int)$u['booking_count'] . ' bookings' : '—'); ?></td>
          <td data-label="Joined"><?php echo e(date('d M Y', strtotime((string)$u['created_at']))); ?></td>
          <td data-label="Status"><?php echo status_badge($u['status']); ?></td>
          <td data-label="Action">
            <?php if ($u['role'] !== 'admin'): ?>
              <form method="post" class="inline-form" action="<?php echo e(url('admin/users.php' . ($query ? '?' . $query : ''))); ?>" <?php echo $u['status'] === 'active' ? 'data-confirm="Deactivate this user? They will no longer be able to log in."' : ''; ?>>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                <button class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>" type="submit"><?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
              </form>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
  <div class="empty-state">No users match your filters.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
