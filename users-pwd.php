<?php
include('inc/header.php');

// Check if the user is not Superadmin or Admin or Sales_Manager
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $repeatPassword = $_POST['repeat_password'];

    if ($newPassword !== $repeatPassword) {
        $errorRepeatPassword = "Passwords don't match.";
    }

    if (empty($errorRepeatPassword)) {

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $userId = $_GET['id'];
        $stmt = $pdo->prepare("UPDATE users SET Password = :password WHERE Id = :id");
        $result = $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);

        if ($result) {

            $_SESSION['password_change_success'] = "Password successfully changed.";
            echo "DEBUG: Password successfully updated in the database.";

            header("Location: users.php");
            exit();
        } else {
            echo "DEBUG: Error updating password in the database.";
        }
    }
}
?>

<h1 class="text-center my-10">Change Password</h1>

<div class="users">
    <?php
    if (isset($_SESSION['password_change_success'])) {
        echo "<div class='success'>{$_SESSION['password_change_success']}</div>";
        unset($_SESSION['password_change_success']);
    }

    if (isset($errorRepeatPassword)) {
        echo "<div class='error'>$errorRepeatPassword</div>";
        unset($errorRepeatPassword);
    }
    ?>
    <form method="POST" action="">
        <div>
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div>
            <label for="repeat_password">Repeat Password:</label>
            <input type="password" id="repeat_password" name="repeat_password" required>
        </div>
        <button type="submit" class="btn">Change Password</button>
    </form>
</div>

<?php include('inc/footer.php'); ?>
