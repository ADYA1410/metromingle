<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/connect.php';

const PHOTO_DIR = 'uploads/spaces';
const PHOTO_MAX_BYTES = 5 * 1024 * 1024;
const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
const BOOKING_STATUSES = ['pending', 'confirmed', 'rejected', 'cancelled'];

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('PLOTSPHERESESSID');
    session_start();
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/* ---------- Output and URL helpers ---------- */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $root = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '';
    $app = realpath(__DIR__ . '/..') ?: '';
    if ($root !== '' && stripos($app, $root) === 0) {
        $base = str_replace('\\', '/', substr($app, strlen($root)));
    } else {
        $base = '/metromingle';
    }
    $base = '/' . trim($base, '/');
    if ($base === '/') {
        $base = '';
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function money($amount): string
{
    return '₹' . number_format((float)$amount, 2);
}

function csv_list(?string $value): array
{
    return array_values(array_unique(array_filter(array_map('trim', explode(',', (string)$value)), 'strlen')));
}

function status_badge(string $status): string
{
    $map = [
        'active' => 'success', 'confirmed' => 'success',
        'pending' => 'warning',
        'inactive' => 'gray', 'cancelled' => 'gray',
        'rejected' => 'danger',
        'admin' => 'danger', 'owner' => 'blue', 'user' => 'gray',
    ];
    $label = $status === 'user' ? 'customer' : $status;
    return '<span class="badge badge-' . ($map[$status] ?? 'gray') . '">' . e($label) . '</span>';
}

function post_str(string $key, int $max = 255): string
{
    return mb_substr(trim((string)($_POST[$key] ?? '')), 0, $max);
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string)($_POST['csrf_token'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        die('Your session has expired or the form is invalid. <a href="javascript:history.back()">Go back</a>, reload the page and try again.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------- Database ---------- */

function db_run(string $sql, array $params = []): mysqli_stmt
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        $values = array_values($params);
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    $result = db_run($sql, $params)->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function db_one(string $sql, array $params = []): ?array
{
    return db_all($sql, $params)[0] ?? null;
}

function db_value(string $sql, array $params = [])
{
    $row = db_one($sql, $params);
    return $row ? reset($row) : null;
}

/* ---------- Authentication ---------- */

function home_for_role(string $role): string
{
    return ['admin' => 'admin/home.php', 'owner' => 'owner/home.php'][$role] ?? 'user/home.php';
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['username'] = (string)$user['username'];
    $_SESSION['full_name'] = (string)($user['full_name'] ?? '');
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['user_id'])) {
        $row = db_one('SELECT id, username, role, full_name, email, status FROM users WHERE id = ?', [(int)$_SESSION['user_id']]);
        if ($row && $row['status'] === 'active') {
            $user = $row;
            $_SESSION['role'] = $row['role'];
        } else {
            $_SESSION = [];
            session_regenerate_id(true);
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('danger', 'Please log in to continue.');
        redirect('login.php');
    }
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        flash('danger', 'You do not have permission to access that page.');
        redirect(home_for_role($user['role']));
    }
    return $user;
}

/* ---------- Photos ---------- */

function is_safe_photo_path(?string $path): bool
{
    return is_string($path) && preg_match('#^uploads/spaces/[a-f0-9]{32}\.(jpg|png|webp)$#', $path) === 1;
}

function photo_html(?string $path, string $alt, string $class = 'space-photo'): string
{
    if (is_safe_photo_path($path) && is_file(__DIR__ . '/../' . $path)) {
        return '<img class="' . e($class) . '" src="' . e(url($path)) . '" alt="' . e($alt) . '" loading="lazy">';
    }
    return '<div class="' . e($class) . ' photo-placeholder" role="img" aria-label="No photo available"><span>No photo yet</span></div>';
}

/**
 * Validates and stores an uploaded image. Returns the relative path, or null with $error set.
 */
function store_space_photo(array $file, ?string &$error): ?string
{
    $error = null;
    $code = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($code !== UPLOAD_ERR_OK) {
        $error = in_array($code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Photo must be 5 MB or smaller.'
            : 'Photo upload failed. Please try again.';
        return null;
    }
    $tmp = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        $error = 'Invalid upload.';
        return null;
    }
    if ((int)$file['size'] <= 0 || (int)$file['size'] > PHOTO_MAX_BYTES) {
        $error = 'Photo must be 5 MB or smaller.';
        return null;
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, PHOTO_EXTENSIONS, true)) {
        $error = 'Only JPG, JPEG, PNG and WebP photos are allowed.';
        return null;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset(PHOTO_TYPES[$mime]) || @getimagesize($tmp) === false) {
        $error = 'The file is not a valid JPG, PNG or WebP image.';
        return null;
    }
    $expected = PHOTO_TYPES[$mime];
    if (!($expected === $ext || ($expected === 'jpg' && $ext === 'jpeg'))) {
        $error = 'The file extension does not match the image type.';
        return null;
    }
    $dir = __DIR__ . '/../' . PHOTO_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        $error = 'Upload folder is not writable.';
        return null;
    }
    $relative = PHOTO_DIR . '/' . bin2hex(random_bytes(16)) . '.' . $expected;
    if (!move_uploaded_file($tmp, __DIR__ . '/../' . $relative)) {
        $error = 'Could not save the photo.';
        return null;
    }
    return $relative;
}

function delete_space_photo(?string $path): void
{
    if (is_safe_photo_path($path)) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
