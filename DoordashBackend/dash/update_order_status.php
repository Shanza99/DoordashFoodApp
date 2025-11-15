<?php
include 'config.php';

$order_id = $_POST['order_id'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($order_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
    exit;
}

$allowed_statuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $result = $stmt->execute([$status, $order_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found or no changes made']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>