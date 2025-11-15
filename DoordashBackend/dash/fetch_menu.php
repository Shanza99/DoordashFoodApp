<?php
include 'config.php';

$restaurant_id = $_GET['restaurant_id'] ?? 0;

try {
    if ($restaurant_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? AND is_active = 1 ORDER BY category, name");
        $stmt->execute([$restaurant_id]);
    } else {
        // If no restaurant_id provided, return empty array
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE 1=0");
        $stmt->execute();
    }
    
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no menu items found, create some sample data for demo
    if (empty($menu_items)) {
        $menu_items = [
            [
                'id' => 1,
                'name' => 'Classic Burger',
                'description' => 'Juicy beef patty with fresh vegetables',
                'price' => '12.99',
                'category' => 'Burgers',
                'is_available' => 1
            ],
            [
                'id' => 2,
                'name' => 'French Fries',
                'description' => 'Crispy golden fries',
                'price' => '4.99',
                'category' => 'Sides',
                'is_available' => 1
            ],
            [
                'id' => 3,
                'name' => 'Chocolate Shake',
                'description' => 'Creamy chocolate milkshake',
                'price' => '5.99',
                'category' => 'Drinks',
                'is_available' => 1
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'menu_items' => $menu_items
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'menu_items' => []
    ]);
}
?>