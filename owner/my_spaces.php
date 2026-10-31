<?php
require_once __DIR__ . '/../auth/check.php';
$user = require_role('owner');
$ownerId = (int)$user['id'];

if (is_post()) {
    csrf_check();
    $spaceId = (int)($_POST['space_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $space = db_one('SELECT id, name, status, photo FROM spaces WHERE id = ? AND owner_id = ?', [$spaceId, $ownerId]);
    if (!$space) {
        flash('danger', 'Space not found.');
    } elseif ($action === 'toggle') {
        $new = $space['status'] === 'active' ? 'inactive' : 'active';
        db_run('UPDATE spaces SET status = ? WHERE id = ? AND owner_id = ?', [$new, $spaceId, $ownerId]);
        flash('success', '"' . $space['name'] . '" is now ' . $new . '.');
    } elseif ($action === 'remove_photo') {
        db_run('UPDATE spaces SET photo = NULL WHERE id = ? AND owner_id = ?', [$spaceId, $ownerId]);
        delete_space_photo($space['photo']);
        flash('success', 'Photo removed from "' . $space['name'] . '".');
    }
    redirect('owner/my_spaces.php');
}

$spaces = db_all(
    "SELECT s.*,
            (SELECT COUNT(*) FROM bookings b WHERE b.space_id = s.id) AS total_bookings,
            (SELECT COUNT(*) FROM bookings b WHERE b.space_id = s.id AND b.status = 'pending') AS pending_bookings
     FROM spaces s WHERE s.owner_id = ? ORDER BY s.created_at DESC",
    [$ownerId]
);

$pageTitle = 'My Spaces';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header page-header-row">
  <div>
    <h1>My Spaces</h1>
    <p>Manage your listings, photos and visibility.</p>
  </div>
  <a class="btn btn-primary" href="<?php echo e(url('owner/add_space.php')); ?>">+ Add Space</a>
</div>

<?php if ($spaces): ?>
<div class="spaces-grid">
  <?php foreach ($spaces as $s): ?>
    <article class="space-card<?php echo $s['status'] === 'inactive' ? ' is-inactive' : ''; ?>">
      <div class="space-card-media">
        <?php echo photo_html($s['photo'], $s['name']); ?>
        <span class="price-tag"><?php echo money($s['price_per_hour']); ?>/hr</span>
      </div>
      <div class="space-card-body">
        <div class="card-title-row">
          <h3 class="space-card-title"><?php echo e($s['name']); ?></h3>
          <?php echo status_badge($s['status']); ?>
        </div>
        <div class="space-meta">
          <span>📍 <?php echo e($s['location']); ?></span>
          <span>👥 <?php echo (int)$s['capacity']; ?> guests</span>
        </div>
        <div class="space-meta">
          <span><?php echo (int)$s['total_bookings']; ?> bookings</span>
          <span><?php echo (int)$s['pending_bookings']; ?> pending</span>
        </div>
        <div class="space-card-footer">
          <a class="btn btn-primary btn-sm" href="<?php echo e(url('owner/edit_space.php?id=' . (int)$s['id'])); ?>">Edit</a>
          <?php if ($s['status'] === 'active'): ?>
            <a class="btn btn-outline btn-sm" href="<?php echo e(url('user/space.php?id=' . (int)$s['id'])); ?>">View</a>
          <?php endif; ?>
          <form method="post" class="inline-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="space_id" value="<?php echo (int)$s['id']; ?>">
            <input type="hidden" name="action" value="toggle">
            <button class="btn btn-sm <?php echo $s['status'] === 'active' ? 'btn-muted' : 'btn-success'; ?>" type="submit"><?php echo $s['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
          </form>
          <?php if (is_safe_photo_path($s['photo'])): ?>
            <form method="post" class="inline-form" data-confirm="Remove this photo?">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="space_id" value="<?php echo (int)$s['id']; ?>">
              <input type="hidden" name="action" value="remove_photo">
              <button class="btn btn-danger btn-sm" type="submit">Remove Photo</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php else: ?>
  <div class="empty-state">You haven't listed any spaces yet. <a href="<?php echo e(url('owner/add_space.php')); ?>">Add your first space</a>.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
