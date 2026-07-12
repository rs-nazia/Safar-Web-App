<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Please log in to bookmark favorites.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;

if ($package_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid package identifier.']);
    exit();
}

try {
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND package_id = ?");
    $stmt->execute([$user_id, $package_id]);
    $favorite = $stmt->fetch();

    if ($favorite) {
        // Delete favorite
        $del_stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND package_id = ?");
        $del_stmt->execute([$user_id, $package_id]);
        echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Removed from favorites.']);
    } else {
        // Insert favorite
        $ins_stmt = $pdo->prepare("INSERT INTO favorites (user_id, package_id) VALUES (?, ?)");
        $ins_stmt->execute([$user_id, $package_id]);
        echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Added to favorites.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
