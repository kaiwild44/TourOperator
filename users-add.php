<?php

include('inc/header.php');

// Check if the user is not Superadmin or Admin
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

$username = $password = $repeat_password = $first_name = $last_name = $role = '';
$image = null;
$error_message = '';

function generateUniqueFileName($first_name, $last_name, $extension)
{
    $date_time = date('Y-m-d_H-i-s');
    $filename = $first_name . '_' . $last_name . '_' . $date_time . '.' . $extension;
    return $filename;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $repeat_password = filter_input(INPUT_POST, 'repeat_password', FILTER_SANITIZE_STRING);
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        $new_image_name = generateUniqueFileName($first_name, $last_name, $image_extension);

        $image = 'img/users/' . $new_image_name;

        if (!move_uploaded_file($image_tmp, $image)) {
            $error_message = 'Failed to upload the image.';
        }
    }

    $registration_date = $_POST['registration_date'];
    $registration_time = $_POST['registration_time'];
    
    if (!empty($registration_date) && !empty($registration_time)) {
        $registration_datetime = $registration_date . ' ' . $registration_time;
    
        $registration_date_timestamp = strtotime($registration_datetime);
        if ($registration_date_timestamp === false) {
            $error_message .= "Invalid custom registration date and time format. Please use a valid format.<br>";
        } else {
            $date_registered = date('Y-m-d H:i:s', $registration_date_timestamp);
        }
    } else {
        $date_registered = date('Y-m-d H:i:s');
    }
     

    if (empty($error_message)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (Username, Password, First_Name, Last_Name, Image, Role, Date_Registered) 
                                VALUES (:username, :password, :first_name, :last_name, :image, :role, :date_registered)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashed_password,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':image' => $image,
                ':role' => $role,
                ':date_registered' => $date_registered
            ]);

            $_SESSION['user_add_success'] = 'User successfully added!';
            header("Location: users.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="users">
    <h1 class="my-10">Add New User</h1>
    <form action="users-add.php" method="POST" enctype="multipart/form-data" id="users-add">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required><br>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required><br>

        <label for="repeat_password">Repeat Password:</label>
        <input type="password" name="repeat_password" id="repeat_password" required><br>

        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" id="first_name"><br>

        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" id="last_name"><br>

        <label for="role">Role:</label>
        <select name="role" id="role" required>
            <option value="Superadmin">Superadmin</option>
            <option value="Admin">Admin</option>
            <option value="Sales_Manager">Sales Manager</option>
            <option value="Online_Manager">Onlne Manager</option>
            <option value="Coordinator">Coordinator</option>
            <option value="Promoter">Promoter</option>
            <option value="Street">Street</option>
            <option value="Partner">Partner</option>
            <option value="Guide">Guide</option>
            <option value="Hired_Guide">Guide (Hired)</option>
            <option value="Driver">Driver</option>
            <option value="Hired_Driver">Driver (Hired)</option>
        </select><br>

        <label for="image">Profile Image:</label>
        <input type="file" name="image" id="image"><br>

        <label for="registration_date">Registration Date (Optional):</label>
        <input type="date" name="registration_date" id="registration_date"><br>

        <label for="registration_time">Registration Time (Optional):</label>
        <input type="time" name="registration_time" id="registration_time"><br>

        <input type="submit" value="Add User" class="btn btn-blue">
        <?php echo $error_message; ?>
    </form>
</div>

<?php include('inc/footer.php'); ?>