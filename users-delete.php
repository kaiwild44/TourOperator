<?php

include('inc/db.php');

if(session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = $_GET['id'];
} else {
    header("Location: users.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: users.php");
        exit();
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    include('inc/header.php');

// Check if the user is not Superadmin or Admin or Sales_Manager
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

?>

<h2>Delete Confirmation</h2>
<p class="my-10">Are you sure you want to delete this user?</p>
<form method="post">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($userId); ?>">
    <input type="submit" name="confirm" value="Delete" class="btn my-10">
    <a href="users.php" class="my-10">Cancel</a>
</form>

<?php
    include('inc/footer.php');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {

    $query = "SELECT Image FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $imagePath = $result['Image'];

        if (!empty($imagePath) && file_exists($imagePath)) {
            if (unlink($imagePath)) {
            } else {
                die('Error: Failed to delete the file from the server.');
            }
        }

        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $userId);
        if ($stmt->execute()) {
            header('Location: users.php');
            exit();
        } else {
            die('Error: Failed to execute the delete query.');
        }
    } else {
        die('Error: Record not found.');
    }
} else {
    die('Error: Invalid request.');
}
?>