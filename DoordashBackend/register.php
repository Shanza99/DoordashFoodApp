<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = sanitize_input($_POST['full_name']);
    $phone = sanitize_input($_POST['phone']);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $password, $full_name, $phone]);
        
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $full_name;
        
        header('Location: index.php');
        exit();
    } catch(PDOException $e) {
        $error = "Email already exists or invalid input.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - DoorDash</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>DoorDash</h1>
            </div>
        </div>
    </header>

    <section class="auth-section">
        <div class="container">
            <div class="auth-form">
                <h2>Sign Up</h2>
                <?php if(isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="tel" name="phone" placeholder="Phone Number">
                    <button type="submit" class="btn-primary">Create Account</button>
                </form>
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </section>
</body>
</html>