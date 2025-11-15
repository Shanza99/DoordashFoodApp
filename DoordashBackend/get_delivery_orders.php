<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'delivery') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get orders that are ready for pickup and not assigned to any delivery person
    $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, r.delivery_time, u.full_name as customer_name, u.phone as customer_phone 
                          FROM orders o 
                          JOIN restaurants r ON o.restaurant_id = r.id 
                          JOIN users u ON o.customer_id = u.id 
                          WHERE o.delivery_person_id IS NULL AND o.status = 'ready_for_pickup' 
                          ORDER BY o.created_at DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'orders' => $orders]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>