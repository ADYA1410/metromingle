<?php
require_once __DIR__ . '/../auth/check.php';
$user = require_role('owner');
$ownerId = (int)$user['id'];

if (is_post()) {
    csrf_check();
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $booking = db_one(
        "SELECT b.* FROM bookings b JOIN spaces s ON s.id = b.space_id WHERE b.id = ? AND s.owner_id = ? AND b.status = 'pending'",
        [$bookingId, $ownerId]
    );
    if (!$booking || !in_array($action, ['confirm', 'reject'], true)) {
        flash('danger', 'Only pending bookings for your own spaces can be updated.');
    } elseif ($action === 'confirm' && db_value(
        "SELECT COUNT(*) FROM bookings WHERE space_id = ? AND booking_date = ? AND status = 'confirmed' AND id <> ? AND start_time < ? AND end_time > ?",
        [(int)$booking['space_id'], $booking['booking_date'], $bookingId, $booking['end_time'], $booking['start_time']]
    ) > 0) {
        flash('danger', 'Cannot confirm: it overlaps another confirmed booking for this space. Reject it or ask the customer to change the time.');
    } else {
        $new = $action === 'confirm' ? 'confirmed' : 'rejected';
        db_run("UPDATE bookings SET status = ? WHERE id = ? AND status = 'pending'", [$new, $bookingId]);
        flash('success', 'Booking #' . $bookingId . ' ' . $new . '.');
    }
    redirect('owner/bookings.php' . (isset($_GET['status']) ? '?status=' . urlencode((string)$_GET['status']) : ''));
}

$status = in_array($_GET['status'] ?? '', BOOKING_STATUSES, true) ? $_GET['status'] : '';
$sql = 'SELECT b.*, s.name AS space_name, u.full_name AS customer_name, u.email AS customer_email, u.username AS customer_username
        FROM bookings b JOIN spaces s ON s.id = b.space_id JOIN users u ON u.id = b.user_id
        WHERE s.owner_id = ?';
$params = [$ownerId];
if ($status !== '') {
    $sql .= ' AND b.status = ?';
    $params[] = $status;
}
$bookings = db_all($sql . " ORDER BY FIELD(b.status, 'pending') DESC, b.booking_date DESC, b.start_time DESC", $params);

$pageTitle = 'Booking Requests';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>Booking Requests</h1>
  <p>Confirm or reject requests for your spaces.</p>
</div>

<div class="tabs">
  <a class="tab<?php echo $status === '' ? ' active' : ''; ?>" href="<?php echo e(url('owner/bookings.php')); ?>">All</a>
  <?php foreach (BOOKING_STATUSES as $st): ?>
    <a class="tab<?php echo $status === $st ? ' active' : ''; ?>" href="<?php echo e(url('owner/bookings.php?status=' . $st)); ?>"><?php echo e(ucfirst($st)); ?></a>
  <?php endforeach; ?>
</div>

<?php if ($bookings): ?>
<div class="card table-card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Customer</th><th>Space</th><th>Event</th><th>Date &amp; Time</th><th>Guests</th><th>Est. Cost</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td data-label="#"><?php echo (int)$b['id']; ?></td>
          <td data-label="Customer"><strong><?php echo e($b['customer_name'] ?: $b['customer_username']); ?></strong><div class="muted small"><?php echo e($b['customer_email']); ?></div></td>
          <td data-label="Space"><?php echo e($b['space_name']); ?></td>
          <td data-label="Event">
            <?php echo e($b['event_type'] ?: '—'); ?>
            <?php if (!empty($b['theme'])): ?><div class="muted small">Theme: <?php echo e($b['theme']); ?></div><?php endif; ?>
            <?php if (!empty($b['notes'])): ?><div class="muted small">Notes: <?php echo nl2br(e($b['notes'])); ?></div><?php endif; ?>
          </td>
          <td data-label="Date &amp; Time"><?php echo e(date('d M Y', strtotime($b['booking_date']))); ?><div class="muted small"><?php echo e(substr($b['start_time'], 0, 5) . ' – ' . substr($b['end_time'], 0, 5)); ?></div></td>
          <td data-label="Guests"><?php echo (int)$b['guests']; ?></td>
          <td data-label="Est. Cost"><?php echo money($b['total_amount']); ?></td>
          <td data-label="Status"><?php echo status_badge($b['status']); ?></td>
          <td data-label="Actions">
            <?php if ($b['status'] === 'pending'): ?>
              <div class="action-group">
                <form method="post" class="inline-form">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                  <input type="hidden" name="action" value="confirm">
                  <button class="btn btn-success btn-sm" type="submit">Confirm</button>
                </form>
                <form method="post" class="inline-form" data-confirm="Reject this booking request?">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                  <input type="hidden" name="action" value="reject">
                  <button class="btn btn-danger btn-sm" type="submit">Reject</button>
                </form>
              </div>
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
  <div class="empty-state">No booking requests<?php echo $status ? ' with status "' . e($status) . '"' : ''; ?> yet.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
