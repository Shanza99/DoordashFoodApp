<?php
require_once 'config.php';

// Redirect if not restaurant owner
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant' || !isset($_SESSION['restaurant_id'])) {
    header('Location: merchant_register.php');
    exit;
}

$restaurant_id = $_SESSION['restaurant_id'];
$restaurant = null;

// Get restaurant data
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    header('Location: merchant_register.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_restaurant') {
    header('Content-Type: application/json');
    
    $name = trim($_POST['restaurant_name']);
    $cuisine_type = trim($_POST['cuisine_type']);
    $description = trim($_POST['description']);
    $delivery_time = trim($_POST['delivery_time']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    
    try {
        $stmt = $pdo->prepare("UPDATE restaurants SET name = ?, description = ?, cuisine_type = ?, delivery_time = ?, delivery_fee = ? WHERE id = ?");
        $stmt->execute([$name, $description, $cuisine_type, $delivery_time, $delivery_fee, $restaurant_id]);
        
        echo json_encode(['success' => true, 'message' => 'Restaurant information updated successfully!']);
        
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
    <title>Edit Restaurant - DoorDash</title>
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

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
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
            <h1 class="page-title">
                <i class="fas fa-edit"></i>
                Edit Restaurant Information
            </h1>
            <p class="page-subtitle">Update your restaurant details</p>
        </div>

        <div class="form-container">
            <a href="restaurant_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <form id="restaurantEditForm" method="POST">
                <input type="hidden" name="action" value="update_restaurant">
                
                <div class="form-section">
                    <h3 class="section-title">Restaurant Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="restaurant_name">Restaurant Name *</label>
                            <input type="text" id="restaurant_name" name="restaurant_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cuisine_type">Cuisine Type *</label>
                            <select id="cuisine_type" name="cuisine_type" class="form-select" required>
                                <option value="">Select Cuisine</option>
                                <option value="American" <?php echo $restaurant['cuisine_type'] === 'American' ? 'selected' : ''; ?>>American</option>
                                <option value="Italian" <?php echo $restaurant['cuisine_type'] === 'Italian' ? 'selected' : ''; ?>>Italian</option>
                                <option value="Mexican" <?php echo $restaurant['cuisine_type'] === 'Mexican' ? 'selected' : ''; ?>>Mexican</option>
                                <option value="Chinese" <?php echo $restaurant['cuisine_type'] === 'Chinese' ? 'selected' : ''; ?>>Chinese</option>
                                <option value="Japanese" <?php echo $restaurant['cuisine_type'] === 'Japanese' ? 'selected' : ''; ?>>Japanese</option>
                                <option value="Indian" <?php echo $restaurant['cuisine_type'] === 'Indian' ? 'selected' : ''; ?>>Indian</option>
                                <option value="Thai" <?php echo $restaurant['cuisine_type'] === 'Thai' ? 'selected' : ''; ?>>Thai</option>
                                <option value="Mediterranean" <?php echo $restaurant['cuisine_type'] === 'Mediterranean' ? 'selected' : ''; ?>>Mediterranean</option>
                                <option value="French" <?php echo $restaurant['cuisine_type'] === 'French' ? 'selected' : ''; ?>>French</option>
                                <option value="Other" <?php echo $restaurant['cuisine_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Restaurant Description</label>
                        <textarea id="description" name="description" class="form-textarea" 
                                  placeholder="Describe your restaurant..."><?php echo htmlspecialchars($restaurant['description']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="delivery_time">Delivery Time *</label>
                            <select id="delivery_time" name="delivery_time" class="form-select" required>
                                <option value="15-25 min" <?php echo $restaurant['delivery_time'] === '15-25 min' ? 'selected' : ''; ?>>15-25 min</option>
                                <option value="20-30 min" <?php echo $restaurant['delivery_time'] === '20-30 min' ? 'selected' : ''; ?>>20-30 min</option>
                                <option value="25-35 min" <?php echo $restaurant['delivery_time'] === '25-35 min' ? 'selected' : ''; ?>>25-35 min</option>
                                <option value="30-40 min" <?php echo $restaurant['delivery_time'] === '30-40 min' ? 'selected' : ''; ?>>30-40 min</option>
                                <option value="35-45 min" <?php echo $restaurant['delivery_time'] === '35-45 min' ? 'selected' : ''; ?>>35-45 min</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="delivery_fee">Delivery Fee *</label>
                            <input type="number" id="delivery_fee" name="delivery_fee" class="form-input" step="0.01" min="0" 
                                   value="<?php echo $restaurant['delivery_fee']; ?>" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Update Restaurant Information
                </button>
            </form>
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

        document.getElementById('restaurantEditForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            fetch('restaurant_edit.php', {
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
                        window.location.href = 'restaurant_dashboard.php';
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
                    title: 'Update failed. Please try again.'
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        console.log('🏪 Restaurant Edit Loaded!');
    </script>
</body>
</html>