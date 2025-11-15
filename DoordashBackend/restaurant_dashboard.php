<?php
require_once 'config.php';

// Redirect if not restaurant owner
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant') {
    header('Location: merchant_register.php');
    exit;
}

// Get restaurant data
$restaurant = null;
if (isset($_SESSION['restaurant_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$_SESSION['restaurant_id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get menu items count
$menu_count = 0;
if ($restaurant) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE restaurant_id = ?");
    $stmt->execute([$restaurant['id']]);
    $menu_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - DoorDash</title>
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

        .restaurant-info {
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

        .menu-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .menu-item-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .menu-item-card:hover {
            transform: translateY(-5px);
        }

        .menu-item-image {
            height: 200px;
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .menu-item-content {
            padding: 20px;
        }

        .menu-item-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .menu-item-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .menu-item-price {
            font-size: 20px;
            font-weight: 700;
            color: #FF3008;
            margin-bottom: 15px;
        }

        .menu-item-actions {
            display: flex;
            gap: 10px;
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-store"></i> Restaurant Dashboard</h1>
            <p>Manage your restaurant and menu items</p>
        </div>
        
        <div class="dashboard-content">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $menu_count; ?></div>
                    <div class="stat-label">Menu Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $restaurant ? $restaurant['rating'] : '0.0'; ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $restaurant ? $restaurant['review_count'] : '0'; ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo $restaurant ? $restaurant['delivery_fee'] : '0.00'; ?></div>
                    <div class="stat-label">Delivery Fee</div>
                </div>
            </div>
            
            <!-- Dashboard Tabs -->
            <div class="dashboard-tabs">
                <button class="dashboard-tab active" data-tab="overview">Overview</button>
                <button class="dashboard-tab" data-tab="menu">Menu Management</button>
                <button class="dashboard-tab" data-tab="settings">Restaurant Settings</button>
                <a href="restaurant_orders.php" class="action-btn btn-primary">
    <i class="fas fa-concierge-bell"></i> Manage Orders
</a>
            </div>
            
            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content active">
                <h2>Restaurant Overview</h2>
                
                <?php if($restaurant): ?>
                <div class="restaurant-info">
                    <h3><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Cuisine Type</div>
                            <div class="info-value"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Delivery Time</div>
                            <div class="info-value"><?php echo htmlspecialchars($restaurant['delivery_time']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Delivery Fee</div>
                            <div class="info-value">$<?php echo $restaurant['delivery_fee']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><?php echo $restaurant['is_active'] ? 'Active' : 'Inactive'; ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Description</div>
                        <div class="info-value"><?php echo htmlspecialchars($restaurant['description'] ?: 'No description provided'); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="menu_management.php" class="action-btn btn-primary">
                        <i class="fas fa-utensils"></i> Manage Menu Items
                    </a>
                    <a href="restaurant_edit.php" class="action-btn btn-secondary">
    <i class="fas fa-edit"></i> Edit Restaurant Info
</a>
                </div>
            </div>
            
            <!-- Menu Management Tab -->
            <div id="menu-tab" class="tab-content">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h2>Menu Items</h2>
                    <a href="menu_management.php" class="action-btn btn-primary">
                        <i class="fas fa-plus"></i> Add Menu Item
                    </a>
                </div>
                
                <!-- Menu items will be loaded here via JavaScript -->
                <div id="menu-items-container">
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h3>No Menu Items</h3>
                        <p>Get started by adding your first menu item.</p>
                        <a href="menu_management.php" class="action-btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Add First Item
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Settings Tab -->
            <div id="settings-tab" class="tab-content">
                <h2>Restaurant Settings</h2>
                <div class="restaurant-info">
                    <p>Update your restaurant information and settings here.</p>
                    <a href="restaurant_edit.php" class="action-btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Restaurant Information
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Tab functionality
        document.querySelectorAll('.dashboard-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.dashboard-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(`${tab.dataset.tab}-tab`).classList.add('active');
            });
        });

        // Load menu items for menu tab
        document.querySelector('[data-tab="menu"]').addEventListener('click', loadMenuItems);

        function loadMenuItems() {
            fetch('get_menu_items.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('menu-items-container');
                    
                    if (data.success && data.menu_items.length > 0) {
                        container.innerHTML = `
                            <div class="menu-items-grid">
                                ${data.menu_items.map(item => `
                                    <div class="menu-item-card">
                                        <div class="menu-item-image">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                        <div class="menu-item-content">
                                            <div class="menu-item-name">${item.name}</div>
                                            <div class="menu-item-description">${item.description || 'No description'}</div>
                                            <div class="menu-item-price">$${item.price}</div>
                                            <div class="menu-item-actions">
                                                <button class="action-btn btn-primary" onclick="editMenuItem(${item.id})">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="action-btn btn-secondary" onclick="deleteMenuItem(${item.id})">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-utensils"></i>
                                <h3>No Menu Items</h3>
                                <p>Get started by adding your first menu item.</p>
                                <a href="menu_management.php" class="action-btn btn-primary" style="margin-top: 15px;">
                                    <i class="fas fa-plus"></i> Add First Item
                                </a>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading menu items:', error);
                });
        }

        function editMenuItem(itemId) {
            window.location.href = `menu_management.php?edit=${itemId}`;
        }

        function deleteMenuItem(itemId) {
            if (confirm('Are you sure you want to delete this menu item?')) {
                // Implement delete functionality
                console.log('Delete item:', itemId);
            }
        }

        console.log('🏪 Restaurant Dashboard Loaded!');
    </script>
</body>
</html>