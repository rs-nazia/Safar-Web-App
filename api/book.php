<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isLoggedIn() || $_SESSION['user_role'] !== 'traveler') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a traveler to book a tour.']);
    exit();
}

$package_id = $_POST['package_id'] ?? null;
$traveler_id = $_SESSION['user_id'];

if (!$package_id) {
    echo json_encode(['success' => false, 'message' => 'Package ID is missing.']);
    exit();
}

try {
    // Check if booking already exists
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE traveler_id = ? AND package_id = ? AND status != 'rejected'");
    $stmt->execute([$traveler_id, $package_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already booked or requested this tour.']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO bookings (traveler_id, package_id) VALUES (?, ?)");
    $stmt->execute([$traveler_id, $package_id]);
    
    echo json_encode(['success' => true, 'message' => 'Booking request sent successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
