<?php
/** @var string $pageTitle */
$user = current_user();
$current = substr(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')), strlen(base_url()) + 1);
$menus = [
    'user' => ['Customer' => [
        'user/home.php' => 'Dashboard',
        'user/browse.php' => 'Browse Spaces',
        'user/my_bookings.php' => 'My Bookings',
    ]],
    'owner' => ['Space Owner' => [
        'owner/home.php' => 'Dashboard',
        'owner/my_spaces.php' => 'My Spaces',
        'owner/add_space.php' => 'Add Space',
        'owner/bookings.php' => 'Booking Requests',
    ]],
    'admin' => ['Administrator' => [
        'admin/home.php' => 'Dashboard',
        'admin/users.php' => 'Users',
        'admin/spaces.php' => 'Spaces',
        'admin/bookings.php' => 'Bookings',
    ]],
];
$aliases = ['user/space.php' => 'user/browse.php', 'user/book.php' => 'user/browse.php', 'owner/edit_space.php' => 'owner/my_spaces.php'];
$activeLink = $aliases[$current] ?? $current;
$flashes = take_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e(($pageTitle ?? 'Event spaces') . ' • PlotSphere'); ?></title>
  <link rel="stylesheet" href="<?php echo e(url('css/styles.css')); ?>">
</head>
<body>
<?php if ($user): ?>
  <header class="topbar">
    <a class="navbar-brand" href="<?php echo e(url(home_for_role($user['role']))); ?>">PlotSphere</a>
    <div class="topbar-user">
      <span class="user"><?php echo e($user['full_name'] ?: $user['username']); ?></span>
      <?php echo status_badge($user['role']); ?>
      <form method="post" action="<?php echo e(url('logout.php')); ?>" class="inline-form">
        <?php echo csrf_field(); ?>
        <button class="btn btn-outline btn-sm" type="submit">Logout</button>
      </form>
    </div>
  </header>
  <div class="dashboard-layout">
    <nav class="sidebar" aria-label="Main navigation">
      <?php foreach ($menus[$user['role']] ?? [] as $section => $links): ?>
        <div class="sidebar-section"><?php echo e($section); ?></div>
        <?php foreach ($links as $href => $label): ?>
          <a class="sidebar-link<?php echo $activeLink === $href ? ' active' : ''; ?>" href="<?php echo e(url($href)); ?>"><?php echo e($label); ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <main class="main-content">
<?php else: ?>
  <header class="navbar">
    <a class="navbar-brand" href="<?php echo e(url('index.php')); ?>">PlotSphere</a>
    <nav class="nav-links">
      <a href="<?php echo e(url('user/browse.php')); ?>"<?php echo $current === 'user/browse.php' ? ' class="active"' : ''; ?>>Browse Spaces</a>
      <a href="<?php echo e(url('login.php')); ?>"<?php echo $current === 'login.php' ? ' class="active"' : ''; ?>>Login</a>
      <a class="btn btn-outline btn-sm" href="<?php echo e(url('register.php')); ?>">Register</a>
    </nav>
  </header>
  <main class="container public-main">
<?php endif; ?>
<?php foreach ($flashes as $f): ?>
  <div class="alert alert-<?php echo e($f['type']); ?>"><?php echo e($f['message']); ?></div>
<?php endforeach; ?>
