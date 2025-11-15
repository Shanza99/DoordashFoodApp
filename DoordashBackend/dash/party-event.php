<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Party & Event Planning - DoorDash</title>
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
        
        .event-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 45px;
        }
        
        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
        }
        
        .event-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 42px;
        }
        
        .event-content {
            padding: 25px;
        }
        
        .event-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }
        
        .event-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 15px;
        }
        
        .event-features {
            list-style: none;
            margin-bottom: 20px;
        }
        
        .event-features li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .event-features li:last-child {
            border-bottom: none;
        }
        
        .event-features i {
            color: #FF3008;
            margin-right: 10px;
            width: 20px;
            font-size: 16px;
        }
        
        .event-btn {
            width: 100%;
            padding: 12px;
            background: #FF3008;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .event-btn:hover {
            background: #e02a07;
        }
        
        .planning-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 45px;
        }
        
        .step-card {
            background: white;
            padding: 30px 20px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        
        .step-number {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #FF3008;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .step-icon {
            font-size: 42px;
            color: #FF3008;
            margin-bottom: 15px;
            margin-top: 10px;
        }
        
        .step-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #333;
        }
        
        .step-description {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .gallery-section {
            background: #f8f9fa;
            padding: 50px 30px;
            border-radius: 15px;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .gallery-item {
            height: 180px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .cta-section {
            text-align: center;
            padding: 50px 30px;
        }
        
        .cta-title {
            font-size: 36px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .cta-description {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta-btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .cta-btn.primary {
            background: #FF3008;
            color: white;
        }
        
        .cta-btn.secondary {
            background: #6c757d;
            color: white;
        }
        
        .cta-btn:hover {
            opacity: 0.9;
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
            
            .event-types,
            .planning-steps,
            .gallery-grid {
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
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .cta-btn {
                width: 100%;
                max-width: 250px;
            }
            
            .event-image {
                height: 160px;
            }
            
            .gallery-item {
                height: 150px;
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
                <a href="catering.php" class="nav-link">Catering</a>
                <a href="party-event.php" class="nav-link active">Party & Events</a>
                <a href="restaurants.php" class="nav-link">Restaurants</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="page-header">
            <h1 class="page-title">Party & Event Planning</h1>
            <p class="page-subtitle">Turn your special moments into unforgettable celebrations with our expert event planning services</p>
        </div>
        
        <div class="content-section">
            <h2 class="section-title">Event Types We Specialize In</h2>
            
            <div class="event-types">
                <div class="event-card">
                    <div class="event-image" style="background-image: url('images/i.jpeg');"></div>
                    <div class="event-content">
                        <h3 class="event-name">Birthday Parties</h3>
                        <p class="event-description">Make birthdays extra special with themed decorations, custom cakes, and entertainment for all ages.</p>
                        <ul class="event-features">
                            <li><i class="fas fa-check"></i> Themed Decorations</li>
                            <li><i class="fas fa-check"></i> Custom Birthday Cakes</li>
                            <li><i class="fas fa-check"></i> Entertainment Options</li>
                            <li><i class="fas fa-check"></i> Party Favors</li>
                        </ul>
                        <button class="event-btn">Plan Birthday Party</button>
                    </div>
                </div>
                
                <div class="event-card">
                    <div class="event-image" style="background-image: url('images/l.jpeg');"></div>
                    <div class="event-content">
                        <h3 class="event-name">Anniversary Celebrations</h3>
                        <p class="event-description">Romantic settings, fine dining, and elegant touches to celebrate years of love and commitment.</p>
                        <ul class="event-features">
                            <li><i class="fas fa-check"></i> Romantic Setup</li>
                            <li><i class="fas fa-check"></i> Fine Dining Experience</li>
                            <li><i class="fas fa-check"></i> Customized Menus</li>
                            <li><i class="fas fa-check"></i> Photography Services</li>
                        </ul>
                        <button class="event-btn">Plan Anniversary</button>
                    </div>
                </div>
                
                <div class="event-card">
                    <div class="event-image" style="background-image: url('images/m.jpeg');"></div>
                    <div class="event-content">
                        <h3 class="event-name">Graduation Parties</h3>
                        <p class="event-description">Celebrate academic achievements with fun themes, great food, and memorable experiences.</p>
                        <ul class="event-features">
                            <li><i class="fas fa-check"></i> School Color Themes</li>
                            <li><i class="fas fa-check"></i> Buffet-Style Dining</li>
                            <li><i class="fas fa-check"></i> Diploma Cake</li>
                            <li><i class="fas fa-check"></i> Memory Wall</li>
                        </ul>
                        <button class="event-btn">Plan Graduation</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-section">
            <h2 class="section-title">Our Event Planning Process</h2>
            
            <div class="planning-steps">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="step-title">Consultation</h3>
                    <p class="step-description">We discuss your vision, budget, and requirements to understand your perfect event.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="step-title">Planning</h3>
                    <p class="step-description">Our team creates a detailed plan including timeline, vendors, and all event elements.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3 class="step-title">Execution</h3>
                    <p class="step-description">We bring your vision to life, managing every detail while you enjoy the celebration.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="step-title">Enjoyment</h3>
                    <p class="step-description">You relax and create beautiful memories while we handle all the logistics.</p>
                </div>
            </div>
        </div>
        
        <div class="content-section">
            <div class="gallery-section">
                <h2 class="section-title">Event Gallery</h2>
                <div class="gallery-grid">
                    <div class="gallery-item" style="background-image: url('images/i.jpeg');"></div>
                    <div class="gallery-item" style="background-image: url('images/l.jpeg');"></div>
                    <div class="gallery-item" style="background-image: url('images/m.jpeg');"></div>
                    <div class="gallery-item" style="background-image: url('images/n.jpeg');"></div>
                </div>
            </div>
        </div>
        
        <div class="content-section">
            <div class="cta-section">
                <h2 class="cta-title">Ready to Plan Your Perfect Event?</h2>
                <p class="cta-description">Let our expert event planners turn your vision into reality. Contact us today to start planning your unforgettable celebration.</p>
                <!-- <div class="cta-buttons">
                    <a href="#" class="cta-btn primary">
                        <i class="fas fa-calendar-check"></i> Book Consultation
                    </a>
                    <a href="#" class="cta-btn secondary">
                        <i class="fas fa-file-invoice"></i> Get Quote
                    </a>
                </div> -->
            </div>
        </div>
    </div>
</body>
</html>