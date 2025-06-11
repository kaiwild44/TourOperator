<?php
include('inc/db.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager' && $_SESSION['role'] !== 'Online_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Function to generate voucher number
function generateVoucherNumber($lastVoucherNumber) {
    // Extract the date portion of the last voucher number
    $lastDatePart = substr($lastVoucherNumber, 0, 8);
    // Get today's date
    $todayDate = date('Ymd');
    
    // If the last voucher was generated on a different date, reset the counter
    if ($lastDatePart != $todayDate) {
        // If the last voucher was generated on a different date, start from 001
        $nextNumericPart = 1;
    } else {
        // Extract the numeric portion and increment it by one
        $lastNumericPart = (int) substr($lastVoucherNumber, -3);
        $nextNumericPart = $lastNumericPart + 1;
    }

    // Format the next voucher number
    $nextVoucherNumber = sprintf('%03d', $nextNumericPart);

    // Create the new voucher number by concatenating the current date and the next numeric part
    $newVoucherNumber = $todayDate . $nextVoucherNumber;

    return $newVoucherNumber;
}

// Function to reset voucher number
function resetVoucherNumber($lastVoucherNumber) {
    // Reset the voucher count to '001'
    return '001';
}

// Check if reset_voucher parameter is set
if (isset($_POST['reset_voucher']) && $_POST['reset_voucher'] === 'true') {
    // Reset the voucher number to '001'
    $nextVoucherNumber = resetVoucherNumber('');
    echo "Voucher number has been reset to: $nextVoucherNumber";
    exit(); // Stop further execution
}

// Query the database to retrieve the last entry's voucher number
$queryLastVoucherNumber = "SELECT voucher_no FROM booking ORDER BY id DESC LIMIT 1";
$statement = $pdo->query($queryLastVoucherNumber);
$lastVoucherRow = $statement->fetch(PDO::FETCH_ASSOC);
$lastVoucherNumber = $lastVoucherRow ? $lastVoucherRow['voucher_no'] : '';

// Generate a unique voucher number
$voucherNumber = generateVoucherNumber($lastVoucherNumber);

// Return the generated voucher number as JSON response
echo json_encode(['voucher_number' => $voucherNumber]);
?>
