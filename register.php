<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($user = current_user()) {
    redirect(home_for_role($user['role']));
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'username' => '', 'role' => ($_GET['role'] ?? '') === 'owner' ? 'owner' : 'user'];

if (is_post()) {
    csrf_check();
    $old = [
        'full_name' => post_str('full_name', 100),
        'email' => post_str('email', 100),
        'username' => post_str('username', 50),
        'role' => ($_POST['role'] ?? '') === 'owner' ? 'owner' : 'user',
    ];
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (mb_strlen($old['full_name']) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $old['username'])) {
        $errors[] = 'Username must be 3–50 characters: letters, numbers, dots or underscores.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$errors && db_value('SELECT COUNT(*) FROM users WHERE username = ?', [$old['username']]) > 0) {
        $errors[] = 'That username is already taken.';
    }

    if (!$errors) {
        db_run(
            'INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)',
            [$old['username'], password_hash($password, PASSWORD_DEFAULT), $old['role'], $old['full_name'], $old['email']]
        );
        flash('success', 'Account created! Please log in.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register • PlotSphere</title>
  <link rel="stylesheet" href="<?php echo e(url('css/styles.css')); ?>">
</head>
<body>
  <div class="auth-bg">
    <div class="auth-card">
      <a class="auth-logo" href="<?php echo e(url('index.php')); ?>">PlotSphere</a>
      <div class="auth-tagline">Create your account</div>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?php echo e($err); ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?php echo e(url('register.php')); ?>" novalidate>
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label class="form-label">I want to</label>
          <div class="role-picker">
            <label><input type="radio" name="role" value="user" <?php echo $old['role'] === 'user' ? 'checked' : ''; ?>> Book event spaces</label>
            <label><input type="radio" name="role" value="owner" <?php echo $old['role'] === 'owner' ? 'checked' : ''; ?>> List my spaces</label>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <input class="form-control" id="full_name" name="full_name" type="text" maxlength="100" value="<?php echo e($old['full_name']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" id="email" name="email" type="email" maxlength="100" value="<?php echo e($old['email']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" id="username" name="username" type="text" maxlength="50" pattern="[A-Za-z0-9_.]{3,50}" value="<?php echo e($old['username']); ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" minlength="6" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="6" required>
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create Account</button>
      </form>

      <p class="auth-switch">Already have an account? <a href="<?php echo e(url('login.php')); ?>">Login</a></p>
    </div>
  </div>
  <script src="<?php echo e(url('js/app.js')); ?>"></script>
</body>
</html>
