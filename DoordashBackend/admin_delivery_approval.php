<?php
require_once 'config.php';

// Redirect if not admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Get pending delivery persons
$pending_delivery = getPendingDeliveryPersons($pdo);
$approved_delivery = getDeliveryPersons($pdo);

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $delivery_person_id = intval($_POST['delivery_person_id']);
        $stmt = $pdo->prepare("UPDATE delivery_persons SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$delivery_person_id]);
        $message = "Delivery person approved successfully!";
    } elseif (isset($_POST['reject'])) {
        $delivery_person_id = intval($_POST['delivery_person_id']);
        
        // Get user ID first
        $stmt = $pdo->prepare("SELECT user_id FROM delivery_persons WHERE id = ?");
        $stmt->execute([$delivery_person_id]);
        $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($delivery_person) {
            // Delete delivery person record
            $stmt = $pdo->prepare("DELETE FROM delivery_persons WHERE id = ?");
            $stmt->execute([$delivery_person_id]);
            
            // Delete user account
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'delivery'");
            $stmt->execute([$delivery_person['user_id']]);
        }
        $message = "Delivery person rejected and account removed!";
    }
    
    // Refresh the page
    header("Location: admin_delivery_approval.php?message=" . urlencode($message));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Delivery Approval</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wide-container {
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

        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .delivery-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
        }

        .delivery-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .delivery-name {
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .delivery-content {
            padding: 20px;
        }

        .delivery-detail {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .delivery-detail-label {
            font-weight: 600;
            color: #333;
        }

        .delivery-detail-value {
            color: #666;
        }

        .delivery-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
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

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="wide-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-user-shield"></i> Admin - Delivery Approval</h1>
            <p>Manage delivery person applications</p>
        </div>
        
        <div class="dashboard-content">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <?php if(isset($_GET['message'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Pending Approval Section -->
            <div class="section">
                <h2>Pending Approval</h2>
                <p>Delivery persons waiting for approval</p>
                
                <div class="delivery-grid">
                    <?php if(count($pending_delivery) > 0): ?>
                        <?php foreach($pending_delivery as $delivery): ?>
                            <div class="delivery-card">
                                <div class="delivery-header">
                                    <div class="delivery-name"><?php echo htmlspecialchars($delivery['full_name']); ?></div>
                                    <span style="color: #ffc107; font-weight: 600;">
                                        <i class="fas fa-clock"></i> Pending Approval
                                    </span>
                                </div>
                                <div class="delivery-content">
                                    <div class="delivery-detail">
                                        <span class="delivery-detail-label">Email:</span>
                                        <span class="delivery-detail-value"><?php echo htmlspecialchars($delivery['email']); ?></span>
                                    </div>
                                    <div class="delivery-detail">
                                        <span class="delivery-detail-label">Phone:</span>
                                        <span class="delivery-detail-value"><?php echo htmlspecialchars($delivery['phone']); ?></span>
                                    </div>
                                    <div class="delivery-detail">
                                        <span class="delivery-detail-label">Vehicle:</span>
                                        <span class="delivery-detail-value"><?php echo ucfirst(htmlspecialchars($delivery['vehicle_type'])); ?></span>
                                    </div>
                                    <div class="delivery-detail">
                                        <span class="delivery-detail-label">License:</span>
                                        <span class="delivery-detail-value"><?php echo htmlspecialchars($delivery['license_number']); ?></span>
                                    </div>
                                    <div class="delivery-detail">
                                        <span class="delivery-detail-label">Registered:</span>
                                        <span class="delivery-detail-value"><?php echo date('M j, Y g:i A', strtotime($delivery['created_at'])); ?></span>
                                    </div>
                                    
                                    <form method="POST" class="delivery-actions">
                                        <input type="hidden" name="delivery_person_id" value="<?php echo $delivery['id']; ?>">
                                        <button type="submit" name="approve" class="action-btn btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="reject" class="action-btn btn-danger" onclick="return confirm('Are you sure you want to reject this delivery person? This will delete their account.')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>No Pending Approvals</h3>
                            <p>All delivery persons have been approved.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approved Delivery Persons Section -->
            <div class="section">
                <h2>Approved Delivery Persons</h2>
                <p>Active delivery persons in the system</p>
                
                <div class="delivery-grid">
                    <?php if(count($approved_delivery) > 0): ?>
                        <?php foreach($approved_delivery as $delivery): ?>
                            <?php if($delivery['is_approved']): ?>
                                <div class="delivery-card">
                                    <div class="delivery-header">
                                        <div class="delivery-name"><?php echo htmlspecialchars($delivery['full_name']); ?></div>
                                        <span style="color: #28a745; font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Approved
                                        </span>
                                    </div>
                                    <div class="delivery-content">
                                        <div class="delivery-detail">
                                            <span class="delivery-detail-label">Email:</span>
                                            <span class="delivery-detail-value"><?php echo htmlspecialchars($delivery['email']); ?></span>
                                        </div>
                                        <div class="delivery-detail">
                                            <span class="delivery-detail-label">Status:</span>
                                            <span class="delivery-detail-value">
                                                <?php echo $delivery['is_available'] ? 
                                                    '<span style="color: #28a745;">Online</span>' : 
                                                    '<span style="color: #6c757d;">Offline</span>'; ?>
                                            </span>
                                        </div>
                                        <div class="delivery-detail">
                                            <span class="delivery-detail-label">Deliveries:</span>
                                            <span class="delivery-detail-value"><?php echo $delivery['total_deliveries']; ?></span>
                                        </div>
                                        <div class="delivery-detail">
                                            <span class="delivery-detail-label">Earnings:</span>
                                            <span class="delivery-detail-value">$<?php echo number_format($delivery['earnings'], 2); ?></span>
                                        </div>
                                        <div class="delivery-detail">
                                            <span class="delivery-detail-label">Rating:</span>
                                            <span class="delivery-detail-value"><?php echo $delivery['rating'] ? $delivery['rating'] : '5.0'; ?>/5</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Approved Delivery Persons</h3>
                            <p>No delivery persons have been approved yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>