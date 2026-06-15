<?php
$db_file = __DIR__ . '/../safar_db.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base_url = rtrim(str_replace(['/dashboard', '/admin', '/api', '/includes', '/pages'], '', $script), '/');
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
?>
