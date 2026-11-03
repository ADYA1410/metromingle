<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($user = current_user()) {
    redirect(home_for_role($user['role']));
}

$activeSpaceSql = "FROM spaces s JOIN users u ON u.id = s.owner_id WHERE s.status = 'active' AND u.status = 'active'";
$stats = [
    'spaces' => (int)db_value("SELECT COUNT(*) $activeSpaceSql"),
    'owners' => (int)db_value("SELECT COUNT(DISTINCT s.owner_id) $activeSpaceSql"),
    'confirmed' => (int)db_value("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'"),
];
$featured = db_all("SELECT s.* $activeSpaceSql ORDER BY s.created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PlotSphere • Event spaces, simply booked</title>
  <link rel="stylesheet" href="<?php echo e(url('css/styles.css')); ?>">
</head>
<body>
  <header class="navbar">
    <a class="navbar-brand" href="<?php echo e(url('index.php')); ?>">PlotSphere</a>
    <nav class="nav-links">
      <a href="<?php echo e(url('user/browse.php')); ?>">Browse Spaces</a>
      <a href="<?php echo e(url('login.php')); ?>">Login</a>
      <a class="btn btn-outline btn-sm" href="<?php echo e(url('register.php')); ?>">Register</a>
    </nav>
  </header>

  <section class="hero">
    <div class="container">
      <h1>Find the right space for your event</h1>
      <p>Discover local event venues, filter by capacity and budget, see transparent hourly pricing and real venue photos, then send a booking request in minutes.</p>
      <form class="hero-search" method="get" action="<?php echo e(url('user/browse.php')); ?>">
        <input class="form-control" type="text" name="location" placeholder="Location, e.g. Banjara Hills" aria-label="Location">
        <input class="form-control" type="number" name="guests" min="1" placeholder="Guests" aria-label="Guests">
        <button class="btn btn-primary" type="submit">Search Spaces</button>
      </form>
      <div class="hero-actions">
        <a class="btn btn-light" href="<?php echo e(url('register.php?role=owner')); ?>">List Your Space</a>
      </div>
    </div>
  </section>

  <section class="stats-band">
    <div class="container stats-grid">
      <div class="stat-card"><div class="stat-number"><?php echo $stats['spaces']; ?></div><div class="stat-label">Active venues</div></div>
      <div class="stat-card"><div class="stat-number"><?php echo $stats['owners']; ?></div><div class="stat-label">Space owners</div></div>
      <div class="stat-card"><div class="stat-number"><?php echo $stats['confirmed']; ?></div><div class="stat-label">Confirmed bookings</div></div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="page-header page-header-row">
        <div>
          <h1>Latest spaces</h1>
          <p>Recently listed venues from local owners.</p>
        </div>
        <a class="btn btn-outline" href="<?php echo e(url('user/browse.php')); ?>">View all spaces</a>
      </div>
      <?php if ($featured): ?>
        <div class="spaces-grid">
          <?php foreach ($featured as $s) { include __DIR__ . '/includes/space_card.php'; } ?>
        </div>
      <?php else: ?>
        <div class="empty-state">No spaces have been listed yet. Are you a venue owner? <a href="<?php echo e(url('register.php?role=owner')); ?>">List your space</a>.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="section section-alt">
    <div class="container">
      <div class="page-header"><h1>How it works</h1><p>Simple steps to plan your next event.</p></div>
      <div class="steps-grid">
        <div class="card"><span class="badge badge-blue">Step 1</span><h3>Discover</h3><p>Filter venues by location, capacity, hourly budget, themes and amenities.</p></div>
        <div class="card"><span class="badge badge-blue">Step 2</span><h3>Request</h3><p>Choose your date, time and guest count. The estimated cost is calculated as hourly price × duration.</p></div>
        <div class="card"><span class="badge badge-blue">Step 3</span><h3>Confirm</h3><p>The space owner confirms or rejects your request. Track every status from your dashboard.</p></div>
      </div>
    </div>
  </section>

  <footer class="site-footer">
    <div class="container footer-inner">
      <strong>PlotSphere</strong>
      <span>&copy; <?php echo date('Y'); ?> PlotSphere. Event space discovery and booking management.</span>
    </div>
  </footer>
  <script src="<?php echo e(url('js/app.js')); ?>"></script>
</body>
</html>
