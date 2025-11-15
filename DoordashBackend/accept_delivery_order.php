<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'delivery') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    try {
        // Get delivery person ID
        $stmt = $pdo->prepare("SELECT id FROM delivery_persons WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$delivery_person) {
            echo json_encode(['success' => false, 'message' => 'Delivery person not found']);
            exit;
        }
        
        // Assign order to delivery person
        $stmt = $pdo->prepare("UPDATE orders SET delivery_person_id = ?, status = 'picked_up' WHERE id = ? AND delivery_person_id IS NULL");
        $stmt->execute([$delivery_person['id'], $order_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order accepted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order already taken or not found']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>