<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$f = [
    'q' => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100),
    'location' => mb_substr(trim((string)($_GET['location'] ?? '')), 0, 100),
    'guests' => max(0, (int)($_GET['guests'] ?? 0)),
    'max_price' => max(0, (float)($_GET['max_price'] ?? 0)),
    'theme' => mb_substr(trim((string)($_GET['theme'] ?? '')), 0, 50),
    'amenity' => mb_substr(trim((string)($_GET['amenity'] ?? '')), 0, 50),
    'sort' => (string)($_GET['sort'] ?? 'newest'),
];
$like = static fn(string $v): string => '%' . addcslashes($v, '%_\\') . '%';

$where = ["s.status = 'active'", "u.status = 'active'"];
$params = [];
if ($f['q'] !== '') {
    $where[] = '(s.name LIKE ? OR s.description LIKE ?)';
    array_push($params, $like($f['q']), $like($f['q']));
}
if ($f['location'] !== '') {
    $where[] = 's.location LIKE ?';
    $params[] = $like($f['location']);
}
if ($f['guests'] > 0) {
    $where[] = 's.capacity >= ?';
    $params[] = $f['guests'];
}
if ($f['max_price'] > 0) {
    $where[] = 's.price_per_hour <= ?';
    $params[] = $f['max_price'];
}
if ($f['theme'] !== '') {
    $where[] = "FIND_IN_SET(?, REPLACE(s.themes, ', ', ',')) > 0";
    $params[] = $f['theme'];
}
if ($f['amenity'] !== '') {
    $where[] = "FIND_IN_SET(?, REPLACE(s.amenities, ', ', ',')) > 0";
    $params[] = $f['amenity'];
}
$sorts = [
    'newest' => 's.created_at DESC',
    'price_asc' => 's.price_per_hour ASC',
    'price_desc' => 's.price_per_hour DESC',
    'capacity' => 's.capacity DESC',
];
$order = $sorts[$f['sort']] ?? $sorts['newest'];

$spaces = db_all(
    'SELECT s.*, u.full_name AS owner_name FROM spaces s JOIN users u ON u.id = s.owner_id WHERE ' . implode(' AND ', $where) . " ORDER BY $order",
    $params
);

$themeOptions = $amenityOptions = [];
foreach (db_all("SELECT s.themes, s.amenities FROM spaces s JOIN users u ON u.id = s.owner_id WHERE s.status = 'active' AND u.status = 'active'") as $row) {
    $themeOptions = array_merge($themeOptions, csv_list($row['themes']));
    $amenityOptions = array_merge($amenityOptions, csv_list($row['amenities']));
}
$themeOptions = array_unique($themeOptions);
$amenityOptions = array_unique($amenityOptions);
natcasesort($themeOptions);
natcasesort($amenityOptions);

$pageTitle = 'Browse Spaces';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <h1>Browse Event Spaces</h1>
  <p>Filter by location, capacity, hourly budget, themes and amenities.</p>
</div>

<form class="card filter-bar" method="get" action="<?php echo e(url('user/browse.php')); ?>">
  <div class="form-group">
    <label class="form-label" for="q">Keyword</label>
    <input class="form-control" id="q" name="q" type="text" value="<?php echo e($f['q']); ?>" placeholder="Name or description">
  </div>
  <div class="form-group">
    <label class="form-label" for="location">Location</label>
    <input class="form-control" id="location" name="location" type="text" value="<?php echo e($f['location']); ?>" placeholder="Area or city">
  </div>
  <div class="form-group">
    <label class="form-label" for="guests">Guests</label>
    <input class="form-control" id="guests" name="guests" type="number" min="1" value="<?php echo $f['guests'] ?: ''; ?>" placeholder="Min capacity">
  </div>
  <div class="form-group">
    <label class="form-label" for="max_price">Max ₹/hour</label>
    <input class="form-control" id="max_price" name="max_price" type="number" min="0" step="100" value="<?php echo $f['max_price'] ?: ''; ?>" placeholder="Any budget">
  </div>
  <div class="form-group">
    <label class="form-label" for="theme">Theme</label>
    <select class="form-control" id="theme" name="theme">
      <option value="">Any theme</option>
      <?php foreach ($themeOptions as $t): ?>
        <option value="<?php echo e($t); ?>" <?php echo $f['theme'] === $t ? 'selected' : ''; ?>><?php echo e($t); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label" for="amenity">Amenity</label>
    <select class="form-control" id="amenity" name="amenity">
      <option value="">Any amenity</option>
      <?php foreach ($amenityOptions as $a): ?>
        <option value="<?php echo e($a); ?>" <?php echo $f['amenity'] === $a ? 'selected' : ''; ?>><?php echo e($a); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label" for="sort">Sort by</label>
    <select class="form-control" id="sort" name="sort">
      <?php foreach (['newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'capacity' => 'Largest capacity'] as $k => $label): ?>
        <option value="<?php echo e($k); ?>" <?php echo $f['sort'] === $k ? 'selected' : ''; ?>><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button class="btn btn-primary" type="submit">Search</button>
    <a class="btn btn-outline" href="<?php echo e(url('user/browse.php')); ?>">Reset</a>
  </div>
</form>

<p class="result-count"><?php echo count($spaces); ?> space<?php echo count($spaces) === 1 ? '' : 's'; ?> found</p>

<?php if ($spaces): ?>
  <div class="spaces-grid">
    <?php foreach ($spaces as $s) { include __DIR__ . '/../includes/space_card.php'; } ?>
  </div>
<?php else: ?>
  <div class="empty-state">No spaces match your filters. Try widening your search or <a href="<?php echo e(url('user/browse.php')); ?>">reset all filters</a>.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
