<?php
require_once __DIR__ . '/../auth/check.php';
require_once __DIR__ . '/../includes/space_form.php';
$user = require_role('owner');

$space = ['status' => 'active'];
$errors = [];

if (is_post()) {
    csrf_check();
    [$space, $errors] = validate_space_input();
    $photo = null;
    if (!$errors && has_uploaded_photo()) {
        $photo = store_space_photo($_FILES['photo'], $uploadError);
        if ($uploadError) {
            $errors[] = $uploadError;
        }
    }
    if (!$errors) {
        db_run(
            'INSERT INTO spaces (owner_id, name, location, capacity, price_per_hour, description, themes, amenities, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [(int)$user['id'], $space['name'], $space['location'], $space['capacity'], $space['price_per_hour'],
             $space['description'], $space['themes'], $space['amenities'], $photo, $space['status']]
        );
        flash('success', 'Space "' . $space['name'] . '" added successfully.');
        redirect('owner/my_spaces.php');
    }
}

$pageTitle = 'Add Space';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>Add a New Space</h1>
  <p>Describe your venue so customers can find it by location, capacity, budget, themes and amenities.</p>
</div>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?php echo e($err); ?></div><?php endforeach; ?>
<?php render_space_form($space, url('owner/add_space.php'), 'Add Space'); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
