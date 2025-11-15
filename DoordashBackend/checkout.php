<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: index.php');
    exit;
}

// Get cart data from localStorage via POST or from session
$cart_items = [];
$restaurant_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_items'])) {
    // Data coming from form submission
    $cart_items = json_decode($_POST['cart_items'], true) ?: [];
    $restaurant_data = json_decode($_POST['restaurant_data'], true) ?: [];
} else {
    // Try to get from session (for direct access)
    $cart_items = $_SESSION['cart'] ?? [];
    $restaurant_data = $_SESSION['restaurant_data'] ?? [];
}

// If no cart data, redirect to restaurants
if (empty($cart_items)) {
    echo "<script>
        alert('Your cart is empty');
        window.location.href = 'restaurants.php';
    </script>";
    exit;
}

// Get user address
$user_address = '';
try {
    $stmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_address = $user['address'] ?? '';
} catch(PDOException $e) {
    // Continue without address
}

// Tax configuration
$tax_rate = 0.08875; // 8.875%

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address']);
    $instructions = trim($_POST['instructions'] ?? '');
    
    if (empty($delivery_address)) {
        $error = 'Please enter a delivery address';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Calculate totals with tax
            $subtotal = 0;
            foreach($cart_items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            $tax_amount = $subtotal * $tax_rate;
            $delivery_fee = $restaurant_data['delivery_fee'] ?? 2.99;
            $total_amount = $subtotal + $tax_amount + $delivery_fee;
            
            // Create order with tax information
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, restaurant_id, total_amount, delivery_address, delivery_fee, instructions, status, subtotal, tax_amount, tax_rate) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $restaurant_data['id'],
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
            $stmt->execute([$delivery_address, $_SESSION['user_id']]);
            
            $pdo->commit();
            
            // Clear cart data
            unset($_SESSION['cart']);
            unset($_SESSION['restaurant_data']);
            
            $success = true;
            $order_id_display = $order_id;
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to place order: ' . $e->getMessage();
        }
    }
}
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
        .wide-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .page-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .checkout-container {
            padding: 30px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
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
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #FF3008;
            box-shadow: 0 0 0 3px rgba(255, 48, 8, 0.1);
        }

        .form-textarea {
            height: 80px;
            resize: vertical;
        }

        .order-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            padding: 15px 0;
            border-top: 2px solid #333;
        }

        .tax-note {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .place-order-btn {
            width: 100%;
            padding: 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .place-order-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .place-order-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .success-message {
            text-align: center;
            padding: 40px;
            color: #155724;
        }

        .success-message i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #28a745;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wide-container">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Checkout</h1>
            <p>Complete your order</p>
        </div>

        <div class="checkout-container">
            <?php if(isset($success) && $success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h2>Order Placed Successfully!</h2>
                    <p>Your order #<?php echo $order_id_display; ?> has been placed and is being prepared.</p>
                    <p>Estimated delivery: <?php echo $restaurant_data['delivery_time'] ?? '25-35 minutes'; ?></p>
                    <div style="margin-top: 30px;">
                        <a href="orders.php" class="place-order-btn" style="display: inline-block; width: auto; padding: 12px 30px; text-decoration: none;">
                            View My Orders
                        </a>
                        <a href="restaurants.php" class="place-order-btn" style="display: inline-block; width: auto; padding: 12px 30px; text-decoration: none; background: #6c757d; margin-left: 10px;">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php if(isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="checkout-grid">
                    <!-- Delivery Information -->
                    <div class="section">
                        <h3 class="section-title">Delivery Information</h3>
                        <form method="POST" id="checkoutForm">
                            <div class="form-group">
                                <label class="form-label" for="delivery_address">Delivery Address *</label>
                                <input type="text" id="delivery_address" name="delivery_address" class="form-input" 
                                       placeholder="Enter your full delivery address" required
                                       value="<?php echo htmlspecialchars($user_address); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="instructions">Delivery Instructions (Optional)</label>
                                <textarea id="instructions" name="instructions" class="form-textarea" 
                                          placeholder="Any special instructions for delivery..."><?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea>
                            </div>
                            
                            <input type="hidden" name="cart_items" id="cartItemsInput" value="<?php echo htmlspecialchars(json_encode($cart_items)); ?>">
                            <input type="hidden" name="restaurant_data" id="restaurantDataInput" value="<?php echo htmlspecialchars(json_encode($restaurant_data)); ?>">
                            <input type="hidden" name="place_order" value="1">
                        </form>
                    </div>

                    <!-- Order Summary -->
                    <div class="section">
                        <h3 class="section-title">Order Summary</h3>
                        <div id="orderSummary">
                            <!-- Order summary will be populated by JavaScript -->
                        </div>
                        
                        <button type="submit" form="checkoutForm" class="place-order-btn" id="placeOrderBtn">
                            <i class="fas fa-credit-card"></i> Place Order
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tax configuration
        const taxRate = 0.08875; // 8.875%

        // Populate order summary on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cartItems = <?php echo json_encode($cart_items); ?>;
            const restaurantData = <?php echo json_encode($restaurant_data); ?>;

            if (cartItems.length === 0 || !restaurantData.id) {
                alert('No items in cart');
                window.location.href = 'restaurants.php';
                return;
            }

            let subtotal = 0;
            let html = '';

            cartItems.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                html += `
                    <div class="order-summary-item">
                        <div>
                            <strong>${item.quantity}x</strong> ${item.name}
                        </div>
                        <div>$${itemTotal.toFixed(2)}</div>
                    </div>
                `;
            });

            const taxAmount = subtotal * taxRate;
            const deliveryFee = restaurantData.delivery_fee || 2.99;
            const total = subtotal + taxAmount + deliveryFee;

            html += `
                <div class="order-summary-item">
                    <div>Subtotal</div>
                    <div>$${subtotal.toFixed(2)}</div>
                </div>
                <div class="order-summary-item">
                    <div>Tax (8.875%)</div>
                    <div>$${taxAmount.toFixed(2)}</div>
                </div>
                <div class="order-summary-item">
                    <div>Delivery Fee</div>
                    <div>$${deliveryFee.toFixed(2)}</div>
                </div>
                <div class="order-total">
                    <div>Total</div>
                    <div>$${total.toFixed(2)}</div>
                </div>
                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                    <strong>Restaurant:</strong> ${restaurantData.name}
                </div>
            `;

            document.getElementById('orderSummary').innerHTML = html;
        });

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const address = document.getElementById('delivery_address').value.trim();
            if (!address) {
                e.preventDefault();
                alert('Please enter a delivery address');
                return;
            }

            // Disable button to prevent double submission
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            placeOrderBtn.disabled = true;
            placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';
        });

        // Check if page was loaded without cart data
        const cartItems = <?php echo json_encode($cart_items); ?>;
        if (cartItems.length === 0) {
            // Try to get from localStorage as fallback
            const savedCart = localStorage.getItem('currentCart');
            const savedRestaurant = localStorage.getItem('currentRestaurant');
            
            if (savedCart && savedRestaurant) {
                // Auto-populate the form with localStorage data
                document.getElementById('cartItemsInput').value = savedCart;
                document.getElementById('restaurantDataInput').value = savedRestaurant;
                
                // Reload the order summary
                const cart = JSON.parse(savedCart);
                const restaurant = JSON.parse(savedRestaurant);
                
                let subtotal = 0;
                let html = '';

                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    html += `
                        <div class="order-summary-item">
                            <div>
                                <strong>${item.quantity}x</strong> ${item.name}
                            </div>
                            <div>$${itemTotal.toFixed(2)}</div>
                        </div>
                    `;
                });

                const taxAmount = subtotal * taxRate;
                const deliveryFee = restaurant.delivery_fee || 2.99;
                const total = subtotal + taxAmount + deliveryFee;

                html += `
                    <div class="order-summary-item">
                        <div>Subtotal</div>
                        <div>$${subtotal.toFixed(2)}</div>
                    </div>
                    <div class="order-summary-item">
                        <div>Tax (8.875%)</div>
                        <div>$${taxAmount.toFixed(2)}</div>
                    </div>
                    <div class="order-summary-item">
                        <div>Delivery Fee</div>
                        <div>$${deliveryFee.toFixed(2)}</div>
                    </div>
                    <div class="order-total">
                        <div>Total</div>
                        <div>$${total.toFixed(2)}</div>
                    </div>
                    <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                        <strong>Restaurant:</strong> ${restaurant.name}
                    </div>
                `;

                document.getElementById('orderSummary').innerHTML = html;
            }
        }
    </script>
</body>
</html>