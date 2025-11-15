<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoorDash - Food Delivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wide-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out;
        }

        .nav-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            font-size: 28px;
            font-weight: 800;
            color: #FF3008;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-logo i {
            font-size: 24px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #333;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .nav-link:hover {
            background: #FF3008;
            color: white;
        }

        .nav-link.active {
            background: #FF3008;
            color: white;
        }

        .content {
            padding: 30px;
        }

        @media (max-width: 768px) {
            .wide-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .nav-header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-link {
                font-size: 13px;
                padding: 6px 12px;
            }

            .content {
                padding: 20px;
            }
        }

        /* Floating background elements */
        .floating-element {
            position: absolute;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            z-index: -1;
            animation: float 15s infinite linear;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            left: 80%;
            animation-delay: -5s;
        }
    </style>
</head>
<body>
    <!-- Floating background elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    
    <div class="wide-container">
        <!-- Common Navigation Header -->
        <div class="nav-header">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-utensils"></i>DOORDASH
            </a>
            <div class="nav-links">
                <a href="index.php" class="nav-link active">Home</a>
                <a href="catering.php" class="nav-link">Catering</a>
                <a href="party-event.php" class="nav-link">Party & Events</a>
                <a href="restaurants.php" class="nav-link">Restaurants</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span style="color: #666; font-size: 14px;">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        <span class="user-badge"><?php echo ucfirst($_SESSION['user_type']); ?></span>
                    </span>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content">
            <!-- Address Section (ALWAYS FIRST) -->
            <div class="promo-banner">
                <div class="promo-title">$0 DELIVERY FEE ON FIRST ORDER</div>
                <div class="promo-subtitle">Other fees may apply</div>
            </div>
            
            <div class="address-section">
                <label class="address-label" for="delivery-address">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <i class="fas fa-map-marker-alt"></i> Deliver to: <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    <?php else: ?>
                        <i class="fas fa-map-marker-alt"></i> Enter your delivery address
                    <?php endif; ?>
                </label>
                <div class="input-group">
                    <i class="fas fa-search input-icon"></i>
                    <input type="text" id="delivery-address" class="address-input" placeholder="Start typing your address...">
                    <div class="loading-spinner" id="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
                
                <div class="suggestions-container" id="suggestions-container">
                    <!-- Suggestions will appear here -->
                </div>
                
                <div class="search-examples">
                    Try: <span data-search="New York">New York</span> 
                    <span data-search="Los Angeles">Los Angeles</span> 
                    <span data-search="Chicago">Chicago</span>
                </div>
            </div>
            
            <button class="continue-btn" id="continue-btn">
                <?php if(isset($_SESSION['user_type'])): ?>
                    <?php if($_SESSION['user_type'] === 'customer'): ?>
                        <i class="fas fa-utensils"></i> Find Restaurants Near You
                    <?php elseif($_SESSION['user_type'] === 'restaurant'): ?>
                        <i class="fas fa-store"></i> Manage Your Restaurant
                    <?php else: ?>
                        <i class="fas fa-motorcycle"></i> Find Delivery Opportunities
                    <?php endif; ?>
                <?php else: ?>
                    <i class="fas fa-utensils"></i> Find Restaurants Near You
                <?php endif; ?>
            </button>
            
            <!-- Authentication Section (BELOW ADDRESS) -->
            <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="auth-section">
                <div class="auth-options">
                    <button class="auth-option active" data-form="signin">Sign In</button>
                    <button class="auth-option" data-form="signup">Sign Up</button>
                </div>
                
                <!-- Sign In Form -->
                <div class="auth-form active" id="signin-form">
                    <div class="form-group">
                        <input type="email" id="signin-email" class="form-input" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="password-group">
                            <input type="password" id="signin-password" class="form-input" placeholder="Enter your password" required>
                            <button class="password-toggle" data-target="signin-password">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button class="auth-btn" id="signin-btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </div>
                
                <!-- Sign Up Form -->
                <div class="auth-form" id="signup-form">
                    <!-- User Type Selection -->
                    <div class="user-type-selector">
                        <div class="user-type selected" data-type="customer">
                            <i class="fas fa-user"></i>
                            <div>Customer</div>
                        </div>
                        <div class="user-type" data-type="restaurant">
                            <i class="fas fa-utensils"></i>
                            <div>Restaurant</div>
                        </div>
                        <div class="user-type" data-type="delivery">
                            <i class="fas fa-motorcycle"></i>
                            <div>Delivery</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" id="signup-name" class="form-input" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <input type="email" id="signup-email" class="form-input" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <input type="tel" id="signup-phone" class="form-input" placeholder="Enter your phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="password-group">
                            <input type="password" id="signup-password" class="form-input" placeholder="Create a password (min. 6 characters)" required minlength="6">
                            <button class="password-toggle" data-target="signup-password">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button class="auth-btn" id="signup-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </div>
                
                <div class="signin-note">
                    <a href="#" class="signin-link" id="saved-address-link">
                        Sign in for saved address <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php else: ?>
                <div class="user-actions">
                    <?php if($_SESSION['user_type'] === 'admin'): ?>
                        <a href="admin.php" class="admin-panel-btn">
                            <i class="fas fa-users-cog"></i> Admin Panel
                        </a>
                    <?php endif; ?>
                    <a href="?logout=1" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="features">
                <div class="feature">
                    <i class="fas fa-shipping-fast"></i>
                    <div class="feature-text">Fast Delivery</div>
                </div>
                <div class="feature">
                    <i class="fas fa-utensils"></i>
                    <div class="feature-text">Best Restaurants</div>
                </div>
                <div class="feature">
                    <i class="fas fa-tag"></i>
                    <div class="feature-text">Great Deals</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Application State
        let currentUser = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION) : 'null'; ?>;
        let selectedUserType = 'customer';
        let selectedAddress = null;
        let debounceTimer;
        
        // DOM Elements
        const authOptions = document.querySelectorAll('.auth-option');
        const authForms = document.querySelectorAll('.auth-form');
        const userTypes = document.querySelectorAll('.user-type');
        const signinBtn = document.getElementById('signin-btn');
        const signupBtn = document.getElementById('signup-btn');
        const passwordToggles = document.querySelectorAll('.password-toggle');
        const addressInput = document.getElementById('delivery-address');
        const suggestionsContainer = document.getElementById('suggestions-container');
        const continueBtn = document.getElementById('continue-btn');
        const loadingSpinner = document.getElementById('loading-spinner');
        const searchExamples = document.querySelectorAll('.search-examples span');
        
        // Sweet Alert Configuration
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        
        // User Type Selection
        userTypes.forEach(type => {
            type.addEventListener('click', () => {
                userTypes.forEach(t => t.classList.remove('selected'));
                type.classList.add('selected');
                selectedUserType = type.dataset.type;
            });
        });
        
        // Authentication Functions
        authOptions.forEach(option => {
            option.addEventListener('click', () => {
                authOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                
                authForms.forEach(form => form.classList.remove('active'));
                document.getElementById(`${option.dataset.form}-form`).classList.add('active');
            });
        });
        
        passwordToggles.forEach(toggle => {
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
        
        // Search Examples
        searchExamples.forEach(example => {
            example.addEventListener('click', () => {
                addressInput.value = example.dataset.search;
                performSearch();
            });
        });
        
        // Sign In Function with Sweet Alert
        signinBtn.addEventListener('click', () => {
            const email = document.getElementById('signin-email').value;
            const password = document.getElementById('signin-password').value;
            
            if (!email || !password) {
                Toast.fire({
                    icon: 'error',
                    title: 'Please fill in all fields'
                });
                return;
            }
            
            setLoading('signin-btn', true);
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);
            
            fetch('', {
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
                        window.location.reload();
                    }, 1500);
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message
                    });
                    setLoading('signin-btn', false);
                }
            })
            .catch(error => {
                Toast.fire({
                    icon: 'error',
                    title: 'Login failed'
                });
                setLoading('signin-btn', false);
                console.error('Login error:', error);
            });
        });
        
        // Sign Up Function with Sweet Alert
        signupBtn.addEventListener('click', () => {
            const name = document.getElementById('signup-name').value;
            const email = document.getElementById('signup-email').value;
            const phone = document.getElementById('signup-phone').value;
            const password = document.getElementById('signup-password').value;
            
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
            
            setLoading('signup-btn', true);
            
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('email', email);
            formData.append('password', password);
            formData.append('full_name', name);
            formData.append('phone', phone);
            formData.append('user_type', selectedUserType);
            
            fetch('', {
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
                        window.location.reload();
                    }, 1500);
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message
                    });
                    setLoading('signup-btn', false);
                }
            })
            .catch(error => {
                Toast.fire({
                    icon: 'error',
                    title: 'Registration failed'
                });
                setLoading('signup-btn', false);
                console.error('Registration error:', error);
            });
        });
        
        // Address Search Functions
        async function geocodeAddress(query) {
            if (!query || query.length < 2) return [];
            
            try {
                loadingSpinner.style.display = 'block';
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=6`);
                if (!response.ok) throw new Error('Geocoding service unavailable');
                const data = await response.json();
                return data.map(item => ({
                    id: item.place_id,
                    name: item.display_name.split(',')[0],
                    details: item.display_name,
                    lat: item.lat,
                    lon: item.lon,
                    type: item.type
                }));
            } catch (error) {
                return getFallbackResults(query);
            } finally {
                loadingSpinner.style.display = 'none';
            }
        }
        
        function getFallbackResults(query) {
            const queryLower = query.toLowerCase();
            const fallbackData = [
                { id: '1', name: 'New York City', details: 'New York, NY, USA', lat: '40.7128', lon: '-74.0060', type: 'city' },
                { id: '2', name: 'Los Angeles', details: 'Los Angeles, CA, USA', lat: '34.0522', lon: '-118.2437', type: 'city' },
                { id: '3', name: 'Chicago', details: 'Chicago, IL, USA', lat: '41.8781', lon: '-87.6298', type: 'city' }
            ];
            return fallbackData.filter(item => item.name.toLowerCase().includes(queryLower));
        }
        
        function displaySuggestions(suggestions) {
            suggestionsContainer.innerHTML = '';
            if (suggestions.length === 0) {
                suggestionsContainer.innerHTML = `<div class="no-suggestions"><i class="fas fa-search"></i>No addresses found</div>`;
            } else {
                suggestions.forEach(address => {
                    const item = document.createElement('div');
                    item.className = 'suggestion-item';
                    item.innerHTML = `<i class="fas fa-map-marker-alt suggestion-icon"></i><div class="suggestion-text"><div class="suggestion-name">${address.name}</div><div class="suggestion-details">${address.details}</div></div>`;
                    item.addEventListener('click', () => {
                        selectAddress(address);
                        addressInput.value = address.details;
                        suggestionsContainer.style.display = 'none';
                    });
                    suggestionsContainer.appendChild(item);
                });
            }
            suggestionsContainer.style.display = 'block';
        }
        
        function selectAddress(address) {
            selectedAddress = address;
            if (currentUser) {
                const formData = new FormData();
                formData.append('action', 'save_address');
                formData.append('address', address.details);
                fetch('', { method: 'POST', body: formData });
            }
        }
        
        function performSearch() {
            const query = addressInput.value.trim();
            if (query.length < 2) {
                suggestionsContainer.style.display = 'none';
                selectedAddress = null;
                return;
            }
            geocodeAddress(query).then(displaySuggestions);
        }
        
        // Event listeners
        addressInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(performSearch, 400);
        });
        
        addressInput.addEventListener('focus', () => {
            if (addressInput.value.length >= 2) performSearch();
        });
        
        document.addEventListener('click', (e) => {
            if (!addressInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
        
        continueBtn.addEventListener('click', () => {
            if (addressInput.value.trim()) {
                if (currentUser) {
                    const addressParam = encodeURIComponent(addressInput.value);
                    window.location.href = `restaurants.php?address=${addressParam}`;
                } else {
                    const addressParam = encodeURIComponent(addressInput.value);
                    window.location.href = `restaurants.php?address=${addressParam}`;
                }
            } else {
                Toast.fire({
                    icon: 'error',
                    title: 'Please enter a delivery address'
                });
            }
        });
        
        // Helper Functions
        function setLoading(buttonId, isLoading) {
            const button = document.getElementById(buttonId);
            if (isLoading) {
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                button.disabled = true;
            } else {
                if (buttonId === 'signin-btn') {
                    button.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
                } else {
                    button.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
                }
                button.disabled = false;
            }
        }
        
        // Initialize
        console.log('🍕 DoorDash App Loaded with Sweet Alerts!');
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