<?php
require_once 'config.php';

$restaurant_id = $_GET['id'] ?? 0;

// Get restaurant details
try {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        header('Location: restaurants.php');
        exit;
    }
    
    // Get menu items grouped by category
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = TRUE ORDER BY category, name");
    $stmt->execute([$restaurant_id]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by category
    $categories = [];
    foreach($menu_items as $item) {
        $categories[$item['category']][] = $item;
    }
    
} catch(PDOException $e) {
    header('Location: restaurants.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wide-container {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .restaurant-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 40px;
            position: relative;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }

        .restaurant-info {
            text-align: center;
        }

        .restaurant-name {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .restaurant-meta {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .menu-container {
            padding: 30px;
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            color: #333;
        }

        .menu-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .menu-item-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .menu-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 48, 8, 0.2);
            border-color: #FF3008;
        }

        .menu-item-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }

        .menu-item-description {
            color: #666;
            margin-bottom: 12px;
            line-height: 1.4;
            font-size: 14px;
        }

        .menu-item-price {
            font-size: 20px;
            font-weight: 700;
            color: #FF3008;
            margin-bottom: 15px;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: #FF3008;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: #e02a07;
            transform: translateY(-2px);
        }

        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .cart-sidebar.open {
            right: 0;
        }

        .cart-header {
            padding: 20px;
            background: #FF3008;
            color: white;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .cart-title {
            font-size: 20px;
            font-weight: 700;
        }

        .close-cart {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .cart-items {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .cart-item-price {
            color: #FF3008;
            font-weight: 600;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: #f0f0f0;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: 600;
        }

        .cart-footer {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .cart-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #FF3008;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(255, 48, 8, 0.3);
            z-index: 999;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
            
            .menu-items-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wide-container">
        <!-- Restaurant Header -->
        <div class="restaurant-header">
            <a href="restaurants.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            
            <div class="restaurant-info">
                <h1 class="restaurant-name"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                <div class="restaurant-meta">
                    <div class="meta-item">
                        <i class="fas fa-star"></i>
                        <?php echo $restaurant['rating']; ?> (<?php echo $restaurant['review_count']; ?> reviews)
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <?php echo $restaurant['delivery_time']; ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-motorcycle"></i>
                        $<?php echo $restaurant['delivery_fee']; ?> delivery
                    </div>
                </div>
                <p><?php echo htmlspecialchars($restaurant['description']); ?></p>
            </div>
        </div>

        <!-- Menu Container -->
        <div class="menu-container">
            <?php if(count($categories) > 0): ?>
                <?php foreach($categories as $category => $items): ?>
                    <div class="category-section">
                        <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                        <div class="menu-items-grid">
                            <?php foreach($items as $item): ?>
                                <div class="menu-item-card">
                                    <div class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="menu-item-description">
                                        <?php echo htmlspecialchars($item['description'] ?: 'Delicious menu item'); ?>
                                    </div>
                                    <div class="menu-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $restaurant['id']; ?>, <?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)">
                                        Add to Cart
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-utensils" style="font-size: 64px; margin-bottom: 20px; display: block; color: #ddd;"></i>
                    <h3>No Menu Items Available</h3>
                    <p>This restaurant hasn't added any menu items yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cart Toggle Button -->
    <div class="cart-toggle" onclick="toggleCart()">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cartCount">0</span>
    </div>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <div class="cart-title">Your Order</div>
            <button class="close-cart" onclick="toggleCart()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="cart-items" id="cartItems">
            <!-- Cart items will be added here dynamically -->
            <div style="text-align: center; padding: 40px 20px; color: #666;">
                <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px; display: block; color: #ddd;"></i>
                <p>Your cart is empty</p>
            </div>
        </div>
        
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cartTotal">$0.00</span>
            </div>
            <button class="checkout-btn" onclick="checkout()">
                <i class="fas fa-credit-card"></i> Checkout
            </button>
        </div>
    </div>

    <script>
        let cart = [];
        let currentRestaurantId = <?php echo $restaurant['id']; ?>;
        const taxRate = 0.08875; // 8.875%

        function addToCart(restaurantId, itemId, itemName, price) {
            <?php if(!isset($_SESSION['user_id'])): ?>
                alert('Please login to add items to cart');
                window.location.href = 'index.php';
                return;
            <?php endif; ?>

            // Check if item is from same restaurant
            if (cart.length > 0 && cart[0].restaurantId !== restaurantId) {
                if (!confirm('You have items from another restaurant in your cart. Would you like to clear the cart and add this item?')) {
                    return;
                }
                cart = [];
            }

            // Add item to cart
            const existingItem = cart.find(item => item.id === itemId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: price,
                    quantity: 1,
                    restaurantId: restaurantId,
                    restaurantName: '<?php echo addslashes($restaurant['name']); ?>'
                });
            }

            updateCartDisplay();
            showNotification('Added ' + itemName + ' to cart!');
        }

        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const cartCount = document.getElementById('cartCount');
            const cartTotal = document.getElementById('cartTotal');

            // Update count
            cartCount.textContent = cart.reduce((total, item) => total + item.quantity, 0);

            // Update items list
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div style="text-align: center; padding: 40px 20px; color: #666;">
                        <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px; display: block; color: #ddd;"></i>
                        <p>Your cart is empty</p>
                    </div>
                `;
                cartTotal.textContent = '$0.00';
                return;
            }

            let subtotal = 0;
            cartItems.innerHTML = cart.map(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                return `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                        </div>
                        <div class="cart-item-quantity">
                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                            <span>${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                        </div>
                    </div>
                `;
            }).join('');

            // Add tax and delivery fee
            const taxAmount = subtotal * taxRate;
            const deliveryFee = <?php echo $restaurant['delivery_fee']; ?>;
            const total = subtotal + taxAmount + deliveryFee;

            // Add summary to cart
            cartItems.innerHTML += `
                <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #f0f0f0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Subtotal:</span>
                        <span>$${subtotal.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Tax (8.875%):</span>
                        <span>$${taxAmount.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Delivery Fee:</span>
                        <span>$${deliveryFee.toFixed(2)}</span>
                    </div>
                </div>
            `;

            cartTotal.textContent = `$${total.toFixed(2)}`;
        }

        function updateQuantity(itemId, change) {
            const item = cart.find(item => item.id === itemId);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id !== itemId);
                }
                updateCartDisplay();
            }
        }

        function toggleCart() {
            document.getElementById('cartSidebar').classList.toggle('open');
        }

        function showNotification(message) {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 1001;
                font-weight: 600;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function checkout() {
            if (cart.length === 0) {
                alert('Your cart is empty');
                return;
            }

            <?php if(!isset($_SESSION['user_id'])): ?>
                alert('Please login to checkout');
                window.location.href = 'index.php';
                return;
            <?php endif; ?>

            // Create a form and submit it to pass data to checkout.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'checkout.php';
            
            // Add cart data
            const cartInput = document.createElement('input');
            cartInput.type = 'hidden';
            cartInput.name = 'cart_items';
            cartInput.value = JSON.stringify(cart);
            form.appendChild(cartInput);
            
            // Add restaurant data
            const restaurantInput = document.createElement('input');
            restaurantInput.type = 'hidden';
            restaurantInput.name = 'restaurant_data';
            restaurantInput.value = JSON.stringify({
                id: <?php echo $restaurant['id']; ?>,
                name: '<?php echo addslashes($restaurant['name']); ?>',
                delivery_fee: <?php echo $restaurant['delivery_fee']; ?>,
                delivery_time: '<?php echo $restaurant['delivery_time']; ?>'
            });
            form.appendChild(restaurantInput);
            
            // Add to document and submit
            document.body.appendChild(form);
            form.submit();
        }

        // Load cart from localStorage on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedCart = localStorage.getItem('currentCart');
            const savedRestaurant = localStorage.getItem('currentRestaurant');
            
            if (savedCart && savedRestaurant) {
                const restaurant = JSON.parse(savedRestaurant);
                if (restaurant.id === currentRestaurantId) {
                    cart = JSON.parse(savedCart);
                    updateCartDisplay();
                }
            }
        });
    </script>
</body>
</html>