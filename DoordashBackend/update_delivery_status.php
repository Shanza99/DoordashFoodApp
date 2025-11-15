<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'delivery') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    // Validate status
    $allowed_statuses = ['picked_up', 'on_the_way', 'delivered'];
    if (!in_array($status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    try {
        // Verify the delivery person owns this order
        $stmt = $pdo->prepare("SELECT dp.id FROM orders o 
                              JOIN delivery_persons dp ON o.delivery_person_id = dp.id 
                              WHERE o.id = ? AND dp.user_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
            exit;
        }
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        
        // If order is delivered, update delivery person stats
        if ($status === 'delivered') {
            // Get delivery fee to add to earnings
            $stmt = $pdo->prepare("SELECT delivery_fee FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // Update delivery person's total deliveries and earnings
                $stmt = $pdo->prepare("UPDATE delivery_persons 
                                      SET total_deliveries = total_deliveries + 1, 
                                          earnings = earnings + ? 
                                      WHERE user_id = ?");
                $stmt->execute([$order['delivery_fee'], $_SESSION['user_id']]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>