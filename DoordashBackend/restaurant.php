<?php
include 'config.php';

if (!isset($_GET['id'])) {
    header('Location: restaurants.php');
    exit();
}

$restaurant_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header('Location: restaurants.php');
    exit();
}

// Get menu items
$menu_stmt = $pdo->prepare("
    SELECT mi.*, c.name as category_name 
    FROM menu_items mi 
    LEFT JOIN categories c ON mi.category_id = c.id 
    WHERE mi.restaurant_id = ?
    ORDER BY mi.featured DESC, mi.id ASC
");
$menu_stmt->execute([$restaurant_id]);
$menu_items = $menu_stmt->fetchAll();

// Group menu items by category
$categories = [];
foreach ($menu_items as $item) {
    $categories[$item['category_name']][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> - DoorDash</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1><a href="restaurants.php" style="color: #ff3000; text-decoration: none;">DoorDash</a></h1>
            </div>
            <div class="header-actions">
                <?php if(is_logged_in()): ?>
                    <a href="cart.php" class="nav-link">Cart</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Sign In</a>
                    <a href="register.php" class="nav-link btn-signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Restaurant Header -->
    <section class="restaurant-header">
        <div class="container">
            <div class="restaurant-info">
                <h1><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                <div class="restaurant-meta-large">
                    <span class="rating">⭐ <?php echo $restaurant['rating']; ?> (<?php echo $restaurant['review_count']; ?>+)</span>
                    <span class="dashpass">10 DashPass</span>
                    <span class="cuisine"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></span>
                    <span class="distance"><?php echo $restaurant['distance']; ?></span>
                </div>
                <p class="delivery-info">Service fees apply</p>
            </div>
        </div>
    </section>

    <!-- Deals Banner -->
    <section class="deals-banner">
        <div class="container">
            <div class="deal-content">
                <h3>Buy 1 get 1 free</h3>
                <p>Terms apply</p>
            </div>
        </div>
    </section>

    <!-- Menu Navigation -->
    <section class="menu-nav">
        <div class="container">
            <nav class="menu-categories">
                <a href="#featured" class="menu-category active">Featured Items</a>
                <a href="#reviews" class="menu-category">Reviews</a>
                <a href="#most-ordered" class="menu-category">Most Ordered</a>
                <?php foreach(array_keys($categories) as $category): ?>
                    <a href="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>" class="menu-category">
                        <?php echo htmlspecialchars($category); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>

    <!-- Menu Items -->
    <section class="menu-items">
        <div class="container">
            <!-- Featured Items -->
            <div id="featured" class="menu-section">
                <h2>Featured Items</h2>
                <div class="menu-grid">
                    <?php foreach($menu_items as $item): if($item['featured']): ?>
                        <div class="menu-item-card">
                            <div class="menu-item-image">
                                <img src="<?php echo $item['image_url'] ?: 'https://via.placeholder.com/200x150'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="menu-item-info">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <?php if($item['deal_text']): ?>
                                    <p class="deal-text"><?php echo htmlspecialchars($item['deal_text']); ?></p>
                                <?php endif; ?>
                                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="item-price">A$<?php echo number_format($item['price'], 2); ?></div>
                                <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                                    <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">
                                    <button type="submit" class="btn-add-cart">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>

            <!-- Other Categories -->
            <?php foreach($categories as $category_name => $items): ?>
                <div id="<?php echo strtolower(str_replace(' ', '-', $category_name)); ?>" class="menu-section">
                    <h2><?php echo htmlspecialchars($category_name); ?></h2>
                    <div class="menu-grid">
                        <?php foreach($items as $item): ?>
                            <div class="menu-item-card">
                                <div class="menu-item-image">
                                    <img src="<?php echo $item['image_url'] ?: 'https://via.placeholder.com/200x150'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="menu-item-info">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <?php if($item['deal_text']): ?>
                                        <p class="deal-text"><?php echo htmlspecialchars($item['deal_text']); ?></p>
                                    <?php endif; ?>
                                    <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="item-price">A$<?php echo number_format($item['price'], 2); ?></div>
                                    <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                                        <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">
                                        <button type="submit" class="btn-add-cart">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <script>
        // Menu navigation
        document.querySelectorAll('.menu-category').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.menu-category').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const target = document.querySelector(this.getAttribute('href'));
                target.scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>