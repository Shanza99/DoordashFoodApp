<?php
include 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

try {
    switch($action) {
        case 'get_menu_items':
            $restaurant_id = $input['restaurant_id'];
            $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ?");
            $stmt->execute([$restaurant_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'menu_items' => $items]);
            break;
            
        case 'add_menu_item':
            $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, category, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['restaurant_id'],
                $input['name'],
                $input['description'],
                $input['price'],
                $input['category'],
                $input['image_url'] ?? ''
            ]);
            echo json_encode(['success' => true, 'message' => 'Menu item added successfully']);
            break;
            
        case 'update_menu_item':
            $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, image_url = ? WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([
                $input['name'],
                $input['description'],
                $input['price'],
                $input['category'],
                $input['image_url'] ?? '',
                $input['id'],
                $input['restaurant_id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Menu item updated successfully']);
            break;
            
        case 'delete_menu_item':
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$input['id'], $input['restaurant_id']]);
            echo json_encode(['success' => true, 'message' => 'Menu item deleted successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>