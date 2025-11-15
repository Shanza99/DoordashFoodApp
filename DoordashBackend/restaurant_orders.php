<?php
require_once 'config.php';

// Redirect if not restaurant owner
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant' || !isset($_SESSION['restaurant_id'])) {
    header('Location: merchant_login.php');
    exit;
}

$restaurant_id = $_SESSION['restaurant_id'];

// Get restaurant orders
try {
    // Get new orders (pending/confirmed)
    $stmt = $pdo->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone 
                          FROM orders o 
                          JOIN users u ON o.customer_id = u.id 
                          WHERE o.restaurant_id = ? AND o.status IN ('pending', 'confirmed', 'preparing') 
                          ORDER BY o.created_at DESC");
    $stmt->execute([$restaurant_id]);
    $new_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get ready orders
    $stmt = $pdo->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone 
                          FROM orders o 
                          JOIN users u ON o.customer_id = u.id 
                          WHERE o.restaurant_id = ? AND o.status = 'ready_for_pickup' 
                          ORDER BY o.created_at DESC");
    $stmt->execute([$restaurant_id]);
    $ready_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get completed orders
    $stmt = $pdo->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone 
                          FROM orders o 
                          JOIN users u ON o.customer_id = u.id 
                          WHERE o.restaurant_id = ? AND o.status IN ('delivered', 'cancelled') 
                          ORDER BY o.created_at DESC LIMIT 20");
    $stmt->execute([$restaurant_id]);
    $completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $new_orders = [];
    $ready_orders = [];
    $completed_orders = [];
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$new_status, $order_id, $restaurant_id]);
        
        $success = "Order #$order_id status updated to " . str_replace('_', ' ', $new_status);
        
        // Refresh page to show updated orders
        header("Location: restaurant_orders.php?success=" . urlencode($success));
        exit;
        
    } catch(PDOException $e) {
        $error = "Failed to update order status: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Orders - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .orders-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
        }

        .orders-tab {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .orders-tab:hover {
            color: #FF3008;
            background: #fff5f5;
        }

        .orders-tab.active {
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

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid #FF3008;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .order-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .order-id {
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-preparing { background: #d1ecf1; color: #0c5460; }
        .status-ready_for_pickup { background: #fff3cd; color: #856404; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        .status-on_the_way { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-content {
            padding: 20px;
        }

        .order-detail {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .order-detail-label {
            font-weight: 600;
            color: #333;
        }

        .order-detail-value {
            color: #666;
        }

        .order-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .breakdown-total {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 14px;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            color: #ddd;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .order-items {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .delivery-info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            border-left: 3px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-concierge-bell"></i> Restaurant Orders</h1>
            <p>Manage incoming orders and preparation</p>
        </div>
        
        <div class="dashboard-content">
            <a href="restaurant_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <?php if(isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Orders Tabs -->
            <div class="orders-tabs">
                <button class="orders-tab active" data-tab="new">New Orders (<?php echo count($new_orders); ?>)</button>
                <button class="orders-tab" data-tab="ready">Ready for Pickup (<?php echo count($ready_orders); ?>)</button>
                <button class="orders-tab" data-tab="completed">Order History</button>
            </div>

            <!-- New Orders Tab -->
            <div id="new-tab" class="tab-content active">
                <h2>New Orders</h2>
                <p>Accept and prepare incoming orders</p>
                
                <div class="orders-grid">
                    <?php if(count($new_orders) > 0): ?>
                        <?php foreach($new_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo str_replace('_', ' ', $order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-content">
                                    <div class="order-detail">
                                        <span class="order-detail-label">Customer:</span>
                                        <span class="order-detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                    </div>
                                    <div class="order-detail">
                                        <span class="order-detail-label">Phone:</span>
                                        <span class="order-detail-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                                    </div>
                                    <div class="order-detail">
                                        <span class="order-detail-label">Delivery Address:</span>
                                        <span class="order-detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                    </div>
                                    
                                    <!-- Order Breakdown with Tax -->
                                    <div class="order-breakdown">
                                        <div class="breakdown-item">
                                            <span>Subtotal:</span>
                                            <span>$<?php echo number_format($order['subtotal'] ?? ($order['total_amount'] - $order['delivery_fee'] - ($order['tax_amount'] ?? 0)), 2); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span>Tax (8.875%):</span>
                                            <span>$<?php echo number_format($order['tax_amount'] ?? 0, 2); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span>Delivery Fee:</span>
                                            <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                                        </div>
                                        <div class="breakdown-total">
                                            <span>Total Amount:</span>
                                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-detail">
                                        <span class="order-detail-label">Placed:</span>
                                        <span class="order-detail-value"><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    
                                    <?php if($order['instructions']): ?>
                                        <div class="order-detail">
                                            <span class="order-detail-label">Instructions:</span>
                                            <span class="order-detail-value"><?php echo htmlspecialchars($order['instructions']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Get order items -->
                                    <?php
                                    $stmt = $pdo->prepare("SELECT mi.name, oi.quantity, oi.price 
                                                          FROM order_items oi 
                                                          JOIN menu_items mi ON oi.menu_item_id = mi.id 
                                                          WHERE oi.order_id = ?");
                                    $stmt->execute([$order['id']]);
                                    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <div class="order-items">
                                        <strong>Order Items:</strong>
                                        <?php foreach($order_items as $item): ?>
                                            <div class="order-item">
                                                <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></span>
                                                <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <?php if($order['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" name="update_status" class="action-btn btn-success">
                                                    <i class="fas fa-check"></i> Accept Order
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="action-btn btn-secondary" onclick="return confirm('Are you sure you want to cancel this order?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php elseif($order['status'] === 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="preparing">
                                                <button type="submit" name="update_status" class="action-btn btn-info">
                                                    <i class="fas fa-utensils"></i> Start Preparing
                                                </button>
                                            </form>
                                        <?php elseif($order['status'] === 'preparing'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="ready_for_pickup">
                                                <button type="submit" name="update_status" class="action-btn btn-warning">
                                                    <i class="fas fa-check-double"></i> Mark Ready
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-concierge-bell"></i>
                            <h3>No New Orders</h3>
                            <p>You don't have any new orders at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ready for Pickup Tab -->
            <div id="ready-tab" class="tab-content">
                <h2>Ready for Pickup</h2>
                <p>Orders waiting for delivery pickup</p>
                
                <div class="orders-grid">
                    <?php if(count($ready_orders) > 0): ?>
                        <?php foreach($ready_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                    <span class="order-status status-ready_for_pickup">Ready for Pickup</span>
                                </div>
                                <div class="order-content">
                                    <div class="order-detail">
                                        <span class="order-detail-label">Customer:</span>
                                        <span class="order-detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                    </div>
                                    <div class="order-detail">
                                        <span class="order-detail-label">Delivery Address:</span>
                                        <span class="order-detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                    </div>
                                    
                                    <!-- Order Breakdown with Tax -->
                                    <div class="order-breakdown">
                                        <div class="breakdown-item">
                                            <span>Subtotal:</span>
                                            <span>$<?php echo number_format($order['subtotal'] ?? ($order['total_amount'] - $order['delivery_fee'] - ($order['tax_amount'] ?? 0)), 2); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span>Tax (8.875%):</span>
                                            <span>$<?php echo number_format($order['tax_amount'] ?? 0, 2); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span>Delivery Fee:</span>
                                            <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                                        </div>
                                        <div class="breakdown-total">
                                            <span>Total Amount:</span>
                                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-detail">
                                        <span class="order-detail-label">Ready Since:</span>
                                        <span class="order-detail-value"><?php echo date('M j, g:i A', strtotime($order['updated_at'])); ?></span>
                                    </div>
                                    
                                    <!-- Show delivery person info if assigned -->
                                    <?php if($order['delivery_person_id']): ?>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT u.full_name, u.phone 
                                                              FROM delivery_persons dp 
                                                              JOIN users u ON dp.user_id = u.id 
                                                              WHERE dp.id = ?");
                                        $stmt->execute([$order['delivery_person_id']]);
                                        $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <?php if($delivery_person): ?>
                                            <div class="delivery-info">
                                                <strong>Delivery Person:</strong> <?php echo htmlspecialchars($delivery_person['full_name']); ?><br>
                                                <strong>Phone:</strong> <?php echo htmlspecialchars($delivery_person['phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="order-actions">
                                            <span style="color: #666; font-style: italic;">
                                                <i class="fas fa-info-circle"></i> Waiting for delivery person to accept
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box"></i>
                            <h3>No Orders Ready</h3>
                            <p>No orders are currently ready for pickup.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completed Orders Tab -->
            <div id="completed-tab" class="tab-content">
                <h2>Order History</h2>
                <p>Recently completed and cancelled orders</p>
                
                <div class="orders-grid">
                    <?php if(count($completed_orders) > 0): ?>
                        <?php foreach($completed_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo str_replace('_', ' ', $order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-content">
                                    <div class="order-detail">
                                        <span class="order-detail-label">Customer:</span>
                                        <span class="order-detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                    </div>
                                    
                                    <!-- Order Breakdown with Tax -->
                                    <div class="order-breakdown">
                                        <div class="breakdown-item">
                                            <span>Subtotal:</span>
                                            <span>$<?php echo number_format($order['subtotal'] ?? ($order['total_amount'] - $order['delivery_fee'] - ($order['tax_amount'] ?? 0)), 2); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span>Tax (8.875%):</span>
                                            <span>$<?php echo number_format($order['tax_amount'] ?? 0, 2); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span>Delivery Fee:</span>
                                            <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                                        </div>
                                        <div class="breakdown-total">
                                            <span>Total Amount:</span>
                                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-detail">
                                        <span class="order-detail-label">Completed:</span>
                                        <span class="order-detail-value"><?php echo date('M j, g:i A', strtotime($order['updated_at'])); ?></span>
                                    </div>

                                    <!-- Show delivery person info for delivered orders -->
                                    <?php if($order['status'] === 'delivered' && $order['delivery_person_id']): ?>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT u.full_name 
                                                              FROM delivery_persons dp 
                                                              JOIN users u ON dp.user_id = u.id 
                                                              WHERE dp.id = ?");
                                        $stmt->execute([$order['delivery_person_id']]);
                                        $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <?php if($delivery_person): ?>
                                            <div class="order-detail">
                                                <span class="order-detail-label">Delivered by:</span>
                                                <span class="order-detail-value"><?php echo htmlspecialchars($delivery_person['full_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h3>No Order History</h3>
                            <p>You don't have any completed orders yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.orders-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.orders-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(`${tab.dataset.tab}-tab`).classList.add('active');
            });
        });

        // Auto-refresh every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);

        console.log('🏪 Restaurant Orders Loaded!');
    </script>
</body>
</html>