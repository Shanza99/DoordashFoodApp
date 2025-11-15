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
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
            exit;
        }
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        
        // If delivered, update delivery person earnings
        if ($status === 'delivered') {
            $stmt = $pdo->prepare("SELECT delivery_fee FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order_data) {
                $delivery_fee = $order_data['delivery_fee'];
                
                // Update delivery person earnings and total deliveries
                $stmt = $pdo->prepare("UPDATE delivery_persons 
                                      SET earnings = earnings + ?, 
                                          total_deliveries = total_deliveries + 1,
                                          updated_at = NOW()
                                      WHERE user_id = ?");
                $stmt->execute([$delivery_fee, $_SESSION['user_id']]);
                
                // Update session earnings
                $_SESSION['delivery_earnings'] += $delivery_fee;
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
        
    } catch(PDOException $e) {
        error_log('Update delivery status error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>