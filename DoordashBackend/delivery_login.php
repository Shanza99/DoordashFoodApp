<?php 
// Remove the session_start() from here since config.php already starts it
require_once 'config.php';

if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'delivery') {
    header('Location: delivery_dashboard.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, dp.id as delivery_person_id, dp.is_approved, dp.is_available, dp.vehicle_type, dp.total_deliveries, dp.earnings 
                              FROM users u 
                              LEFT JOIN delivery_persons dp ON u.id = dp.user_id 
                              WHERE u.email = ? AND u.user_type = 'delivery' AND u.is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($password === $user['password']) {
                if (!$user['is_approved']) {
                    $error = 'Your delivery account is pending approval. Please wait for activation.';
                } else {
                    // Update last login
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['delivery_person_id'] = $user['delivery_person_id'];
                    $_SESSION['delivery_approved'] = $user['is_approved'];
                    $_SESSION['delivery_available'] = $user['is_available'];
                    $_SESSION['vehicle_type'] = $user['vehicle_type'];
                    $_SESSION['total_deliveries'] = $user['total_deliveries'];
                    $_SESSION['earnings'] = $user['earnings'];
                    
                    header('Location: delivery_dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'No delivery account found with this email';
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasher Login - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wide-container {
            width: 100%;
            max-width: 500px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .page-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .page-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .form-container {
            padding: 30px;
        }

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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

        .form-input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .auth-links {
            text-align: center;
            margin-top: 20px;
        }

        .auth-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .auth-link:hover {
            text-decoration: underline;
            background: #f8fff9;
        }

        .demo-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #28a745;
        }

        .demo-credentials h4 {
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-credentials p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="wide-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-motorcycle"></i>
                Dasher Login
            </h1>
            <p class="page-subtitle">Access your delivery dashboard</p>
        </div>

        <div class="form-container">
            <?php if(isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> 
                    <?php 
                    if($_GET['success'] === 'registered') {
                        echo 'Registration successful! Your account is pending approval.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-section">
                <form method="POST" id="deliveryLoginForm">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Password *</label>
                        <div class="password-group">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                    </button>
                </form>

                <div class="auth-links">
                    <a href="delivery_register.php" class="auth-link">
                        <i class="fas fa-motorcycle"></i> Become a Dasher
                    </a>
                    <a href="index.php" class="auth-link">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>

              
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentNode.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Simple form submission without AJAX
        document.getElementById('deliveryLoginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            submitBtn.disabled = true;
            
            // Let the form submit normally
        });

        console.log('🚴 Dasher Login Loaded!');
    </script>
</body>
</html>