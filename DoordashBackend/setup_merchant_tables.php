<?php
require_once 'config.php';

echo "<h2>Setting up merchant tables...</h2>";

$sql_queries = [
    "CREATE TABLE IF NOT EXISTS user_restaurants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        restaurant_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_restaurant (user_id, restaurant_id)
    )" => "user_restaurants table",
    
    "INSERT IGNORE INTO user_restaurants (user_id, restaurant_id) 
     SELECT u.id, r.id 
     FROM users u 
     CROSS JOIN restaurants r 
     WHERE u.user_type = 'restaurant'" => "Link existing restaurant owners"
];

foreach ($sql_queries as $sql => $description) {
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ $description created successfully</p>";
    } catch(PDOException $e) {
        echo "<p style='color: red;'>✗ Error creating $description: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Setup complete!</h3>";
echo "<p><a href='merchant_login.php'>Go to Merchant Login</a></p>";
?>