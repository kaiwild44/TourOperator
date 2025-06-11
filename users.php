<?php

include('inc/header.php');

// Check if the user is not Superadmin or Admin
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

	try {
		$stmt = $pdo->query("SELECT * FROM users");
		$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
	}

	// Define the sorting order of roles
	$roleOrder = [
		'Superadmin' => 1,
		'Admin' => 2,
		'Sales_Manager' => 3,
		'Sales_Manager' => 4,
		'Promoter' => 5,
		'Street' => 6,
		'Guide' => 7,
		'Partner' => 8,
	];

	// Custom sorting function based on role order and username alphabetically
	usort($users, function($a, $b) use ($roleOrder) {
		// First, check if roles are in the $roleOrder array, otherwise assign a high value to prioritize them at the end
		$aOrder = isset($roleOrder[$a['Role']]) ? $roleOrder[$a['Role']] : PHP_INT_MAX;
		$bOrder = isset($roleOrder[$b['Role']]) ? $roleOrder[$b['Role']] : PHP_INT_MAX;

		// Sort by role order
		if ($aOrder !== $bOrder) {
			return $aOrder - $bOrder;
		}

		// If roles are the same, sort by username's first letter alphabetically
		return strcmp($a['Username'][0], $b['Username'][0]);
	});

	$id = 1;

?>


<h1 class="text-center">Users</h1>
<div class="wrapper-fit-centered">
    <a href="<?php echo $siteurl; ?>users-add.php" class="btn">Add User</a>
    <?php
    if (isset($_SESSION['user_add_success'])) {
        $userAddSuccess = $_SESSION['user_add_success'];
        unset($_SESSION['user_add_success']);
        echo "<div class='success-msg text-center'>$userAddSuccess</div>";
    }
    ?>

    <table class="tbl-fit">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Role</th>
                <th>Date Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $currentRole = null;
            foreach ($users as $user):
                if ($currentRole !== $user['Role']):
                    // If this is not the first role, close the previous row
                    if ($currentRole !== null): ?>
                        </tr> <!-- Close the previous row -->
                    <?php endif; ?>
                    <!-- <tr class="role-separator">
                        <td colspan="8"><h2 class="text-center"><?= $user['Role'] . "s" ?></h2></td>
                    </tr> -->
                <?php endif; ?>
                <tr>
                    <td data-label="id:"><?php echo $id++; ?></td>
                    <td><img src="<?php echo $user['Image']; ?>" width="100"></td>
                    <td data-label="Username:"><?php echo $user['Username']; ?></td>
                    <td data-label="First Name:"><?php echo $user['First_Name']; ?></td>
                    <td data-label="Last Name:"><?php echo $user['Last_Name']; ?></td>
                    <td data-label="Role:"><?php echo $user['Role']; ?></td>
                    <td data-label="Date:"><?php echo $user['Date_Registered']; ?></td>
                    <td>
                        <div class="users-table-buttons">
                            <a href="users-profile.php?id=<?php echo $user['Id']; ?>" class="btn">View</a>
                            <a href="users-edit.php?id=<?php echo $user['Id']; ?>" class="btn">Edit</a>
                            <a href="users-delete.php?id=<?php echo $user['Id']; ?>" class="btn">Delete</a>
                            <a href="users-pwd.php?id=<?php echo $user['Id']; ?>" class="btn">Pwd</a>
                        </div>
                    </td>
                </tr>
                <?php
                $currentRole = $user['Role'];
            endforeach;
            ?>
        </tbody>
    </table>
</div>

<?php include('inc/footer.php'); ?>