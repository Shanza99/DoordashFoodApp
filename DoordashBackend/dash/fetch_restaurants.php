<?php
include 'config.php';

try {
    $stmt = $pdo->query("SELECT * FROM restaurants WHERE is_active = 1 ORDER BY created_at DESC");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no restaurants, create sample data
    if (empty($restaurants)) {
        $restaurants = [
            [
                'id' => 1,
                'name' => 'Burger Palace',
                'cuisine_type' => 'American • Burgers',
                'description' => 'Best burgers in town with fresh ingredients',
                'rating' => '4.50',
                'delivery_time' => '20-30 min',
                'delivery_fee' => '2.99',
                'is_active' => 1
            ],
            [
                'id' => 2,
                'name' => 'Pizza Heaven',
                'cuisine_type' => 'Italian • Pizza',
                'description' => 'Authentic Italian pizzas made with love',
                'rating' => '4.70',
                'delivery_time' => '25-35 min',
                'delivery_fee' => '1.99',
                'is_active' => 1
            ],
            [
                'id' => 3,
                'name' => 'Sushi Master',
                'cuisine_type' => 'Japanese • Sushi',
                'description' => 'Fresh sushi and Japanese cuisine',
                'rating' => '4.80',
                'delivery_time' => '30-40 min',
                'delivery_fee' => '3.99',
                'is_active' => 1
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'restaurants' => $restaurants
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'restaurants' => []
    ]);
}
?>