<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) {
        $_SESSION['redirect_to'] = 'restaurant.php?id=' . $_POST['restaurant_id'];
        header('Location: login.php');
        exit();
    }

    $menu_item_id = $_POST['menu_item_id'];
    $restaurant_id = $_POST['restaurant_id'];
    $user_id = get_user_id();

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add item to cart
    if (isset($_SESSION['cart'][$menu_item_id])) {
        $_SESSION['cart'][$menu_item_id]['quantity']++;
    } else {
        // Get menu item details
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$menu_item_id]);
        $menu_item = $stmt->fetch();

        $_SESSION['cart'][$menu_item_id] = [
            'id' => $menu_item_id,
            'name' => $menu_item['name'],
            'price' => $menu_item['price'],
            'quantity' => 1,
            'restaurant_id' => $restaurant_id
        ];
    }

    header('Location: cart.php');
    exit();
}
?>