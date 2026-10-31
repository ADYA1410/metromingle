<?php
require_once __DIR__ . '/../auth/check.php';
require_once __DIR__ . '/../includes/space_form.php';
$user = require_role('owner');
$ownerId = (int)$user['id'];

$spaceId = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$existing = db_one('SELECT * FROM spaces WHERE id = ? AND owner_id = ?', [$spaceId, $ownerId]);
if (!$existing) {
    flash('danger', 'Space not found.');
    redirect('owner/my_spaces.php');
}

$space = $existing;
$errors = [];

if (is_post()) {
    csrf_check();
    [$input, $errors] = validate_space_input();
    $space = array_merge($existing, $input);
    $photo = $existing['photo'];
    $newPhoto = null;

    if (!$errors && has_uploaded_photo()) {
        $newPhoto = store_space_photo($_FILES['photo'], $uploadError);
        if ($uploadError) {
            $errors[] = $uploadError;
        } else {
            $photo = $newPhoto;
        }
    } elseif (!$errors && !empty($_POST['remove_photo'])) {
        $photo = null;
    }

    if (!$errors) {
        db_run(
            'UPDATE spaces SET name = ?, location = ?, capacity = ?, price_per_hour = ?, description = ?, themes = ?, amenities = ?, photo = ?, status = ?
             WHERE id = ? AND owner_id = ?',
            [$input['name'], $input['location'], $input['capacity'], $input['price_per_hour'], $input['description'],
             $input['themes'], $input['amenities'], $photo, $input['status'], $spaceId, $ownerId]
        );
        if ($photo !== $existing['photo']) {
            delete_space_photo($existing['photo']);
        }
        flash('success', 'Space "' . $input['name'] . '" updated.');
        redirect('owner/my_spaces.php');
    }
}

$pageTitle = 'Edit Space';
require __DIR__ . '/../includes/header.php';
?>
<p><a class="back-link" href="<?php echo e(url('owner/my_spaces.php')); ?>">&larr; Back to my spaces</a></p>
<div class="page-header">
  <h1>Edit: <?php echo e($existing['name']); ?></h1>
  <p>Update details, photo or visibility of this listing.</p>
</div>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?php echo e($err); ?></div><?php endforeach; ?>
<?php render_space_form($space, url('owner/edit_space.php?id=' . $spaceId), 'Save Changes'); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
