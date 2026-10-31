<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$space = db_one(
    "SELECT s.*, u.full_name AS owner_name FROM spaces s JOIN users u ON u.id = s.owner_id
     WHERE s.id = ? AND s.status = 'active' AND u.status = 'active'",
    [(int)($_GET['id'] ?? 0)]
);
if (!$space) {
    flash('danger', 'That space is not available.');
    redirect('user/browse.php');
}
$viewer = current_user();

$pageTitle = $space['name'];
require __DIR__ . '/../includes/header.php';
?>
<p><a class="back-link" href="<?php echo e(url('user/browse.php')); ?>">&larr; Back to all spaces</a></p>

<div class="detail-layout">
  <div>
    <div class="detail-photo"><?php echo photo_html($space['photo'], $space['name'], 'space-photo space-photo-lg'); ?></div>
    <div class="card">
      <h1 class="detail-title"><?php echo e($space['name']); ?></h1>
      <div class="space-meta">
        <span>📍 <?php echo e($space['location']); ?></span>
        <span>👥 Up to <?php echo (int)$space['capacity']; ?> guests</span>
        <span>🏠 Listed by <?php echo e($space['owner_name'] ?: 'Owner'); ?></span>
      </div>
      <h3 class="section-title">About this space</h3>
      <p class="prose"><?php echo nl2br(e($space['description'] ?: 'No description provided.')); ?></p>

      <h3 class="section-title">Themes</h3>
      <div class="tag-list">
        <?php foreach (csv_list($space['themes']) ?: ['Not specified'] as $t): ?><span class="badge badge-blue"><?php echo e($t); ?></span><?php endforeach; ?>
      </div>
      <h3 class="section-title">Amenities</h3>
      <div class="tag-list">
        <?php foreach (csv_list($space['amenities']) ?: ['Not specified'] as $a): ?><span class="badge badge-gray"><?php echo e($a); ?></span><?php endforeach; ?>
      </div>
    </div>
  </div>

  <aside class="card booking-summary">
    <div class="price-large"><?php echo money($space['price_per_hour']); ?> <small>/ hour</small></div>
    <p class="muted">Estimated cost = hourly price × booked duration. Requests are confirmed by the space owner.</p>
    <?php if (!$viewer): ?>
      <a class="btn btn-primary btn-block" href="<?php echo e(url('login.php')); ?>">Login to Book</a>
      <p class="muted center">New here? <a href="<?php echo e(url('register.php')); ?>">Create a customer account</a></p>
    <?php elseif ($viewer['role'] === 'user'): ?>
      <a class="btn btn-primary btn-block" href="<?php echo e(url('user/book.php?space_id=' . (int)$space['id'])); ?>">Request Booking</a>
    <?php else: ?>
      <div class="alert alert-info">Only customer accounts can make bookings.</div>
    <?php endif; ?>
  </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
