<?php
include 'config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM restaurants WHERE is_active = 1");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'restaurants' => $restaurants
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>