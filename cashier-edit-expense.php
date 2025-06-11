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
    // Fetch data from the expense table including the assignment names for the current date
    $expenseQuery = "SELECT expense.*, expense_assignment.name AS assignment_name
    FROM expense
    LEFT JOIN expense_assignment ON expense.assignment_id = expense_assignment.id
    WHERE DATE(expense.date) = ?";
    $expenseStatement = $pdo->prepare($expenseQuery);
    $expenseStatement->execute([$currentDate]); // Use the current date
    $expenseData = $expenseStatement->fetch(PDO::FETCH_ASSOC); // Fetch a single row

} catch (PDOException $e) {
    // Handle database connection errors
    echo "Query failed: " . $e->getMessage();
    die();
}

// Fetch expense assignments
$expenseAssignments = fetchAssignments($pdo, 'expense_assignment');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'])) {
    if ($_POST['type'] == 'expense') {
        // Handle expense form submission
        // Validate and sanitize user inputs
        $assignmentId = $_POST['assignment'];
        $expenseTitle = $_POST['expense_title'];
        $amount = $_POST['amount'];

        // Update the expense record in the database
        try {
            $updateQuery = "UPDATE expense SET assignment_id = ?, expense_title = ?, amount = ? WHERE id = ?";
            $updateStatement = $pdo->prepare($updateQuery);
            $updateStatement->execute([$assignmentId, $expenseTitle, $amount, $expenseData['id']]);

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
    <h2 class="my-20 text-center">Edit Expense</h2>
    <div class="cashier-entry">
        <div>
            <h3 class="text-center">Expense</h3><br>
            <form class="booking" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="type" value="expense">
                <div>
                    <label for="assignment">Assignment</label>
                    <select id="assignment" name="assignment" style="width:170px">
                        <?php foreach ($expenseAssignments as $assignment): ?>
                            <option value="<?php echo $assignment['id']; ?>" <?php if ($assignment['id'] == $expenseData['assignment_id']) echo 'selected'; ?>><?php echo $assignment['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="expense_title">Expense Title</label>
                    <textarea name="expense_title" id="expense_title" cols="30" rows="1" style="height:37px;width:170px"><?php echo htmlspecialchars($expenseData['expense_title']); ?></textarea>
                </div>
                <div>
                    <label for="amount">Amount</label>
                    <input type="text" name="amount" id="amount" style="width:100px;height:37px" value="<?php echo $expenseData['amount']; ?>"><br>
                    <input type="submit" value="Submit" class="btn">
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('inc/footer.php'); ?>
