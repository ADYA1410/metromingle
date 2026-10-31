<?php
require_once __DIR__ . '/../auth/check.php';
$user = require_role('user');
$userId = (int)$user['id'];

$counts = array_fill_keys(BOOKING_STATUSES, 0);
foreach (db_all('SELECT status, COUNT(*) AS c FROM bookings WHERE user_id = ? GROUP BY status', [$userId]) as $row) {
    $counts[$row['status']] = (int)$row['c'];
}
$upcoming = db_all(
    "SELECT b.*, s.name AS space_name, s.location FROM bookings b JOIN spaces s ON s.id = b.space_id
     WHERE b.user_id = ? AND b.booking_date >= CURDATE() AND b.status IN ('pending','confirmed')
     ORDER BY b.booking_date, b.start_time LIMIT 5",
    [$userId]
);
$latest = db_all(
    "SELECT s.* FROM spaces s JOIN users u ON u.id = s.owner_id WHERE s.status = 'active' AND u.status = 'active' ORDER BY s.created_at DESC LIMIT 3"
);

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>Welcome, <?php echo e($user['full_name'] ?: $user['username']); ?></h1>
  <p>Find a space and manage your booking requests.</p>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-number"><?php echo array_sum($counts); ?></div><div class="stat-label">Total bookings</div></div>
  <div class="stat-card stat-warning"><div class="stat-number"><?php echo $counts['pending']; ?></div><div class="stat-label">Pending</div></div>
  <div class="stat-card stat-success"><div class="stat-number"><?php echo $counts['confirmed']; ?></div><div class="stat-label">Confirmed</div></div>
  <div class="stat-card stat-gray"><div class="stat-number"><?php echo $counts['rejected'] + $counts['cancelled']; ?></div><div class="stat-label">Rejected / cancelled</div></div>
</div>

<div class="card">
  <div class="card-header"><h2>Upcoming events</h2><a href="<?php echo e(url('user/my_bookings.php')); ?>">View all</a></div>
  <?php if ($upcoming): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Space</th><th>Event</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($upcoming as $b): ?>
          <tr>
            <td data-label="Space"><strong><?php echo e($b['space_name']); ?></strong><div class="muted small"><?php echo e($b['location']); ?></div></td>
            <td data-label="Event"><?php echo e($b['event_type'] ?: '—'); ?></td>
            <td data-label="Date"><?php echo e(date('d M Y', strtotime($b['booking_date']))); ?></td>
            <td data-label="Time"><?php echo e(substr($b['start_time'], 0, 5) . ' – ' . substr($b['end_time'], 0, 5)); ?></td>
            <td data-label="Status"><?php echo status_badge($b['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">No upcoming events. <a href="<?php echo e(url('user/browse.php')); ?>">Browse spaces</a> to plan one.</div>
  <?php endif; ?>
</div>

<?php if ($latest): ?>
  <div class="page-header page-header-row section-gap">
    <div><h1>New spaces</h1></div>
    <a class="btn btn-outline btn-sm" href="<?php echo e(url('user/browse.php')); ?>">Browse all</a>
  </div>
  <div class="spaces-grid">
    <?php foreach ($latest as $s) { include __DIR__ . '/../includes/space_card.php'; } ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
