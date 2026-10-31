<?php
require_once __DIR__ . '/../auth/check.php';
$user = require_role('owner');
$ownerId = (int)$user['id'];

$spaceStats = db_one("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active FROM spaces WHERE owner_id = ?", [$ownerId]);
$bookingStats = db_one(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(b.status = 'pending'), 0) AS pending,
            COALESCE(SUM(b.status = 'confirmed'), 0) AS confirmed,
            COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_amount END), 0) AS revenue
     FROM bookings b JOIN spaces s ON s.id = b.space_id WHERE s.owner_id = ?",
    [$ownerId]
);
$pending = db_all(
    "SELECT b.*, s.name AS space_name, u.full_name AS customer_name, u.username AS customer_username
     FROM bookings b JOIN spaces s ON s.id = b.space_id JOIN users u ON u.id = b.user_id
     WHERE s.owner_id = ? AND b.status = 'pending' ORDER BY b.booking_date LIMIT 5",
    [$ownerId]
);

$pageTitle = 'Owner Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header page-header-row">
  <div>
    <h1>Welcome, <?php echo e($user['full_name'] ?: $user['username']); ?></h1>
    <p>Overview of your listings and booking requests.</p>
  </div>
  <a class="btn btn-primary" href="<?php echo e(url('owner/add_space.php')); ?>">+ Add Space</a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-number"><?php echo (int)$spaceStats['total']; ?></div><div class="stat-label">Total spaces (<?php echo (int)$spaceStats['active']; ?> active)</div></div>
  <div class="stat-card stat-warning"><div class="stat-number"><?php echo (int)$bookingStats['pending']; ?></div><div class="stat-label">Pending requests</div></div>
  <div class="stat-card stat-success"><div class="stat-number"><?php echo (int)$bookingStats['confirmed']; ?></div><div class="stat-label">Confirmed bookings</div></div>
  <div class="stat-card"><div class="stat-number stat-money"><?php echo money($bookingStats['revenue']); ?></div><div class="stat-label">Est. value of confirmed bookings</div></div>
</div>

<div class="card">
  <div class="card-header"><h2>Pending requests</h2><a href="<?php echo e(url('owner/bookings.php?status=pending')); ?>">Manage all</a></div>
  <?php if ($pending): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Customer</th><th>Space</th><th>Event</th><th>Date &amp; Time</th><th>Guests</th><th>Est. Cost</th></tr></thead>
        <tbody>
        <?php foreach ($pending as $b): ?>
          <tr>
            <td data-label="Customer"><?php echo e($b['customer_name'] ?: $b['customer_username']); ?></td>
            <td data-label="Space"><?php echo e($b['space_name']); ?></td>
            <td data-label="Event"><?php echo e($b['event_type'] ?: '—'); ?></td>
            <td data-label="Date &amp; Time"><?php echo e(date('d M Y', strtotime($b['booking_date'])) . ', ' . substr($b['start_time'], 0, 5) . '–' . substr($b['end_time'], 0, 5)); ?></td>
            <td data-label="Guests"><?php echo (int)$b['guests']; ?></td>
            <td data-label="Est. Cost"><?php echo money($b['total_amount']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">No pending requests right now.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
