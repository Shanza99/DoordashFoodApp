<?php
function registerUser($pdo) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $user_type = $_POST['user_type'];
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($full_name) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User with this email already exists']);
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed_password, $full_name, $phone, $user_type]);
        
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_type'] = $user_type;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
        
    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
}

function loginUser($pdo) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        
        echo json_encode(['success' => true, 'message' => 'Login successful', 'user_type' => $user['user_type']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
}

function saveUserAddress($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $address = $_POST['address'];
    $lat = $_POST['lat'];
    $lon = $_POST['lon'];
    
    $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
    $stmt->execute([$address, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Address saved']);
}
?>