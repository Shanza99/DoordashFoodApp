<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: login.php');
    exit;
}

// Get cart from session
$cart = $_SESSION['cart'] ?? [];
$total = 0;

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Get first item to determine restaurant
$first_item = reset($cart);
$restaurant_id = $first_item['restaurant_id'];

// Calculate total
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get restaurant delivery fee
$stmt = $pdo->prepare("SELECT delivery_fee FROM restaurants WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
$delivery_fee = $restaurant['delivery_fee'] ?? 2.99;

$final_total = $total + $delivery_fee;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = $_POST['delivery_address'];
    $instructions = $_POST['instructions'] ?? '';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, restaurant_id, total_amount, delivery_address, delivery_fee, instructions, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $restaurant_id, $final_total, $delivery_address, $delivery_fee, $instructions]);
        $order_id = $pdo->lastInsertId();
        
        // Add order items
        foreach ($cart as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to confirmation
        header('Location: order_confirmation.php?id=' . $order_id);
        exit;
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to place order: " . $e->getMessage();
    }
}

// Get user's saved address
$stmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$saved_address = $user['address'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .checkout-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .checkout-content {
            padding: 30px;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 48, 8, 0.3);
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1><i class="fas fa-shopping-bag"></i> Checkout</h1>
            <p>Complete your order</p>
        </div>
        
        <div class="checkout-content">
            <?php if(isset($error)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach($cart as $item): ?>
                    <div class="order-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="order-item">
                    <span>Delivery Fee</span>
                    <span>$<?php echo number_format($delivery_fee, 2); ?></span>
                </div>
                <div class="order-item" style="font-weight: bold; border-bottom: none;">
                    <span>Total</span>
                    <span>$<?php echo number_format($final_total, 2); ?></span>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Delivery Address</label>
                    <textarea name="delivery_address" class="form-input" rows="3" required placeholder="Enter your delivery address"><?php echo htmlspecialchars($saved_address); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Delivery Instructions (Optional)</label>
                    <textarea name="instructions" class="form-textarea" rows="2" placeholder="Any special delivery instructions?"></textarea>
                </div>
                
                <button type="submit" name="place_order" class="submit-btn">
                    <i class="fas fa-check"></i> Place Order - $<?php echo number_format($final_total, 2); ?>
                </button>
            </form>
        </div>
    </div>
</body>
</html>