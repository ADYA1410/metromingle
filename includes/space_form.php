<?php

const THEME_SUGGESTIONS = ['Royal', 'Modern', 'Rustic', 'Classic', 'Minimalist', 'Bohemian', 'Garden', 'Outdoor', 'Glamour', 'Corporate'];
const AMENITY_SUGGESTIONS = ['AC', 'Parking', 'Wifi', 'Projector', 'Stage', 'PA System', 'Catering', 'Lighting', 'Dance Floor', 'Valet', 'Restrooms', 'Power Backup'];

/**
 * Reads and validates the space form. Returns [data, errors].
 */
function validate_space_input(): array
{
    $data = [
        'name' => post_str('name', 100),
        'location' => post_str('location', 150),
        'capacity' => post_str('capacity', 7),
        'price_per_hour' => post_str('price_per_hour', 12),
        'description' => post_str('description', 2000),
        'themes' => implode(',', csv_list(post_str('themes', 255))),
        'amenities' => implode(',', csv_list(post_str('amenities', 255))),
        'status' => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
    ];
    $errors = [];
    if (mb_strlen($data['name']) < 3) {
        $errors[] = 'Space name must be at least 3 characters.';
    }
    if (mb_strlen($data['location']) < 3) {
        $errors[] = 'Please enter the location (area and city).';
    }
    $capacity = filter_var($data['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
    if ($capacity === false) {
        $errors[] = 'Capacity must be a whole number between 1 and 100,000.';
    }
    $price = filter_var($data['price_per_hour'], FILTER_VALIDATE_FLOAT);
    if ($price === false || $price <= 0 || $price > 10000000) {
        $errors[] = 'Hourly price must be a positive amount.';
    }
    if (mb_strlen($data['themes']) > 255 || mb_strlen($data['amenities']) > 255) {
        $errors[] = 'Themes and amenities must each be under 255 characters.';
    }
    $data['capacity'] = $capacity === false ? $data['capacity'] : (int)$capacity;
    $data['price_per_hour'] = $price === false ? $data['price_per_hour'] : round((float)$price, 2);
    return [$data, $errors];
}

function has_uploaded_photo(): bool
{
    return isset($_FILES['photo']) && (int)$_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;
}

function render_space_form(array $space, string $action, string $submitLabel): void
{
    $hasPhoto = is_safe_photo_path($space['photo'] ?? null);
    ?>
  <form class="card" method="post" action="<?php echo e($action); ?>" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo PHOTO_MAX_BYTES; ?>">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="name">Space Name *</label>
        <input class="form-control" id="name" name="name" maxlength="100" value="<?php echo e($space['name'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="location">Location *</label>
        <input class="form-control" id="location" name="location" maxlength="150" value="<?php echo e($space['location'] ?? ''); ?>" placeholder="e.g. Banjara Hills, Hyderabad" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="capacity">Capacity (guests) *</label>
        <input class="form-control" id="capacity" name="capacity" type="number" min="1" max="100000" value="<?php echo e($space['capacity'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="price_per_hour">Price per hour (₹) *</label>
        <input class="form-control" id="price_per_hour" name="price_per_hour" type="number" min="1" step="0.01" value="<?php echo e($space['price_per_hour'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="status">Listing status</label>
        <select class="form-control" id="status" name="status">
          <option value="active" <?php echo ($space['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active (visible to customers)</option>
          <option value="inactive" <?php echo ($space['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive (hidden)</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="description">Description</label>
      <textarea class="form-control" id="description" name="description" rows="4" maxlength="2000"><?php echo e($space['description'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label" for="themes">Themes <span class="muted">(comma separated)</span></label>
      <input class="form-control" id="themes" name="themes" maxlength="255" value="<?php echo e(str_replace(',', ', ', (string)($space['themes'] ?? ''))); ?>">
      <div class="chip-list" data-target="themes">
        <?php foreach (THEME_SUGGESTIONS as $t): ?><button type="button" class="chip"><?php echo e($t); ?></button><?php endforeach; ?>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="amenities">Amenities <span class="muted">(comma separated)</span></label>
      <input class="form-control" id="amenities" name="amenities" maxlength="255" value="<?php echo e(str_replace(',', ', ', (string)($space['amenities'] ?? ''))); ?>">
      <div class="chip-list" data-target="amenities">
        <?php foreach (AMENITY_SUGGESTIONS as $a): ?><button type="button" class="chip"><?php echo e($a); ?></button><?php endforeach; ?>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="photo">Venue Photo <span class="muted">(JPG, PNG or WebP, max 5 MB)</span></label>
      <div class="photo-field">
        <div class="photo-preview" id="photo-preview"><?php echo photo_html($space['photo'] ?? null, $space['name'] ?? 'Venue photo', 'space-photo space-photo-sm'); ?></div>
        <div>
          <input class="form-control" id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
          <?php if ($hasPhoto): ?>
            <label class="checkbox-label"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
            <p class="muted small">Choosing a new file replaces the current photo.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?php echo e($submitLabel); ?></button>
      <a class="btn btn-outline" href="<?php echo e(url('owner/my_spaces.php')); ?>">Cancel</a>
    </div>
  </form>
    <?php
}
