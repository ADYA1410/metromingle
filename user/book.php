<?php
require_once __DIR__ . '/../auth/check.php';
$user = require_role('user');

$spaceId = (int)($_GET['space_id'] ?? ($_POST['space_id'] ?? 0));
$space = db_one(
    "SELECT s.* FROM spaces s JOIN users u ON u.id = s.owner_id WHERE s.id = ? AND s.status = 'active' AND u.status = 'active'",
    [$spaceId]
);
if (!$space) {
    flash('danger', 'That space is not available for booking.');
    redirect('user/browse.php');
}

$themes = csv_list($space['themes']);
$capacity = (int)$space['capacity'];
$price = (float)$space['price_per_hour'];
$today = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+1 year'));
$errors = [];
$old = ['event_type' => '', 'booking_date' => '', 'start_time' => '', 'end_time' => '', 'guests' => '', 'theme' => '', 'notes' => ''];

if (is_post()) {
    csrf_check();
    $old = [
        'event_type' => post_str('event_type', 100),
        'booking_date' => post_str('booking_date', 10),
        'start_time' => post_str('start_time', 5),
        'end_time' => post_str('end_time', 5),
        'guests' => post_str('guests', 6),
        'theme' => post_str('theme', 100),
        'notes' => post_str('notes', 1000),
    ];

    $date = DateTime::createFromFormat('!Y-m-d', $old['booking_date']);
    $start = DateTime::createFromFormat('!H:i', $old['start_time']);
    $end = DateTime::createFromFormat('!H:i', $old['end_time']);
    $guests = filter_var($old['guests'], FILTER_VALIDATE_INT);

    if ($old['event_type'] === '') {
        $errors[] = 'Please enter the event type.';
    }
    if (!$date || $date->format('Y-m-d') !== $old['booking_date']) {
        $errors[] = 'Please choose a valid event date.';
    } elseif ($old['booking_date'] < $today) {
        $errors[] = 'The event date cannot be in the past.';
    } elseif ($old['booking_date'] > $maxDate) {
        $errors[] = 'Bookings can be made up to one year in advance.';
    }
    if (!$start || !$end || $start->format('H:i') !== $old['start_time'] || $end->format('H:i') !== $old['end_time']) {
        $errors[] = 'Please choose valid start and end times.';
    } elseif ($end <= $start) {
        $errors[] = 'End time must be after start time (bookings must start and end on the same day).';
    } elseif (($end->getTimestamp() - $start->getTimestamp()) < 3600) {
        $errors[] = 'The minimum booking duration is 1 hour.';
    } elseif ($old['booking_date'] === $today && $old['start_time'] <= date('H:i')) {
        $errors[] = 'For today\'s date, the start time must be later than the current time.';
    }
    if ($guests === false || $guests < 1) {
        $errors[] = 'Please enter the number of guests.';
    } elseif ($guests > $capacity) {
        $errors[] = "This space holds a maximum of $capacity guests.";
    }
    if ($old['theme'] !== '' && !in_array($old['theme'], $themes, true)) {
        $errors[] = 'Please choose one of the listed themes.';
    }

    if (!$errors) {
        $conflict = db_value(
            "SELECT COUNT(*) FROM bookings WHERE space_id = ? AND booking_date = ? AND status = 'confirmed' AND start_time < ? AND end_time > ?",
            [$spaceId, $old['booking_date'], $old['end_time'] . ':00', $old['start_time'] . ':00']
        );
        if ($conflict > 0) {
            $errors[] = 'This space already has a confirmed booking that overlaps the selected time. Please choose another time.';
        }
    }

    if (!$errors) {
        $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
        $total = round($hours * $price, 2);
        db_run(
            "INSERT INTO bookings (user_id, space_id, event_type, booking_date, start_time, end_time, guests, notes, theme, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
            [(int)$user['id'], $spaceId, $old['event_type'], $old['booking_date'], $old['start_time'] . ':00', $old['end_time'] . ':00',
             (int)$guests, $old['notes'] !== '' ? $old['notes'] : null, $old['theme'] !== '' ? $old['theme'] : null, $total]
        );
        flash('success', 'Booking request sent! Estimated cost: ' . money($total) . '. The owner will confirm or reject it.');
        redirect('user/my_bookings.php');
    }
}

$pageTitle = 'Book ' . $space['name'];
require __DIR__ . '/../includes/header.php';
?>
<p><a class="back-link" href="<?php echo e(url('user/space.php?id=' . $spaceId)); ?>">&larr; Back to space details</a></p>
<div class="page-header">
  <h1>Request Booking: <?php echo e($space['name']); ?></h1>
  <p><?php echo e($space['location']); ?> • <?php echo money($price); ?>/hr • Up to <?php echo $capacity; ?> guests</p>
</div>

<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?php echo e($err); ?></div><?php endforeach; ?>

<div class="detail-layout">
  <div class="card">
    <form method="post" action="<?php echo e(url('user/book.php')); ?>" id="booking-form">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="space_id" value="<?php echo $spaceId; ?>">
      <input type="hidden" id="price_per_hour" value="<?php echo e($price); ?>">

      <div class="form-group">
        <label class="form-label" for="event_type">Event Type *</label>
        <input class="form-control" id="event_type" name="event_type" list="event-types" maxlength="100" value="<?php echo e($old['event_type']); ?>" placeholder="e.g. Birthday, Wedding, Corporate Meet" required>
        <datalist id="event-types">
          <?php foreach (['Birthday', 'Wedding', 'Anniversary', 'Engagement', 'Corporate Meet', 'Conference', 'Workshop', 'Party', 'Exhibition'] as $t): ?>
            <option value="<?php echo e($t); ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="booking_date">Event Date *</label>
          <input class="form-control" id="booking_date" name="booking_date" type="date" min="<?php echo $today; ?>" max="<?php echo $maxDate; ?>" value="<?php echo e($old['booking_date']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="guests">Guest Count * (max <?php echo $capacity; ?>)</label>
          <input class="form-control" id="guests" name="guests" type="number" min="1" max="<?php echo $capacity; ?>" value="<?php echo e($old['guests']); ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="start_time">Start Time *</label>
          <input class="form-control" id="start_time" name="start_time" type="time" value="<?php echo e($old['start_time']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="end_time">End Time *</label>
          <input class="form-control" id="end_time" name="end_time" type="time" value="<?php echo e($old['end_time']); ?>" required>
        </div>
      </div>
      <?php if ($themes): ?>
      <div class="form-group">
        <label class="form-label" for="theme">Preferred Theme</label>
        <select class="form-control" id="theme" name="theme">
          <option value="">No preference</option>
          <?php foreach ($themes as $t): ?>
            <option value="<?php echo e($t); ?>" <?php echo $old['theme'] === $t ? 'selected' : ''; ?>><?php echo e($t); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label class="form-label" for="notes">Notes for the owner</label>
        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="1000" placeholder="Setup needs, special requests, etc."><?php echo e($old['notes']); ?></textarea>
      </div>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Send Booking Request</button>
        <a class="btn btn-outline" href="<?php echo e(url('user/browse.php')); ?>">Cancel</a>
      </div>
    </form>
  </div>

  <aside class="card booking-summary">
    <?php echo photo_html($space['photo'], $space['name'], 'space-photo space-photo-sm'); ?>
    <h3>Cost estimate</h3>
    <dl class="summary-list">
      <dt>Hourly price</dt><dd><?php echo money($price); ?></dd>
      <dt>Duration</dt><dd id="sum_hours">—</dd>
      <dt>Estimated total</dt><dd><strong id="amount_preview">—</strong></dd>
    </dl>
    <p class="muted">Final amount is calculated on the server as hourly price × duration. No payment is taken online.</p>
  </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
