<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($user = current_user()) {
    redirect(home_for_role($user['role']));
}

$error = '';
$username = '';
$lockedUntil = (int)($_SESSION['login_locked_until'] ?? 0);

if (is_post()) {
    csrf_check();
    $username = post_str('username', 50);
    $password = (string)($_POST['password'] ?? '');

    if ($lockedUntil > time()) {
        $error = 'Too many failed attempts. Please wait a minute and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $row = db_one('SELECT id, username, password, role, full_name, status FROM users WHERE username = ? LIMIT 1', [$username]);
        $valid = false;
        if ($row) {
            $stored = (string)$row['password'];
            if (password_get_info($stored)['algo'] !== null && password_get_info($stored)['algo'] !== 0) {
                $valid = password_verify($password, $stored);
                if ($valid && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                    db_run('UPDATE users SET password = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int)$row['id']]);
                }
            } elseif (hash_equals($stored, $password)) {
                // Legacy plaintext account: upgrade to a secure hash on first successful login.
                $valid = true;
                db_run('UPDATE users SET password = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int)$row['id']]);
            }
        }

        if (!$valid) {
            $_SESSION['login_failures'] = (int)($_SESSION['login_failures'] ?? 0) + 1;
            if ($_SESSION['login_failures'] >= 5) {
                $_SESSION['login_locked_until'] = time() + 60;
                $_SESSION['login_failures'] = 0;
            }
            $error = 'Invalid username or password.';
        } elseif ($row['status'] !== 'active') {
            $error = 'This account has been deactivated. Please contact the administrator.';
        } else {
            unset($_SESSION['login_failures'], $_SESSION['login_locked_until']);
            login_user($row);
            redirect(home_for_role($row['role']));
        }
    }
}

$pageTitle = 'Login';
$flashes = take_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login • MetroMingle</title>
  <link rel="stylesheet" href="<?php echo e(url('css/styles.css')); ?>">
</head>
<body>
  <div class="auth-bg">
    <div class="auth-card">
      <a class="auth-logo" href="<?php echo e(url('index.php')); ?>">MetroMingle</a>
      <div class="auth-tagline">Event spaces, simply booked</div>

      <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?php echo e($f['type']); ?>"><?php echo e($f['message']); ?></div>
      <?php endforeach; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

      <form method="post" action="<?php echo e(url('login.php')); ?>" novalidate>
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" id="username" name="username" type="text" maxlength="50" value="<?php echo e($username); ?>" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" id="password" name="password" type="password" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Login</button>
      </form>

      <p class="auth-switch">Don't have an account? <a href="<?php echo e(url('register.php')); ?>">Register</a></p>
      <p class="auth-switch"><a href="<?php echo e(url('user/browse.php')); ?>">Browse spaces without logging in</a></p>
    </div>
  </div>
  <script src="<?php echo e(url('js/app.js')); ?>"></script>
</body>
</html>
