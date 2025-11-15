<?php
require_once 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_delivery') {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_plate = trim($_POST['vehicle_plate']);
    $license_number = trim($_POST['license_number']);
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }
        
        // Create user account
        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, 'delivery')");
        $stmt->execute([$email, $password, $full_name, $phone]);
        
        $user_id = $pdo->lastInsertId();
        
        // Create delivery person record
        $stmt = $pdo->prepare("INSERT INTO delivery_persons (user_id, vehicle_type, vehicle_plate, license_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $vehicle_type, $vehicle_plate, $license_number]);
        
        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_type'] = 'delivery';
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Delivery account created successfully! Your account will be activated after verification.',
            'redirect' => 'delivery_dashboard.php'
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
    <title>Become a Dasher - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
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
            color: #28a745;
            margin-right: 10px;
            font-size: 16px;
        }

        .requirements {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        .requirements h4 {
            color: #28a745;
            margin-bottom: 10px;
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
                    <a href="delivery_login.php" class="nav-link">Dasher Sign In</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-header">
            <h1 class="page-title">Become a Dasher</h1>
            <p class="page-subtitle">Earn money on your schedule</p>
        </div>

        <div class="form-container">
            <div class="requirements">
                <h4><i class="fas fa-info-circle"></i> Requirements to Become a Dasher:</h4>
                <ul style="margin-left: 20px; color: #666;">
                    <li>Must be 18 years or older</li>
                    <li>Have a valid driver's license</li>
                    <li>Own a vehicle (car, motorcycle, bike, or scooter)</li>
                    <li>Have a smartphone</li>
                    <li>Pass a background check</li>
                </ul>
            </div>

            <div class="form-section">
                <h3 class="section-title">Benefits of Dashing</h3>
                <ul class="benefits-list">
                    <li><i class="fas fa-dollar-sign"></i> Earn up to $25 per hour</li>
                    <li><i class="fas fa-calendar-alt"></i> Flexible schedule</li>
                    <li><i class="fas fa-map-marker-alt"></i> Work in your area</li>
                    <li><i class="fas fa-gift"></i> Cash out daily</li>
                    <li><i class="fas fa-shield-alt"></i> Insurance coverage</li>
                </ul>
            </div>

            <form id="deliveryForm" method="POST">
                <div class="form-section">
                    <h3 class="section-title">Personal Information</h3>
                    
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
                        <input type="password" id="password" name="password" class="form-input" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Vehicle Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="vehicle_type">Vehicle Type *</label>
                        <select id="vehicle_type" name="vehicle_type" class="form-select" required>
                            <option value="">Select Vehicle Type</option>
                            <option value="car">Car</option>
                            <option value="motorcycle" selected>Motorcycle</option>
                            <option value="bicycle">Bicycle</option>
                            <option value="scooter">Scooter</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="vehicle_plate">Vehicle Plate Number</label>
                            <input type="text" id="vehicle_plate" name="vehicle_plate" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="license_number">Driver's License Number *</label>
                            <input type="text" id="license_number" name="license_number" class="form-input" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="agree_terms" required> 
                            I agree to the <a href="#" style="color: #28a745;">Terms of Service</a> and <a href="#" style="color: #28a745;">Privacy Policy</a> *
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="confirm_requirements" required> 
                            I confirm that I meet all the requirements to become a Dasher *
                        </label>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-motorcycle"></i> Start Dashing
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p>Already a Dasher? <a href="delivery_login.php" style="color: #28a745; font-weight: 600;">Sign In Here</a></p>
            </div>
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

        document.getElementById('deliveryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'register_delivery');

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                Toast.fire({
                    icon: 'error',
                    title: 'Passwords do not match'
                });
                return;
            }

            // Show loading state
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            fetch('delivery_register.php', {
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
    </script>
</body>
</html>