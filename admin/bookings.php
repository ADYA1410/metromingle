<?php
require_once __DIR__ . '/../auth/check.php';
require_role('admin');

$status = in_array($_GET['status'] ?? '', BOOKING_STATUSES, true) ? $_GET['status'] : '';
$query = $status !== '' ? '?status=' . $status : '';

// Allowed admin transitions: current status => permitted new statuses.
$transitions = ['pending' => ['confirmed', 'rejected', 'cancelled'], 'confirmed' => ['cancelled']];

if (is_post()) {
    csrf_check();
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $new = (string)($_POST['new_status'] ?? '');
    $booking = db_one('SELECT * FROM bookings WHERE id = ?', [$bookingId]);
    if (!$booking || !in_array($new, $transitions[$booking['status']] ?? [], true)) {
        flash('danger', 'That status change is not allowed.');
    } elseif ($new === 'confirmed' && db_value(
        "SELECT COUNT(*) FROM bookings WHERE space_id = ? AND booking_date = ? AND status = 'confirmed' AND id <> ? AND start_time < ? AND end_time > ?",
        [(int)$booking['space_id'], $booking['booking_date'], $bookingId, $booking['end_time'], $booking['start_time']]
    ) > 0) {
        flash('danger', 'Cannot confirm: it overlaps another confirmed booking for this space.');
    } else {
        db_run('UPDATE bookings SET status = ? WHERE id = ? AND status = ?', [$new, $bookingId, $booking['status']]);
        flash('success', 'Booking #' . $bookingId . ' marked as ' . $new . '.');
    }
    redirect('admin/bookings.php' . $query);
}

$sql = 'SELECT b.*, s.name AS space_name, o.full_name AS owner_name, o.username AS owner_username,
               u.full_name AS customer_name, u.username AS customer_username
        FROM bookings b JOIN spaces s ON s.id = b.space_id JOIN users u ON u.id = b.user_id JOIN users o ON o.id = s.owner_id';
$params = [];
if ($status !== '') {
    $sql .= ' WHERE b.status = ?';
    $params[] = $status;
}
$bookings = db_all($sql . ' ORDER BY b.created_at DESC', $params);

$pageTitle = 'Bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>All Bookings</h1>
  <p>View and manage every booking on the platform.</p>
</div>

<div class="tabs">
  <a class="tab<?php echo $status === '' ? ' active' : ''; ?>" href="<?php echo e(url('admin/bookings.php')); ?>">All</a>
  <?php foreach (BOOKING_STATUSES as $st): ?>
    <a class="tab<?php echo $status === $st ? ' active' : ''; ?>" href="<?php echo e(url('admin/bookings.php?status=' . $st)); ?>"><?php echo e(ucfirst($st)); ?></a>
  <?php endforeach; ?>
</div>

<?php if ($bookings): ?>
<div class="card table-card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Customer</th><th>Space / Owner</th><th>Event</th><th>Date &amp; Time</th><th>Guests</th><th>Est. Cost</th><th>Status</th><th>Change</th></tr></thead>
      <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td data-label="#"><?php echo (int)$b['id']; ?></td>
          <td data-label="Customer"><?php echo e($b['customer_name'] ?: $b['customer_username']); ?></td>
          <td data-label="Space / Owner"><strong><?php echo e($b['space_name']); ?></strong><div class="muted small"><?php echo e($b['owner_name'] ?: $b['owner_username']); ?></div></td>
          <td data-label="Event"><?php echo e($b['event_type'] ?: '—'); ?></td>
          <td data-label="Date &amp; Time"><?php echo e(date('d M Y', strtotime($b['booking_date']))); ?><div class="muted small"><?php echo e(substr($b['start_time'], 0, 5) . ' – ' . substr($b['end_time'], 0, 5)); ?></div></td>
          <td data-label="Guests"><?php echo (int)$b['guests']; ?></td>
          <td data-label="Est. Cost"><?php echo money($b['total_amount']); ?></td>
          <td data-label="Status"><?php echo status_badge($b['status']); ?></td>
          <td data-label="Change">
            <?php if (!empty($transitions[$b['status']])): ?>
              <form method="post" class="inline-form status-form" action="<?php echo e(url('admin/bookings.php' . $query)); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                <select class="form-control form-control-sm" name="new_status" aria-label="New status">
                  <?php foreach ($transitions[$b['status']] as $opt): ?>
                    <option value="<?php echo e($opt); ?>"><?php echo e(ucfirst($opt)); ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">Update</button>
              </form>
            <?php else: ?>
              <span class="muted">Final</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
  <div class="empty-state">No bookings<?php echo $status ? ' with status "' . e($status) . '"' : ''; ?>.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
