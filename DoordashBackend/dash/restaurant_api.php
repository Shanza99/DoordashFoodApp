<?php
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';

    switch($action) {
        case 'get_restaurant_by_owner':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Not logged in']);
                break;
            }
            
            $user_id = $_SESSION['user_id'];
            try {
                // For now, return all restaurants since we don't have owner association
                // In a real app, you'd link restaurants to users
                $stmt = $pdo->query("SELECT * FROM restaurants WHERE is_active = 1 ORDER BY created_at DESC");
                $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'restaurants' => $restaurants]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>