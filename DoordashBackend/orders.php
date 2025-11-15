<?php
require_once 'config.php';

// Redirect if not customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: index.php');
    exit;
}

// Get user orders with tax information
try {
    $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, r.image_url as restaurant_image 
                          FROM orders o 
                          JOIN restaurants r ON o.restaurant_id = r.id 
                          WHERE o.customer_id = ? 
                          ORDER BY o.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Add similar styles as restaurants.php */
        .wide-container {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .page-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .orders-container {
            padding: 30px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #FF3008;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .order-info h3 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .order-status {
            padding: 6px 12px;
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

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #333;
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
    </style>
</head>
<body>
    <div class="wide-container">
        <!-- Navigation Header -->
        <div class="nav-header">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-utensils"></i>DOORDASH
            </a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="restaurants.php" class="nav-link">Restaurants</a>
                <a href="orders.php" class="nav-link active">My Orders</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span style="color: #666; font-size: 14px;">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Sign In</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-header">
            <h1>My Orders</h1>
            <p>Track your food deliveries</p>
        </div>

        <div class="orders-container">
            <?php if(count($orders) > 0): ?>
                <?php foreach($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['restaurant_name']); ?></h3>
                                <p style="color: #666; margin: 0;">Placed on <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo str_replace('_', ' ', $order['status']); ?>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label">Delivery Address</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span>
                            </div>
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
                        
                        <?php if($order['instructions']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Instructions</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['instructions']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($order['status'] === 'delivered'): ?>
                            <div style="text-align: right;">
                                <button class="action-btn btn-success" style="padding: 8px 16px;">
                                    <i class="fas fa-utensils"></i> Reorder
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet. Start exploring restaurants!</p>
                    <a href="restaurants.php" class="action-btn btn-success" style="display: inline-block; padding: 12px 24px; text-decoration: none; margin-top: 15px;">
                        <i class="fas fa-utensils"></i> Browse Restaurants
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($_GET['logout'])): ?>
        <script>window.location.href = 'index.php?logout=1';</script>
    <?php endif; ?>
</body>
</html>