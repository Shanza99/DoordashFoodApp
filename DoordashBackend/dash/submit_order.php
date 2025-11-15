<?php
include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

// If no JSON input, try form data
if (empty($input)) {
    $input = $_POST;
}

$restaurant_id = $input['restaurant_id'] ?? '';
$customer_name = $input['customer_name'] ?? '';
$customer_phone = $input['customer_phone'] ?? '';
$customer_email = $input['customer_email'] ?? '';
$delivery_address = $input['delivery_address'] ?? '';
$special_instructions = $input['special_instructions'] ?? '';
$order_total = $input['order_total'] ?? 0;
$order_items = $input['order_items'] ?? [];

// Validate required fields
if (empty($restaurant_id) || empty($customer_name) || empty($customer_phone) || empty($delivery_address)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (restaurant_id, customer_name, customer_phone, customer_email, delivery_address, special_instructions, order_total, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$restaurant_id, $customer_name, $customer_phone, $customer_email, $delivery_address, $special_instructions, $order_total]);
    $order_id = $pdo->lastInsertId();
    
    // Insert order items if provided
    if (!empty($order_items)) {
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($order_items as $item) {
            $stmt->execute([$order_id, $item['name'], $item['quantity'] ?? 1, $item['price']]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'order_id' => $order_id, 
        'message' => 'Order placed successfully!'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to place order: ' . $e->getMessage()
    ]);
}
?>