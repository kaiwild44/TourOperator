<?php
include('inc/header.php');

// Set the timezone to Baku
date_default_timezone_set('Asia/Baku');

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

// Modify the booking query to filter by the current date and exclude rows where both paid_cash are 0
$query = "SELECT booking.*, users.First_Name AS agent_first_name, users.Last_Name AS agent_last_name
        FROM booking 
        LEFT JOIN users ON booking.agent_id = users.Id
        WHERE DATE(booking.booking_date) = ? AND (booking.paid_cash != 0)";
$stmt = $pdo->prepare($query);
$stmt->execute([$currentDate]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    // Fetch data from the expense table including the assignment names for the current date
    $expenseQuery = "SELECT expense.*, expense_assignment.name AS assignment_name
    FROM expense
    LEFT JOIN expense_assignment ON expense.assignment_id = expense_assignment.id
    WHERE DATE(expense.date) = ?";
    $expenseStatement = $pdo->prepare($expenseQuery);
    $expenseStatement->execute([$currentDate]); // Use the current date
    $expenseData = $expenseStatement->fetchAll(PDO::FETCH_ASSOC);

    // Fetch data from the income table including the assignment names for the current date
    $incomeQuery = "SELECT income.*, income_assignment.name AS assignment_name
    FROM income
    LEFT JOIN income_assignment ON income.assignment_id = income_assignment.id
    WHERE DATE(income.date) = ?";
    $incomeStatement = $pdo->prepare($incomeQuery);
    $incomeStatement->execute([$currentDate]); // Use the current date
    $incomeData = $incomeStatement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database connection errors
    echo "Query failed: " . $e->getMessage();
    die();
}

// Initialize variables for totals
$totalPrice = 0;
$totalPaidCash = 0;
$totalBalanceDue = 0;

// Iterate through bookings to calculate totals
foreach ($bookings as $booking) {
    $totalPrice += $booking['price'];
    $totalPaidCash += $booking['paid_cash'];
    $totalBalanceDue += $booking['balance_due'];
}

// Fetch assignment options dynamically using PDO
function fetchAssignments($pdo, $tableName) {
    $sql = "SELECT id, name FROM $tableName";
    $stmt = $pdo->query($sql);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $options;
}

// Fetch income and expense assignments
$incomeAssignments = fetchAssignments($pdo, 'income_assignment');
$expenseAssignments = fetchAssignments($pdo, 'expense_assignment');

// Initialize variables for totals after fetching data from the database
$totalIncomeAmount = 0;
foreach ($incomeData as $income) {
    $totalIncomeAmount += $income['amount'];
}

// Calculate the total sum of the amount column from the expense table after fetching data from the database
$totalExpenseAmount = 0;
foreach ($expenseData as $expense) {
    $totalExpenseAmount += $expense['amount'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'])) {
    if ($_POST['type'] == 'income') {
        // Assuming you have a PDO database connection named $pdo
        $assignment = $_POST['assignment'];
        $income_title = $_POST['income_title']; // Corrected field name
        $amount = $_POST['amount'];
        $currentDate = date('Y-m-d'); // Get today's date

        // Insert data into the income table
        $sql = "INSERT INTO income (date, assignment_id, income_title, amount) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentDate, $assignment, $income_title, $amount]);

        if ($stmt->rowCount() > 0) {
            // Redirect to refresh the page
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: Unable to insert income data.";
        }
    } elseif ($_POST['type'] == 'expense') {
        // Assuming you have a PDO database connection named $pdo
        $assignment = $_POST['assignment'];
        $expense_title = $_POST['expense_title']; // Corrected field name
        $amount = $_POST['amount'];
        $currentDate = date('Y-m-d'); // Get today's date

        // Insert data into the expense table
        $sql = "INSERT INTO expense (date, assignment_id, expense_title, amount) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentDate, $assignment, $expense_title, $amount]);

        if ($stmt->rowCount() > 0) {
            // Redirect to refresh the page
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: Unable to insert expense data.";
        }
    }
}

?>
    <style>
        .table-container {
            display: flex;
            gap: 20px;
        }

        .table-container .table-wrapper {
            overflow-y: auto; /* Enable vertical scrolling when content exceeds the maximum height */
        }

        .table-container table {
            border-collapse: collapse;
            width: 100%; /* Table takes 100% of the wrapper */
        }

        .table-container th,
        .table-container td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        .sticky-header th {
            position: sticky;
            top: 0;
            background-color: #f2f2f2;
        }

        .cashier-total {
            font-weight: bold;
            text-align: center;
            margin: 5px 0 20px;
            font-size: 1.2em;
        }

        @media all and (max-width:768px) {
            
            .table-container {
                flex-direction: column;
            }

            .booking {
                width: 100%;
            }

            .cashier-entry form {
                border: 0;
            }

            .booking input[type=text] {
                margin: 0;
            }
        }
        .button-container {
            display: flex; /* Use flexbox layout */
            justify-content: center; /* Center items horizontally */
        }

        .button-form {
            margin: 0 5px; /* Add some spacing between buttons */
        }

        </style>

<div class="wrapper-fit-centered">
<h2 class="my-20 text-center">Günlük Kassa</h2>
        <div class="cashier-total">Total Amount: <?= number_format($totalPaidCash + $totalIncomeAmount - $totalExpenseAmount, 2); ?>
 AZN</div>
    <div class="cashier-entry">
        <div>
            <h3 class="text-center">Income</h3><br>
            <form class="booking" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="type" value="income">
                <div>
                    <label for="assignment">Assignment</label>
                    <select id="assignment" name="assignment" style="width:170px">
                        <?php foreach ($incomeAssignments as $assignment): ?>
                            <option value="<?php echo $assignment['id']; ?>"><?php echo $assignment['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                <div>
                    <label for="income_title">Income Title</label>
                    <textarea name="income_title" id="income_title" cols="30" rows="1" style="height:37px;width:170px"></textarea>
                </div>
                </div>
                <div>
                    <label for="amount">Amount</label>
                    <input type="text" name="amount" id="amount" style="width:100px;height:37px"><br>
                    <input type="submit" value="Submit" class="btn">
                </div>
            </form>
        </div>
        <div>
            <h3 class="text-center">Expense</h3><br>
            <form class="booking" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="type" value="expense">
                <div>
                    <label for="assignment">Assignment</label>
                    <select id="assignment" name="assignment" style="width:170px">
                        <?php foreach ($expenseAssignments as $assignment): ?>
                            <option value="<?php echo $assignment['id']; ?>"><?php echo $assignment['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <div>
                        <label for="expense_title">Expense Title</label>
                        <textarea name="expense_title" id="expense_title" cols="30" rows="1" style="height:37px;width:170px"></textarea>
                    </div>
                </div>
                <div>
                    <label for="amount">Amount</label>
                    <input type="text" name="amount" id="amount" style="width:100px;height:37px"><br>
                    <input type="submit" value="Submit" class="btn">
                </div>
            </form>
        </div>
    </div>
</div>
<br><br>
    <div class="wrapper-fit-centered">
            <div class="table-container">
                <div class="table-wrapper">
                    <table class="table font-small-8">
                        <tbody>
                            <tr><th colspan="8" style="text-align:center">Tourists</th></tr>
                            <tr class="sticky-header">
                                <th>Voucher</th>
                                <th>Date</th>
                                <th>Tour Name</th>
                                <th>Tour Date</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Paid</th>
                                <th>Bal</th>
                            </tr>
                            <?php foreach ($bookings as $index => $booking): ?>
                            <tr class="<?php echo $index % 2 == 0 ? 'even-row' : 'odd-row'; ?>">
                                <td><?php echo $booking['voucher_no']; ?></td>
                                <td><?php echo date('d-m-y', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo $booking['tour_name']; ?></td>
                                <td><?php echo date('d-m-y', strtotime($booking['tour_date'])); ?></td>
                                <td><?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></td>
                                <td><?php echo number_format($booking['price'], 2); ?></td>
                                <td><?php echo number_format($booking['paid_cash'], 2); ?></td>
                                <td><?php echo number_format($booking['balance_due'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="5" style="text-align: right;"><strong>Total:</strong></td>
                                <td><?php echo number_format($totalPrice, 2); ?></td>
                                <td><?php echo 'AZN ' . number_format($totalPaidCash, 2); ?></td>
                                <td><?php echo number_format($totalBalanceDue, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="8"></td>
                            </tr>
                            <tr>
                                <th>#</th>
                                <th></th>
                                <th>Assignment</th>
                                <th></th>
                                <th>Income</th>
                                <th></th>
                                <th>Amount</th>
                                <th></th>
                            </tr>
                            <?php
                            $totalIncome = 0; // Initialize total income outside of the loop

                            foreach ($incomeData as $index => $income) :
                                $totalIncome += $income['amount']; // Add the current income amount to the total
                            ?>
                            <tr class="<?php echo ($index % 2 == 0) ? 'even-row' : 'odd-row'; ?>">
                                <td><?php echo ($index + 1); ?></td>
                                <td></td>
                                <td><?php echo $income['assignment_name']; ?></td>
                                <td></td>
                                <td><?php echo $income['income_title']; ?></td>
                                <td></td>
                                <td><?php echo $income['amount']; ?></td>
                                <td style="text-align:center">
                                    <!-- Container for buttons -->
                                    <div class="button-container">
                                        <!-- Edit button with Form -->
                                        <form action="cashier-edit-income.php" method="post" class="button-form">
                                            <input type="hidden" name="id" value="<?php echo $income['id']; ?>">
                                            <button type="submit" class="btn-edit">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </form>
                                        <!-- Remove button with Form -->
                                        <form action="cashier-delete-income.php" method="post" class="button-form">
                                            <input type="hidden" name="id" value="<?php echo $income['id']; ?>">
                                            <button type="submit" class="btn-remove" onclick="return confirm('Are you sure you want to delete this income?');">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <tr class="<?php echo (count($incomeData) % 2 == 0) ? 'even-row' : 'odd-row'; ?>">
                                <td colspan="6">Total:</td>
                                <td><?php echo 'AZN ' . number_format($totalIncome, 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-wrapper">
                    <table class="table font-small-8">
                        <tbody>
                            <tr>
                                <th colspan="5" style="text-align:center"><b>Expense</b></th>
                            </tr>
                            <tr class="sticky-header">
                                <th>#</th>
                                <th>Assignment</th>
                                <th>Expense Title</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                            <?php
                            $totalExpense = 0; // Initialize total expense
                            foreach ($expenseData as $index => $expense) :
                            ?>
                                <tr class="<?php echo ($index % 2 == 0) ? 'even-row' : 'odd-row'; ?>">
                                    <td><?php echo ($index + 1); ?></td>
                                    <td><?php echo $expense['assignment_name']; ?></td>
                                    <td><?php echo $expense['expense_title']; ?></td>
                                    <td><?php echo $expense['amount']; ?></td>
                                    <td style="text-align:center">
                                    <!-- Container for buttons -->
                                    <div class="button-container">
                                        <!-- Edit button with Form -->
                                        <form action="cashier-edit-expense.php" method="post" class="button-form">
                                            <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                                            <button type="submit" class="btn-edit">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </form>
                                        <!-- Remove button with Form -->
                                        <form action="cashier-delete-expense.php" method="post" class="button-form">
                                            <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                                            <button type="submit" class="btn-remove" onclick="return confirm('Are you sure you want to delete this expense?');">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                </tr>
                                <?php
                                $totalExpense += $expense['amount']; // Accumulate total expense
                            endforeach;
                                ?>
                            <tr class="<?php echo (count($expenseData) % 2 == 0) ? 'even-row' : 'odd-row'; ?>">
                                <td colspan="3">Total:</td>
                                <td><?php echo 'AZN ' . $totalExpense; ?></td>
                                <td></td> <!-- Empty cell for alignment with header -->
                            </tr>
                        </tbody>
                    </table>
                </div>
    </div>
</div>
<br><br>

<div class="wrapper-fit-centered">
    <button id="copyToExcelButton" class="btn">Copy to Excel</button>
</div>

<script>
    document.getElementById('copyToExcelButton').addEventListener('click', function () {
        // Function to format data for copying to Excel
        function formatForExcel() {
            var table1Rows = document.querySelectorAll('.table-container:first-child table tbody tr');
            var table2Rows = document.querySelectorAll('.table-container:last-child table tbody tr');

            var excelData = '';

            <?php foreach ($bookings as $index => $booking): ?>
                excelData += '<?php echo date("Y-m-d", strtotime($booking["date"])); ?>\t';
                excelData += '<?php echo $booking["voucher_no"]; ?>\t';
                excelData += '<?php echo $booking["paid_cash"]; ?>\t';
                excelData += '<?php echo $booking["price"]; ?>\t';
                excelData += '<?php echo date("Y-m-d", strtotime($booking["tour_date"])); ?>\t';
                excelData += '<?php echo $booking["tour_name"]; ?>\t';
                excelData += '<?php echo $booking["balance_due"]; ?>\t';
                excelData += '<?php echo $booking["first_name"] . ' ' . $booking["last_name"]; ?>\t';

                <?php if (isset($expenseData[$index])): ?>
                    excelData += '<?php echo $expenseData[$index]["amount"]; ?>\t';
                    excelData += '<?php echo $expenseData[$index]["assignment_name"]; ?>\t';
                    excelData += '<?php echo $expenseData[$index]["expense_title"]; ?>\t';
                <?php else: ?>
                    excelData += '\t\t\t';
                <?php endif; ?>

                excelData += '\n';
            <?php endforeach; ?>

            <?php for ($i = count($bookings); $i < count($expenseData); $i++): ?>
                excelData += '\t\t\t\t\t\t\t\t';
                excelData += '<?php echo $expenseData[$i]["amount"]; ?>\t';
                excelData += '<?php echo $expenseData[$i]["assignment_name"]; ?>\t';
                excelData += '<?php echo $expenseData[$i]["expense_title"]; ?>\t';
                excelData += '\n';
            <?php endfor; ?>

            excelData += '\n';
            // INCOME DATA
            <?php foreach ($incomeData as $index => $income): ?>
                excelData += '<?php echo $income["date"]; ?>\t';
                excelData += '\t';
                excelData += '<?php echo $income["amount"]; ?>\t';
                excelData += '<?php echo $income["amount"]; ?>\t';
                excelData += '\t';
                excelData += '<?php echo $income["assignment_name"]; ?>\t';
                excelData += '\t';
                excelData += '<?php echo $income["income_title"]; ?>\t';

                excelData += '\n';
            <?php endforeach; ?>

            excelData += '\n';

            return excelData;
        }

        // Copy data to clipboard
        var excelData = formatForExcel();
        var textarea = document.createElement('textarea');
        textarea.textContent = excelData;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);

        // Inform the user that data has been copied
        alert('Data copied to clipboard. You can paste it into Excel.');
    });
</script>



<?php include('inc/footer.php'); ?>