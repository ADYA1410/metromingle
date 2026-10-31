<?php
require_once __DIR__ . '/../auth/check.php';
$user = require_role('user');
$userId = (int)$user['id'];

if (is_post()) {
    csrf_check();
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $updated = db_run(
        "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'",
        [$bookingId, $userId]
    )->affected_rows;
    $updated
        ? flash('success', 'Booking request cancelled.')
        : flash('danger', 'Only your own pending bookings can be cancelled.');
    redirect('user/my_bookings.php');
}

$status = in_array($_GET['status'] ?? '', BOOKING_STATUSES, true) ? $_GET['status'] : '';
$sql = 'SELECT b.*, s.name AS space_name, s.location, s.photo FROM bookings b JOIN spaces s ON s.id = b.space_id WHERE b.user_id = ?';
$params = [$userId];
if ($status !== '') {
    $sql .= ' AND b.status = ?';
    $params[] = $status;
}
$bookings = db_all($sql . ' ORDER BY b.booking_date DESC, b.start_time DESC', $params);

$pageTitle = 'My Bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header page-header-row">
  <div>
    <h1>My Bookings</h1>
    <p>Track your booking requests and their status.</p>
  </div>
  <a class="btn btn-primary" href="<?php echo e(url('user/browse.php')); ?>">Book a Space</a>
</div>

<div class="tabs">
  <a class="tab<?php echo $status === '' ? ' active' : ''; ?>" href="<?php echo e(url('user/my_bookings.php')); ?>">All</a>
  <?php foreach (BOOKING_STATUSES as $st): ?>
    <a class="tab<?php echo $status === $st ? ' active' : ''; ?>" href="<?php echo e(url('user/my_bookings.php?status=' . $st)); ?>"><?php echo e(ucfirst($st)); ?></a>
  <?php endforeach; ?>
</div>

<?php if ($bookings): ?>
<div class="card table-card">
  <div class="table-wrap">
    <table class="data-table">
      <colgroup>
        <col class="col-space"><col class="col-event"><col class="col-date"><col class="col-num">
        <col class="col-money"><col class="col-status"><col class="col-action">
      </colgroup>
      <thead><tr><th>Space</th><th>Event</th><th>Date &amp; Time</th><th>Guests</th><th>Est. Cost</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td data-label="Space">
            <div class="cell-media">
              <?php echo photo_html($b['photo'], $b['space_name'], 'thumb'); ?>
              <div><strong><?php echo e($b['space_name']); ?></strong><div class="muted small"><?php echo e($b['location']); ?></div></div>
            </div>
          </td>
          <td data-label="Event">
            <?php echo e($b['event_type'] ?: '—'); ?>
            <?php if (!empty($b['notes'])): ?><div class="muted small" title="<?php echo e($b['notes']); ?>">Notes: <?php echo e(mb_strimwidth($b['notes'], 0, 60, '…')); ?></div><?php endif; ?>
          </td>
          <td data-label="Date &amp; Time"><?php echo e(date('d M Y', strtotime($b['booking_date']))); ?><div class="muted small"><?php echo e(substr($b['start_time'], 0, 5) . ' – ' . substr($b['end_time'], 0, 5)); ?></div></td>
          <td data-label="Guests"><?php echo (int)$b['guests']; ?></td>
          <td data-label="Est. Cost"><?php echo money($b['total_amount']); ?></td>
          <td data-label="Status"><?php echo status_badge($b['status']); ?></td>
          <td data-label="Action">
            <?php if ($b['status'] === 'pending'): ?>
              <form method="post" class="inline-form" data-confirm="Cancel this booking request?">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
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
  <div class="empty-state">No bookings<?php echo $status ? ' with status "' . e($status) . '"' : ''; ?> yet. <a href="<?php echo e(url('user/browse.php')); ?>">Browse spaces</a> to make your first request.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
