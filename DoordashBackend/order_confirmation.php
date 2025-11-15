<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, r.delivery_time 
                      FROM orders o 
                      JOIN restaurants r ON o.restaurant_id = r.id 
                      WHERE o.id = ? AND o.customer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, mi.name 
                      FROM order_items oi 
                      JOIN menu_items mi ON oi.menu_item_id = mi.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .confirmation-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px;
        }
        
        .confirmation-content {
            padding: 30px;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        
        .detail-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        
        .action-btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            background: #FF3008;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 48, 8, 0.3);
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-header">
            <i class="fas fa-check-circle" style="font-size: 64px; margin-bottom: 20px;"></i>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order</p>
        </div>
        
        <div class="confirmation-content">
            <h2>Order #<?php echo $order['id']; ?></h2>
            <p>Your order from <strong><?php echo htmlspecialchars($order['restaurant_name']); ?></strong> has been placed successfully.</p>
            
            <div class="order-details">
                <h3>Order Details</h3>
                <?php foreach($order_items as $item): ?>
                    <div class="detail-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="detail-item">
                    <span>Delivery Fee</span>
                    <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                </div>
                <div class="detail-item" style="font-weight: bold; border-top: 1px solid #ddd; padding-top: 10px;">
                    <span>Total</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <p><strong>Estimated Delivery:</strong> <?php echo $order['delivery_time']; ?></p>
            <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
            
            <div style="margin-top: 30px;">
                <a href="order_tracking.php?id=<?php echo $order['id']; ?>" class="action-btn">
                    <i class="fas fa-map-marker-alt"></i> Track Order
                </a>
                <a href="restaurants.php" class="action-btn" style="background: #6c757d;">
                    <i class="fas fa-utensils"></i> Order Again
                </a>
            </div>
        </div>
    </div>
</body>
</html>