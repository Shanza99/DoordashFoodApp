<?php
include 'config.php';

try {
    $stmt = $pdo->query("
        SELECT o.*, r.name as restaurant_name 
        FROM orders o 
        LEFT JOIN restaurants r ON o.restaurant_id = r.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no orders found, return empty array
    if (empty($orders)) {
        $orders = [];
    }
    
    echo json_encode([
        'success' => true, 
        'orders' => $orders
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'orders' => []
    ]);
}
?>