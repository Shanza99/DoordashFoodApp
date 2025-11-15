<?php
require_once 'config.php';

// Handle form submission first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $restaurant_id = $_SESSION['restaurant_id'] ?? null;
    
    if (!$restaurant_id) {
        echo json_encode(['success' => false, 'message' => 'Restaurant not found']);
        exit;
    }
    
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    
    try {
        if ($_POST['action'] === 'add_menu_item') {
            // Handle image upload
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'menu_item_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, category, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$restaurant_id, $item_name, $description, $price, $category, $image_url]);
            
            echo json_encode(['success' => true, 'message' => 'Menu item added successfully!']);
            
        } elseif ($_POST['action'] === 'update_menu_item' && isset($_POST['item_id'])) {
            $item_id = intval($_POST['item_id']);
            
            // Check if item belongs to restaurant
            $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$item_id, $restaurant_id]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Menu item not found']);
                exit;
            }
            
            // Handle image upload
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'menu_item_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                }
            }
            
            if ($image_url) {
                $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$item_name, $description, $price, $category, $image_url, $item_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ? WHERE id = ?");
                $stmt->execute([$item_name, $description, $price, $category, $item_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Menu item updated successfully!']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Redirect if not restaurant owner
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant') {
    header('Location: merchant_register.php');
    exit;
}

$restaurant_id = $_SESSION['restaurant_id'] ?? null;
$edit_item = null;

// Check if editing existing item
if (isset($_GET['edit']) && $restaurant_id) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND restaurant_id = ?");
    $stmt->execute([$_GET['edit'], $restaurant_id]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - DoorDash</title>
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

        .image-upload {
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-upload:hover {
            border-color: #FF3008;
            background: #fff5f5;
        }

        .image-upload i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 15px auto;
            display: none;
            border-radius: 8px;
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
    </style>
</head>
<body>
    <div class="wide-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-utensils"></i>
                <?php echo $edit_item ? 'Edit Menu Item' : 'Add Menu Item'; ?>
            </h1>
            <p class="page-subtitle">Manage your restaurant's menu items</p>
        </div>

        <div class="form-container">
            <a href="restaurant_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <form id="menuItemForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_item ? 'update_menu_item' : 'add_menu_item'; ?>">
                <?php if($edit_item): ?>
                    <input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>">
                <?php endif; ?>

                <div class="form-section">
                    <h3 class="section-title">Item Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="item_name">Item Name *</label>
                        <input type="text" id="item_name" name="item_name" class="form-input" 
                               value="<?php echo $edit_item ? htmlspecialchars($edit_item['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-textarea" 
                                  placeholder="Describe your menu item..."><?php echo $edit_item ? htmlspecialchars($edit_item['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="price">Price *</label>
                        <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" 
                               value="<?php echo $edit_item ? $edit_item['price'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category">Category *</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Appetizers" <?php echo ($edit_item && $edit_item['category'] === 'Appetizers') ? 'selected' : ''; ?>>Appetizers</option>
                            <option value="Main Course" <?php echo ($edit_item && $edit_item['category'] === 'Main Course') ? 'selected' : ''; ?>>Main Course</option>
                            <option value="Desserts" <?php echo ($edit_item && $edit_item['category'] === 'Desserts') ? 'selected' : ''; ?>>Desserts</option>
                            <option value="Beverages" <?php echo ($edit_item && $edit_item['category'] === 'Beverages') ? 'selected' : ''; ?>>Beverages</option>
                            <option value="Sides" <?php echo ($edit_item && $edit_item['category'] === 'Sides') ? 'selected' : ''; ?>>Sides</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Item Image</h3>
                    
                    <div class="image-upload" onclick="document.getElementById('image_upload').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload item image</p>
                        <p style="font-size: 12px; color: #999;">Recommended: 500x500px, JPG or PNG</p>
                        <input type="file" id="image_upload" name="image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                    
                    <img id="image_preview" class="image-preview" 
                         src="<?php echo $edit_item && $edit_item['image_url'] ? htmlspecialchars($edit_item['image_url']) : ''; ?>" 
                         alt="Image preview">
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i>
                    <?php echo $edit_item ? 'Update Menu Item' : 'Add Menu Item'; ?>
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

        function previewImage(input) {
            const preview = document.getElementById('image_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('menuItemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            fetch('menu_management.php', {
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
                    title: 'Operation failed. Please try again.'
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Show existing image if editing
        <?php if($edit_item && $edit_item['image_url']): ?>
            document.getElementById('image_preview').style.display = 'block';
        <?php endif; ?>

        console.log('📝 Menu Management Loaded!');
    </script>
</body>
</html>