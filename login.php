<?php

require 'inc/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = :username");
    $stmt->execute(['username' => $username]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['user_id'] = $user['Id'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role'];
        
        // Insert login time into user_logs table
        $loginQuery = "INSERT INTO user_logs (user_id, username, login_time) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($loginQuery);
        $stmt->execute([$user['Id'], $user['Username']]);
        
        $_SESSION['show_greeting'] = "Welcome back, " . $user['Username'] . "!";
        
        header("Location: index.php");
        exit();
    } else {
        $error_message = "<p class='error-msg'>Invalid username or password.</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATI Office Login</title>
  <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
  <div class="login-container">
    <div class="login">
      <h2>User Login</h2>
    <?php if ($error_message): ?>
        <p class="error-msg;"><?php echo $error_message; ?></p>
    <?php endif; ?>
      <form action="login.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="Enter your username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required><br>
        <input type="submit" value="Login" class="btn">
      </form>
    </div>
  </div>
</body>
</html>