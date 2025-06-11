<?php
include('inc/header.php');

// Set the timezone to Baku
date_default_timezone_set('Asia/Baku');

// Function to fetch assignments
function fetchAssignments($pdo, $tableName) {
    $sql = "SELECT id, name FROM $tableName";
    $stmt = $pdo->query($sql);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $options;
}

// Check if the user is not Superadmin or Admin
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Check if the booking was successfully added
$bookingSuccess = false;
if (isset($_SESSION['booking_success']) && $_SESSION['booking_success']) {
    $bookingSuccess = true;
    // Clear the session variable to prevent showing the message again on page refresh
    unset($_SESSION['booking_success']);
}

// Get the current date
$currentDate = date('Y-m-d');

try {
    // Fetch data from the income table including the assignment names for the current date
    $incomeQuery = "SELECT income.*, income_assignment.name AS assignment_name
    FROM income
    LEFT JOIN income_assignment ON income.assignment_id = income_assignment.id
    WHERE DATE(income.date) = ?";
    $incomeStatement = $pdo->prepare($incomeQuery);
    $incomeStatement->execute([$currentDate]); // Use the current date
    $incomeData = $incomeStatement->fetch(PDO::FETCH_ASSOC); // Fetch a single row

} catch (PDOException $e) {
    // Handle database connection errors
    echo "Query failed: " . $e->getMessage();
    die();
}

// Fetch income assignments
$incomeAssignments = fetchAssignments($pdo, 'income_assignment');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'])) {
    if ($_POST['type'] == 'income') {
        // Handle income form submission
        // Validate and sanitize user inputs
        $assignmentId = $_POST['assignment'];
        $incomeTitle = $_POST['income_title'];
        $amount = $_POST['amount'];

        // Update the income record in the database
        try {
            $updateQuery = "UPDATE income SET assignment_id = ?, income_title = ?, amount = ? WHERE id = ?";
            $updateStatement = $pdo->prepare($updateQuery);
            $updateStatement->execute([$assignmentId, $incomeTitle, $amount, $incomeData['id']]);

            // Redirect to a success page or display a success message
            header("Location: cashier.php");
            exit();
        } catch (PDOException $e) {
            // Handle database update errors
            echo "Update failed: " . $e->getMessage();
            die();
        }
    }
}

?>
<div class="wrapper-fit-centered">
    <h2 class="my-20 text-center">Edit Income</h2>
    <div class="cashier-entry">
        <div>
            <h3 class="text-center">Income</h3><br>
            <form class="booking" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="type" value="income">
                <div>
                    <label for="assignment">Assignment</label>
                    <select id="assignment" name="assignment" style="width:170px">
                        <?php foreach ($incomeAssignments as $assignment): ?>
                            <option value="<?php echo $assignment['id']; ?>" <?php if ($assignment['id'] == $incomeData['assignment_id']) echo 'selected'; ?>><?php echo $assignment['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="income_title">Income Title</label>
                    <textarea name="income_title" id="income_title" cols="30" rows="1" style="height:37px;width:170px"><?php echo htmlspecialchars($incomeData['income_title']); ?></textarea>
                </div>
                <div>
                    <label for="amount">Amount</label>
                    <input type="text" name="amount" id="amount" style="width:100px;height:37px" value="<?php echo $incomeData['amount']; ?>"><br>
                    <input type="submit" value="Submit" class="btn">
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('inc/footer.php'); ?>
