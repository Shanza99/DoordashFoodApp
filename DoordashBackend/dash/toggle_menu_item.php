<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    
    if (empty($item_id)) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit;
    }
    
    $new_status = ($current_status == 1) ? 0 : 1;
    
    try {
        $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
        $result = $stmt->execute([$new_status, $item_id]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Menu item availability updated',
                'new_status' => $new_status
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update menu item']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>