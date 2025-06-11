<?php

include('inc/header.php');

// Check if the user is not Superadmin or Admin
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: users.php");
        exit();
    }

    $dateRegistered = $user['Date_Registered'];

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$username = $first_name = $last_name = $role = $registration_date = $registration_time = '';
$error_message = '';

function generateUniqueFileName($first_name, $last_name, $extension)
{
    $date_time = date('Y-m-d_H-i-s');
    $filename = $first_name . '_' . $last_name . '_' . $date_time . '.' . $extension;
    return $filename;
}

function deleteOldImage($old_image_path)
{
    if (file_exists($old_image_path)) {
        unlink($old_image_path);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $registration_date = filter_input(INPUT_POST, 'registration_date', FILTER_SANITIZE_STRING);
    $registration_time = filter_input(INPUT_POST, 'registration_time', FILTER_SANITIZE_STRING);

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        $new_image_name = generateUniqueFileName($first_name, $last_name, $image_extension);

        $image = 'img/users/' . $new_image_name;

        deleteOldImage($user['Image']);

        if (!move_uploaded_file($image_tmp, $image)) {
            $error_message = 'Failed to upload the image.';
        }
    } else {
        $image = $user['Image'];
    }

    if (empty($error_message)) {
        try {
            $stmt = $pdo->prepare("UPDATE users 
                                SET Username = :username, First_Name = :first_name, Last_Name = :last_name, 
                                Image = :image, Role = :role, 
                                Date_Registered = :date_registered 
                                WHERE Id = :user_id");

            $registration_datetime = $registration_date . ' ' . $registration_time;

            $stmt->execute([
                ':username' => $username,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':image' => $image,
                ':role' => $role,
                ':date_registered' => $registration_datetime,
                ':user_id' => $user_id
            ]);

            header("Location: users.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>
    <div class="users">
        <h1 class="my-10">Edit User</h1>
        <form action="users-edit.php?id=<?php echo $user_id; ?>" method="POST" enctype="multipart/form-data" id="users-edit">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo $user['Username']; ?>" required><br>

            <label for="first_name">First Name:</label>
            <input type="text" name="first_name" id="first_name" value="<?php echo $user['First_Name']; ?>"><br>

            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" id="last_name" value="<?php echo $user['Last_Name']; ?>"><br>

            <label for="role">Role:</label>
            <select name="role" id="role" required>
                <option value="Superadmin" <?php echo $user['Role'] === 'Superadmin' ? 'selected' : ''; ?>>Superadmin</option>
                <option value="Admin" <?php echo $user['Role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="Sales_Manager" <?php echo $user['Role'] === 'Sales_Manager' ? 'selected' : ''; ?>>Sales Manager</option>
                <option value="Online_Manager" <?php echo $user['Role'] === 'Online_Manager' ? 'selected' : ''; ?>>Online Manager</option>
                <option value="Coordinator" <?php echo $user['Role'] === 'Coordinator' ? 'selected' : ''; ?>>Coordinator</option>
                <option value="Promoter" <?php echo $user['Role'] === 'Promoter' ? 'selected' : ''; ?>>Promoter</option>
                <option value="Street" <?php echo $user['Role'] === 'Street' ? 'selected' : ''; ?>>Street</option>
                <option value="Partner" <?php echo $user['Role'] === 'Partner' ? 'selected' : ''; ?>>Partner</option>
                <option value="Guide" <?php echo $user['Role'] === 'Guide' ? 'selected' : ''; ?>>Guide</option>
                <option value="Driver" <?php echo $user['Role'] === 'Driver' ? 'selected' : ''; ?>>Driver</option>
                <option value="Hired_Guide" <?php echo $user['Role'] === 'Hired_Guide' ? 'selected' : ''; ?>>Guide (Hired)</option>
                <option value="Hired_Driver" <?php echo $user['Role'] === 'Hired_Driver' ? 'selected' : ''; ?>>Driver (Hired)</option>
            </select><br>

            <label for="image">Profile Image:</label>
            <input type="file" name="image" id="image"><br>

            <?php if (!empty($user['Image'])) : ?>
                <img src="<?php echo $user['Image']; ?>" alt="Profile Image" width="100"><br>
            <?php endif; ?>
            
            <label for="registration_date">Registration Date:</label>
            <input type="date" name="registration_date" id="registration_date" value="<?php echo date('Y-m-d', strtotime($dateRegistered)); ?>" required><br>

            <label for="registration_time">Registration Time:</label>
            <input type="time" name="registration_time" id="registration_time" value="<?php echo date('H:i', strtotime($dateRegistered)); ?>" required><br>


            <input type="submit" value="Update User" class="btn btn-blue">
            <?php echo $error_message; ?>
        </form>
    </div>

<?php include('inc/footer.php'); ?>