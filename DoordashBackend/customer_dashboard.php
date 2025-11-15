<?php
require_once 'config.php';

// Redirect if not customer
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: customer_register.php');
    exit;
}

// Get customer data
$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['user_name'];

// Debug: Check session data
error_log("Session data: " . print_r($_SESSION, true));

// Get complete user data from database
try {
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$customer_id]);
    $customer_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer_data) {
        // Update session with latest data from database
        $_SESSION['user_address'] = $customer_data['address'] ?? '';
        $_SESSION['user_email'] = $customer_data['email'] ?? $_SESSION['user_email'];
        $_SESSION['user_name'] = $customer_data['full_name'] ?? $_SESSION['user_name'];
    }
    
    error_log("Customer data from DB: " . print_r($customer_data, true));
    
} catch(PDOException $e) {
    error_log("User data error: " . $e->getMessage());
    $customer_data = [];
}

// Get order history - SIMPLIFIED QUERY
try {
    error_log("Fetching orders for customer ID: " . $customer_id);
    
    $orders_stmt = $pdo->prepare("
        SELECT o.*, r.name as restaurant_name 
        FROM orders o 
        LEFT JOIN restaurants r ON o.restaurant_id = r.id 
        WHERE o.customer_id = ? 
        ORDER BY o.created_at DESC
    ");
    $orders_stmt->execute([$customer_id]);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($orders) . " orders for customer " . $customer_id);
    error_log("Orders data: " . print_r($orders, true));
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $items_stmt = $pdo->prepare("
            SELECT oi.*, mi.name as item_name, mi.price as item_price
            FROM order_items oi 
            LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order['id']]);
        $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total items count
        $order['total_items'] = array_sum(array_column($order['items'], 'quantity'));
        
        error_log("Order " . $order['id'] . " has " . count($order['items']) . " items");
    }
    
    $total_orders = count($orders);
    $pending_orders = array_filter($orders, function($order) {
        return in_array($order['status'], ['pending', 'confirmed', 'preparing', 'ready_for_pickup', 'picked_up', 'on_the_way']);
    });
    
} catch(PDOException $e) {
    error_log("Orders error: " . $e->getMessage());
    $orders = [];
    $total_orders = 0;
    $pending_orders = [];
}

// Calculate total spent
$total_spent = 0;
foreach ($orders as $order) {
    $total_spent += floatval($order['total_amount']);
}

// Debug final data
error_log("Final orders count: " . count($orders));
error_log("Final user address: " . ($_SESSION['user_address'] ?? 'NOT SET'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .dashboard-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .dashboard-content {
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #FF3008;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #FF3008;
            margin-bottom: 5px;
        }

        .dashboard-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
        }

        .dashboard-tab {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .dashboard-tab:hover {
            color: #FF3008;
            background: #fff5f5;
        }

        .dashboard-tab.active {
            color: #FF3008;
            border-bottom-color: #FF3008;
            background: #fff5f5;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .action-btn {
            padding: 12px 20px;
            margin: 5px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #FF3008;
            color: white;
        }

        .btn-primary:hover {
            background: #e02a07;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .orders-grid {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 2px solid transparent;
        }

        .order-card:hover {
            transform: translateY(-5px);
            border-color: #FF3008;
        }

        .order-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .order-restaurant {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .order-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }

        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-preparing { background: #d1ecf1; color: #0c5460; }
        .status-ready_for_pickup { background: #d4edda; color: #155724; }
        .status-picked_up { background: #d4edda; color: #155724; }
        .status-on_the_way { background: #d4edda; color: #155724; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-body {
            padding: 20px;
        }

        .order-items {
            margin-bottom: 15px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            color: #333;
        }

        .item-quantity {
            color: #666;
        }

        .item-price {
            font-weight: 600;
            color: #FF3008;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
            font-size: 18px;
            font-weight: 700;
        }

        .order-actions {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #ddd;
        }

        .customer-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .info-value {
            color: #666;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .order-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-user"></i> Customer Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($customer_name); ?>!</p>
        </div>
        
        <div class="dashboard-content">
            <a href="index.php" class="action-btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($pending_orders); ?></div>
                    <div class="stat-label">Active Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($total_spent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php 
                        $delivered_orders = array_filter($orders, function($order) {
                            return $order['status'] === 'delivered';
                        });
                        echo count($delivered_orders);
                    ?></div>
                    <div class="stat-label">Delivered Orders</div>
                </div>
            </div>
            
            <!-- Dashboard Tabs -->
            <div class="dashboard-tabs">
                <button class="dashboard-tab active" data-tab="overview">Overview</button>
                <button class="dashboard-tab" data-tab="orders">Order History</button>
                <button class="dashboard-tab" data-tab="profile">My Profile</button>
                <a href="restaurants.php" class="action-btn btn-primary">
                    <i class="fas fa-utensils"></i> Order Food
                </a>
            </div>
            
            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content active">
                <h2>Quick Overview</h2>
                
                <div class="customer-info">
                    <h3>My Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($customer_name); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Default Address</div>
                            <div class="info-value"><?php echo htmlspecialchars(!empty($_SESSION['user_address']) ? $_SESSION['user_address'] : 'Not set'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value">
                                <?php 
                                    echo date('M j, Y', strtotime($customer_data['created_at'] ?? 'now'));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <h3>Recent Orders</h3>
                <?php if(count($orders) > 0): ?>
                    <div class="orders-grid">
                        <?php foreach(array_slice($orders, 0, 3) as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-restaurant">
                                        <i class="fas fa-utensils"></i>
                                        <?php echo htmlspecialchars($order['restaurant_name'] ?? 'Unknown Restaurant'); ?>
                                    </div>
                                    <div class="order-meta">
                                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                                        <span><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
                                    </div>
                                    <div class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo str_replace('_', ' ', $order['status']); ?>
                                    </div>
                                </div>
                                
                                <div class="order-body">
                                    <div class="order-items">
                                        <?php if(!empty($order['items'])): ?>
                                            <?php foreach($order['items'] as $item): ?>
                                                <div class="order-item">
                                                    <div class="item-name"><?php echo htmlspecialchars($item['item_name'] ?? 'Unknown Item'); ?></div>
                                                    <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                                                    <div class="item-price">$<?php echo number_format(($item['price'] ?? 0) * $item['quantity'], 2); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="order-item">
                                                <div class="item-name">No items found for this order</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="order-total">
                                        <span>Total Amount:</span>
                                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                    
                                    <?php if(!empty($order['instructions'])): ?>
                                        <div style="margin-top: 10px; color: #666; font-size: 14px;">
                                            <i class="fas fa-sticky-note"></i> 
                                            Instructions: <?php echo htmlspecialchars($order['instructions']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-actions">
                                    <?php if(!empty($order['restaurant_id'])): ?>
                                        <a href="restaurant_menu.php?id=<?php echo $order['restaurant_id']; ?>" class="action-btn btn-primary">
                                            <i class="fas fa-utensils"></i> Order Again
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if(count($orders) > 3): ?>
                        <div style="text-align: center; margin-top: 20px;">
                            <button class="action-btn btn-secondary" onclick="switchTab('orders')">
                                View All Orders
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Yet</h3>
                        <p>Start your first order and enjoy delicious food delivered to your door!</p>
                        <a href="restaurants.php" class="action-btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-utensils"></i> Order Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Orders Tab -->
            <div id="orders-tab" class="tab-content">
                <h2>Order History</h2>
                
                <?php if(count($orders) > 0): ?>
                    <div class="orders-grid">
                        <?php foreach($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-restaurant">
                                        <i class="fas fa-utensils"></i>
                                        <?php echo htmlspecialchars($order['restaurant_name'] ?? 'Unknown Restaurant'); ?>
                                    </div>
                                    <div class="order-meta">
                                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                                        <span><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
                                    </div>
                                    <div class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo str_replace('_', ' ', $order['status']); ?>
                                    </div>
                                </div>
                                
                                <div class="order-body">
                                    <div class="order-items">
                                        <?php if(!empty($order['items'])): ?>
                                            <?php foreach($order['items'] as $item): ?>
                                                <div class="order-item">
                                                    <div class="item-name"><?php echo htmlspecialchars($item['item_name'] ?? 'Unknown Item'); ?></div>
                                                    <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                                                    <div class="item-price">$<?php echo number_format(($item['price'] ?? 0) * $item['quantity'], 2); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="order-item">
                                                <div class="item-name">No items found for this order</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="order-total">
                                        <span>Total Amount:</span>
                                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                    
                                    <?php if(!empty($order['instructions'])): ?>
                                        <div style="margin-top: 10px; color: #666; font-size: 14px;">
                                            <i class="fas fa-sticky-note"></i> 
                                            Instructions: <?php echo htmlspecialchars($order['instructions']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-actions">
                                    <?php if(!empty($order['restaurant_id'])): ?>
                                        <a href="restaurant_menu.php?id=<?php echo $order['restaurant_id']; ?>" class="action-btn btn-primary">
                                            <i class="fas fa-utensils"></i> Order Again
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders yet.</p>
                        <a href="restaurants.php" class="action-btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-utensils"></i> Start Your First Order
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content">
                <h2>My Profile</h2>
                
                <div class="customer-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($customer_name); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($customer_data['phone'] ?? 'Not set'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Default Address</div>
                            <div class="info-value"><?php echo htmlspecialchars(!empty($_SESSION['user_address']) ? $_SESSION['user_address'] : 'Not set'); ?></div>
                        </div>
                    </div>
                    
                   
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Tab functionality
        function switchTab(tabName) {
            document.querySelectorAll('.dashboard-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(`${tabName}-tab`).classList.add('active');
        }

        document.querySelectorAll('.dashboard-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                switchTab(tab.dataset.tab);
            });
        });

        // Handle URL parameters for tabs
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            switchTab(tabParam);
        }

        console.log('👤 Customer Dashboard Loaded!');
        console.log('Orders count:', <?php echo count($orders); ?>);
    </script>
</body>
</html>