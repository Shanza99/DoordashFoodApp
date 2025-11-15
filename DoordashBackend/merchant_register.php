<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_restaurant_with_address') {
    // This will be handled by the AJAX call in config.php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Restaurant - DoorDash</title>
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
        <div class="page-header">
            <h1><i class="fas fa-store"></i> Register Your Restaurant</h1>
            <p>Join DoorDash and reach thousands of customers</p>
        </div>

        <div class="form-container">
            <form id="restaurantRegisterForm" method="POST">
                <input type="hidden" name="action" value="register_restaurant_with_address">
                
                <!-- Restaurant Information -->
                <div class="form-section">
                    <h3 class="section-title">Restaurant Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="restaurant_name">Restaurant Name *</label>
                        <input type="text" id="restaurant_name" name="restaurant_name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Restaurant Description</label>
                        <textarea id="description" name="description" class="form-textarea" placeholder="Tell us about your restaurant..."></textarea>
                    </div>

                    <div class="form-row">
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
                        <div class="form-group">
                            <label class="form-label" for="delivery_time">Delivery Time *</label>
                            <select id="delivery_time" name="delivery_time" class="form-select" required>
                                <option value="15-25 min">15-25 min</option>
                                <option value="20-30 min">20-30 min</option>
                                <option value="25-35 min">25-35 min</option>
                                <option value="30-40 min">30-40 min</option>
                                <option value="35-45 min">35-45 min</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="delivery_fee">Delivery Fee *</label>
                            <input type="number" id="delivery_fee" name="delivery_fee" class="form-input" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>

                <!-- Restaurant Address -->
                <div class="form-section">
                    <h3 class="section-title">Restaurant Address</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="address">Street Address *</label>
                        <input type="text" id="address" name="address" class="form-input" placeholder="123 Main Street" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="city">City *</label>
                            <input type="text" id="city" name="city" class="form-input" placeholder="New York" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="state">State *</label>
                            <select id="state" name="state" class="form-select" required>
                                <option value="">Select State</option>
                                <option value="AL">Alabama</option>
                                <option value="AK">Alaska</option>
                                <option value="AZ">Arizona</option>
                                <option value="AR">Arkansas</option>
                                <option value="CA">California</option>
                                <option value="CO">Colorado</option>
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
                                <option value="HI">Hawaii</option>
                                <option value="ID">Idaho</option>
                                <option value="IL">Illinois</option>
                                <option value="IN">Indiana</option>
                                <option value="IA">Iowa</option>
                                <option value="KS">Kansas</option>
                                <option value="KY">Kentucky</option>
                                <option value="LA">Louisiana</option>
                                <option value="ME">Maine</option>
                                <option value="MD">Maryland</option>
                                <option value="MA">Massachusetts</option>
                                <option value="MI">Michigan</option>
                                <option value="MN">Minnesota</option>
                                <option value="MS">Mississippi</option>
                                <option value="MO">Missouri</option>
                                <option value="MT">Montana</option>
                                <option value="NE">Nebraska</option>
                                <option value="NV">Nevada</option>
                                <option value="NH">New Hampshire</option>
                                <option value="NJ">New Jersey</option>
                                <option value="NM">New Mexico</option>
                                <option value="NY">New York</option>
                                <option value="NC">North Carolina</option>
                                <option value="ND">North Dakota</option>
                                <option value="OH">Ohio</option>
                                <option value="OK">Oklahoma</option>
                                <option value="OR">Oregon</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="RI">Rhode Island</option>
                                <option value="SC">South Carolina</option>
                                <option value="SD">South Dakota</option>
                                <option value="TN">Tennessee</option>
                                <option value="TX">Texas</option>
                                <option value="UT">Utah</option>
                                <option value="VT">Vermont</option>
                                <option value="VA">Virginia</option>
                                <option value="WA">Washington</option>
                                <option value="WV">West Virginia</option>
                                <option value="WI">Wisconsin</option>
                                <option value="WY">Wyoming</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="zip_code">ZIP Code *</label>
                        <input type="text" id="zip_code" name="zip_code" class="form-input" placeholder="10001" required>
                    </div>
                </div>

                <!-- User Account Information (only show if not logged in) -->
                <?php if(!isset($_SESSION['user_id'])): ?>
                <div class="form-section">
                    <h3 class="section-title">Account Information</h3>
                    
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

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-input" required minlength="6">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-store"></i> Register Restaurant
                </button>
            </form>

            <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="login-prompt">
                Already have an account? <a href="index.php" class="login-link">Sign in here</a>
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

        document.getElementById('restaurantRegisterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            fetch('config.php', {
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
                            window.location.href = 'restaurant_dashboard.php';
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
    </script>
</body>
</html>