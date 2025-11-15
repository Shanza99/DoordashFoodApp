<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, u.full_name as customer_name, 
                      dp.full_name as delivery_person_name, dp.phone as delivery_person_phone 
                      FROM orders o 
                      JOIN restaurants r ON o.restaurant_id = r.id 
                      JOIN users u ON o.customer_id = u.id 
                      LEFT JOIN users dp ON o.delivery_person_id = dp.id 
                      WHERE o.id = ? AND (o.customer_id = ? OR ? = 'admin')");
$stmt->execute([$order_id, $_SESSION['user_id'], $_SESSION['user_type']]);
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

// Define status steps
$status_steps = [
    'pending' => 1,
    'confirmed' => 2,
    'preparing' => 3,
    'ready_for_pickup' => 4,
    'picked_up' => 5,
    'on_the_way' => 6,
    'delivered' => 7
];

$current_step = $status_steps[$order['status']] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .tracking-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .tracking-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .tracking-content {
            padding: 30px;
        }
        
        .progress-bar {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0;
        }
        
        .progress-bar::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: <?php echo (($current_step - 1) / 6) * 100; ?>%;
            height: 4px;
            background: #28a745;
            z-index: 2;
            transition: width 0.5s ease;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 3;
        }
        
        .step-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 14px;
        }
        
        .step.active .step-icon {
            background: #28a745;
            color: white;
        }
        
        .step.completed .step-icon {
            background: #28a745;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .step.active .step-label {
            color: #28a745;
            font-weight: 600;
        }
        
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <div class="tracking-header">
            <h1><i class="fas fa-map-marker-alt"></i> Track Your Order</h1>
            <p>Order #<?php echo $order['id']; ?></p>
        </div>
        
        <div class="tracking-content">
            <div class="progress-bar">
                <?php
                $steps = [
                    ['icon' => 'fa-clock', 'label' => 'Order Placed'],
                    ['icon' => 'fa-check', 'label' => 'Confirmed'],
                    ['icon' => 'fa-utensils', 'label' => 'Preparing'],
                    ['icon' => 'fa-box', 'label' => 'Ready'],
                    ['icon' => 'fa-motorcycle', 'label' => 'Picked Up'],
                    ['icon' => 'fa-road', 'label' => 'On the Way'],
                    ['icon' => 'fa-home', 'label' => 'Delivered']
                ];
                
                foreach ($steps as $index => $step):
                    $step_number = $index + 1;
                    $step_class = '';
                    if ($step_number < $current_step) {
                        $step_class = 'completed';
                    } elseif ($step_number == $current_step) {
                        $step_class = 'active';
                    }
                ?>
                    <div class="step <?php echo $step_class; ?>">
                        <div class="step-icon">
                            <i class="fas <?php echo $step['icon']; ?>"></i>
                        </div>
                        <div class="step-label"><?php echo $step['label']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-info">
                <h3>Order Details</h3>
                <div class="info-grid">
                    <div>
                        <strong>Restaurant:</strong> <?php echo htmlspecialchars($order['restaurant_name']); ?><br>
                        <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                        <strong>Status:</strong> 
                        <span style="text-transform: capitalize;"><?php echo str_replace('_', ' ', $order['status']); ?></span>
                    </div>
                    <div>
                        <strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?><br>
                        <strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?>
                    </div>
                </div>
                
                <?php if($order['delivery_person_name']): ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                        <strong>Delivery Person:</strong> <?php echo htmlspecialchars($order['delivery_person_name']); ?>
                        <?php if($order['delivery_person_phone']): ?>
                            | <strong>Phone:</strong> <?php echo $order['delivery_person_phone']; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="action-btn" style="display: inline-block; padding: 12px 24px; background: #FF3008; color: white; text-decoration: none; border-radius: 8px;">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>