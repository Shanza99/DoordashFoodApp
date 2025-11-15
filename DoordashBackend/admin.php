<?php
require_once 'config.php';

// Redirect if not admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$users = getAllUsers($pdo);
$restaurants = getRestaurants($pdo, false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-container { 
            max-width: 1200px; 
            margin: 20px auto; 
            background: white; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .admin-header { 
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%); 
            color: white; 
            padding: 40px; 
            text-align: center; 
        }
        .admin-content { 
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
        .admin-tabs { 
            display: flex; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #f0f0f0; 
            flex-wrap: wrap;
        }
        .admin-tab { 
            padding: 15px 25px; 
            background: none; 
            border: none; 
            cursor: pointer; 
            font-weight: 600; 
            color: #666; 
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .admin-tab:hover {
            color: #FF3008;
            background: #fff5f5;
        }
        .admin-tab.active { 
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
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        .data-table th, .data-table td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid #f0f0f0; 
        }
        .data-table th { 
            background: #f8f9fa; 
            font-weight: 600; 
            color: #333; 
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
        .action-btn { 
            padding: 8px 12px; 
            margin: 2px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 12px; 
            transition: all 0.3s ease;
        }
        .btn-edit { 
            background: #17a2b8; 
            color: white; 
        }
        .btn-edit:hover {
            background: #138496;
            transform: translateY(-1px);
        }
        .btn-delete { 
            background: #dc3545; 
            color: white; 
        }
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .btn-save { 
            background: #28a745; 
            color: white; 
        }
        .btn-save:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .editable { 
            border: 1px dashed #007bff; 
            padding: 8px; 
            border-radius: 3px; 
            min-width: 100px;
            cursor: pointer;
        }
        .editable:focus {
            outline: none;
            border: 1px solid #007bff;
            background: #f8f9fa;
        }
        .user-type-select, .user-status-select, .restaurant-status-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        .add-new-btn {
            background: #FF3008;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .add-new-btn:hover {
            background: #e02a07;
            transform: translateY(-2px);
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-users-cog"></i> Admin Dashboard</h1>
            <p>Full Control Panel - Manage Users & Restaurants</p>
        </div>
        
        <div class="admin-content">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Main App
            </a>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <?php
                $total_users = count($users);
                $customers = array_filter($users, fn($u) => $u['user_type'] === 'customer');
                $restaurant_owners = array_filter($users, fn($u) => $u['user_type'] === 'restaurant');
                $delivery_partners = array_filter($users, fn($u) => $u['user_type'] === 'delivery');
                $admins = array_filter($users, fn($u) => $u['user_type'] === 'admin');
                $active_restaurants = array_filter($restaurants, fn($r) => $r['is_active']);
                ?>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($customers); ?></div>
                    <div class="stat-label">Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($restaurant_owners); ?></div>
                    <div class="stat-label">Restaurant Owners</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($delivery_partners); ?></div>
                    <div class="stat-label">Delivery Partners</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($restaurants); ?></div>
                    <div class="stat-label">Total Restaurants</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($active_restaurants); ?></div>
                    <div class="stat-label">Active Restaurants</div>
                </div>
            </div>
            
            <!-- Admin Tabs -->
            <div class="admin-tabs">
                <button class="admin-tab active" data-tab="users">Users Management</button>
                <button class="admin-tab" data-tab="restaurants">Restaurants Management</button>
                <button class="admin-tab" data-tab="analytics">Analytics</button>
                 <a href="admin_delivery_approval.php" class="action-btn btn-primary">
    <i class="fas fa-concierge-bell"></i> Dasher Approvals
</a>
            </div>
            
            <!-- Users Tab -->
            <div id="users-tab" class="tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2><i class="fas fa-users"></i> Users Management</h2>
                    <button class="add-new-btn" id="add-user-btn">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>
                
                <?php if(empty($users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No Users Found</h3>
                        <p>No users have been registered yet.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr data-user-id="<?php echo $user['id']; ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="full_name">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="email">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="phone">
                                        <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="user-type-select" data-user-id="<?php echo $user['id']; ?>">
                                        <option value="customer" <?php echo $user['user_type'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="restaurant" <?php echo $user['user_type'] === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                                        <option value="delivery" <?php echo $user['user_type'] === 'delivery' ? 'selected' : ''; ?>>Delivery</option>
                                        <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="user-status-select" data-user-id="<?php echo $user['id']; ?>">
                                        <option value="1" <?php echo $user['is_active'] ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo !$user['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <button class="action-btn btn-save save-user" data-user-id="<?php echo $user['id']; ?>">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                    <?php if($user['user_type'] !== 'admin' || $user['id'] == $_SESSION['user_id']): ?>
                                    <button class="action-btn btn-delete delete-user" 
                                            data-user-id="<?php echo $user['id']; ?>" 
                                            data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Restaurants Tab -->
            <div id="restaurants-tab" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2><i class="fas fa-utensils"></i> Restaurants Management</h2>
                    <button class="add-new-btn" id="add-restaurant-btn">
                        <i class="fas fa-plus"></i> Add New Restaurant
                    </button>
                </div>
                
                <?php if(empty($restaurants)): ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h3>No Restaurants Found</h3>
                        <p>No restaurants have been added yet.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Cuisine</th>
                                <th>Rating</th>
                                <th>Delivery Time</th>
                                <th>Delivery Fee</th>
                                <th>Featured</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($restaurants as $restaurant): ?>
                            <tr data-restaurant-id="<?php echo $restaurant['id']; ?>">
                                <td><?php echo $restaurant['id']; ?></td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="name">
                                        <?php echo htmlspecialchars($restaurant['name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="cuisine_type">
                                        <?php echo htmlspecialchars($restaurant['cuisine_type'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="rating">
                                        <?php echo $restaurant['rating'] ?? 'N/A'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="delivery_time">
                                        <?php echo htmlspecialchars($restaurant['delivery_time'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="editable" contenteditable="true" data-field="delivery_fee">
                                        $<?php echo $restaurant['delivery_fee'] ?? '0.00'; ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="restaurant-featured-select" data-restaurant-id="<?php echo $restaurant['id']; ?>">
                                        <option value="1" <?php echo $restaurant['featured'] ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo !$restaurant['featured'] ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="restaurant-status-select" data-restaurant-id="<?php echo $restaurant['id']; ?>">
                                        <option value="1" <?php echo $restaurant['is_active'] ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo !$restaurant['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="action-btn btn-save save-restaurant" data-restaurant-id="<?php echo $restaurant['id']; ?>">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                    <button class="action-btn btn-delete delete-restaurant" 
                                            data-restaurant-id="<?php echo $restaurant['id']; ?>" 
                                            data-restaurant-name="<?php echo htmlspecialchars($restaurant['name']); ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Analytics Tab -->
            <div id="analytics-tab" class="tab-content">
                <h2><i class="fas fa-chart-bar"></i> Analytics Dashboard</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($admins); ?></div>
                        <div class="stat-label">Administrators</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($restaurants, fn($r) => $r['featured'])); ?></div>
                        <div class="stat-label">Featured Restaurants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            $total_rating = 0;
                            $rated_restaurants = array_filter($restaurants, fn($r) => $r['rating'] > 0);
                            if(count($rated_restaurants) > 0) {
                                foreach($rated_restaurants as $r) {
                                    $total_rating += $r['rating'];
                                }
                                echo number_format($total_rating / count($rated_restaurants), 1);
                            } else {
                                echo '0.0';
                            }
                            ?>
                        </div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            $recent_users = array_filter($users, function($u) {
                                return strtotime($u['created_at']) >= strtotime('-7 days');
                            });
                            echo count($recent_users);
                            ?>
                        </div>
                        <div class="stat-label">New Users (7 days)</div>
                    </div>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 15px; margin-top: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3><i class="fas fa-chart-pie"></i> User Type Distribution</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 24px; font-weight: bold; color: #FF3008;"><?php echo count($customers); ?></div>
                            <div style="color: #666;">Customers</div>
                            <div style="font-size: 12px; color: #888; margin-top: 5px;">
                                <?php echo $total_users > 0 ? number_format((count($customers) / $total_users) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo count($restaurant_owners); ?></div>
                            <div style="color: #666;">Restaurant Owners</div>
                            <div style="font-size: 12px; color: #888; margin-top: 5px;">
                                <?php echo $total_users > 0 ? number_format((count($restaurant_owners) / $total_users) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 24px; font-weight: bold; color: #17a2b8;"><?php echo count($delivery_partners); ?></div>
                            <div style="color: #666;">Delivery Partners</div>
                            <div style="font-size: 12px; color: #888; margin-top: 5px;">
                                <?php echo $total_users > 0 ? number_format((count($delivery_partners) / $total_users) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 24px; font-weight: bold; color: #6f42c1;"><?php echo count($admins); ?></div>
                            <div style="color: #666;">Administrators</div>
                            <div style="font-size: 12px; color: #888; margin-top: 5px;">
                                <?php echo $total_users > 0 ? number_format((count($admins) / $total_users) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Sweet Alert Configuration
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });

        // Tab functionality
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(`${tab.dataset.tab}-tab`).classList.add('active');
            });
        });

        // User Management
        document.querySelectorAll('.save-user').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const row = this.closest('tr');
                
                const data = {
                    action: 'update_user',
                    user_id: userId,
                    field: 'multiple',
                    full_name: row.querySelector('[data-field="full_name"]').textContent,
                    email: row.querySelector('[data-field="email"]').textContent,
                    phone: row.querySelector('[data-field="phone"]').textContent,
                    user_type: row.querySelector('.user-type-select').value,
                    is_active: row.querySelector('.user-status-select').value
                };

                updateUser(data);
            });
        });

        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', function() {
                if(this.disabled) return;
                
                const userId = this.dataset.userId;
                const userName = this.dataset.userName;

                Swal.fire({
                    title: 'Delete User?',
                    html: `Are you sure you want to delete <strong>${userName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = {
                            action: 'delete_user',
                            user_id: userId
                        };
                        deleteUser(data);
                    }
                });
            });
        });

        // Restaurant Management
        document.querySelectorAll('.save-restaurant').forEach(btn => {
            btn.addEventListener('click', function() {
                const restaurantId = this.dataset.restaurantId;
                const row = this.closest('tr');
                
                const data = {
                    action: 'update_restaurant',
                    restaurant_id: restaurantId,
                    name: row.querySelector('[data-field="name"]').textContent,
                    cuisine_type: row.querySelector('[data-field="cuisine_type"]').textContent,
                    rating: parseFloat(row.querySelector('[data-field="rating"]').textContent) || 0,
                    delivery_time: row.querySelector('[data-field="delivery_time"]').textContent,
                    delivery_fee: parseFloat(row.querySelector('[data-field="delivery_fee"]').textContent.replace('$', '')) || 0,
                    featured: row.querySelector('.restaurant-featured-select').value,
                    is_active: row.querySelector('.restaurant-status-select').value
                };

                updateRestaurant(data);
            });
        });

        document.querySelectorAll('.delete-restaurant').forEach(btn => {
            btn.addEventListener('click', function() {
                const restaurantId = this.dataset.restaurantId;
                const restaurantName = this.dataset.restaurantName;

                Swal.fire({
                    title: 'Delete Restaurant?',
                    html: `Are you sure you want to delete <strong>${restaurantName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = {
                            action: 'delete_restaurant',
                            restaurant_id: restaurantId
                        };
                        deleteRestaurant(data);
                    }
                });
            });
        });

        // Add new user/restaurant buttons
        document.getElementById('add-user-btn')?.addEventListener('click', () => {
            Swal.fire({
                title: 'Add New User',
                html: `
                    <input id="swal-fullname" class="swal2-input" placeholder="Full Name" required>
                    <input id="swal-email" class="swal2-input" placeholder="Email" type="email" required>
                    <input id="swal-phone" class="swal2-input" placeholder="Phone">
                    <input id="swal-password" class="swal2-input" placeholder="Password" type="password" required>
                    <select id="swal-user-type" class="swal2-input">
                        <option value="customer">Customer</option>
                        <option value="restaurant">Restaurant Owner</option>
                        <option value="delivery">Delivery Partner</option>
                        <option value="admin">Administrator</option>
                    </select>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Add User',
                preConfirm: () => {
                    return {
                        full_name: document.getElementById('swal-fullname').value,
                        email: document.getElementById('swal-email').value,
                        phone: document.getElementById('swal-phone').value,
                        password: document.getElementById('swal-password').value,
                        user_type: document.getElementById('swal-user-type').value
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // This would need backend implementation for adding users
                    Toast.fire({
                        icon: 'info',
                        title: 'Add user functionality would be implemented here'
                    });
                }
            });
        });

        document.getElementById('add-restaurant-btn')?.addEventListener('click', () => {
            Swal.fire({
                title: 'Add New Restaurant',
                html: `
                    <input id="swal-name" class="swal2-input" placeholder="Restaurant Name" required>
                    <input id="swal-cuisine" class="swal2-input" placeholder="Cuisine Type" required>
                    <input id="swal-delivery-time" class="swal2-input" placeholder="Delivery Time" required>
                    <input id="swal-delivery-fee" class="swal2-input" placeholder="Delivery Fee" type="number" step="0.01" required>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Add Restaurant',
                preConfirm: () => {
                    return {
                        name: document.getElementById('swal-name').value,
                        cuisine_type: document.getElementById('swal-cuisine').value,
                        delivery_time: document.getElementById('swal-delivery-time').value,
                        delivery_fee: document.getElementById('swal-delivery-fee').value
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // This would need backend implementation for adding restaurants
                    Toast.fire({
                        icon: 'info',
                        title: 'Add restaurant functionality would be implemented here'
                    });
                }
            });
        });

        // API Functions
        function updateUser(data) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.fire({
                        icon: 'success',
                        title: result.message
                    });
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: result.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Update failed'
                });
            });
        }

        function deleteUser(data) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.fire({
                        icon: 'success',
                        title: result.message
                    });
                    // Remove the row from the table
                    document.querySelector(`[data-user-id="${data.user_id}"]`).remove();
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: result.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Delete failed'
                });
            });
        }

        function updateRestaurant(data) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.fire({
                        icon: 'success',
                        title: result.message
                    });
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: result.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Update failed'
                });
            });
        }

        function deleteRestaurant(data) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.fire({
                        icon: 'success',
                        title: result.message
                    });
                    // Remove the row from the table
                    document.querySelector(`[data-restaurant-id="${data.restaurant_id}"]`).remove();
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: result.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Delete failed'
                });
            });
        }

        // Initialize
        console.log('👑 Admin Panel Loaded Successfully!');
    </script>
</body>
</html>