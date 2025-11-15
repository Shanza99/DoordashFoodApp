<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Services - DoorDash</title>
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
        
        .page-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 60px 30px;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-title {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .page-subtitle {
            font-size: 18px;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .content-section {
            margin: 0 auto 50px;
            padding: 0 30px;
        }
        
        .section-title {
            font-size: 32px;
            text-align: center;
            margin-bottom: 35px;
            color: #333;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 45px;
        }
        
        .feature-card {
            background: white;
            padding: 35px 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 42px;
            color: #FF3008;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-description {
            color: #666;
            line-height: 1.6;
            font-size: 15px;
        }
        
        .catering-packages {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .package-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .package-card:hover {
            transform: translateY(-3px);
        }
        
        .package-header {
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .package-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .package-price {
            font-size: 36px;
            font-weight: 800;
        }
        
        .package-details {
            padding: 25px;
        }
        
        .package-features {
            list-style: none;
            margin-bottom: 20px;
        }
        
        .package-features li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            font-size: 15px;
        }
        
        .package-features li:last-child {
            border-bottom: none;
        }
        
        .package-features i {
            color: #FF3008;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .package-btn {
            width: 100%;
            padding: 14px;
            background: #FF3008;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .package-btn:hover {
            background: #e02a07;
        }
        
        .contact-section {
            background: #f8f9fa;
            padding: 50px 30px;
            text-align: center;
            border-radius: 15px;
        }
        
        .contact-title {
            font-size: 32px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .contact-description {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .contact-btn {
            padding: 14px 35px;
            background: #FF3008;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .contact-btn:hover {
            background: #e02a07;
        }
        
        .nav-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        @media (max-width: 768px) {
            .wide-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .page-header {
                padding: 40px 20px;
            }
            
            .page-title {
                font-size: 32px;
            }
            
            .section-title {
                font-size: 26px;
            }
            
            .content-section {
                padding: 0 20px;
            }
            
            .features-grid,
            .catering-packages {
                grid-template-columns: 1fr;
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
        }
    </style>
</head>
<body>
    <div class="wide-container">
        <!-- Common Navigation Header -->
        <div class="nav-header">
            <div class="logo"><i class="fas fa-utensils"></i>DOORDASH</div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="catering.php" class="nav-link active">Catering</a>
                <a href="party-event.php" class="nav-link">Party & Events</a>
                <a href="restaurants.php" class="nav-link">Restaurants</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="page-header">
            <h1 class="page-title">Professional Catering Services</h1>
            <p class="page-subtitle">Delicious food, impeccable service, and unforgettable experiences for your special events</p>
        </div>
        
        <div class="content-section">
            <h2 class="section-title">Why Choose Our Catering?</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cheese"></i>
                    </div>
                    <h3 class="feature-title">Fresh Ingredients</h3>
                    <p class="feature-description">We use only the freshest, highest-quality ingredients sourced from local suppliers and trusted partners.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="feature-title">Professional Staff</h3>
                    <p class="feature-description">Our experienced catering team ensures flawless execution and exceptional service from start to finish.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensil-spoon"></i>
                    </div>
                    <h3 class="feature-title">Custom Menus</h3>
                    <p class="feature-description">Work with our chefs to create personalized menus that perfectly match your event theme and dietary needs.</p>
                </div>
            </div>
        </div>
        
        <div class="content-section">
            <h2 class="section-title">Catering Packages</h2>
            
            <div class="catering-packages">
                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Business Lunch</h3>
                        <div class="package-price">$25<span style="font-size: 14px;">/person</span></div>
                    </div>
                    <div class="package-details">
                        <ul class="package-features">
                            <li><i class="fas fa-check"></i> Fresh Sandwich Platters</li>
                            <li><i class="fas fa-check"></i> Seasonal Salads</li>
                            <li><i class="fas fa-check"></i> Assorted Beverages</li>
                            <li><i class="fas fa-check"></i> Cookies & Desserts</li>
                            <li><i class="fas fa-check"></i> Paper Goods Included</li>
                        </ul>
                        <button class="package-btn">Select Package</button>
                    </div>
                </div>
                
                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Wedding Celebration</h3>
                        <div class="package-price">$65<span style="font-size: 14px;">/person</span></div>
                    </div>
                    <div class="package-details">
                        <ul class="package-features">
                            <li><i class="fas fa-check"></i> 3-Course Plated Dinner</li>
                            <li><i class="fas fa-check"></i> Champagne Toast</li>
                            <li><i class="fas fa-check"></i> Wedding Cake Service</li>
                            <li><i class="fas fa-check"></i> Professional Wait Staff</li>
                            <li><i class="fas fa-check"></i> Setup & Cleanup</li>
                        </ul>
                        <button class="package-btn">Select Package</button>
                    </div>
                </div>
                
                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Corporate Event</h3>
                        <div class="package-price">$45<span style="font-size: 14px;">/person</span></div>
                    </div>
                    <div class="package-details">
                        <ul class="package-features">
                            <li><i class="fas fa-check"></i> Action Stations</li>
                            <li><i class="fas fa-check"></i> International Cuisine</li>
                            <li><i class="fas fa-check"></i> Premium Bar Service</li>
                            <li><i class="fas fa-check"></i> Branded Servers</li>
                            <li><i class="fas fa-check"></i> Event Coordination</li>
                        </ul>
                        <button class="package-btn">Select Package</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-section">
            <div class="contact-section">
                <h2 class="contact-title">Ready to Plan Your Event?</h2>
                <p class="contact-description">Contact our catering specialists today to discuss your needs and get a custom quote for your perfect event.</p>
                <!-- <a href="contact.php" class="contact-btn">
                    <i class="fas fa-calendar-alt"></i> Schedule Consultation
                </a> -->
            </div>
        </div>
    </div>
</body>
</html>