<?php
require_once 'config.php';

// Redirect if not delivery person
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'delivery') {
    header('Location: delivery_login.php');
    exit;
}

// Check if delivery account is approved
if (!isset($_SESSION['delivery_approved']) || !$_SESSION['delivery_approved']) {
    $pending_approval = true;
} else {
    $pending_approval = false;
}

// Get delivery person data
$delivery_person = null;
$total_earnings = 0;
$total_deliveries = 0;
$current_orders = [];
$completed_orders = [];

if (isset($_SESSION['user_id'])) {
    try {
        // Get delivery person details
        $stmt = $pdo->prepare("SELECT dp.*, u.full_name, u.email, u.phone 
                              FROM delivery_persons dp 
                              JOIN users u ON dp.user_id = u.id 
                              WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($delivery_person) {
            $total_earnings = $delivery_person['earnings'];
            $total_deliveries = $delivery_person['total_deliveries'];
            
            // Get current assigned orders
            $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, u.full_name as customer_name 
                                  FROM orders o 
                                  JOIN restaurants r ON o.restaurant_id = r.id 
                                  JOIN users u ON o.customer_id = u.id 
                                  WHERE o.delivery_person_id = ? AND o.status IN ('picked_up', 'on_the_way') 
                                  ORDER BY o.created_at DESC");
            $stmt->execute([$delivery_person['id']]);
            $current_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get completed orders history
            $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, u.full_name as customer_name 
                                  FROM orders o 
                                  JOIN restaurants r ON o.restaurant_id = r.id 
                                  JOIN users u ON o.customer_id = u.id 
                                  WHERE o.delivery_person_id = ? AND o.status IN ('delivered', 'cancelled') 
                                  ORDER BY o.updated_at DESC LIMIT 20");
            $stmt->execute([$delivery_person['id']]);
            $completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasher Dashboard - DoorDash</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            border-left: 4px solid #28a745;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
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
            color: #28a745;
            background: #f8fff9;
        }

        .dashboard-tab.active {
            color: #28a745;
            border-bottom-color: #28a745;
            background: #f8fff9;
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

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
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

        .delivery-info {
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

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid #28a745;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .order-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .order-id {
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        .status-on_the_way { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-content {
            padding: 20px;
        }

        .order-detail {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .order-detail-label {
            font-weight: 600;
            color: #333;
        }

        .order-detail-value {
            color: #666;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
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

        .availability-toggle {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #28a745;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .pending-approval {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .pending-approval i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            color: #ffc107;
        }

        /* Order History Styles */
        .history-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
            transition: transform 0.3s ease;
        }

        .history-card:hover {
            transform: translateY(-3px);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .history-restaurant {
            font-weight: 700;
            color: #333;
            font-size: 16px;
        }

        .history-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .history-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .history-amount {
            font-weight: 700;
            color: #28a745;
            text-align: right;
            font-size: 18px;
        }

        .history-address {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .history-date {
            color: #999;
            font-size: 12px;
            text-align: right;
        }

        .earnings-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .breakdown-total {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            padding-top: 8px;
            border-top: 2px solid #dee2e6;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-motorcycle"></i> Dasher Dashboard</h1>
            <p>Manage your deliveries and earnings</p>
        </div>
        
        <div class="dashboard-content">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <?php if($pending_approval): ?>
                <div class="pending-approval">
                    <i class="fas fa-clock"></i>
                    <h3>Account Pending Approval</h3>
                    <p>Your delivery account is currently under review. You'll be able to start accepting deliveries once your account is approved by an administrator.</p>
                    <p>Please check back later or contact support if you have questions.</p>
                    <div style="margin-top: 15px;">
                        <button class="action-btn btn-warning" onclick="contactSupport()">
                            <i class="fas fa-headset"></i> Contact Support
                        </button>
                        <button class="action-btn btn-secondary" onclick="logout()">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            <?php else: ?>
            
            <!-- Availability Toggle -->
            <div class="availability-toggle">
                <span class="info-label">Online Status:</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="availabilityToggle" <?php echo ($delivery_person && $delivery_person['is_available']) ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span id="availabilityStatus" class="info-value">
                    <?php echo ($delivery_person && $delivery_person['is_available']) ? 'Online - Accepting Orders' : 'Offline'; ?>
                </span>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_deliveries; ?></div>
                    <div class="stat-label">Total Deliveries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($total_earnings, 2); ?></div>
                    <div class="stat-label">Total Earnings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($current_orders); ?></div>
                    <div class="stat-label">Active Deliveries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo ($delivery_person && $delivery_person['rating']) ? $delivery_person['rating'] : '5.0'; ?>
                    </div>
                    <div class="stat-label">Customer Rating</div>
                </div>
            </div>
            
            <!-- Dashboard Tabs -->
            <div class="dashboard-tabs">
                <button class="dashboard-tab active" data-tab="overview">Overview</button>
                <button class="dashboard-tab" data-tab="orders">Active Orders</button>
                <button class="dashboard-tab" data-tab="available">Available Orders</button>
                <button class="dashboard-tab" data-tab="history">Order History</button>
                <button class="dashboard-tab" data-tab="profile">Profile</button>
            </div>
            
            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content active">
                <h2>Delivery Overview</h2>
                
                <?php if($delivery_person): ?>
                <div class="delivery-info">
                    <h3>Welcome, <?php echo htmlspecialchars($delivery_person['full_name']); ?>!</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Vehicle Type</div>
                            <div class="info-value"><?php echo ucfirst(htmlspecialchars($delivery_person['vehicle_type'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">License Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($delivery_person['license_number']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Vehicle Plate</div>
                            <div class="info-value"><?php echo htmlspecialchars($delivery_person['vehicle_plate'] ?: 'Not provided'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span style="color: #28a745; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> Approved
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="action-btn btn-success" onclick="loadAvailableOrders()">
                        <i class="fas fa-shopping-bag"></i> Check Available Orders
                    </button>
                    <button class="action-btn btn-warning" onclick="refreshOrders()">
                        <i class="fas fa-sync-alt"></i> Refresh Orders
                    </button>
                </div>
            </div>
            
            <!-- Active Orders Tab -->
            <div id="orders-tab" class="tab-content">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h2>Active Deliveries</h2>
                    <button class="action-btn btn-success" onclick="refreshOrders()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <div id="active-orders-container">
                    <?php if(count($current_orders) > 0): ?>
                        <div class="orders-grid">
                            <?php foreach($current_orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php echo str_replace('_', ' ', $order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="order-content">
                                        <div class="order-detail">
                                            <span class="order-detail-label">Restaurant:</span>
                                            <span class="order-detail-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                                        </div>
                                        <div class="order-detail">
                                            <span class="order-detail-label">Customer:</span>
                                            <span class="order-detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                        </div>
                                        <div class="order-detail">
                                            <span class="order-detail-label">Total Amount:</span>
                                            <span class="order-detail-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                        <div class="order-detail">
                                            <span class="order-detail-label">Delivery Fee:</span>
                                            <span class="order-detail-value">$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                                        </div>
                                        <div class="order-detail">
                                            <span class="order-detail-label">Delivery Address:</span>
                                            <span class="order-detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <?php if($order['status'] === 'picked_up'): ?>
                                                <button class="action-btn btn-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'on_the_way')">
                                                    <i class="fas fa-play"></i> Start Delivery
                                                </button>
                                            <?php elseif($order['status'] === 'on_the_way'): ?>
                                                <button class="action-btn btn-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">
                                                    <i class="fas fa-check"></i> Mark Delivered
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>No Active Deliveries</h3>
                            <p>You don't have any active deliveries at the moment.</p>
                            <button class="action-btn btn-success" onclick="loadAvailableOrders()" style="margin-top: 15px;">
                                <i class="fas fa-search"></i> Find Available Orders
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Available Orders Tab -->
            <div id="available-tab" class="tab-content">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h2>Available Orders</h2>
                    <button class="action-btn btn-success" onclick="loadAvailableOrders()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <div id="available-orders-container">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No Orders Loaded</h3>
                        <p>Click the refresh button to load available orders in your area.</p>
                    </div>
                </div>
            </div>
            
            <!-- Order History Tab -->
            <div id="history-tab" class="tab-content">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h2>Delivery History</h2>
                    <button class="action-btn btn-success" onclick="refreshOrders()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <div id="order-history-container">
                    <?php if(count($completed_orders) > 0): ?>
                        <div class="order-history">
                            <?php foreach($completed_orders as $order): ?>
                                <div class="history-card">
                                    <div class="history-header">
                                        <div class="history-restaurant">
                                            Order #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['restaurant_name']); ?>
                                        </div>
                                        <span class="history-status status-<?php echo $order['status']; ?>">
                                            <?php echo str_replace('_', ' ', $order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="history-details">
                                        <div>
                                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                                        </div>
                                        <div class="history-amount">
                                            $<?php echo number_format($order['total_amount'], 2); ?>
                                        </div>
                                        <div>
                                            <strong>Delivery Fee:</strong> $<?php echo number_format($order['delivery_fee'], 2); ?>
                                        </div>
                                        <div class="history-date">
                                            <?php echo date('M j, g:i A', strtotime($order['updated_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="history-address">
                                        <strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?>
                                    </div>
                                    
                                    <!-- Show earnings breakdown for delivered orders -->
                                    <?php if($order['status'] === 'delivered'): ?>
                                        <div class="earnings-breakdown">
                                            <div class="breakdown-item">
                                                <span>Delivery Fee:</span>
                                                <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                                            </div>
                                            <div class="breakdown-total">
                                                <span>Your Earnings:</span>
                                                <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h3>No Delivery History</h3>
                            <p>You haven't completed any deliveries yet.</p>
                            <button class="action-btn btn-success" onclick="loadAvailableOrders()" style="margin-top: 15px;">
                                <i class="fas fa-search"></i> Find Available Orders
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content">
                <h2>Delivery Profile</h2>
                <div class="delivery-info">
                    <?php if($delivery_person): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($delivery_person['full_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($delivery_person['email']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($delivery_person['phone']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Vehicle Type</div>
                                <div class="info-value"><?php echo ucfirst(htmlspecialchars($delivery_person['vehicle_type'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Vehicle Plate</div>
                                <div class="info-value"><?php echo htmlspecialchars($delivery_person['vehicle_plate'] ?: 'Not provided'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">License Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($delivery_person['license_number']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="action-btn btn-warning" onclick="showEarningsHistory()">
                        <i class="fas fa-chart-line"></i> View Earnings History
                    </button>
                    <button class="action-btn btn-secondary" onclick="showSupport()">
                        <i class="fas fa-headset"></i> Contact Support
                    </button>
                </div>
            </div>
            
            <?php endif; // End of approved account section ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });

        // Tab functionality
        document.querySelectorAll('.dashboard-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.dashboard-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(`${tab.dataset.tab}-tab`).classList.add('active');
                
                // Load available orders when that tab is clicked
                if (tab.dataset.tab === 'available') {
                    loadAvailableOrders();
                }
            });
        });

        // Availability toggle
        document.getElementById('availabilityToggle').addEventListener('change', function() {
            const isAvailable = this.checked;
            const statusElement = document.getElementById('availabilityStatus');
            
            fetch('update_delivery_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `available=${isAvailable ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusElement.textContent = isAvailable ? 'Online - Accepting Orders' : 'Offline';
                    Toast.fire({
                        icon: 'success',
                        title: isAvailable ? 'You are now online and can receive orders' : 'You are now offline'
                    });
                    
                    // Reload available orders if going online
                    if (isAvailable) {
                        loadAvailableOrders();
                    }
                } else {
                    this.checked = !isAvailable;
                    Toast.fire({
                        icon: 'error',
                        title: data.message || 'Failed to update status'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isAvailable;
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to update status'
                });
            });
        });

        // Load available orders
        function loadAvailableOrders() {
            const container = document.getElementById('available-orders-container');
            container.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading available orders...
                </div>
            `;

            fetch('get_delivery_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.orders.length > 0) {
                        container.innerHTML = `
                            <div class="orders-grid">
                                ${data.orders.map(order => `
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-id">Order #${order.id}</div>
                                            <span class="order-status status-ready_for_pickup">Ready for Pickup</span>
                                        </div>
                                        <div class="order-content">
                                            <div class="order-detail">
                                                <span class="order-detail-label">Restaurant:</span>
                                                <span class="order-detail-value">${order.restaurant_name}</span>
                                            </div>
                                            <div class="order-detail">
                                                <span class="order-detail-label">Customer:</span>
                                                <span class="order-detail-value">${order.customer_name}</span>
                                            </div>
                                            <div class="order-detail">
                                                <span class="order-detail-label">Delivery Address:</span>
                                                <span class="order-detail-value">${order.delivery_address}</span>
                                            </div>
                                            <div class="order-detail">
                                                <span class="order-detail-label">Total Amount:</span>
                                                <span class="order-detail-value">$${parseFloat(order.total_amount).toFixed(2)}</span>
                                            </div>
                                            <div class="order-detail">
                                                <span class="order-detail-label">Delivery Fee:</span>
                                                <span class="order-detail-value">$${parseFloat(order.delivery_fee).toFixed(2)}</span>
                                            </div>
                                            
                                            <div class="order-actions">
                                                <button class="action-btn btn-success" onclick="acceptOrder(${order.id})">
                                                    <i class="fas fa-check"></i> Accept Order
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
                                <i class="fas fa-search"></i>
                                <h3>No Available Orders</h3>
                                <p>There are no orders available for delivery in your area at the moment.</p>
                                <button class="action-btn btn-success" onclick="loadAvailableOrders()" style="margin-top: 15px;">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading orders:', error);
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Error Loading Orders</h3>
                            <p>Failed to load available orders. Please try again.</p>
                        </div>
                    `;
                });
        }

        function acceptOrder(orderId) {
            if (!confirm('Are you sure you want to accept this delivery order?')) {
                return;
            }

            fetch('accept_delivery_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: 'Order accepted successfully!'
                    });
                    loadAvailableOrders();
                    // Switch to active orders tab
                    document.querySelector('[data-tab="orders"]').click();
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message || 'Failed to accept order'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to accept order'
                });
            });
        }

        function updateOrderStatus(orderId, status) {
            const statusText = status.replace('_', ' ');
            
            if (!confirm(`Are you sure you want to mark this order as "${statusText}"?`)) {
                return;
            }

            fetch('update_delivery_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: `Order marked as ${statusText}`
                    });
                    // Refresh the page to show updated orders
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message || 'Failed to update order status'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to update order status'
                });
            });
        }

        function refreshOrders() {
            window.location.reload();
        }

        function showEarningsHistory() {
            Toast.fire({
                icon: 'info',
                title: 'Earnings history feature coming soon!'
            });
        }

        function showSupport() {
            Toast.fire({
                icon: 'info',
                title: 'Support contact: support@doordash.com'
            });
        }

        function contactSupport() {
            Toast.fire({
                icon: 'info',
                title: 'Contact support at: support@doordash.com'
            });
        }

        function logout() {
            window.location.href = 'index.php?logout=1';
        }

        console.log('🚴 Dasher Dashboard Loaded!');
    </script>
</body>
</html>