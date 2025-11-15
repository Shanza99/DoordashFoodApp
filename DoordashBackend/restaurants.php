<?php
require_once 'config.php';

// Handle address from index.php or URL parameter
$delivery_address = '';
$is_location_search = isset($_GET['location_search']);
$original_search = '';

if (isset($_GET['address']) && !empty($_GET['address'])) {
    $delivery_address = urldecode($_GET['address']);
} elseif ($is_location_search && isset($_GET['search']) && !empty($_GET['search'])) {
    $delivery_address = urldecode($_GET['search']);
    $original_search = $delivery_address;
}

// Save address to session/database if provided
if (!empty($delivery_address)) {
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
            $stmt->execute([$delivery_address, $_SESSION['user_id']]);
            $_SESSION['user_address'] = $delivery_address;
        } catch(PDOException $e) {
            error_log("Address save error: " . $e->getMessage());
        }
    } else {
        $_SESSION['guest_address'] = $delivery_address;
    }
}

// Get current address for display
if (!empty($delivery_address)) {
    $current_address = $delivery_address;
} elseif (isset($_SESSION['user_address'])) {
    $current_address = $_SESSION['user_address'];
} elseif (isset($_SESSION['guest_address'])) {
    $current_address = $_SESSION['guest_address'];
} else {
    $current_address = '';
}

$search = $_GET['search'] ?? '';
$cuisine = $_GET['cuisine'] ?? '';
$sort = $_GET['sort'] ?? 'rating';

// Get restaurants based on search criteria
try {
    $sql = "SELECT * FROM restaurants WHERE is_active = TRUE";
    $params = [];
    $search_message = '';
    $has_search_filter = false;
    $location_based_results = false;
    
    if (!empty($search)) {
        if ($is_location_search) {
            // LOCATION-BASED SEARCH: Search in restaurant addresses
            $searchTerm = "%$search%";
            $sql .= " AND (address LIKE ? OR city LIKE ? OR state LIKE ? OR zip_code LIKE ? OR name LIKE ? OR cuisine_type LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $search_message = "restaurants in \"$search\"";
            $has_search_filter = true;
            $location_based_results = true;
            
            error_log("Location search for: $search");
        } else {
            // REGULAR SEARCH: Search in restaurant names and cuisine
            $searchTerm = "%$search%";
            $sql .= " AND (name LIKE ? OR cuisine_type LIKE ? OR description LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            $search_message = "restaurants matching \"$search\"";
            $has_search_filter = true;
        }
    }
    
    if (!empty($cuisine)) {
        $sql .= " AND cuisine_type LIKE ?";
        $params[] = "%$cuisine%";
        $has_search_filter = true;
        
        if (!empty($search_message)) {
            $search_message .= " and cuisine \"$cuisine\"";
        } else {
            $search_message = "restaurants with cuisine \"$cuisine\"";
        }
    }
    
    // Add sorting
    switch($sort) {
        case 'delivery_time':
            $sql .= " ORDER BY delivery_time ASC";
            break;
        case 'delivery_fee':
            $sql .= " ORDER BY delivery_fee ASC";
            break;
        case 'rating':
        default:
            $sql .= " ORDER BY rating DESC, review_count DESC";
            break;
    }
    
    error_log("SQL: $sql");
    error_log("Params: " . print_r($params, true));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($restaurants) . " restaurants");
    
    // If no restaurants found with location search, try broader search
    $showing_all_due_to_no_results = false;
    if (count($restaurants) === 0 && $location_based_results) {
        error_log("No restaurants found for location search, showing all restaurants");
        $showing_all_due_to_no_results = true;
        
        // Get all active restaurants without location filter
        $sql_all = "SELECT * FROM restaurants WHERE is_active = TRUE";
        switch($sort) {
            case 'delivery_time': 
                $sql_all .= " ORDER BY delivery_time ASC"; 
                break;
            case 'delivery_fee': 
                $sql_all .= " ORDER BY delivery_fee ASC"; 
                break;
            default: 
                $sql_all .= " ORDER BY rating DESC, review_count DESC"; 
                break;
        }
        
        $stmt_all = $pdo->query($sql_all);
        $restaurants = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get unique cuisine types
    $cuisineStmt = $pdo->query("SELECT DISTINCT cuisine_type FROM restaurants WHERE is_active = TRUE ORDER BY cuisine_type");
    $cuisineTypes = $cuisineStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    error_log("Database error in restaurants.php: " . $e->getMessage());
    $restaurants = [];
    $cuisineTypes = [];
    $search_message = '';
    $showing_all_due_to_no_results = false;
}

// Determine search results message
$results_count = count($restaurants);
if ($showing_all_due_to_no_results) {
    $results_message = "No restaurants found for \"$original_search\". Showing all $results_count restaurants instead";
} elseif (!empty($search_message)) {
    $results_message = "Found $results_count $search_message";
} else {
    $results_message = "Showing all $results_count restaurants";
}
?>

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
            max-width: 1200px;
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

        .search-results-info {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            text-align: center;
        }

        .results-count {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .fallback-message {
            background: #fff3cd;
            color: #856404;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
            font-size: 14px;
        }

        .suggest-alternative {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        .suggest-alternative a {
            color: #FF3008;
            font-weight: 600;
            text-decoration: none;
        }

        .suggest-alternative a:hover {
            text-decoration: underline;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-input, .filter-select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-btn {
            padding: 10px 20px;
            background: #FF3008;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .restaurants-grid {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .restaurant-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .restaurant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 48, 8, 0.2);
            border-color: #FF3008;
        }

        .restaurant-image {
            height: 200px;
            background: linear-gradient(135deg, #FF3008 0%, #FF6B6B 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            position: relative;
        }

        .restaurant-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #FF3008;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .restaurant-content {
            padding: 20px;
        }

        .restaurant-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }

        .restaurant-cuisine {
            color: #666;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .restaurant-description {
            color: #888;
            margin-bottom: 15px;
            line-height: 1.4;
            font-size: 14px;
        }

        .restaurant-address {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            font-style: italic;
        }

        .restaurant-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .restaurant-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .restaurant-rating i {
            color: #FFD700;
        }

        .restaurant-delivery {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 14px;
        }

        .restaurant-delivery i {
            color: #FF3008;
        }

        .view-menu-btn {
            width: 100%;
            padding: 12px;
            background: #FF3008;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-menu-btn:hover {
            background: #e02a07;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #666;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #ddd;
        }

        .empty-state-actions {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 24px;
            background: #FF3008;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .action-btn:hover {
            background: #e02a07;
            transform: translateY(-2px);
        }

        .action-btn.secondary {
            background: #6c757d;
        }

        .action-btn.secondary:hover {
            background: #5a6268;
        }

        .address-display {
            background: #e7f3ff;
            padding: 15px 30px;
            border-bottom: 1px solid #b3d9ff;
        }

        .address-display-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .address-text {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .change-address-btn {
            color: #FF3008;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            border: 1px solid #FF3008;
        }

        .change-address-btn:hover {
            background: rgba(255, 48, 8, 0.1);
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .restaurants-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .address-display-content {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .empty-state-actions {
                flex-direction: column;
                align-items: center;
            }

            .action-btn {
                width: 200px;
                text-align: center;
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
                <a href="restaurants.php" class="nav-link active">Restaurants</a>
                <a href="orders.php" class="nav-link">My Orders</a>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <span style="color: #666; font-size: 14px;">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Sign In</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Address Display -->
        <?php if (!empty($current_address)): ?>
        <div class="address-display">
            <div class="address-display-content">
                <div class="address-text">
                    <i class="fas fa-map-marker-alt" style="color: #FF3008;"></i>
                    <strong>Delivering to:</strong> <?php echo htmlspecialchars($current_address); ?>
                </div>
                <a href="index.php" class="change-address-btn">
                    <i class="fas fa-edit"></i> Change Address
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search Results Info -->
        <div class="search-results-info">
            <div class="results-count"><?php echo $results_message; ?></div>
            
            <?php if ($showing_all_due_to_no_results): ?>
            <div class="fallback-message">
                <i class="fas fa-info-circle"></i>
                No restaurants found specifically for "<?php echo htmlspecialchars($original_search); ?>". 
                Showing all available restaurants instead.
            </div>
            <?php endif; ?>
        </div>

        <div class="page-header">
            <h1>Discover Restaurants</h1>
            <p>Find your favorite food delivered <?php echo !empty($current_address) ? 'to ' . htmlspecialchars($current_address) : 'to your location'; ?></p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="filter-grid">
                <!-- Preserve location search flag and address -->
                <?php if ($is_location_search): ?>
                <input type="hidden" name="location_search" value="true">
                <?php endif; ?>
                <?php if (!empty($current_address)): ?>
                <input type="hidden" name="address" value="<?php echo htmlspecialchars($current_address); ?>">
                <?php endif; ?>
                
                <div class="filter-group">
                    <label class="filter-label">Search Restaurants</label>
                    <input type="text" name="search" class="filter-input" placeholder="Search by name, cuisine, or location..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Cuisine Type</label>
                    <select name="cuisine" class="filter-select">
                        <option value="">All Cuisines</option>
                        <?php foreach($cuisineTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $cuisine === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" class="filter-select">
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        <option value="delivery_time" <?php echo $sort === 'delivery_time' ? 'selected' : ''; ?>>Fastest Delivery</option>
                        <option value="delivery_fee" <?php echo $sort === 'delivery_fee' ? 'selected' : ''; ?>>Lowest Delivery Fee</option>
                    </select>
                </div>
                
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Restaurants Grid -->
        <div class="restaurants-grid">
            <?php if(count($restaurants) > 0): ?>
                <?php foreach($restaurants as $restaurant): ?>
                    <div class="restaurant-card" onclick="window.location.href='restaurant_menu.php?id=<?php echo $restaurant['id']; ?>&address=<?php echo urlencode($current_address); ?>'">
                        <div class="restaurant-image">
                            <i class="fas fa-utensils"></i>
                            <?php if($restaurant['featured']): ?>
                                <div class="restaurant-badge">Featured</div>
                            <?php endif; ?>
                        </div>
                        <div class="restaurant-content">
                            <div class="restaurant-name"><?php echo htmlspecialchars($restaurant['name']); ?></div>
                            <div class="restaurant-cuisine"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></div>
                            
                            <?php if(!empty($restaurant['address'])): ?>
                            <div class="restaurant-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php 
                                $display_address = $restaurant['address'];
                                if (!empty($restaurant['city'])) {
                                    $display_address .= ', ' . $restaurant['city'];
                                }
                                if (!empty($restaurant['state'])) {
                                    $display_address .= ', ' . $restaurant['state'];
                                }
                                echo htmlspecialchars($display_address); 
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="restaurant-description"><?php echo htmlspecialchars($restaurant['description'] ?: 'Delicious food delivered fast'); ?></div>
                            
                            <div class="restaurant-details">
                                <div class="restaurant-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo $restaurant['rating']; ?> (<?php echo $restaurant['review_count']; ?>)
                                </div>
                                <div class="restaurant-delivery">
                                    <i class="fas fa-clock"></i>
                                    <?php echo $restaurant['delivery_time']; ?>
                                </div>
                            </div>
                            
                            <div class="restaurant-details">
                                <div style="color: #666; font-size: 14px;">
                                    <i class="fas fa-motorcycle"></i> $<?php echo $restaurant['delivery_fee']; ?> delivery
                                </div>
                                <div style="color: #28a745; font-weight: 600;">
                                    $•$$
                                </div>
                            </div>
                            
                            <button class="view-menu-btn" onclick="event.stopPropagation(); window.location.href='restaurant_menu.php?id=<?php echo $restaurant['id']; ?>&address=<?php echo urlencode($current_address); ?>'">
                                View Menu & Order
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <h3>No Restaurants Available</h3>
                    <p>We couldn't find any restaurants at the moment.</p>
                    
                    <div class="empty-state-actions">
                        <a href="index.php" class="action-btn">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Make entire restaurant card clickable
        document.querySelectorAll('.restaurant-card').forEach(card => {
            card.style.cursor = 'pointer';
        });

        <?php if(isset($_GET['logout'])): ?>
            window.location.href = 'index.php?logout=1';
        <?php endif; ?>
    </script>
</body>
</html>