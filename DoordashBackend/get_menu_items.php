<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is restaurant owner
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant' || !isset($_SESSION['restaurant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$restaurant_id = $_SESSION['restaurant_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name");
    $stmt->execute([$restaurant_id]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'menu_items' => $menu_items]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>