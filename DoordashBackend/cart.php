<?php
include 'config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$total = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!empty($cart)) {
        // Get first item to determine restaurant
        $first_item = reset($cart);
        $restaurant_id = $first_item['restaurant_id'];
        $delivery_address = $_SESSION['delivery_address'];
        
        // Calculate total
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, restaurant_id, total_amount, delivery_address, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([get_user_id(), $restaurant_id, $total, $delivery_address]);
        $order_id = $pdo->lastInsertId();

        // Add order items
        foreach ($cart as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        // Clear cart
        $_SESSION['cart'] = [];
        
        header('Location: order_confirmation.php?id=' . $order_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - DoorDash</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1><a href="restaurants.php" style="color: #ff3000; text-decoration: none;">DoorDash</a></h1>
            </div>
            <div class="header-actions">
                <a href="restaurants.php" class="nav-link">Continue Shopping</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </header>

    <section class="cart-section">
        <div class="container">
            <h2>Your Cart</h2>
            
            <?php if(empty($cart)): ?>
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <a href="restaurants.php" class="btn-primary">Browse Restaurants</a>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items">
                        <?php foreach($cart as $item): ?>
                            <?php 
                            $item_total = $item['price'] * $item['quantity'];
                            $total += $item_total;
                            ?>
                            <div class="cart-item">
                                <div class="item-info">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="item-price">A$<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="item-quantity">
                                    <span>Qty: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-total">
                                    A$<?php echo number_format($item_total, 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>A$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee:</span>
                            <span>A$2.99</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>A$<?php echo number_format($total + 2.99, 2); ?></span>
                        </div>
                        
                       // In cart.php, replace the existing form with:
<form method="POST" action="place_order.php">
    <!-- Keep existing cart display -->
    <button type="submit" name="checkout" class="btn-primary btn-checkout">
        Proceed to Checkout - A$<?php echo number_format($total + 2.99, 2); ?>
    </button>
</form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>