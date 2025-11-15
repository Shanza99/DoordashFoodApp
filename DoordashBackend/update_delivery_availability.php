<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'delivery') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_available = isset($_POST['available']) ? intval($_POST['available']) : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE delivery_persons SET is_available = ? WHERE user_id = ?");
        $stmt->execute([$is_available, $_SESSION['user_id']]);
        
        $_SESSION['delivery_available'] = $is_available;
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>