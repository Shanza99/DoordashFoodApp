<?php
include 'config.php';

try {
    // For demo purposes, return all orders
    // In real app, filter by merchant's restaurant
    $stmt = $pdo->query("
        SELECT o.*, r.name as restaurant_name 
        FROM orders o 
        LEFT JOIN restaurants r ON o.restaurant_id = r.id 
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no orders, create sample data
    if (empty($orders)) {
        $orders = [
            [
                'id' => 1,
                'customer_name' => 'John Doe',
                'customer_phone' => '555-0101',
                'customer_email' => 'john@example.com',
                'delivery_address' => '123 Main St, New York, NY',
                'order_total' => '45.97',
                'status' => 'pending',
                'restaurant_name' => 'Burger Palace',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'customer_name' => 'Jane Smith',
                'customer_phone' => '555-0102',
                'customer_email' => 'jane@example.com',
                'delivery_address' => '456 Oak Ave, New York, NY',
                'order_total' => '32.50',
                'status' => 'preparing',
                'restaurant_name' => 'Burger Palace',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ]
        ];
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