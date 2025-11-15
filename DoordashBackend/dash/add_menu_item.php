<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_id = $_POST['restaurant_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? 'Main Course';
    
    // Validate inputs
    if (empty($restaurant_id) || empty($name) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant ID, name and price are required']);
        exit;
    }
    
    if (!is_numeric($price) || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Price must be a positive number']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, category, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $result = $stmt->execute([$restaurant_id, $name, $description, $price, $category]);
        
        if ($result) {
            $item_id = $pdo->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Menu item added successfully!',
                'item_id' => $item_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add menu item']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>