<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants - DoorDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .restaurants-content {
            padding: 30px;
        }

        .location-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .location-text {
            flex: 1;
        }

        .location-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .location-address {
            font-size: 14px;
            color: #666;
        }

        .location-change {
            color: #FF3008;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 8px 16px;
            border: 1px solid #FF3008;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .location-change:hover {
            background: #FF3008;
            color: white;
        }

        .restaurant-list {
            margin-top: 20px;
        }

        .restaurant-card {
            display: flex;
            padding: 25px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .restaurant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #FF3008;
        }

        .restaurant-image {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            margin-right: 25px;
            flex-shrink: 0;
        }

        .restaurant-info {
            flex: 1;
        }

        .restaurant-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .restaurant-cuisine {
            font-size: 16px;
            color: #666;
            margin-bottom: 12px;
        }

        .restaurant-description {
            font-size: 14px;
            color: #888;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .restaurant-details {
            display: flex;
            align-items: center;
            gap: 25px;
            font-size: 14px;
            color: #666;
        }

        .restaurant-rating {
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .restaurant-rating i {
            color: #FFD700;
            margin-right: 4px;
        }

        .restaurant-delivery {
            display: flex;
            align-items: center;
        }

        .restaurant-delivery i {
            color: #FF3008;
            margin-right: 4px;
        }

        .restaurant-fee {
            display: flex;
            align-items: center;
        }

        .restaurant-fee i {
            color: #28a745;
            margin-right: 4px;
        }

        .featured-badge {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .section-title {
            font-size: 28px;
            text-align: center;
            margin-bottom: 30px;
            color: #333;
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

            .restaurants-content {
                padding: 20px;
            }

            .restaurant-card {
                flex-direction: column;
                padding: 20px;
            }

            .restaurant-image {
                width: 100%;
                height: 140px;
                margin-right: 0;
                margin-bottom: 15px;
            }

            .location-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .restaurant-details {
                flex-wrap: wrap;
                gap: 15px;
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
                <a href="catering.php" class="nav-link">Catering</a>
                <a href="party-event.php" class="nav-link">Party & Events</a>
                <a href="restaurants.php" class="nav-link active">Restaurants</a>
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
        
        <div class="restaurants-content">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <div class="location-header">
                <div class="location-text">
                    <div class="location-title">
                        <i class="fas fa-map-marker-alt"></i> Delivery Location
                    </div>
                    <div class="location-address">
                        <?php 
                        $address = isset($_GET['address']) ? htmlspecialchars($_GET['address']) : 'Enter your delivery address';
                        echo $address;
                        ?>
                    </div>
                </div>
                <a href="index.php" class="location-change">
                    <i class="fas fa-edit"></i> Change
                </a>
            </div>

            <h2 class="section-title">Restaurants Near You</h2>
            
            <div class="restaurant-list">
                <!-- Restaurant 1 -->
                <div class="restaurant-card" onclick="window.location.href='#'">
                    <div class="restaurant-image">
                        <i class="fas fa-pizza-slice"></i>
                    </div>
                    <div class="restaurant-info">
                        <div class="restaurant-name">
                            Mario's Italian Kitchen
                            <span class="featured-badge">Featured</span>
                        </div>
                        <div class="restaurant-cuisine">Italian • Pizza • Pasta</div>
                        <div class="restaurant-description">
                            Authentic Italian cuisine with homemade pasta, wood-fired pizzas, and traditional desserts. Family-owned since 1985.
                        </div>
                        <div class="restaurant-details">
                            <div class="restaurant-rating">
                                <i class="fas fa-star"></i> 4.7 (1.2k+)
                            </div>
                            <div class="restaurant-delivery">
                                <i class="fas fa-clock"></i> 25-35 min
                            </div>
                            <div class="restaurant-fee">
                                <i class="fas fa-dollar-sign"></i> $2.99 delivery
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restaurant 2 -->
                <div class="restaurant-card" onclick="window.location.href='menu.php'">
                    <div class="restaurant-image">
                        <i class="fas fa-hamburger"></i>
                    </div>
                    <div class="restaurant-info">
                        <div class="restaurant-name">
                            Burger Palace
                        </div>
                        <div class="restaurant-cuisine">American • Burgers • Fast Food</div>
                        <div class="restaurant-description">
                            Gourmet burgers, crispy fries, and hand-spun milkshakes. All beef is 100% grass-fed and locally sourced.
                        </div>
                        <div class="restaurant-details">
                            <div class="restaurant-rating">
                                <i class="fas fa-star"></i> 4.5 (890+)
                            </div>
                            <div class="restaurant-delivery">
                                <i class="fas fa-clock"></i> 20-30 min
                            </div>
                            <div class="restaurant-fee">
                                <i class="fas fa-dollar-sign"></i> $1.99 delivery
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restaurant 3 -->
                <div class="restaurant-card" onclick="window.location.href='menu.php'">
                    <div class="restaurant-image">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="restaurant-info">
                        <div class="restaurant-name">
                            Sushi Zen
                            <span class="featured-badge">Popular</span>
                        </div>
                        <div class="restaurant-cuisine">Japanese • Sushi • Asian</div>
                        <div class="restaurant-description">
                            Fresh sushi and authentic Japanese dishes prepared by master chefs. Experience the art of Japanese cuisine.
                        </div>
                        <div class="restaurant-details">
                            <div class="restaurant-rating">
                                <i class="fas fa-star"></i> 4.8 (1.5k+)
                            </div>
                            <div class="restaurant-delivery">
                                <i class="fas fa-clock"></i> 30-45 min
                            </div>
                            <div class="restaurant-fee">
                                <i class="fas fa-dollar-sign"></i> $3.49 delivery
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restaurant 4 -->
                <div class="restaurant-card" onclick="window.location.href='menu.php'">
                    <div class="restaurant-image">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="restaurant-info">
                        <div class="restaurant-name">
                            Green Garden
                        </div>
                        <div class="restaurant-cuisine">Vegetarian • Healthy • Salads</div>
                        <div class="restaurant-description">
                            Fresh, organic vegetarian and vegan options. All ingredients are locally sourced and sustainably grown.
                        </div>
                        <div class="restaurant-details">
                            <div class="restaurant-rating">
                                <i class="fas fa-star"></i> 4.6 (750+)
                            </div>
                            <div class="restaurant-delivery">
                                <i class="fas fa-clock"></i> 25-40 min
                            </div>
                            <div class="restaurant-fee">
                                <i class="fas fa-dollar-sign"></i> $2.49 delivery
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple restaurant card click handler
        document.querySelectorAll('.restaurant-card').forEach(card => {
            card.addEventListener('click', function() {
                // In a real app, this would navigate to the restaurant's menu page
                console.log('Navigating to restaurant menu');
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