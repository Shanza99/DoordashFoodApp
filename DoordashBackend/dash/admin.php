<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add_user':
            handleAddUser($pdo);
            break;
        case 'add_restaurant':
            handleAddRestaurant($pdo);
            break;
        case 'update_user':
            handleUpdateUser($pdo);
            break;
        case 'update_restaurant':
            handleUpdateRestaurant($pdo);
            break;
        case 'delete_user':
            handleDeleteUser($pdo);
            break;
        case 'delete_restaurant':
            handleDeleteRestaurant($pdo);
            break;
        case 'toggle_user_status':
            handleToggleUserStatus($pdo);
            break;
        case 'toggle_restaurant_status':
            handleToggleRestaurantStatus($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Handle GET requests for data fetching
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_users') {
        getUsers($pdo);
    } elseif ($action === 'get_restaurants') {
        getRestaurants($pdo);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

function handleAddUser($pdo) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'customer';
    
    if (empty($full_name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    try {
        // Check if email exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, user_type, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $result = $stmt->execute([$full_name, $email, $phone, $password, $user_type]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add user']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleAddRestaurant($pdo) {
    $name = $_POST['name'] ?? '';
    $cuisine_type = $_POST['cuisine_type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name) || empty($cuisine_type)) {
        echo json_encode(['success' => false, 'message' => 'Name and cuisine type are required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO restaurants (name, cuisine_type, description, delivery_time, delivery_fee, rating, is_active) VALUES (?, ?, ?, '20-30 min', 2.99, 4.5, 1)");
        $result = $stmt->execute([$name, $cuisine_type, $description]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Restaurant added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add restaurant']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleUpdateUser($pdo) {
    $user_id = $_POST['user_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_type = $_POST['user_type'] ?? 'customer';
    $is_active = $_POST['is_active'] ?? 1;
    
    if (empty($user_id) || empty($full_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    try {
        // Check if email exists for other users
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $user_id]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, user_type = ?, is_active = ? WHERE id = ?");
        $result = $stmt->execute([$full_name, $email, $phone, $user_type, $is_active, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleUpdateRestaurant($pdo) {
    $restaurant_id = $_POST['restaurant_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $cuisine_type = $_POST['cuisine_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $rating = $_POST['rating'] ?? 4.5;
    $delivery_time = $_POST['delivery_time'] ?? '20-30 min';
    $delivery_fee = $_POST['delivery_fee'] ?? 2.99;
    $is_active = $_POST['is_active'] ?? 1;
    $featured = $_POST['featured'] ?? 0;
    
    if (empty($restaurant_id) || empty($name) || empty($cuisine_type)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE restaurants SET name = ?, cuisine_type = ?, description = ?, rating = ?, delivery_time = ?, delivery_fee = ?, is_active = ?, featured = ? WHERE id = ?");
        $result = $stmt->execute([$name, $cuisine_type, $description, $rating, $delivery_time, $delivery_fee, $is_active, $featured, $restaurant_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Restaurant updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update restaurant']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteUser($pdo) {
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteRestaurant($pdo) {
    $restaurant_id = $_POST['restaurant_id'] ?? '';
    
    if (empty($restaurant_id)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
        $result = $stmt->execute([$restaurant_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Restaurant deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete restaurant']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleToggleUserStatus($pdo) {
    $user_id = $_POST['user_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    $new_status = ($current_status == 1) ? 0 : 1;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $result = $stmt->execute([$new_status, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleToggleRestaurantStatus($pdo) {
    $restaurant_id = $_POST['restaurant_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    
    if (empty($restaurant_id)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant ID is required']);
        return;
    }
    
    $new_status = ($current_status == 1) ? 0 : 1;
    
    try {
        $stmt = $pdo->prepare("UPDATE restaurants SET is_active = ? WHERE id = ?");
        $result = $stmt->execute([$new_status, $restaurant_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Restaurant status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update restaurant status']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'users' => $users]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getRestaurants($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM restaurants ORDER BY created_at DESC");
        $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'restaurants' => $restaurants]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>