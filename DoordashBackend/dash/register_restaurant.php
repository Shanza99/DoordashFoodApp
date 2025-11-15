<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = $_POST['name'] ?? '';
    $cuisine_type = $_POST['cuisine_type'] ?? '';
    $description = $_POST['description'] ?? 'Best food in town';
    $delivery_time = $_POST['delivery_time'] ?? '20-30 min';
    $delivery_fee = $_POST['delivery_fee'] ?? 2.99;
    $rating = $_POST['rating'] ?? 4.5;
    $featured = $_POST['featured'] ?? 0;
    $is_active = $_POST['is_active'] ?? 1;

    // Validate required fields
    if (empty($name) || empty($cuisine_type)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant name and cuisine type are required']);
        exit;
    }

    try {
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO restaurants (name, description, cuisine_type, delivery_time, delivery_fee, rating, featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([
            $name, 
            $description, 
            $cuisine_type, 
            $delivery_time, 
            $delivery_fee, 
            $rating, 
            $featured, 
            $is_active
        ]);

        if ($result) {
            $restaurant_id = $pdo->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Restaurant registered successfully!', 
                'restaurant_id' => $restaurant_id,
                'data' => [
                    'id' => $restaurant_id,
                    'name' => $name,
                    'cuisine_type' => $cuisine_type,
                    'description' => $description,
                    'delivery_time' => $delivery_time,
                    'delivery_fee' => $delivery_fee,
                    'rating' => $rating,
                    'featured' => $featured,
                    'is_active' => $is_active
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to register restaurant']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>