<?php /** @var array $s  Space row with optional owner_name */ ?>
<article class="space-card">
  <a href="<?php echo e(url('user/space.php?id=' . (int)$s['id'])); ?>" class="space-card-media">
    <?php echo photo_html($s['photo'] ?? null, $s['name']); ?>
    <span class="price-tag"><?php echo money($s['price_per_hour']); ?>/hr</span>
  </a>
  <div class="space-card-body">
    <h3 class="space-card-title"><a href="<?php echo e(url('user/space.php?id=' . (int)$s['id'])); ?>"><?php echo e($s['name']); ?></a></h3>
    <div class="space-meta">
      <span>📍 <?php echo e($s['location']); ?></span>
      <span>👥 Up to <?php echo (int)$s['capacity']; ?> guests</span>
    </div>
    <div class="tag-list">
      <?php foreach (array_slice(csv_list($s['themes'] ?? ''), 0, 4) as $t): ?>
        <span class="badge badge-blue"><?php echo e($t); ?></span>
      <?php endforeach; ?>
    </div>
    <div class="space-card-footer">
      <a class="btn btn-outline btn-sm" href="<?php echo e(url('user/space.php?id=' . (int)$s['id'])); ?>">View Details</a>
      <a class="btn btn-primary btn-sm" href="<?php echo e(url('user/book.php?space_id=' . (int)$s['id'])); ?>">Book Now</a>
    </div>
  </div>
</article>
