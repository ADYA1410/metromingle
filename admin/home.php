<?php
require_once __DIR__ . '/../auth/check.php';
$user = require_role('admin');

$users = db_one("SELECT COUNT(*) AS total, COALESCE(SUM(role = 'user'), 0) AS customers, COALESCE(SUM(role = 'owner'), 0) AS owners, COALESCE(SUM(status = 'inactive'), 0) AS inactive FROM users");
$spaces = db_one("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active FROM spaces");
$bookings = db_one(
    "SELECT COUNT(*) AS total, COALESCE(SUM(status = 'pending'), 0) AS pending, COALESCE(SUM(status = 'confirmed'), 0) AS confirmed,
            COALESCE(SUM(CASE WHEN status = 'confirmed' THEN total_amount END), 0) AS value
     FROM bookings"
);
$recent = db_all(
    'SELECT b.*, s.name AS space_name, u.full_name AS customer_name, u.username AS customer_username
     FROM bookings b JOIN spaces s ON s.id = b.space_id JOIN users u ON u.id = b.user_id
     ORDER BY b.created_at DESC LIMIT 6'
);

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>Admin Dashboard</h1>
  <p>Platform overview based on live data.</p>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-number"><?php echo (int)$users['total']; ?></div><div class="stat-label"><?php echo (int)$users['customers']; ?> customers • <?php echo (int)$users['owners']; ?> owners • <?php echo (int)$users['inactive']; ?> inactive</div></div>
  <div class="stat-card"><div class="stat-number"><?php echo (int)$spaces['total']; ?></div><div class="stat-label">Spaces (<?php echo (int)$spaces['active']; ?> active)</div></div>
  <div class="stat-card stat-warning"><div class="stat-number"><?php echo (int)$bookings['pending']; ?></div><div class="stat-label">Pending of <?php echo (int)$bookings['total']; ?> bookings</div></div>
  <div class="stat-card stat-success"><div class="stat-number"><?php echo (int)$bookings['confirmed']; ?></div><div class="stat-label">Confirmed (<?php echo money($bookings['value']); ?> est.)</div></div>
</div>

<div class="card">
  <div class="card-header"><h2>Recent bookings</h2><a href="<?php echo e(url('admin/bookings.php')); ?>">Manage bookings</a></div>
  <?php if ($recent): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Space</th><th>Date</th><th>Est. Cost</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $b): ?>
          <tr>
            <td data-label="#"><?php echo (int)$b['id']; ?></td>
            <td data-label="Customer"><?php echo e($b['customer_name'] ?: $b['customer_username']); ?></td>
            <td data-label="Space"><?php echo e($b['space_name']); ?></td>
            <td data-label="Date"><?php echo e(date('d M Y', strtotime($b['booking_date']))); ?></td>
            <td data-label="Est. Cost"><?php echo money($b['total_amount']); ?></td>
            <td data-label="Status"><?php echo status_badge($b['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">No bookings yet.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
