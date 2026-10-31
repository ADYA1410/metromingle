<?php
require_once __DIR__ . '/../auth/check.php';
require_role('admin');

$status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$query = http_build_query(array_filter(['status' => $status, 'q' => $q]));

if (is_post()) {
    csrf_check();
    $spaceId = (int)($_POST['space_id'] ?? 0);
    $space = db_one('SELECT id, name, status FROM spaces WHERE id = ?', [$spaceId]);
    if (!$space) {
        flash('danger', 'Space not found.');
    } else {
        $new = $space['status'] === 'active' ? 'inactive' : 'active';
        db_run('UPDATE spaces SET status = ? WHERE id = ?', [$new, $spaceId]);
        flash('success', '"' . $space['name'] . '" is now ' . $new . '.');
    }
    redirect('admin/spaces.php' . ($query ? '?' . $query : ''));
}

$sql = 'SELECT s.*, u.full_name AS owner_name, u.username AS owner_username, u.status AS owner_status,
          (SELECT COUNT(*) FROM bookings b WHERE b.space_id = s.id) AS booking_count
        FROM spaces s JOIN users u ON u.id = s.owner_id WHERE 1 = 1';
$params = [];
if ($status !== '') {
    $sql .= ' AND s.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $sql .= ' AND (s.name LIKE ? OR s.location LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)';
    array_push($params, $like, $like, $like, $like);
}
$spaces = db_all($sql . ' ORDER BY s.created_at DESC', $params);

$pageTitle = 'Spaces';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>Space Listings</h1>
  <p>All venues on the platform. Deactivated spaces are hidden from customers.</p>
</div>

<form class="card filter-bar filter-bar-compact" method="get">
  <div class="form-group">
    <label class="form-label" for="q">Search</label>
    <input class="form-control" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Space, location or owner">
  </div>
  <div class="form-group">
    <label class="form-label" for="status">Status</label>
    <select class="form-control" id="status" name="status">
      <option value="">All</option>
      <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
      <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
  </div>
  <div class="filter-actions">
    <button class="btn btn-primary" type="submit">Filter</button>
    <a class="btn btn-outline" href="<?php echo e(url('admin/spaces.php')); ?>">Reset</a>
  </div>
</form>

<?php if ($spaces): ?>
<div class="card table-card">
  <div class="table-wrap">
    <table class="data-table">
      <colgroup>
        <col class="col-space"><col class="col-owner"><col class="col-num"><col class="col-money">
        <col class="col-num"><col class="col-status"><col class="col-action">
      </colgroup>
      <thead><tr><th>Space</th><th>Owner</th><th>Capacity</th><th>Price/hr</th><th>Bookings</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($spaces as $s): ?>
        <tr>
          <td data-label="Space">
            <div class="cell-media">
              <?php echo photo_html($s['photo'], $s['name'], 'thumb'); ?>
              <div><strong><?php echo e($s['name']); ?></strong><div class="muted small"><?php echo e($s['location']); ?></div></div>
            </div>
          </td>
          <td data-label="Owner"><?php echo e($s['owner_name'] ?: $s['owner_username']); ?><?php echo $s['owner_status'] === 'inactive' ? ' <span class="badge badge-gray">owner inactive</span>' : ''; ?></td>
          <td data-label="Capacity"><?php echo (int)$s['capacity']; ?></td>
          <td data-label="Price/hr"><?php echo money($s['price_per_hour']); ?></td>
          <td data-label="Bookings"><?php echo (int)$s['booking_count']; ?></td>
          <td data-label="Status"><?php echo status_badge($s['status']); ?></td>
          <td data-label="Action">
            <div class="action-group">
              <?php if ($s['status'] === 'active' && $s['owner_status'] === 'active'): ?>
                <a class="btn btn-outline btn-sm" href="<?php echo e(url('user/space.php?id=' . (int)$s['id'])); ?>">View</a>
              <?php endif; ?>
              <form method="post" class="inline-form" action="<?php echo e(url('admin/spaces.php' . ($query ? '?' . $query : ''))); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="space_id" value="<?php echo (int)$s['id']; ?>">
                <button class="btn btn-sm <?php echo $s['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>" type="submit"><?php echo $s['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
  <div class="empty-state">No spaces match your filters.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
