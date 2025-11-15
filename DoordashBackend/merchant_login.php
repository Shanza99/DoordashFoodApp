<?php
require_once 'config.php';

// Handle form submission first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_merchant') {
    header('Content-Type: application/json');
    
    $restaurant_name = trim($_POST['restaurant_name']);
    $cuisine_type = trim($_POST['cuisine_type']);
    $description = trim($_POST['description']);
    $delivery_time = trim($_POST['delivery_time']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    try {
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            // Update user type to restaurant if not already
            if ($_SESSION['user_type'] !== 'restaurant') {
                $stmt = $pdo->prepare("UPDATE users SET user_type = 'restaurant' WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['user_type'] = 'restaurant';
            }
        } else {
            // Create new user account
            $password = trim($_POST['password']);
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already registered']);
                exit;
            }
            
            // Create new user
            $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, 'restaurant')");
            $stmt->execute([$email, $password, $full_name, $phone]);
            
            $user_id = $pdo->lastInsertId();
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 'restaurant';
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
        }
        
        // Create restaurant entry WITH ADDRESS
        $stmt = $pdo->prepare("INSERT INTO restaurants (name, description, cuisine_type, delivery_time, delivery_fee, address, rating, review_count) VALUES (?, ?, ?, ?, ?, ?, 4.5, 0)");
        $stmt->execute([$restaurant_name, $description, $cuisine_type, $delivery_time, $delivery_fee, $address]);
        
        $restaurant_id = $pdo->lastInsertId();
        
        // Link user to restaurant
        $stmt = $pdo->prepare("INSERT INTO user_restaurants (user_id, restaurant_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $restaurant_id]);
        
        // Store restaurant ID in session for easy access
        $_SESSION['restaurant_id'] = $restaurant_id;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Restaurant registered successfully!', 
            'redirect' => 'restaurant_dashboard.php'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Merchant - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wide-container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .page-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .page-title {
            font-size: 36px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
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

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #FF3008;
            box-shadow: 0 0 0 3px rgba(255, 48, 8, 0.1);
        }

        .form-textarea {
            height: 100px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 48, 8, 0.3);
        }

        .benefits-list {
            list-style: none;
            margin: 20px 0;
        }

        .benefits-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }

        .benefits-list li:last-child {
            border-bottom: none;
        }

        .benefits-list i {
            color: #FF3008;
            margin-right: 10px;
            font-size: 16px;
        }

        .alert-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .address-example {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .wide-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="wide-container">
        <!-- Common Navigation Header -->
        <div class="nav-header">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-utensils"></i>DOORDASH
            </a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="restaurants.php" class="nav-link">Restaurants</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span style="color: #666; font-size: 14px;">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="merchant_login.php" class="nav-link">Merchant Sign In</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-header">
            <h1 class="page-title">Become a Merchant</h1>
            <p class="page-subtitle">Join thousands of restaurants growing with DoorDash</p>
        </div>

        <div class="form-container">
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'restaurant'): ?>
                <div class="form-section">
                    <h3 class="section-title">Restaurant Dashboard</h3>
                    <p>Welcome to your restaurant dashboard! You can manage your restaurant and menu items here.</p>
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <a href="restaurant_dashboard.php" class="submit-btn" style="text-decoration: none; text-align: center;">
                            <i class="fas fa-store"></i> Manage Restaurant
                        </a>
                        <a href="menu_management.php" class="submit-btn" style="text-decoration: none; text-align: center; background: #28a745;">
                            <i class="fas fa-utensils"></i> Manage Menu
                        </a>
                    </div>
                </div>
            <?php elseif(isset($_SESSION['user_id'])): ?>
                <div class="form-section">
                    <div class="alert-message">
                        <i class="fas fa-exclamation-triangle"></i> 
                        You are currently registered as a <?php echo $_SESSION['user_type']; ?>. To become a merchant, you need to register as a restaurant owner.
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-section">
                <h3 class="section-title">Benefits of Partnering with DoorDash</h3>
                <ul class="benefits-list">
                    <li><i class="fas fa-users"></i> Reach thousands of new customers</li>
                    <li><i class="fas fa-chart-line"></i> Increase your revenue</li>
                    <li><i class="fas fa-tools"></i> Easy-to-use management tools</li>
                    <li><i class="fas fa-shield-alt"></i> Secure payments</li>
                    <li><i class="fas fa-headset"></i> 24/7 customer support</li>
                </ul>
            </div>

            <?php if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'restaurant'): ?>
            <form id="merchantForm" method="POST">
                <input type="hidden" name="action" value="register_merchant">
                
                <div class="form-section">
                    <h3 class="section-title">Restaurant Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="restaurant_name">Restaurant Name *</label>
                            <input type="text" id="restaurant_name" name="restaurant_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cuisine_type">Cuisine Type *</label>
                            <select id="cuisine_type" name="cuisine_type" class="form-select" required>
                                <option value="">Select Cuisine</option>
                                <option value="American">American</option>
                                <option value="Italian">Italian</option>
                                <option value="Mexican">Mexican</option>
                                <option value="Chinese">Chinese</option>
                                <option value="Japanese">Japanese</option>
                                <option value="Indian">Indian</option>
                                <option value="Thai">Thai</option>
                                <option value="Mediterranean">Mediterranean</option>
                                <option value="French">French</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Restaurant Description</label>
                        <textarea id="description" name="description" class="form-textarea" placeholder="Describe your restaurant..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="delivery_time">Delivery Time *</label>
                            <select id="delivery_time" name="delivery_time" class="form-select" required>
                                <option value="15-25 min">15-25 min</option>
                                <option value="20-30 min" selected>20-30 min</option>
                                <option value="25-35 min">25-35 min</option>
                                <option value="30-40 min">30-40 min</option>
                                <option value="35-45 min">35-45 min</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="delivery_fee">Delivery Fee *</label>
                            <input type="number" id="delivery_fee" name="delivery_fee" class="form-input" step="0.01" min="0" value="2.99" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Restaurant Location</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="address">Restaurant Address *</label>
                        <input type="text" id="address" name="address" class="form-input" required 
                               placeholder="Enter full restaurant address (street, city, state, zip code)">
                        <div class="address-example">
                            Example: 123 Main Street, New York, NY 10001
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Contact Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                </div>

                <?php if(!isset($_SESSION['user_id'])): ?>
                <div class="form-section">
                    <h3 class="section-title">Account Setup</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-input" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="agree_terms" required> 
                            I agree to the <a href="#" style="color: #FF3008;">Terms of Service</a> and <a href="#" style="color: #FF3008;">Privacy Policy</a> *
                        </label>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-store"></i> Become a Merchant
                </button>
            </form>
            <?php endif; ?>
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

        document.getElementById('merchantForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);

            // Validate passwords if user is not logged in
            <?php if(!isset($_SESSION['user_id'])): ?>
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                Toast.fire({
                    icon: 'error',
                    title: 'Passwords do not match'
                });
                return;
            }
            <?php endif; ?>

            // Show loading state
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            fetch('merchant_register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    throw new Error('Server returned non-JSON response');
                }
            })
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                    
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message
                    });
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Registration failed. Please try again.'
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Add logout functionality
        document.addEventListener('DOMContentLoaded', function() {
            const logoutLinks = document.querySelectorAll('a[href*="logout"]');
            logoutLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'index.php?logout=1';
                });
            });
        });
    </script>

    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    ?>
</body>
</html>