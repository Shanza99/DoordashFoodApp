<?php
require_once 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'register') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        $user_type = 'customer'; // Force customer type
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already registered']);
                exit;
            }
            
            // Create new user
            $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$email, $password, $full_name, $phone, $user_type]);
            
            $user_id = $pdo->lastInsertId();
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_address'] = ''; // Initialize address as empty
            
            echo json_encode([
                'success' => true, 
                'message' => 'Account created successfully!', 
                'redirect' => 'customer_dashboard.php'
            ]);
            
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
        
    } elseif ($_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type = 'customer'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && $user['password'] === $password) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_address'] = $user['address'] ?? ''; // Set address from database
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Login successful!', 
                    'redirect' => 'customer_dashboard.php'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            }
            
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Customer - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
            padding: 40px;
            text-align: center;
        }

        .form-container {
            padding: 30px;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .auth-tab {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .auth-tab:hover {
            color: #FF3008;
            background: #fff5f5;
        }

        .auth-tab.active {
            color: #FF3008;
            border-bottom-color: #FF3008;
            background: #fff5f5;
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #e9ecef;
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

        .login-prompt {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link {
            color: #FF3008;
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wide-container">
        <!-- Navigation Header -->
        <div class="nav-header">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-utensils"></i>DOORDASH
            </a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="restaurants.php" class="nav-link">Restaurants</a>
                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer'): ?>
                    <span style="color: #666; font-size: 14px;">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="customer_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="customer_register.php" class="nav-link active">Become a Customer</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-header">
            <h1><i class="fas fa-user"></i> Become a Customer</h1>
            <p>Join thousands of customers enjoying fast food delivery</p>
        </div>

        <div class="form-container">
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer'): ?>
                <div class="form-section">
                    <h3 class="section-title">Welcome Back!</h3>
                    <p>You are already logged in as a customer. You can manage your orders and account from your dashboard.</p>
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <a href="customer_dashboard.php" class="submit-btn" style="text-decoration: none; text-align: center;">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                        <a href="restaurants.php" class="submit-btn" style="text-decoration: none; text-align: center; background: #28a745;">
                            <i class="fas fa-utensils"></i> Order Food
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Benefits Section -->
                <div class="form-section">
                    <h3 class="section-title">Why Become a DoorDash Customer?</h3>
                    <ul class="benefits-list">
                        <li><i class="fas fa-shipping-fast"></i> Fast delivery from your favorite restaurants</li>
                        <li><i class="fas fa-tag"></i> Exclusive deals and discounts</li>
                        <li><i class="fas fa-utensils"></i> Wide variety of cuisines</li>
                        <li><i class="fas fa-map-marker-alt"></i> Real-time order tracking</li>
                        <li><i class="fas fa-shield-alt"></i> Secure payment options</li>
                        <li><i class="fas fa-history"></i> Easy order history tracking</li>
                    </ul>
                </div>

                <!-- Auth Tabs -->
                <div class="auth-tabs">
                    <button class="auth-tab active" data-form="signin">Sign In</button>
                    <button class="auth-tab" data-form="signup">Create Account</button>
                </div>

                <!-- Sign In Form -->
                <div class="auth-form active" id="signin-form">
                    <form id="customerLoginForm">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label class="form-label" for="login-email">Email Address *</label>
                            <input type="email" id="login-email" name="email" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="login-password">Password *</label>
                            <div class="password-group">
                                <input type="password" id="login-password" name="password" class="form-input" required>
                                <button type="button" class="password-toggle" data-target="login-password">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" id="login-btn">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </button>
                    </form>
                </div>

                <!-- Sign Up Form -->
                <div class="auth-form" id="signup-form">
                    <form id="customerRegisterForm">
                        <input type="hidden" name="action" value="register">
                        
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

                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <div class="password-group">
                                <input type="password" id="password" name="password" class="form-input" required minlength="6">
                                <button type="button" class="password-toggle" data-target="password">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" id="register-btn">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>
                </div>
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

        // Tab functionality
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(`${tab.dataset.form}-form`).classList.add('active');
            });
        });

        // Password toggle functionality
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.dataset.target;
                const passwordInput = document.getElementById(targetId);
                const icon = toggle.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Login form submission - EXACTLY LIKE INDEX.PHP
        document.getElementById('customerLoginForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            if (!email || !password) {
                Toast.fire({
                    icon: 'error',
                    title: 'Please fill in all fields'
                });
                return;
            }
            
            const submitBtn = document.getElementById('login-btn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);
            
            fetch('customer_register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
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
                    }, 1500);
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
                    title: 'Login failed. Please try again.'
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Register form submission - EXACTLY LIKE INDEX.PHP
        document.getElementById('customerRegisterForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('full_name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;
            
            if (!name || !email || !phone || !password) {
                Toast.fire({
                    icon: 'error',
                    title: 'Please fill in all fields'
                });
                return;
            }
            
            if (password.length < 6) {
                Toast.fire({
                    icon: 'error',
                    title: 'Password must be at least 6 characters'
                });
                return;
            }
            
            const submitBtn = document.getElementById('register-btn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('email', email);
            formData.append('password', password);
            formData.append('full_name', name);
            formData.append('phone', phone);
            
            fetch('customer_register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
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
                    }, 1500);
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
                    window.location.href = 'customer_register.php?logout=1';
                });
            });
        });

        console.log('Customer Register Page Loaded');
    </script>

    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: customer_register.php');
        exit;
    }
    ?>
</body>
</html>