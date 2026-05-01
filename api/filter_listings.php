<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? 'all';
$location = $_GET['location'] ?? '';
$max_price = $_GET['price'] ?? 5000;

$query = "SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id WHERE p.price <= ?";
$params = [$max_price];

if ($type === 'tour' || $type === 'hotel') {
    $query .= " AND p.type = ?";
    $params[] = $type;
}

if (!empty($location)) {
    $query .= " AND p.location LIKE ?";
    $params[] = "%$location%";
}

$query .= " ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format prices for JSON
    foreach ($results as &$r) {
        $r['price_formatted'] = number_format($r['price'], 2);
    }
    
    echo json_encode(['status' => 'success', 'data' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
