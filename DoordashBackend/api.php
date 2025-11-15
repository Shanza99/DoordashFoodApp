<?php
require_once 'config.php';

// Set CORS headers to allow requests from any origin
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Get the action from query parameters or request body
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

// Log the request for debugging
error_log("API Request - Action: $action, Method: " . $_SERVER['REQUEST_METHOD']);

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action parameter required']);
    exit;
}

switch($action) {
    case 'get_restaurants':
        getRestaurants();
        break;
    case 'get_menu_items':
        getMenuItems();
        break;
    case 'get_customer_orders':
        getCustomerOrders();
        break;
    case 'search_restaurants':
        searchRestaurants();
        break;
    case 'place_order':
        placeOrder();
        break;
    case 'test_connection':
        testConnection();
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}

function testConnection() {
    echo json_encode([
        'success' => true,
        'message' => 'API is working',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function getRestaurants() {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM restaurants WHERE is_active = TRUE ORDER BY rating DESC";
        $stmt = $pdo->query($sql);
        $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'restaurants' => $restaurants,
            'count' => count($restaurants)
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function searchRestaurants() {
    global $pdo;
    
    $search = $_GET['search'] ?? '';
    $location = $_GET['location'] ?? '';
    
    try {
        $sql = "SELECT * FROM restaurants WHERE is_active = TRUE";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR cuisine_type LIKE ? OR description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($location)) {
            $sql .= " AND (address LIKE ? OR city LIKE ? OR state LIKE ? OR zip_code LIKE ?)";
            $locationTerm = "%$location%";
            $params[] = $locationTerm;
            $params[] = $locationTerm;
            $params[] = $locationTerm;
            $params[] = $locationTerm;
        }
        
        $sql .= " ORDER BY rating DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'restaurants' => $restaurants,
            'count' => count($restaurants)
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getMenuItems() {
    global $pdo;
    
    $restaurant_id = $_GET['restaurant_id'] ?? 0;
    
    if (!$restaurant_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Restaurant ID required']);
        return;
    }
    
    try {
        $sql = "SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = TRUE ORDER BY category, name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$restaurant_id]);
        $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'menu_items' => $menu_items,
            'count' => count($menu_items)
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getCustomerOrders() {
    global $pdo;
    
    $customer_id = $_GET['customer_id'] ?? 0;
    
    if (!$customer_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Customer ID required']);
        return;
    }
    
    try {
        $sql = "SELECT o.*, r.name as restaurant_name 
                FROM orders o 
                JOIN restaurants r ON o.restaurant_id = r.id 
                WHERE o.customer_id = ? 
                ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get order items for each order
        foreach ($orders as &$order) {
            $items_sql = "SELECT oi.*, mi.name as item_name, mi.price as item_price
                         FROM order_items oi 
                         JOIN menu_items mi ON oi.menu_item_id = mi.id 
                         WHERE oi.order_id = ?";
            $items_stmt = $pdo->prepare($items_sql);
            $items_stmt->execute([$order['id']]);
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'count' => count($orders)
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function placeOrder() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        return;
    }
    
    $customer_id = $input['customer_id'] ?? 0;
    $cart_items = $input['cart_items'] ?? [];
    $delivery_address = $input['delivery_address'] ?? '';
    $instructions = $input['instructions'] ?? '';
    
    if (!$customer_id || empty($cart_items) || empty($delivery_address)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get restaurant ID from first cart item
        $restaurant_id = $cart_items[0]['restaurant_id'];
        
        // Calculate totals
        $subtotal = 0;
        foreach($cart_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $tax_rate = 0.08875;
        $tax_amount = $subtotal * $tax_rate;
        
        // Get restaurant delivery fee
        $stmt = $pdo->prepare("SELECT delivery_fee FROM restaurants WHERE id = ?");
        $stmt->execute([$restaurant_id]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
        $delivery_fee = $restaurant['delivery_fee'] ?? 2.99;
        
        $total_amount = $subtotal + $tax_amount + $delivery_fee;
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, restaurant_id, total_amount, delivery_address, delivery_fee, instructions, status, subtotal, tax_amount, tax_rate) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([
            $customer_id,
            $restaurant_id,
            $total_amount,
            $delivery_address,
            $delivery_fee,
            $instructions,
            $subtotal,
            $tax_amount,
            $tax_rate
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Add order items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach($cart_items as $item) {
            $stmt->execute([
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['price']
            ]);
        }
        
        // Update user address
        $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
        $stmt->execute([$delivery_address, $customer_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order_id
        ]);
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to place order: ' . $e->getMessage()
        ]);
    }
}
?>