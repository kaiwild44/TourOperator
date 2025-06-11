    <?php
    include('inc/db.php');
    
    if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $group_id = $_POST['group_id'];
        $extra_expenses = $_POST['extra_expense'];
        $expense_assignments = $_POST['expense_assignment']; // Capture the assignments

        try {
            // Begin a transaction for safety
            $pdo->beginTransaction();

            // Prepare the statement for updating expenses
            $stmtUpdate = $pdo->prepare("
                UPDATE extra_expenses 
                SET expense_assignment = :assignment, expense_amount = :amount
                WHERE group_id = :group_id AND expense_assignment = :current_assignment
            ");

            // Loop through each expense to update it
            foreach ($expense_assignments as $i => $current_assignment) {
                $new_assignment = trim($expense_assignments[$i]); // Get the new assignment
                $amount = trim($extra_expenses[$i]); // Get the amount

                // Update the expenses directly based on the assignment
                $stmtUpdate->execute([
                    ':group_id' => $group_id,
                    ':assignment' => $new_assignment, // The new assignment value
                    ':amount' => $amount,              // The new amount value
                    ':current_assignment' => $current_assignment // Match the existing assignment
                ]);
            }

            // Commit the transaction
            $pdo->commit();

            // Redirect back to the program page after updating
            header("Location: program.php?group_id=" . urlencode($group_id));
            exit();

        } catch (PDOException $e) {
            // Roll back the transaction on error
            $pdo->rollBack();
            error_log("Error updating extra expenses: " . $e->getMessage());
            echo "There was an issue updating the expenses. Please try again.";
            exit();
        }
    } else {
        // Redirect if accessed directly without POST data
        header("Location: index.php");
        exit();
    }