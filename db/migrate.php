<?php
/**
 * Idempotent, additive schema migration for Phase 1.
 * Run from the command line: C:\xampp\php\php.exe db\migrate.php
 * It never drops tables, columns or rows.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this migration from the command line.');
}

require_once __DIR__ . '/connect.php';

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0] > 0;
}

function column_type(mysqli $conn, string $table, string $column): string
{
    $stmt = $conn->prepare('SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return (string)($stmt->get_result()->fetch_row()[0] ?? '');
}

$steps = [];

if (!column_exists($conn, 'users', 'status')) {
    $conn->query("ALTER TABLE users ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER email");
    $steps[] = 'users.status added';
}
if (!column_exists($conn, 'spaces', 'photo')) {
    $conn->query('ALTER TABLE spaces ADD COLUMN photo VARCHAR(255) NULL DEFAULT NULL AFTER amenities');
    $steps[] = 'spaces.photo added';
}
if (!column_exists($conn, 'bookings', 'notes')) {
    $conn->query('ALTER TABLE bookings ADD COLUMN notes TEXT NULL AFTER guests');
    $steps[] = 'bookings.notes added';
}
if (strpos(column_type($conn, 'bookings', 'status'), "'rejected'") === false) {
    $conn->query("ALTER TABLE bookings MODIFY status ENUM('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    $steps[] = "bookings.status now allows 'rejected'";
}

echo $steps ? "Applied:\n - " . implode("\n - ", $steps) . "\n" : "Schema already up to date.\n";
