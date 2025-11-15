<?php
session_start();
// Database configuration
// $host = 'localhost';
// $dbname = 'doordash';
// $username = 'root';
// $password = '';
$host = "localhost";
$dbname = "doordash";
$username = "root";
$password = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "<h2>Setting up Merchant System...</h2>";

// Create user_restaurants table
$sql = "CREATE TABLE IF NOT EXISTS user_restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_restaurant (user_id, restaurant_id)
)";

try {
    $pdo->exec($sql);
    echo "<p style='color: green;'>✓ user_restaurants table created successfully</p>";
} catch(PDOException $e) {
    echo "<p style='color: orange;'>Note: " . $e->getMessage() . "</p>";
}

// Link existing restaurant owners to restaurants
$sql = "INSERT IGNORE INTO user_restaurants (user_id, restaurant_id) 
        SELECT u.id, r.id 
        FROM users u 
        CROSS JOIN restaurants r 
        WHERE u.user_type = 'restaurant' 
        AND NOT EXISTS (
            SELECT 1 FROM user_restaurants ur WHERE ur.user_id = u.id
        )";

try {
    $result = $pdo->exec($sql);
    echo "<p style='color: green;'>✓ Linked existing restaurant owners to restaurants</p>";
} catch(PDOException $e) {
    echo "<p style='color: orange;'>Note: " . $e->getMessage() . "</p>";
}

echo "<h3 style='color: green;'>Setup complete!</h3>";
echo "<p><a href='merchant_login.php' style='color: #FF3008; font-weight: bold;'>Go to Merchant Login</a></p>";
echo "<p><a href='index.php' style='color: #666;'>Go to Home Page</a></p>";

// Show current restaurant users
echo "<h4>Current Restaurant Owners:</h4>";
try {
    $stmt = $pdo->query("SELECT u.id, u.email, u.full_name, ur.restaurant_id 
                         FROM users u 
                         LEFT JOIN user_restaurants ur ON u.id = ur.user_id 
                         WHERE u.user_type = 'restaurant'");
    $restaurant_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($restaurant_users) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Restaurant ID</th></tr>";
        foreach ($restaurant_users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>" . ($user['restaurant_id'] ? $user['restaurant_id'] : 'Not linked') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No restaurant owners found.</p>";
    }
} catch(PDOException $e) {
    echo "<p>Error fetching users: " . $e->getMessage() . "</p>";
}
?>