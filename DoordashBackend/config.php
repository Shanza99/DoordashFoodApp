<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$dbname = "doordash";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    sendJsonResponse(false, 'Database connection failed: ' . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    handlePostRequest();
    exit;
}

function handlePostRequest() {
    global $pdo;
    
    $action = $_POST['action'];
    
    switch($action) {

        case 'register':
            registerUser($pdo);
            break;
        case 'login':
            loginUser($pdo);
            break;
        case 'merchant_login':
            merchantLogin($pdo);
            break;
        case 'register_merchant':
            registerMerchant($pdo);
            break;
        case 'register_delivery':
            registerDeliveryPerson($pdo);
            break;
        case 'delivery_login':
            deliveryLogin($pdo);
            break;
        case 'save_address':
            saveUserAddress($pdo);
            break;
        case 'add_menu_item':
            addMenuItem($pdo);
            break;
        case 'update_menu_item':
            updateMenuItem($pdo);
            break;
        case 'update_restaurant':
            updateRestaurant($pdo);
            break;
        case 'delete_user':
            deleteUser($pdo);
            break;
        case 'delete_restaurant':
            deleteRestaurant($pdo);
            break;
        case 'approve_delivery_person':
            approveDeliveryPerson($pdo);
            break;
        case 'reject_delivery_person':
            rejectDeliveryPerson($pdo);
            break;
            
// Add this to the handlePostRequest switch statement
case 'register_restaurant_with_address':
    registerRestaurantWithAddress($pdo);
    break;
        default:
            sendJsonResponse(false, 'Invalid action: ' . $action);
    }
}

function sendJsonResponse($success, $message, $additionalData = []) {
    header('Content-Type: application/json');
    $response = array_merge(['success' => $success, 'message' => $message], $additionalData);
    echo json_encode($response);
    exit;
}

function registerUser($pdo) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'customer';
    
    if (empty($email) || empty($password) || empty($full_name) || empty($phone)) {
        sendJsonResponse(false, 'All fields are required');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            sendJsonResponse(false, 'Email already registered');
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$email, $password, $full_name, $phone, $user_type]);
        
        if ($result) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            
            sendJsonResponse(true, 'Registration successful!');
        } else {
            sendJsonResponse(false, 'Registration failed');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function loginUser($pdo) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($password === $user['password']) {
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                
                // If user is a restaurant owner, get their restaurant ID
                if ($user['user_type'] === 'restaurant') {
                    setupRestaurantSession($pdo, $user['id']);
                }
                
                // If user is a delivery person, get delivery info
                if ($user['user_type'] === 'delivery') {
                    setupDeliverySession($pdo, $user['id']);
                }
                
                sendJsonResponse(true, 'Login successful', ['user_type' => $user['user_type']]);
            } else {
                sendJsonResponse(false, 'Invalid password');
            }
        } else {
            sendJsonResponse(false, 'User not found or account inactive');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function merchantLogin($pdo) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (empty($email) || empty($password)) {
        sendJsonResponse(false, 'Email and password are required');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type IN ('restaurant', 'admin') AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($password === $user['password']) {
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Setup restaurant session for restaurant owners
                if ($user['user_type'] === 'restaurant') {
                    setupRestaurantSession($pdo, $user['id']);
                }
                
                sendJsonResponse(true, 'Login successful!', ['redirect' => 'restaurant_dashboard.php']);
            } else {
                sendJsonResponse(false, 'Invalid password');
            }
        } else {
            sendJsonResponse(false, 'No restaurant account found with this email');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function deliveryLogin($pdo) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (empty($email) || empty($password)) {
        sendJsonResponse(false, 'Email and password are required');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, dp.is_approved, dp.is_available FROM users u 
                              LEFT JOIN delivery_persons dp ON u.id = dp.user_id 
                              WHERE u.email = ? AND u.user_type = 'delivery' AND u.is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($password === $user['password']) {
                if (!$user['is_approved']) {
                    sendJsonResponse(false, 'Your delivery account is pending approval. Please wait for activation.');
                }
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['delivery_approved'] = $user['is_approved'];
                $_SESSION['delivery_available'] = $user['is_available'];
                
                // Setup delivery session
                setupDeliverySession($pdo, $user['id']);
                
                sendJsonResponse(true, 'Login successful!', ['redirect' => 'delivery_dashboard.php']);
            } else {
                sendJsonResponse(false, 'Invalid password');
            }
        } else {
            sendJsonResponse(false, 'No delivery account found with this email');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function registerDeliveryPerson($pdo) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $vehicle_type = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : 'motorcycle';
    $vehicle_plate = isset($_POST['vehicle_plate']) ? trim($_POST['vehicle_plate']) : '';
    $license_number = isset($_POST['license_number']) ? trim($_POST['license_number']) : '';
    
    if (empty($email) || empty($password) || empty($full_name) || empty($phone) || empty($license_number)) {
        sendJsonResponse(false, 'All required fields must be filled');
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            sendJsonResponse(false, 'Email already registered');
        }
        
        // Create user account
        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, 'delivery')");
        $result = $stmt->execute([$email, $password, $full_name, $phone]);
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            
            // Create delivery person record (initially not approved)
            $stmt = $pdo->prepare("INSERT INTO delivery_persons (user_id, vehicle_type, vehicle_plate, license_number, is_approved) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$user_id, $vehicle_type, $vehicle_plate, $license_number]);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 'delivery';
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['delivery_approved'] = false;
            $_SESSION['delivery_available'] = false;
            
            sendJsonResponse(true, 'Delivery account created successfully! Your account will be activated after verification.', ['redirect' => 'delivery_dashboard.php']);
        } else {
            sendJsonResponse(false, 'Registration failed');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function approveDeliveryPerson($pdo) {
    // Check if user is admin
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        sendJsonResponse(false, 'Unauthorized access');
    }
    
    $delivery_person_id = isset($_POST['delivery_person_id']) ? intval($_POST['delivery_person_id']) : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE delivery_persons SET is_approved = 1 WHERE id = ?");
        $result = $stmt->execute([$delivery_person_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            sendJsonResponse(true, 'Delivery person approved successfully');
        } else {
            sendJsonResponse(false, 'Delivery person not found');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function rejectDeliveryPerson($pdo) {
    // Check if user is admin
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        sendJsonResponse(false, 'Unauthorized access');
    }
    
    $delivery_person_id = isset($_POST['delivery_person_id']) ? intval($_POST['delivery_person_id']) : 0;
    
    try {
        // Get user ID first to also delete the user account
        $stmt = $pdo->prepare("SELECT user_id FROM delivery_persons WHERE id = ?");
        $stmt->execute([$delivery_person_id]);
        $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($delivery_person) {
            // Delete delivery person record
            $stmt = $pdo->prepare("DELETE FROM delivery_persons WHERE id = ?");
            $stmt->execute([$delivery_person_id]);
            
            // Delete user account
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'delivery'");
            $stmt->execute([$delivery_person['user_id']]);
            
            sendJsonResponse(true, 'Delivery person rejected and account removed');
        } else {
            sendJsonResponse(false, 'Delivery person not found');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function setupRestaurantSession($pdo, $user_id) {
    // Try to get restaurant ID from user_restaurants table first
    $stmt = $pdo->prepare("SELECT restaurant_id FROM user_restaurants WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user_restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_restaurant) {
        $_SESSION['restaurant_id'] = $user_restaurant['restaurant_id'];
        return;
    }
    
    // Fallback: get the first restaurant (for existing users)
    $stmt = $pdo->prepare("SELECT id FROM restaurants LIMIT 1");
    $stmt->execute();
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($restaurant) {
        $_SESSION['restaurant_id'] = $restaurant['id'];
        
        // Create the user_restaurant relationship
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_restaurants (user_id, restaurant_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $restaurant['id']]);
    }
}

function setupDeliverySession($pdo, $user_id) {
    // Get delivery person information
    $stmt = $pdo->prepare("SELECT * FROM delivery_persons WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($delivery_person) {
        $_SESSION['delivery_person_id'] = $delivery_person['id'];
        $_SESSION['delivery_approved'] = $delivery_person['is_approved'];
        $_SESSION['delivery_available'] = $delivery_person['is_available'];
        $_SESSION['vehicle_type'] = $delivery_person['vehicle_type'];
        $_SESSION['total_deliveries'] = $delivery_person['total_deliveries'];
        $_SESSION['earnings'] = $delivery_person['earnings'];
    }
}

function registerMerchant($pdo) {
    $required_fields = ['restaurant_name', 'cuisine_type', 'delivery_time', 'delivery_fee', 'full_name', 'phone', 'email', 'address'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            sendJsonResponse(false, "Field '$field' is required");
        }
    }
    
    $restaurant_name = trim($_POST['restaurant_name']);
    $cuisine_type = trim($_POST['cuisine_type']);
    $description = trim($_POST['description'] ?? '');
    $delivery_time = trim($_POST['delivery_time']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    try {
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            // Update user type to restaurant if not already
            if ($_SESSION['user_type'] !== 'restaurant') {
                $stmt = $pdo->prepare("UPDATE users SET user_type = 'restaurant' WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['user_type'] = 'restaurant';
            }
        } else {
            // Create new user account
            $password = trim($_POST['password']);
            
            if (empty($password)) {
                sendJsonResponse(false, 'Password is required');
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'Email already registered');
            }
            
            // Create new user
            $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, 'restaurant')");
            $stmt->execute([$email, $password, $full_name, $phone]);
            
            $user_id = $pdo->lastInsertId();
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 'restaurant';
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
        }
        
        // Create restaurant entry
        $stmt = $pdo->prepare("INSERT INTO restaurants (name, description, cuisine_type, delivery_time, delivery_fee, rating, review_count) VALUES (?, ?, ?, ?, ?, 4.5, 0)");
        $stmt->execute([$restaurant_name, $description, $cuisine_type, $delivery_time, $delivery_fee]);
        
        $restaurant_id = $pdo->lastInsertId();
        
        // Link user to restaurant
        $stmt = $pdo->prepare("INSERT INTO user_restaurants (user_id, restaurant_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $restaurant_id]);
        
        // Store restaurant ID in session for easy access
        $_SESSION['restaurant_id'] = $restaurant_id;
        
        sendJsonResponse(true, 'Restaurant registered successfully!', ['redirect' => 'restaurant_dashboard.php']);
        
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function addMenuItem($pdo) {
    // Check if user is logged in as restaurant owner
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'restaurant' || !isset($_SESSION['restaurant_id'])) {
        sendJsonResponse(false, 'Unauthorized access');
    }
    
    $restaurant_id = $_SESSION['restaurant_id'];
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    
    if (empty($item_name) || empty($category) || $price <= 0) {
        sendJsonResponse(false, 'Item name, category, and valid price are required');
    }
    
    try {
        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'menu_item_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, category, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$restaurant_id, $item_name, $description, $price, $category, $image_url]);
        
        sendJsonResponse(true, 'Menu item added successfully!');
        
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function updateMenuItem($pdo) {
    // Check if user is logged in as restaurant owner
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'restaurant' || !isset($_SESSION['restaurant_id'])) {
        sendJsonResponse(false, 'Unauthorized access');
    }
    
    $restaurant_id = $_SESSION['restaurant_id'];
    $item_id = intval($_POST['item_id'] ?? 0);
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    
    if (empty($item_id) || empty($item_name) || empty($category) || $price <= 0) {
        sendJsonResponse(false, 'All fields are required');
    }
    
    try {
        // Check if item belongs to restaurant
        $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$item_id, $restaurant_id]);
        
        if (!$stmt->fetch()) {
            sendJsonResponse(false, 'Menu item not found');
        }
        
        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'menu_item_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            }
        }
        
        if ($image_url) {
            $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$item_name, $description, $price, $category, $image_url, $item_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ? WHERE id = ?");
            $stmt->execute([$item_name, $description, $price, $category, $item_id]);
        }
        
        sendJsonResponse(true, 'Menu item updated successfully!');
        
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function updateRestaurant($pdo) {
    // Check if user is logged in as restaurant owner
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'restaurant' || !isset($_SESSION['restaurant_id'])) {
        sendJsonResponse(false, 'Unauthorized access');
    }
    
    $restaurant_id = $_SESSION['restaurant_id'];
    $name = trim($_POST['restaurant_name'] ?? '');
    $cuisine_type = trim($_POST['cuisine_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $delivery_time = trim($_POST['delivery_time'] ?? '');
    $delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
    
    if (empty($name) || empty($cuisine_type) || empty($delivery_time) || $delivery_fee < 0) {
        sendJsonResponse(false, 'All required fields must be filled');
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE restaurants SET name = ?, description = ?, cuisine_type = ?, delivery_time = ?, delivery_fee = ? WHERE id = ?");
        $stmt->execute([$name, $description, $cuisine_type, $delivery_time, $delivery_fee, $restaurant_id]);
        
        sendJsonResponse(true, 'Restaurant information updated successfully!');
        
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function saveUserAddress($pdo) {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'Not logged in');
    }
    
    $user_id = $_SESSION['user_id'];
    $address = $_POST['address'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
        $stmt->execute([$address, $user_id]);
        
        sendJsonResponse(true, 'Address saved');
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// Admin functions
function deleteUser($pdo) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        sendJsonResponse(false, 'Unauthorized access');
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type != 'admin'");
        $result = $stmt->execute([$user_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            sendJsonResponse(true, 'User deleted successfully');
        } else {
            sendJsonResponse(false, 'User not found or cannot delete admin');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function deleteRestaurant($pdo) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        sendJsonResponse(false, 'Unauthorized access');
    }
    
    $restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
        $result = $stmt->execute([$restaurant_id]);
        
        if ($result) {
            sendJsonResponse(true, 'Restaurant deleted successfully');
        } else {
            sendJsonResponse(false, 'Restaurant not found');
        }
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// Helper functions
function getRestaurants($pdo, $active_only = true) {
    try {
        $sql = "SELECT * FROM restaurants";
        if ($active_only) {
            $sql .= " WHERE is_active = TRUE";
        }
        $sql .= " ORDER BY featured DESC, rating DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getAllUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getDeliveryPersons($pdo) {
    try {
        $stmt = $pdo->query("SELECT u.*, dp.* FROM users u 
                            JOIN delivery_persons dp ON u.id = dp.user_id 
                            ORDER BY dp.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getPendingDeliveryPersons($pdo) {
    try {
        $stmt = $pdo->query("SELECT u.*, dp.* FROM users u 
                            JOIN delivery_persons dp ON u.id = dp.user_id 
                            WHERE dp.is_approved = 0 
                            ORDER BY dp.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getPendingOrders($pdo, $delivery_person_id = null) {
    try {
        $sql = "SELECT o.*, r.name as restaurant_name, u.full_name as customer_name, u.address as customer_address 
                FROM orders o 
                JOIN restaurants r ON o.restaurant_id = r.id 
                JOIN users u ON o.customer_id = u.id 
                WHERE o.status IN ('ready_for_pickup', 'picked_up', 'on_the_way')";
        
        if ($delivery_person_id) {
            $sql .= " AND o.delivery_person_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$delivery_person_id]);
        } else {
            $sql .= " AND o.delivery_person_id IS NULL AND o.status = 'ready_for_pickup'";
            $stmt = $pdo->query($sql);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function updateDeliveryStatus($pdo, $delivery_person_id, $is_available) {
    try {
        $stmt = $pdo->prepare("UPDATE delivery_persons SET is_available = ? WHERE user_id = ?");
        $stmt->execute([$is_available, $delivery_person_id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function assignOrderToDelivery($pdo, $order_id, $delivery_person_id) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET delivery_person_id = ?, status = 'picked_up' WHERE id = ?");
        $stmt->execute([$delivery_person_id, $order_id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function updateOrderStatus($pdo, $order_id, $status) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Add this function to handle restaurant registration with address
function registerRestaurantWithAddress($pdo) {
    // Check if user is logged in as restaurant owner or creating new account
    $user_id = $_SESSION['user_id'] ?? null;
    $is_new_user = false;
    
    $required_fields = ['restaurant_name', 'cuisine_type', 'delivery_time', 'delivery_fee', 'address', 'city', 'state', 'zip_code'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            sendJsonResponse(false, "Field '$field' is required");
        }
    }
    
    $restaurant_name = trim($_POST['restaurant_name']);
    $cuisine_type = trim($_POST['cuisine_type']);
    $description = trim($_POST['description'] ?? '');
    $delivery_time = trim($_POST['delivery_time']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    
    try {
        // Create user account if not logged in
        if (!$user_id) {
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $full_name = trim($_POST['full_name']);
            $phone = trim($_POST['phone']);
            
            if (empty($email) || empty($password) || empty($full_name) || empty($phone)) {
                sendJsonResponse(false, 'All user fields are required for new account');
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'Email already registered');
            }
            
            // Create new user
            $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, 'restaurant')");
            $stmt->execute([$email, $password, $full_name, $phone]);
            
            $user_id = $pdo->lastInsertId();
            $is_new_user = true;
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 'restaurant';
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
        }
        
        // Create restaurant entry WITH ADDRESS
        $stmt = $pdo->prepare("INSERT INTO restaurants (name, description, cuisine_type, delivery_time, delivery_fee, address, city, state, zip_code, rating, review_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 4.5, 0)");
        $stmt->execute([$restaurant_name, $description, $cuisine_type, $delivery_time, $delivery_fee, $address, $city, $state, $zip_code]);
        
        $restaurant_id = $pdo->lastInsertId();
        
        // Link user to restaurant
        $stmt = $pdo->prepare("INSERT INTO user_restaurants (user_id, restaurant_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $restaurant_id]);
        
        // Store restaurant ID in session for easy access
        $_SESSION['restaurant_id'] = $restaurant_id;
        
        sendJsonResponse(true, 'Restaurant registered successfully!', [
            'redirect' => 'restaurant_dashboard.php',
            'is_new_user' => $is_new_user
        ]);
        
    } catch(PDOException $e) {
        sendJsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

?>